# balancer — Traefik v3 (reverse proxy + TLS)

Reverse proxy **Traefik v3** para Docker Swarm, com TLS automático via Let's Encrypt
(httpchallenge) e dashboard protegido por basicauth. Cria o ponto de entrada (`:80`/`:443`) e
usa a rede externa pública por onde as demais stacks publicam.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `DOMAIN` | sim | — | domínio base (dashboard em `traefik.balancer.DOMAIN`) |
| `ACME_EMAIL` | sim | — | e-mail de contato do Let's Encrypt |
| `HTTP_AUTH_BASIC` | sim | — | basicauth do dashboard, formato `usuario:hash_bcrypt` (gere com `htpasswd -nbB user senha`) |
| `PROXY_NET` | não | `web` | nome da rede overlay pública |
| `TRAEFIK_IMAGE_TAG` | não | `v3.6` | tag da imagem Traefik |
| `TRAEFIK_LOG_LEVEL` | não | `WARN` | nível de log (`DEBUG`/`INFO`/`WARN`/`ERROR`) |

## Pré-requisitos
- Roda no nó **manager** (acessa o socket do Docker).
- A rede overlay pública precisa existir e ser **attachable**:
  ```bash
  docker network create --driver overlay --attachable web
  ```
- Portas 80 e 443 livres no host (publicadas em modo `host`).
- DNS dos serviços apontando para o host (o httpchallenge valida na porta 80).

## Como as outras stacks publicam
Cada serviço exposto entra na rede `web` e declara, em `deploy.labels`:
```yaml
- traefik.enable=true
- traefik.http.routers.<nome>.rule=Host(`<fqdn>`)
- traefik.http.routers.<nome>.entrypoints=websecure
- traefik.http.routers.<nome>.tls=true
- traefik.http.routers.<nome>.tls.certresolver=letsencryptresolver
- traefik.http.services.<nome>.loadbalancer.server.port=<porta-interna>
```
> `exposedByDefault=false`: sem `traefik.enable=true` o serviço não é roteado.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404 em todos os serviços | rede `web` não existe / Traefik não subiu no manager | criar a rede; conferir placement `node.role == manager` |
| Certificado não emite | DNS não aponta / porta 80 fechada / rate limit do LE | conferir DNS e firewall; ver `logs` |
| Dashboard 401 eterno | `HTTP_AUTH_BASIC` inválido | regenerar com `htpasswd -nbB`; cuidado com `$` em alguns shells |
| Serviço novo não aparece | faltou `traefik.enable=true` ou está fora da `web` | adicionar label/rede |
