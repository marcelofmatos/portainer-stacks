# supabase — Supabase self-hosted (Docker Swarm)

**Supabase** (backend Postgres + Auth + REST/GraphQL + Realtime + Storage + Edge Functions +
Studio) publicado via Traefik v3 com TLS. **Apenas o Studio e a API (Kong)** são expostos; os
demais serviços ficam só na rede interna.

> ⚠️ **ADAPTAÇÃO best-effort do compose oficial** (github.com/supabase/supabase, pasta `docker/`)
> para **Docker Swarm**. O upstream foi feito para `docker compose` (v2). Em Swarm, o `depends_on`
> com `condition: service_healthy` e os `healthcheck` **não são honrados do mesmo jeito**: a ordem
> de inicialização é best-effort e alguns containers podem reiniciar algumas vezes no primeiro
> deploy até o `db`/`analytics` ficarem prontos (todos têm restart automático). Alguns recursos
> (coleta de logs pelo `vector`, social login, S3 backend, pooler/Supavisor) podem exigir ajustes
> adicionais. Para produção crítica, avalie o serviço gerenciado.

## Componentes

| Serviço | Imagem | Papel | Exposto |
|---|---|---|---|
| `studio` | `supabase/studio` | Dashboard web | sim (Traefik, `SUPABASE_STUDIO_FQDN`) |
| `kong` | `kong` | API gateway (entrada de toda a API) | sim (Traefik, `SUPABASE_API_FQDN`) |
| `auth` | `supabase/gotrue` | Autenticação (JWT, e-mail, OAuth) | interno |
| `rest` | `postgrest/postgrest` | API REST/GraphQL automática | interno |
| `realtime` | `supabase/realtime` | Subscriptions websocket | interno |
| `storage` | `supabase/storage-api` | API de arquivos (+ transformação de imagem) | interno |
| `imgproxy` | `darthsim/imgproxy` | Processamento de imagens | interno |
| `meta` | `supabase/postgres-meta` | Introspecção do banco (usado pelo Studio) | interno |
| `functions` | `supabase/edge-runtime` | Edge Functions (Deno) | interno |
| `analytics` | `supabase/logflare` | Backend de logs/analytics | interno |
| `vector` | `timberio/vector` | Coletor de logs dos containers | interno |
| `db` | `supabase/postgres` | PostgreSQL (com pgvector) | interno |

## Variáveis de ambiente

### Segredos (obrigatórios — sem default)
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `POSTGRES_PASSWORD` | sim | — | senha do PostgreSQL (usada por todos os serviços) |
| `JWT_SECRET` | sim | — | segredo HS256 dos JWTs (mín. 32 chars) |
| `ANON_KEY` | sim | — | JWT da role `anon` (gerado a partir do `JWT_SECRET`) |
| `SERVICE_ROLE_KEY` | sim | — | JWT da role `service_role` (gerado a partir do `JWT_SECRET`) |
| `DASHBOARD_USERNAME` | sim | — | usuário do basic-auth do Studio (no Kong) |
| `DASHBOARD_PASSWORD` | sim | — | senha do basic-auth do Studio (no Kong) |
| `SECRET_KEY_BASE` | sim | — | chave de sessão do `realtime` (Elixir, 64 chars) |
| `VAULT_ENC_KEY` | sim | — | chave de criptografia do Vault (32 chars) |
| `LOGFLARE_API_KEY` | sim | — | chave de API do `analytics`/`vector` |

### FQDNs / proxy
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `SUPABASE_STUDIO_FQDN` | sim | — | domínio público do Studio (ex.: `studio.exemplo.com`) |
| `SUPABASE_API_FQDN` | sim | — | domínio público da API/Kong (ex.: `api.exemplo.com`) |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `SUPABASE_PUBLIC_URL` | não | `https://${SUPABASE_API_FQDN}` | URL pública da API (usada pelo Studio) |
| `API_EXTERNAL_URL` | não | `https://${SUPABASE_API_FQDN}` | URL externa da API (usada pelo Auth) |
| `SITE_URL` | não | `https://${SUPABASE_STUDIO_FQDN}` | URL do app para redirects do Auth |
| `ADDITIONAL_REDIRECT_URLS` | não | (vazio) | URLs adicionais de redirect do Auth |

### Banco / API
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `POSTGRES_HOST` | não | `db` | host do PostgreSQL |
| `POSTGRES_DB` | não | `postgres` | nome do banco |
| `POSTGRES_PORT` | não | `5432` | porta do PostgreSQL |
| `PGRST_DB_SCHEMAS` | não | `public,storage,graphql_public` | schemas expostos pelo PostgREST |
| `JWT_EXPIRY` | não | `3600` | validade dos JWTs (segundos) |

### Studio / Auth / Storage / Functions
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `STUDIO_DEFAULT_ORGANIZATION` | não | `Default Organization` | nome da org padrão |
| `STUDIO_DEFAULT_PROJECT` | não | `Default Project` | nome do projeto padrão |
| `DISABLE_SIGNUP` | não | `false` | desabilita cadastro de novos usuários |
| `ENABLE_EMAIL_SIGNUP` | não | `true` | habilita cadastro por e-mail |
| `ENABLE_EMAIL_AUTOCONFIRM` | não | `false` | confirma e-mail automaticamente |
| `ENABLE_ANONYMOUS_USERS` | não | `false` | habilita usuários anônimos |
| `ENABLE_PHONE_SIGNUP` | não | `true` | habilita cadastro por telefone |
| `ENABLE_PHONE_AUTOCONFIRM` | não | `true` | confirma telefone automaticamente |
| `SMTP_ADMIN_EMAIL` | não | `admin@example.com` | e-mail admin do mailer |
| `SMTP_HOST` | não | `supabase-mail` | host SMTP (troque por um real para enviar e-mail) |
| `SMTP_PORT` | não | `2500` | porta SMTP |
| `SMTP_USER` | não | `fake_mail_user` | usuário SMTP |
| `SMTP_PASS` | não | `fake_mail_password` | senha SMTP |
| `SMTP_SENDER_NAME` | não | `fake_sender` | nome do remetente |
| `STORAGE_FILE_SIZE_LIMIT` | não | `52428800` | limite de upload (bytes) |
| `IMGPROXY_ENABLE_WEBP_DETECTION` | não | `true` | habilita WebP no imgproxy |
| `FUNCTIONS_VERIFY_JWT` | não | `false` | exige JWT válido nas Edge Functions |
| `DOCKER_SOCKET_LOCATION` | não | `/var/run/docker.sock` | caminho do socket Docker (para o `vector`) |

### Tags de imagem (todas opcionais)
| Variável | Default |
|---|---|
| `STUDIO_IMAGE_TAG` | `20240422-5cf8f30` |
| `KONG_IMAGE_TAG` | `2.8.1` |
| `GOTRUE_IMAGE_TAG` | `v2.149.0` |
| `POSTGREST_IMAGE_TAG` | `v12.0.1` |
| `REALTIME_IMAGE_TAG` | `v2.28.32` |
| `STORAGE_IMAGE_TAG` | `v1.0.6` |
| `IMGPROXY_IMAGE_TAG` | `v3.8.0` |
| `POSTGRES_META_IMAGE_TAG` | `v0.80.0` |
| `EDGE_RUNTIME_IMAGE_TAG` | `v1.45.2` |
| `LOGFLARE_IMAGE_TAG` | `1.4.0` |
| `POSTGRES_IMAGE_TAG` | `15.1.1.41` |
| `VECTOR_IMAGE_TAG` | `0.28.1-alpine` |
| `WORKER_HOSTNAME` | (vazio) — hostname do worker para fixar `db`/`storage`/`imgproxy` em multi-worker |

## Pré-requisitos
- Docker Swarm inicializado; stack `balancer` (Traefik) e rede `web` ativas.
- DNS de `SUPABASE_STUDIO_FQDN` e `SUPABASE_API_FQDN` apontando para o host.
- Arquivos de config em `./config/` (o Portainer clona o repo, então os caminhos relativos
  `./config/...` funcionam no deploy a partir de Git/App Template).

## Geração das chaves

**Nunca** use os valores de exemplo do Supabase em produção. Gere os seus:

1. **`JWT_SECRET`** — uma string aleatória com **no mínimo 32 caracteres**:
   ```bash
   openssl rand -hex 32
   ```
2. **`ANON_KEY`** e **`SERVICE_ROLE_KEY`** — são **JWTs HS256 assinados com o `JWT_SECRET`**, com
   payload `{"role":"anon"|"service_role","iss":"supabase","iat":...,"exp":...}`. Gere-os pela
   ferramenta oficial na doc da Supabase (seção *Self-Hosting → Generate API Keys*:
   <https://supabase.com/docs/guides/self-hosting/docker#generate-api-keys>) ou via `jwt`/script
   assinando com o seu `JWT_SECRET`. **As três chaves precisam ser coerentes entre si** (mesmo
   `JWT_SECRET`).
3. **`SECRET_KEY_BASE`** (realtime) — 64 chars:
   ```bash
   openssl rand -base64 64 | tr -d '\n' | cut -c1-64
   ```
4. **`VAULT_ENC_KEY`** — 32 chars:
   ```bash
   openssl rand -hex 16
   ```
5. **`POSTGRES_PASSWORD`**, **`DASHBOARD_PASSWORD`**, **`LOGFLARE_API_KEY`** — senhas/chaves
   aleatórias:
   ```bash
   openssl rand -hex 24
   ```
6. **`DASHBOARD_USERNAME`** — o usuário do basic-auth do Studio (ex.: `admin`).

## Uso
1. Deploy da stack `balancer` (Traefik) primeiro.
2. Suba a stack `supabase` informando as variáveis (no mínimo os 9 segredos + os 2 FQDNs).
3. Acesse o **Studio** em `https://SUPABASE_STUDIO_FQDN` (login = `DASHBOARD_USERNAME` /
   `DASHBOARD_PASSWORD`, via basic-auth do Kong).
4. A **API** fica em `https://SUPABASE_API_FQDN` (REST: `/rest/v1/`, Auth: `/auth/v1/`,
   Realtime: `/realtime/v1/`, Storage: `/storage/v1/`, Functions: `/functions/v1/`).

Deploy manual (alternativa):
```bash
export SUPABASE_STUDIO_FQDN=studio.exemplo.com SUPABASE_API_FQDN=api.exemplo.com
export POSTGRES_PASSWORD=... JWT_SECRET=... ANON_KEY=... SERVICE_ROLE_KEY=... \
       DASHBOARD_USERNAME=admin DASHBOARD_PASSWORD=... SECRET_KEY_BASE=... \
       VAULT_ENC_KEY=... LOGFLARE_API_KEY=...
docker stack deploy -c supabase/docker-compose.yml supabase
```

## Arquivos de config (`./config/`)
| Arquivo | Usado por | Observação |
|---|---|---|
| `config/kong.yml` | `kong` | rotas da API; usa placeholders `$SUPABASE_ANON_KEY`, `$DASHBOARD_USERNAME` etc. (expandidos no entrypoint) — **sem segredos reais** |
| `config/vector.yml` | `vector` | pipeline de logs → `analytics`; usa `${LOGFLARE_API_KEY}` |
| `config/db/*.sql` | `db` | scripts de init (roles, jwt, realtime/analytics schemas, webhooks) — leem `$POSTGRES_PASSWORD`/`$JWT_SECRET` do ambiente |
| `config/functions/main/index.ts` | `functions` | router padrão das Edge Functions |

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS no Studio ou API | serviço fora da `web` / DNS não aponta | conferir rede `web`, labels e DNS dos FQDNs |
| Studio carrega mas API falha | `kong` não subiu / `ANON_KEY` incoerente com `JWT_SECRET` | conferir logs do `kong` e regenerar as chaves a partir do mesmo `JWT_SECRET` |
| `auth`/`rest`/`storage` reiniciando no 1º deploy | `db`/`analytics` ainda inicializando (Swarm não honra `depends_on healthy`) | aguardar; o restart automático estabiliza após o `db` ficar pronto |
| Login do Studio rejeitado | basic-auth do Kong | usar `DASHBOARD_USERNAME`/`DASHBOARD_PASSWORD` |
| Senhas de roles não batem | `roles.sql` roda só no **init** do volume novo | trocar `POSTGRES_PASSWORD` exige recriar o volume `db-data` (ou `ALTER USER` manual) |
| Logs vazios no Studio | `vector` roteia por `container_name` fixo; em Swarm os nomes têm sufixo de task | ajustar `config/vector.yml` (regras `router`) para os nomes reais, ou tolerar logs parciais |
| `db` não inicia em multi-worker | volume local em outro nó | fixar `db` (e `storage`/`imgproxy`) via `WORKER_HOSTNAME` + constraint `node.hostname` |

## Segurança
- Os 9 segredos **não** ficam no repositório — passe-os como variáveis da stack no Portainer.
- O Studio é protegido por basic-auth no Kong; ainda assim, considere restrição de IP / forward-auth
  (stack `authelia`) no Traefik para o `SUPABASE_STUDIO_FQDN`.
- A porta do PostgreSQL **não** é publicada no host (acesso só pela rede interna `default`).
