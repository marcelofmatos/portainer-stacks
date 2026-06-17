# portainer-stacks

Coleção de stacks Docker Swarm prontas para deploy via **Portainer App Templates** ou
`docker stack deploy`. Cada pasta contém um `docker-compose.yml` e um `README.md` com uso,
variáveis de ambiente e troubleshooting.

Toda a customização é feita por **variáveis de ambiente** (com defaults sensatos); nenhum segredo
fica no repositório.

## Stacks

### Infra / proxy
| Stack | Descrição | Doc |
|---|---|---|
| [`balancer`](balancer/) | Reverse proxy Traefik v3 + TLS Let's Encrypt | [README](balancer/README.md) |
| [`error-pages`](error-pages/) | Páginas de erro customizadas (middleware do Traefik) | [README](error-pages/README.md) |
| [`swarmprom`](swarmprom/) | Monitoramento Swarm (Prometheus/Grafana/cAdvisor/node-exporter) | [README](swarmprom/README.md) |
| [`docker-service-update`](docker-service-update/) | Webhook de deploy (re-deploy de serviços Swarm via CI/CD) — roda no manager | [README](docker-service-update/README.md) |

### Identidade / acesso
| Stack | Descrição | Doc |
|---|---|---|
| [`account`](account/) | OpenLDAP + LAM + self-service-password | [README](account/README.md) |
| [`lldap`](lldap/) | LDAP leve (usuários + grupos) + UI web | [README](lldap/README.md) |
| [`keycloak`](keycloak/) | IAM/SSO (OIDC/SAML) + PostgreSQL | [README](keycloak/README.md) |
| [`authelia`](authelia/) | Autenticação/2FA (forward-auth) + Redis | [README](authelia/README.md) |

### Apps / dados / ferramentas
| Stack | Descrição | Doc |
|---|---|---|
| [`drive`](drive/) | ownCloud (arquivos) + MySQL + Redis | [README](drive/README.md) |
| [`minio`](minio/) | Object storage S3 + console | [README](minio/README.md) |
| [`phpmyadmin`](phpmyadmin/) | phpMyAdmin (admin MySQL/MariaDB) | [README](phpmyadmin/README.md) |
| [`pgadmin4`](pgadmin4/) | Admin de PostgreSQL | [README](pgadmin4/README.md) |
| [`stirlingpdf`](stirlingpdf/) | Ferramentas de PDF | [README](stirlingpdf/README.md) |
| [`excalidraw`](excalidraw/) | Quadro branco / desenho | [README](excalidraw/README.md) |
| [`openspeedtest`](openspeedtest/) | Teste de velocidade de rede | [README](openspeedtest/README.md) |
| [`workflows`](workflows/) | n8n (automação) + PostgreSQL | [README](workflows/README.md) |
| [`evolution-api`](evolution-api/) | API de WhatsApp (usa `postgres-pgvector` + `redis`) | [README](evolution-api/README.md) |
| [`rocketchat`](rocketchat/) | Chat de equipe + MongoDB | [README](rocketchat/README.md) |
| [`joomla`](joomla/) | CMS Joomla + MySQL | [README](joomla/README.md) |
| [`wordpress`](wordpress/) | CMS/blog WordPress (usa `mariadb`) | [README](wordpress/README.md) |
| [`wikijs`](wikijs/) | Wiki em Markdown (usa `postgres-pgvector`) | [README](wikijs/README.md) |
| [`moodle`](moodle/) | LMS Moodle + MariaDB | [README](moodle/README.md) |
| [`ligerosmart`](ligerosmart/) | Help desk / ITSM (base OTRS) + MariaDB + Elasticsearch | [README](ligerosmart/README.md) |
| [`mailtester`](mailtester/) | MailCatcher (captura de e-mails em dev) | [README](mailtester/README.md) |
| [`espocrm`](espocrm/) | CRM (contas/contatos/funil) — usa `mariadb` | [README](espocrm/README.md) |
| [`twenty`](twenty/) | CRM moderno — usa `postgres-pgvector` + `redis` | [README](twenty/README.md) |
| [`chatwoot`](chatwoot/) | Atendimento omnichannel + WhatsApp (integra `evolution-api`) | [README](chatwoot/README.md) |
| [`typebot`](typebot/) | Construtor de chatbots (builder + viewer) — usa `postgres-pgvector` | [README](typebot/README.md) |

### Bancos e cache (compartilhados, rede `data`)
| Stack | Descrição | Doc |
|---|---|---|
| [`mariadb`](mariadb/) | MariaDB compartilhado | [README](mariadb/README.md) |
| [`postgres-pgvector`](postgres-pgvector/) | PostgreSQL + pgvector nativo (IA/RAG) | [README](postgres-pgvector/README.md) |
| [`redis`](redis/) | Redis (cache/memória) compartilhado | [README](redis/README.md) |

### IA
| Stack | Descrição | Doc |
|---|---|---|
| [`ollama`](ollama/) | Runtime de LLMs | [README](ollama/README.md) |
| [`litellm`](litellm/) | Gateway OpenAI-compatible para LLMs | [README](litellm/README.md) |
| [`chromadb`](chromadb/) | Vector database (RAG) | [README](chromadb/README.md) |
| [`supabase`](supabase/) | Backend self-hosted (Postgres+Auth+API+Studio...) | [README](supabase/README.md) |
| [`flowise`](flowise/) | Builder de agentes de IA (LLM/RAG) — usa `litellm`/`ollama` | [README](flowise/README.md) |

> Ordem sugerida de deploy: **balancer** primeiro (cria o ponto de entrada). Depois as demais em qualquer ordem; `error-pages`/`authelia` viram middlewares que você aplica nas outras stacks.

## Convenções
- **Proxy:** Traefik v3 na rede overlay externa `web`, `exposedByDefault=false` (todo serviço
  exposto declara `traefik.enable=true` em `deploy.labels`).
- **TLS:** Let's Encrypt via httpchallenge (`certresolver=letsencryptresolver`); o DNS de cada
  FQDN deve apontar para o host (porta 80 acessível).
- **Swarm:** labels do Traefik ficam em `deploy.labels`. Bancos/cache ficam fora da rede `web`.
- **Volumes:** locais ao nó. Em cluster multi-worker, fixe os serviços com volume via
  `WORKER_HOSTNAME` (constraint `node.hostname`, comentado nos composes).

## Usar como App Template no Portainer
1. No Portainer: **Settings → App Templates**.
2. Em **URL**, informe o `templates.json` cru deste repositório:
   ```
   https://raw.githubusercontent.com/marcelofmatos/portainer-stacks/main/templates.json
   ```
3. Salve. As stacks aparecem em **App Templates**; ao escolher uma, o Portainer pede as variáveis
   de ambiente e faz o deploy a partir do `docker-compose.yml` correspondente neste repo.

## Deploy manual (alternativa)
```bash
export DRIVE_FQDN=drive.exemplo.com OWNCLOUD_ADMIN_PASSWORD=... OWNCLOUD_DB_PASSWORD=... MYSQL_ROOT_PASSWORD=...
docker stack deploy -c drive/docker-compose.yml drive
```

## Pré-requisitos gerais
- Docker Swarm inicializado.
- Rede overlay pública: `docker network create --driver overlay --attachable web`.
- Para integração LDAP entre stacks: `docker network create --driver overlay --attachable ldap`.
- Para bancos compartilhados (`mariadb`, `postgres-pgvector`): `docker network create --driver overlay --attachable data`.
- Stack `balancer` (Traefik) em execução no manager.

## Segurança
- Segredos **não** são versionados — passe-os como variáveis de ambiente da stack no Portainer.
- O self-service-password (stack `account`) usa **Docker configs externos**; veja o README da stack.
