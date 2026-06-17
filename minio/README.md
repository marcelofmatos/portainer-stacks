# minio — MinIO (object storage S3)

**MinIO** é um servidor de armazenamento de objetos compatível com a API S3. Publicado via
Traefik v3 com TLS em dois domínios distintos: um para a **API S3** (usada por clientes/SDKs) e
outro para o **Console** web de administração.

## Componentes
| Serviço | Imagem | Porta interna | Função |
|---|---|---|---|
| `minio` | `minio/minio` | 9000 (API S3), 9001 (Console) | armazenamento de objetos + console web |

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `MINIO_S3_FQDN` | sim | — | domínio público da API S3 (ex.: `s3.exemplo.com`) |
| `MINIO_CONSOLE_FQDN` | sim | — | domínio público do console web (ex.: `minio.exemplo.com`) |
| `MINIO_ROOT_USER` | sim | — | usuário root (admin) inicial |
| `MINIO_ROOT_PASSWORD` | sim | — | senha do usuário root (segredo; mín. 8 caracteres) |
| `MINIO_IMAGE_TAG` | não | `latest` | tag da imagem MinIO |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `WORKER_HOSTNAME` | não | — | hostname do nó para fixar o volume em cluster multi-worker |

## Pré-requisitos
- **Hardware mínimo:** 1 vCPU · 512 MB RAM · 20 GB disco
- **Hardware ideal:** 2 vCPU · 1 GB RAM · 100 GB disco
- Docker Swarm inicializado e stack `balancer` (Traefik) em execução.
- Rede overlay externa `web`: `docker network create --driver overlay --attachable web`.
- DNS de `MINIO_S3_FQDN` **e** `MINIO_CONSOLE_FQDN` apontando para o host (porta 80 acessível
  para o desafio HTTP do Let's Encrypt).

## Uso
```bash
export MINIO_S3_FQDN=s3.exemplo.com MINIO_CONSOLE_FQDN=minio.exemplo.com \
       MINIO_ROOT_USER=admin MINIO_ROOT_PASSWORD=...
docker stack deploy -c minio/docker-compose.yml minio
```
- **Console:** acesse `https://MINIO_CONSOLE_FQDN` e faça login com `MINIO_ROOT_USER` /
  `MINIO_ROOT_PASSWORD`. Crie buckets, usuários e chaves de acesso por ali.
- **API S3:** aponte clientes/SDKs (mc, aws-cli, boto3, etc.) para `https://MINIO_S3_FQDN`.

> NOTA (volumes em Swarm): o volume `minio-data` é local ao nó. Em cluster com mais de um worker,
> fixe o serviço definindo `WORKER_HOSTNAME` e descomentando o constraint `node.hostname` no
> compose, garantindo que o serviço suba sempre no nó onde os dados estão.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | serviço fora da `web` / DNS não aponta | conferir rede, labels e DNS dos dois FQDNs |
| Console não carrega / redirect errado | `MINIO_BROWSER_REDIRECT_URL` divergente | conferir `MINIO_CONSOLE_FQDN` (define o redirect) |
| Serviço não sobe | senha root curta | usar `MINIO_ROOT_PASSWORD` com no mínimo 8 caracteres |
| Dados sumiram após redeploy | container subiu em outro nó | fixar o nó via `WORKER_HOSTNAME` (volume é local) |
| Cliente S3 com erro de TLS/host | endpoint apontando para o console | usar `MINIO_S3_FQDN` (porta 9000) para a API, não o console |
