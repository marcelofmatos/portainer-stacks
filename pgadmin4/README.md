# pgadmin4 — pgAdmin 4

**pgAdmin 4** publicado via Traefik v3 com TLS. Serve para **administrar os bancos PostgreSQL do
cluster**: ao adicionar um *server* na interface, informe o host do banco (ex.: `db` ou
`<stack>_db`) alcançável pela rede do container.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `PGADMIN_FQDN` | sim | — | domínio público (ex.: `pgadmin.exemplo.com`) |
| `PGADMIN_EMAIL` | sim | — | e-mail de login do admin (`PGADMIN_DEFAULT_EMAIL`) |
| `PGADMIN_PASSWORD` | sim | — | senha de login do admin (segredo) |
| `PGADMIN_IMAGE_TAG` | não | `latest` | tag da imagem `dpage/pgadmin4` |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `WORKER_HOSTNAME` | não | — | hostname do worker para fixar o volume (multi-worker) |

## Pré-requisitos
- Traefik (stack `balancer`) e rede `web` ativos.
- DNS de `PGADMIN_FQDN` apontando para o host.
- O serviço precisa enxergar o(s) banco(s): para acessar bancos em outras stacks, anexe também a
  rede onde eles estão (ajuste a seção `networks` do serviço) ou use um host alcançável na `web`.

## Uso
1. Acesse `https://PGADMIN_FQDN` e faça login com `PGADMIN_EMAIL` / `PGADMIN_PASSWORD`.
2. **Add New Server** → aba *Connection* → informe o **host do banco** (ex.: `db` ou
   `<stack>_db`), porta `5432`, usuário e senha do PostgreSQL.

## Segurança
- Exponha apenas se necessário; considere proteger com middleware de autenticação no Traefik
  (basicauth) ou restrição de IP.
- Não versione `PGADMIN_PASSWORD` — passe-o como variável de ambiente da stack no Portainer.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | fora da `web` / DNS não aponta | conferir rede/labels e DNS |
| "could not translate host name" | host do banco não resolve pela rede do container | anexar a rede do banco ao serviço `pgadmin` |
| "password authentication failed" | credenciais do PostgreSQL incorretas | revisar usuário/senha no cadastro do server |
| Configurações some após redeploy | volume não fixado no mesmo nó | definir `WORKER_HOSTNAME` e descomentar o constraint de hostname |
