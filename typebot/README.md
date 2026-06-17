# typebot — Typebot (construtor de chatbots)

**Typebot** (builder visual de chatbots/fluxos conversacionais) publicado via Traefik v3 com TLS.
Reaproveita o **PostgreSQL** compartilhado (stack `postgres-pgvector`) na rede `data` — não sobe banco
próprio. Os fluxos publicados podem ser entregues no WhatsApp via stack **`evolution-api`**.

## Componentes
| Serviço | Imagem | Função |
|---|---|---|
| `builder` | `baptistearno/typebot-builder` | Editor de fluxos + autenticação (`TYPEBOT_BUILDER_FQDN`) |
| `viewer` | `baptistearno/typebot-viewer` | Runtime que serve os bots publicados (`TYPEBOT_VIEWER_FQDN`) |

## Arquitetura

```mermaid
flowchart LR
    editor((Editor)) -->|HTTPS BUILDER_FQDN| traefik[Traefik · web]
    visitante((Visitante)) -->|HTTPS VIEWER_FQDN| traefik
    traefik --> builder[builder]
    traefik --> viewer[viewer]
    builder -->|5432 · data| pg[(postgres · pgvector)]
    viewer -->|5432 · data| pg
    viewer -.->|HTTP API| evo[evolution-api]
    builder -.->|S3| minio[(minio)]
```

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `TYPEBOT_BUILDER_FQDN` | sim | — | domínio do editor (ex.: `typebot.exemplo.com`) |
| `TYPEBOT_VIEWER_FQDN` | sim | — | domínio do runtime/bots (ex.: `bot.exemplo.com`) |
| `TYPEBOT_ENCRYPTION_SECRET` | sim | — | segredo de criptografia, 32 chars (gere com `openssl rand -base64 24`) |
| `TYPEBOT_DB_PASSWORD` | sim | — | senha do usuário do PostgreSQL |
| `TYPEBOT_ADMIN_EMAIL` | não | — | e-mail que vira admin no primeiro acesso |
| `TYPEBOT_DISABLE_SIGNUP` | não | `true` | bloqueia auto-cadastro de novos usuários |
| `TYPEBOT_DB_HOST` | não | `postgres` | host do PostgreSQL na rede `data` |
| `TYPEBOT_DB_PORT` | não | `5432` | porta do PostgreSQL |
| `TYPEBOT_DB_USER` | não | `postgres` | usuário do PostgreSQL |
| `TYPEBOT_DB_NAME` | não | `typebot` | banco usado pelo Typebot |
| `TYPEBOT_S3_ENDPOINT` | não | — | endpoint S3/MinIO para mídias (ex.: `s3.exemplo.com`); vazio = sem upload |
| `TYPEBOT_S3_ACCESS_KEY` | não | — | access key do bucket |
| `TYPEBOT_S3_SECRET_KEY` | não | — | secret key do bucket |
| `TYPEBOT_S3_BUCKET` | não | `typebot` | nome do bucket |
| `TYPEBOT_S3_PORT` | não | `443` | porta do endpoint S3 |
| `TYPEBOT_S3_SSL` | não | `true` | usar TLS no endpoint S3 |
| `TYPEBOT_IMAGE_TAG` | não | `latest` | tag das imagens typebot-builder/viewer |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `DATA_NET` | não | `data` | rede overlay dos serviços compartilhados |

## Pré-requisitos
- Stack `balancer` (Traefik) + rede `web`; DNS de `TYPEBOT_BUILDER_FQDN` e `TYPEBOT_VIEWER_FQDN`
  apontando para o host.
- Rede `data`: `docker network create --driver overlay --attachable data`.
- Stack **`postgres-pgvector`** na rede `data` com um banco para o Typebot:
  ```sql
  CREATE DATABASE typebot;
  ```
- (Opcional) Stack **`minio`** para armazenar imagens/arquivos dos fluxos — preencha `TYPEBOT_S3_*`.

## Uso
1. Crie o banco `typebot` e gere o `TYPEBOT_ENCRYPTION_SECRET`.
2. Faça o deploy. O `builder` aplica as migrações no primeiro start.
3. Acesse `https://TYPEBOT_BUILDER_FQDN`, faça login (o `TYPEBOT_ADMIN_EMAIL` vira admin) e crie seu
   primeiro bot. Os bots publicados ficam em `https://TYPEBOT_VIEWER_FQDN`.
4. **WhatsApp via Evolution API:** use o bloco *WhatsApp* do Typebot ou um webhook que dispara a
   Evolution API (`POST /message/sendText/<instância>` com header `apikey`) para entregar as mensagens.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| Erro de conexão com o banco | `data` ausente / banco `typebot` não criado / senha errada | criar a rede, o banco e conferir `TYPEBOT_DB_*` |
| Login redireciona errado | `NEXTAUTH_URL` ≠ domínio do builder | conferir `TYPEBOT_BUILDER_FQDN` |
| Bot publicado não abre | `NEXT_PUBLIC_VIEWER_URL` incorreto / viewer fora da `web` | conferir `TYPEBOT_VIEWER_FQDN` e labels |
| Upload de imagem falha | S3/MinIO não configurado | preencher `TYPEBOT_S3_*` e criar o bucket |
| 404/sem TLS | DNS não aponta / fora da `web` | conferir rede/labels e DNS |
