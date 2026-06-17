# librechat — LibreChat (UI multi-provedor de LLMs)

**LibreChat** é uma interface de chat tipo ChatGPT com **múltiplos provedores** (OpenAI, Anthropic,
Ollama, litellm), busca, RAG e agentes. Publicado via Traefik v3 com TLS, com **MongoDB embarcado**
(serviço `db` próprio da stack) e um **Meilisearch** próprio (busca nas conversas). O banco fica na
rede interna `default` e também na `data` **só** para ferramentas de administração (mongo-express) o
alcançarem como `librechat_db`. Volume dedicado = fácil migrar de host.

## Componentes
| Serviço | Imagem | Função |
|---|---|---|
| `api` | `ghcr.io/danny-avila/librechat` | Web + API, exposto via Traefik (porta 3080) |
| `db` | `mongo` | MongoDB embarcado (conversas, usuários, configs) |
| `meilisearch` | `getmeili/meilisearch` | Índice de busca das conversas |

## Arquitetura

```mermaid
flowchart LR
    user((Usuário)) -->|HTTPS LIBRECHAT_FQDN| traefik[Traefik · web]
    traefik --> api[api]
    api -->|27017 · default| db[(db · MongoDB)]
    api -->|7700 · default| meili[meilisearch]
    me[mongo-express] -.->|27017 · data · librechat_db| db
    api -.->|provedores| llm[litellm / ollama / OpenAI]
```

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `LIBRECHAT_FQDN` | sim | — | domínio público (ex.: `librechat.exemplo.com`) |
| `LIBRECHAT_MONGO_PASSWORD` | sim | — | senha do usuário do MongoDB (segredo) |
| `LIBRECHAT_CREDS_KEY` | sim | — | chave de criptografia (32 bytes hex: `openssl rand -hex 32`) |
| `LIBRECHAT_CREDS_IV` | sim | — | IV de criptografia (16 bytes hex: `openssl rand -hex 16`) |
| `LIBRECHAT_JWT_SECRET` | sim | — | segredo JWT (`openssl rand -hex 32`) |
| `LIBRECHAT_JWT_REFRESH_SECRET` | sim | — | segredo JWT refresh (`openssl rand -hex 32`) |
| `LIBRECHAT_MEILI_MASTER_KEY` | sim | — | master key do Meilisearch (`openssl rand -hex 32`) |
| `LIBRECHAT_MONGO_USER` | não | `root` | usuário do MongoDB |
| `LIBRECHAT_MONGO_HOST` | não | `db` | host do MongoDB (serviço interno desta stack) |
| `LIBRECHAT_MONGO_DB` | não | `LibreChat` | banco usado pelo LibreChat |
| `LIBRECHAT_ALLOW_REGISTRATION` | não | `false` | cadastro de novos usuários (fechado por padrão; abra só para criar a 1ª conta) |
| `LIBRECHAT_OPENAI_API_KEY` | não | — | chave OpenAI (ou do litellm) |
| `LIBRECHAT_OPENAI_REVERSE_PROXY` | não | — | base OpenAI-compatible (ex.: `https://litellm.exemplo.com/v1`) |
| `LIBRECHAT_IMAGE_TAG` | não | `latest` | tag da imagem librechat |
| `LIBRECHAT_MONGO_IMAGE_TAG` | não | `7` | tag da imagem MongoDB |
| `LIBRECHAT_MEILI_IMAGE_TAG` | não | `v1.12` | tag da imagem meilisearch |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `DATA_NET` | não | `data` | rede overlay dos serviços compartilhados |

## Pré-requisitos
- Stack `balancer` (Traefik) + rede `web`; DNS de `LIBRECHAT_FQDN` apontando para o host.
- Rede `data`: `docker network create --driver overlay --attachable data` (usada pelas ferramentas de admin).
- **Não** precisa da stack `mongodb`: o banco sobe junto. Para administrá-lo, aponte o `mongo-express`
  para o host `librechat_db` (porta 27017) na rede `data`.
- Gere os segredos (`CREDS_KEY`, `CREDS_IV`, `JWT_*`, `MEILI_MASTER_KEY`) conforme a tabela.

## Uso
1. Defina os segredos e faça o deploy. O LibreChat cria as coleções no MongoDB no primeiro start.
2. **Criar a 1ª conta:** o registro vem **fechado** (`LIBRECHAT_ALLOW_REGISTRATION=false`). Suba com
   `LIBRECHAT_ALLOW_REGISTRATION=true`, acesse `https://LIBRECHAT_FQDN`, registre seu usuário e então
   **volte para `false`** e reimplante (o LibreChat não tem admin pré-criado).
3. Para usar o `litellm` como backend, defina `LIBRECHAT_OPENAI_REVERSE_PROXY` e a chave; modelos
   adicionais podem ser configurados via `librechat.yaml` (config avançada — ver doc oficial).

### Migrar para outro host
Como o banco e a busca são dedicados, basta migrar os volumes `db-data` (MongoDB) e
`meilisearch-data` (índice) para o novo nó e subir a stack lá — sem mexer em banco compartilhado de
outras stacks.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| App não conecta ao Mongo | `db` ainda subindo / senha divergente | aguardar o `db`; conferir `LIBRECHAT_MONGO_PASSWORD` igual no app e no banco e `authSource=admin` |
| Busca não funciona | Meilisearch fora / master key divergente | conferir `LIBRECHAT_MEILI_MASTER_KEY` nos dois serviços |
| Credenciais salvas "quebram" após restart | `CREDS_KEY`/`CREDS_IV` mudaram | manter os valores fixos |
| 404/sem TLS | DNS não aponta / fora da `web` | conferir rede/labels e DNS |
| mongo-express não acha o banco | host errado | usar `librechat_db:27017` na rede `data` |
| Setup/conversas sumiram | volume do banco resetado | preservar o volume `db-data` |
