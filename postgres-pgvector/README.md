# postgres-pgvector — PostgreSQL + pgvector

**PostgreSQL** com a extensão **[pgvector](https://github.com/pgvector/pgvector)** nativa, para
armazenar e consultar **embeddings** (vetores) em aplicações de **IA / RAG**: busca semântica,
memória de agentes, recomendação, deduplicação, etc.

Usa a imagem oficial `pgvector/pgvector`, que já vem com a extensão `vector` compilada e instalada
— basta executar `CREATE EXTENSION vector;` no banco (feito automaticamente no primeiro boot, veja
abaixo).

Esta é uma stack de **banco interno**: NÃO é publicada via Traefik. O serviço entra na rede overlay
externa compartilhada `data`. Outras stacks anexam essa mesma rede e conectam pelo host `postgres`.

## Componentes
| Serviço | Imagem | Função |
|---|---|---|
| `postgres` | `pgvector/pgvector` | PostgreSQL com extensão `vector` |

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `POSTGRES_PASSWORD` | sim | — | senha do usuário do PostgreSQL (segredo) |
| `POSTGRES_DB` | não | `vectordb` | nome do banco criado no primeiro boot |
| `POSTGRES_USER` | não | `postgres` | usuário dono do banco |
| `PGVECTOR_IMAGE_TAG` | não | `pg16` | tag da imagem (ex.: `pg16`, `pg15`, `pg17`) |
| `PGVECTOR_INIT_CONFIG` | não | `pgvector_init_v1` | nome do Docker config com o script de init |
| `POSTGRES_PORT` | não | `5432` | porta publicada no nó (só se descomentar `ports`) |
| `DATA_NET` | não | `data` | rede overlay externa compartilhada dos bancos |
| `WORKER_HOSTNAME` | não | — | hostname do worker para fixar o volume (cluster multi-worker) |

## Pré-requisitos
- Docker **Swarm** ativo.
- A rede overlay externa `data` precisa existir **antes** de subir a stack (veja abaixo).
- O Docker config com o script de init precisa existir **antes** do primeiro deploy (veja abaixo).

### Criar a rede `data`
A rede é compartilhada entre todas as stacks que precisam falar com o banco. Crie uma única vez:
```bash
docker network create --driver overlay --attachable data
```
Se você usar outro nome, ajuste a variável `DATA_NET`.

### Criar o Docker config de init (extensão pgvector)
O arquivo `config/01-pgvector.sql` (nesta pasta) contém `CREATE EXTENSION IF NOT EXISTS vector;`.
Ele é montado em `/docker-entrypoint-initdb.d/01-pgvector.sql` e executado **apenas no primeiro
boot, quando o volume está vazio**. Crie o Docker config a partir do arquivo:
```bash
docker config create pgvector_init_v1 config/01-pgvector.sql
```
O nome (`pgvector_init_v1`) deve bater com `PGVECTOR_INIT_CONFIG`. Como configs no Swarm são
imutáveis, para alterar o script crie um novo config (`pgvector_init_v2`) e atualize a variável.

> Pelo Portainer: **Configs → Add config**, nome `pgvector_init_v1`, e cole o conteúdo de
> `config/01-pgvector.sql`.

### Alternativa: criar a extensão manualmente
Se preferir não usar o Docker config (ou o banco já existia antes), rode o `CREATE EXTENSION` à mão:
```bash
docker exec -it <container_postgres> psql -U postgres -d vectordb -c "CREATE EXTENSION IF NOT EXISTS vector;"
```
Lembre que o init automático só roda em banco **novo/vazio**; em banco já existente, use o comando
manual acima.

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
-- (a extensão já foi criada no init; este comando é idempotente)
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
| `config pgvector_init_v1 not found` | Docker config ausente | criar o config a partir de `config/01-pgvector.sql` |
| `ERROR: type "vector" does not exist` | extensão não criada (banco preexistente) | rodar `CREATE EXTENSION vector;` manualmente |
| extensão não criada apesar do config | banco não estava vazio no boot | o init só roda em volume novo; criar a extensão manualmente |
| app não conecta (`could not translate host name "postgres"`) | app não está na rede `data` | anexar a rede externa `data` ao serviço da app |
| dados sumiram após reagendar serviço | volume é local ao nó (Swarm) | fixar `WORKER_HOSTNAME` e descomentar o constraint de hostname |
| `password authentication failed` | `POSTGRES_PASSWORD` divergente | conferir a senha; após o primeiro boot ela fica gravada no volume |
