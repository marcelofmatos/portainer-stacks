# evolution-api — Evolution API (WhatsApp)

**Evolution API** (gateway de API para WhatsApp) publicado via Traefik v3 com TLS. Reaproveita os
serviços compartilhados da rede `data`: **PostgreSQL** (stack `postgres-pgvector`) e **Redis**
(stack `redis`) — não sobe banco/cache próprios.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `EVOLUTION_FQDN` | sim | — | domínio público (ex.: `evolution.exemplo.com`) |
| `EVOLUTION_API_KEY` | sim | — | chave global de autenticação da API (segredo) |
| `EVOLUTION_DB_PASSWORD` | sim | — | senha do usuário do PostgreSQL |
| `EVOLUTION_DB_HOST` | não | `postgres` | host do PostgreSQL na rede `data` |
| `EVOLUTION_DB_PORT` | não | `5432` | porta do PostgreSQL |
| `EVOLUTION_DB_USER` | não | `postgres` | usuário do PostgreSQL |
| `EVOLUTION_DB_NAME` | não | `evolution` | banco usado pela Evolution |
| `EVOLUTION_REDIS_URI` | não | `redis://redis:6379/6` | URI do Redis (com senha: `redis://default:<senha>@redis:6379/6`) |
| `EVOLUTION_LANGUAGE` | não | `pt-BR` | idioma |
| `EVOLUTION_IMAGE_TAG` | não | `v2.2.3` | tag da imagem atendai/evolution-api |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `DATA_NET` | não | `data` | rede overlay dos serviços compartilhados |
| `WORKER_HOSTNAME` | não | — | fixa o volume num nó (cluster multi-worker) |

## Pré-requisitos
- Stack `balancer` (Traefik) + rede `web`; DNS de `EVOLUTION_FQDN` apontando para o host.
- Rede `data`: `docker network create --driver overlay --attachable data`.
- Stack **`postgres-pgvector`** (ou outro PostgreSQL) na rede `data` com um banco para a Evolution:
  ```sql
  CREATE DATABASE evolution;
  ```
- Stack **`redis`** na rede `data` (se o Redis tiver senha, use a URI autenticada em `EVOLUTION_REDIS_URI`).

## Uso
1. Crie o banco `evolution` no PostgreSQL compartilhado (acima).
2. Faça o deploy. A API responde em `https://EVOLUTION_FQDN`.
3. Autentique chamadas com o header `apikey: <EVOLUTION_API_KEY>`. Crie instâncias via
   `POST /instance/create` e leia o QR Code para parear o WhatsApp.

## Segurança
- `EVOLUTION_API_KEY` é a chave mestra — use um valor forte e mantenha em segredo.
- Considere proteger rotas administrativas com middleware no Traefik (basicauth/authelia).

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| Erro de conexão com o banco | `data` ausente / banco `evolution` não criado / senha errada | criar a rede, o banco e conferir `EVOLUTION_DB_*` |
| `NOAUTH`/erro no Redis | Redis com senha e URI sem credenciais | usar `redis://default:<senha>@redis:6379/6` em `EVOLUTION_REDIS_URI` |
| 404/sem TLS | fora da `web` / DNS não aponta | conferir rede/labels e DNS |
| Instâncias somem ao reagendar | volume local ao nó (multi-worker) | fixar `node.hostname` via `WORKER_HOSTNAME` |
