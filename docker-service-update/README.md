# docker-service-update — Docker Manager API

Webhook de deploy para **Docker Swarm**. API HTTP leve (Flask + Docker SDK) que recebe o nome de
uma imagem e força o re-deploy (`docker service update --force`) de todos os serviços do Swarm que
a utilizam. Pensada para ser chamada por pipelines de CI/CD após publicar uma nova imagem.

Projeto OSS: [`marcelofmatos/docker-service-update`](https://github.com/marcelofmatos/docker-service-update)
(imagem `ghcr.io/marcelofmatos/docker-service-update`).

> **Atenção:** o endpoint força o re-deploy de qualquer serviço do cluster. Nunca o exponha sem
> autenticação — esta stack o publica obrigatoriamente atrás de basicauth do Traefik.

## Arquitetura

```mermaid
flowchart LR
    ci((CI/CD)) -->|HTTPS + basicauth| traefik[Traefik · web]
    traefik -->|UPDATE_FQDN · :80| api[api]
    api -->|docker.sock| swarm[(Docker Swarm · manager)]
    swarm -.->|service update --force| services[Serviços do cluster]
```

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `UPDATE_FQDN` | sim | — | domínio do webhook (ex.: `deploy.exemplo.com`) |
| `UPDATE_AUTH_BASIC` | sim | — | basicauth do endpoint, formato `usuario:hash_bcrypt` (gere com `htpasswd -nbB user senha`) |
| `PROXY_NET` | não | `web` | nome da rede overlay pública do Traefik |
| `APP_IMAGE_TAG` | não | `latest` | tag da imagem `ghcr.io/marcelofmatos/docker-service-update` |

## Pré-requisitos
- Stack `balancer` (Traefik) em execução e a rede `web` criada (`docker network create --driver overlay --attachable web`).
- O serviço roda no nó **manager** (acessa `/var/run/docker.sock` para gerenciar o Swarm).
- DNS de `UPDATE_FQDN` apontando para o host (httpchallenge valida na porta 80).

## Uso

### `POST /update_services`
Atualiza todos os serviços Swarm que usam a imagem informada.

```bash
curl --silent --fail -X POST "https://usuario:senha@${UPDATE_FQDN}/update_services" \
  -H "Content-Type: application/json" \
  -d '{"image_name": "ghcr.io/seu-org/meu-servico:main"}'
```

Resposta de sucesso (`200`):
```json
{ "message": "Services updated successfully", "updated_services": ["meu-servico_web"] }
```

No pipeline, guarde a URL com credenciais embutidas como secret (ex.: `WEBHOOK_DEPLOY_MAIN`) e
chame-a ao final do build da imagem.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| `401 Unauthorized` | basicauth ausente/incorreto | use `https://user:senha@host/...`; regenere com `htpasswd -nbB` |
| `500` ao chamar a API | sem acesso ao socket / nó não é manager | confirme placement `node.role == manager` e o mount de `docker.sock` |
| `updated_services` vazio | nenhum serviço usa exatamente aquela imagem | confira o `image_name` (o match é por substring do `Image` do serviço) |
| 404 no FQDN | rede `web` ausente ou DNS errado | conferir rede/Traefik e o apontamento DNS |
| Certificado não emite | DNS não aponta / porta 80 fechada | conferir DNS e firewall do host |
