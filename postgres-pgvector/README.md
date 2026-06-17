# postgres-pgvector — PostgreSQL + pgvector

**PostgreSQL** com a extensão **[pgvector](https://github.com/pgvector/pgvector)** nativa, para
armazenar e consultar **embeddings** (vetores) em aplicações de **IA / RAG**: busca semântica,
memória de agentes, recomendação, deduplicação, etc.

Usa a imagem oficial `pgvector/pgvector`, que já vem com a extensão `vector` compilada e instalada
— basta executar `CREATE EXTENSION IF NOT EXISTS vector;` **em cada banco** que for usar vetores
(a extensão é por banco; veja a seção de uso).

Esta é uma stack de **banco interno**: NÃO é publicada via Traefik. O serviço entra na rede overlay
externa compartilhada `data`. Outras stacks anexam essa mesma rede e conectam pelo host `postgres`.

## Componentes
| Serviço | Imagem | Função |
|---|---|---|
| `postgres` | `pgvector/pgvector` | PostgreSQL com extensão `vector` |

## Arquitetura

```mermaid
flowchart LR
    apps[Stacks consumidoras] -->|5432 · data| postgres[(postgres · pgvector)]
    postgres --> vol[(volume)]
```

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `POSTGRES_PASSWORD` | sim | — | senha do usuário do PostgreSQL (segredo) |
| `POSTGRES_DB` | não | `vectordb` | nome do banco criado no primeiro boot |
| `POSTGRES_USER` | não | `postgres` | usuário dono do banco |
| `PGVECTOR_IMAGE_TAG` | não | `pg16` | tag da imagem (ex.: `pg16`, `pg15`, `pg17`) |
| `POSTGRES_PORT` | não | `5432` | porta publicada no nó (só se descomentar `ports`) |
| `DATA_NET` | não | `data` | rede overlay externa compartilhada dos bancos |
| `WORKER_HOSTNAME` | não | — | hostname do worker para fixar o volume (cluster multi-worker) |

## Pré-requisitos
- Docker **Swarm** ativo.
- A rede overlay externa `data` precisa existir **antes** de subir a stack (veja abaixo).

### Criar a rede `data`
A rede é compartilhada entre todas as stacks que precisam falar com o banco. Crie uma única vez:
```bash
docker network create --driver overlay --attachable data
```
Se você usar outro nome, ajuste a variável `DATA_NET`.

### Habilitar a extensão pgvector (por banco)
A imagem já traz a extensão instalada, mas o `CREATE EXTENSION` é **por banco** — rode-o uma vez em
cada banco que for usar vetores (o banco padrão `vectordb` e/ou os bancos das stacks consumidoras,
como `dify`, `chatwoot`, etc.):
```bash
docker exec -it <container_postgres> psql -U postgres -d <banco> -c "CREATE EXTENSION IF NOT EXISTS vector;"
```
O comando é idempotente (não falha se a extensão já existir). Muitas apps que usam pgvector já rodam
esse `CREATE EXTENSION` sozinhas na inicialização — confira o README da stack consumidora.

## Uso

### Como outras stacks consomem o banco
Na stack que precisa do banco, anexe a rede externa `data` e conecte ao host `postgres:5432`:
```yaml
services:
  minha-app:
    networks:
      - data
networks:
  data:
    external: true
    name: data
```
String de conexão (substitua a senha pela sua):
```
postgresql://postgres:SENHA@postgres:5432/vectordb
```

### Exemplo: tabela com coluna vetorial
```sql
-- Habilite a extensão neste banco (idempotente):
CREATE EXTENSION IF NOT EXISTS vector;

-- 1536 = dimensão de embeddings (ex.: text-embedding-3-small / ada-002).
-- Ajuste para a dimensão do seu modelo.
CREATE TABLE documentos (
  id        bigserial PRIMARY KEY,
  conteudo  text,
  embedding vector(1536)
);

-- Índice ANN para busca rápida por similaridade (distância de cosseno).
CREATE INDEX ON documentos USING hnsw (embedding vector_cosine_ops);

-- Busca dos 5 mais semelhantes a um vetor de consulta:
SELECT id, conteudo
FROM documentos
ORDER BY embedding <=> '[0.1, 0.2, ...]'
LIMIT 5;
```
Operadores de distância: `<->` (L2), `<#>` (produto interno negativo), `<=>` (cosseno).

### Acesso externo opcional
Por padrão o banco só é alcançável de dentro da rede `data`. Para conectar de fora do cluster
(ex.: `psql` local, ferramenta de BI), descomente o bloco `ports` no `docker-compose.yml`
(modo `host`, publica `${POSTGRES_PORT:-5432}` no nó onde o container roda) e redeploy.
Atenção: isso expõe o banco na interface do host — proteja por firewall e use senha forte.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| `network data not found` no deploy | rede externa não existe | criar com `docker network create --driver overlay --attachable data` |
| `ERROR: type "vector" does not exist` | extensão não criada naquele banco | rodar `CREATE EXTENSION IF NOT EXISTS vector;` no banco em questão |
| app não conecta (`could not translate host name "postgres"`) | app não está na rede `data` | anexar a rede externa `data` ao serviço da app |
| dados sumiram após reagendar serviço | volume é local ao nó (Swarm) | fixar `WORKER_HOSTNAME` e descomentar o constraint de hostname |
| `password authentication failed` | `POSTGRES_PASSWORD` divergente | conferir a senha; após o primeiro boot ela fica gravada no volume |
