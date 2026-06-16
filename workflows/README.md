# workflows — n8n

**n8n** (automação de workflows / low-code) publicado via Traefik v3 com TLS, usando **PostgreSQL**
como banco de dados persistente.

## Componentes

| Serviço | Imagem | Função | Rede |
|---|---|---|---|
| `n8n` | `n8nio/n8n` | editor + execução de workflows (porta interna 5678) | `default` + `web` |
| `db` | `postgres` | banco de dados do n8n | `default` |

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `N8N_FQDN` | sim | — | domínio público do n8n (ex.: `workflows.exemplo.com`) |
| `N8N_ENCRYPTION_KEY` | sim | — | chave de criptografia das credenciais do n8n (segredo) |
| `N8N_DB_PASSWORD` | sim | — | senha do banco PostgreSQL (segredo) |
| `N8N_DB_NAME` | não | `n8n` | nome do banco PostgreSQL |
| `N8N_DB_USER` | não | `n8n` | usuário do banco PostgreSQL |
| `TZ` | não | `America/Sao_Paulo` | timezone usado pelos agendamentos (cron) |
| `N8N_IMAGE_TAG` | não | `latest` | tag da imagem n8n |
| `POSTGRES_IMAGE_TAG` | não | `16-alpine` | tag da imagem PostgreSQL |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

> **N8N_ENCRYPTION_KEY**: gere uma chave aleatória forte (ex.: `openssl rand -hex 32`) e **guarde-a**.
> Se for perdida, todas as credenciais salvas no n8n ficam ilegíveis.

## Pré-requisitos
- Docker Swarm inicializado.
- Traefik (stack `balancer`) e rede `web` ativos.
- DNS de `N8N_FQDN` apontando para o host (porta 80 acessível para o desafio HTTP do Let's Encrypt).

## Uso
1. Defina as variáveis obrigatórias e faça o deploy:
   ```bash
   export N8N_FQDN=workflows.exemplo.com
   export N8N_ENCRYPTION_KEY=$(openssl rand -hex 32)
   export N8N_DB_PASSWORD=...
   docker stack deploy -c workflows/docker-compose.yml workflows
   ```
2. Acesse `https://N8N_FQDN` e crie o usuário owner no primeiro acesso.

## Notas de operação
- **Volumes em Swarm são locais ao nó.** Em cluster com mais de um worker, fixe `n8n` e `db` no mesmo
  nó definindo `WORKER_HOSTNAME` e descomentando o constraint `node.hostname` no compose — caso
  contrário o serviço pode subir em um nó sem o volume com os dados.
- O volume `n8n_data` (`/home/node/.n8n`) guarda a config local e a chave de criptografia em disco;
  preserve-o ao recriar a stack.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | serviço fora da `web` / DNS não aponta | conferir rede/labels e DNS de `N8N_FQDN` |
| n8n não conecta no banco | `db` não pronto ou senha divergente | conferir `N8N_DB_PASSWORD` igual nos dois serviços e logs do `db` |
| Credenciais "corrompidas" após redeploy | `N8N_ENCRYPTION_KEY` mudou ou volume perdido | usar a mesma chave e preservar `n8n_data` |
| Webhooks com URL errada | `N8N_FQDN` incorreto | ajustar `N8N_FQDN` (afeta `WEBHOOK_URL` e `N8N_EDITOR_BASE_URL`) |
| Dados somem ao recriar | volume em outro nó (multi-worker) | fixar nó via `WORKER_HOSTNAME` |
