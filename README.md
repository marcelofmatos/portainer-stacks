# portainer-stacks

Coleção de stacks Docker Swarm prontas para deploy via **Portainer App Templates** ou
`docker stack deploy`. Cada pasta contém um `docker-compose.yml` e um `README.md` com uso,
variáveis de ambiente e troubleshooting.

Toda a customização é feita por **variáveis de ambiente** (com defaults sensatos); nenhum segredo
fica no repositório.

## Requisitos de hardware

Cada stack documenta o **hardware mínimo** (sobe e funciona) e o **ideal** (folga para uso real) na
seção `## Pré-requisitos` do seu README, e repete um resumo no comentário de cabeçalho do
`docker-compose.yml`. Os números são por **1 réplica** e cobrem os serviços da própria stack — stacks
que reaproveitam bancos/cache compartilhados (`mariadb`, `postgres-pgvector`, `redis`, `mongodb`) somam
o consumo dessas stacks por cima. Use-os para dimensionar o nó **worker** antes do deploy: subir uma
stack pesada num nó sem RAM/CPU suficiente costuma resultar em `502 Bad Gateway` (container reiniciando,
OOM ou nó sobrecarregado).

Porte das stacks (por RAM, do mínimo ao ideal):

| Porte | RAM (mín → ideal) | Stacks |
|---|---|---|
| **Leve** | 64 MB → 512 MB | `error-pages`, `cloudflared`, `socat`, `web-redirect`, `docker-service-update`, `haproxy`, `lldap`, `ssh-server`, `balancer`, `phpmyadmin`, `pgadmin4`, `mongo-express`, `redisinsight`, `excalidraw`, `openspeedtest`, `mailtester`, `protonmail-bridge`, `searxng`, `authelia`, `phpnetmap`, `redis` |
| **Médio** | 512 MB → 2 GB | `account`, `keycloak`, `zabbix`, `mariadb`, `postgres-pgvector`, `mongodb`, `chromadb`, `qdrant`, `minio`, `workflows`, `evolution-api`, `joomla`, `wordpress`, `wikijs`, `espocrm`, `typebot`, `stirlingpdf`, `flowise`, `litellm`, `open-webui`, `element` |
| **Pesado** | 2 GB → 4–8 GB | `swarmprom`, `elasticsearch`, `drive`, `rocketchat`, `moodle`, `twenty`, `botpress`, `langfuse`, `librechat`, `anythingllm`, `chatwoot`, `ligerosmart`, `dify`, `supabase` |
| **GPU / ML** | 8 GB+ (GPU recomendada) | `ollama`, `comfyui`, `ragflow` |

## Stacks

### Infra / proxy
| Stack | Descrição | Doc |
|---|---|---|
| [`balancer`](balancer/) | Reverse proxy Traefik v3 + TLS Let's Encrypt | [README](balancer/README.md) |
| [`error-pages`](error-pages/) | Páginas de erro customizadas (middleware do Traefik) | [README](error-pages/README.md) |
| [`swarmprom`](swarmprom/) | Monitoramento Swarm (Prometheus/Grafana/cAdvisor/node-exporter) | [README](swarmprom/README.md) |
| [`zabbix`](zabbix/) | Monitoramento (server+web+agent) — usa `postgres-pgvector` | [README](zabbix/README.md) |
| [`phpnetmap`](phpnetmap/) | Mapa/monitoramento de rede via SNMP | [README](phpnetmap/README.md) |
| [`haproxy`](haproxy/) | Load balancer TCP/HTTP + página de stats | [README](haproxy/README.md) |
| [`cloudflared`](cloudflared/) | Cloudflare Tunnel (expõe serviços sem abrir portas/IP público) | [README](cloudflared/README.md) |
| [`socat`](socat/) | Relay TCP (expõe serviço interno para fora) | [README](socat/README.md) |
| [`web-redirect`](web-redirect/) | Redirecionador HTTP (301/302) | [README](web-redirect/README.md) |
| [`ssh-server`](ssh-server/) | Servidor OpenSSH/SFTP | [README](ssh-server/README.md) |
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
| [`element`](element/) | Matrix self-hosted (Synapse + Element Web) + PostgreSQL | [README](element/README.md) |
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
| [`botpress`](botpress/) | Plataforma de chatbots — usa `postgres-pgvector` | [README](botpress/README.md) |
| [`protonmail-bridge`](protonmail-bridge/) | Ponte SMTP/IMAP da conta ProtonMail | [README](protonmail-bridge/README.md) |

### Bancos e cache compartilhados — opção (rede `data`)

> Alternativa ao padrão preferido (banco embarcado por stack). Útil para um DB central; a rede
> `data` também conecta as **ferramentas de administração** aos bancos de cada stack (`<stack>_db`).

| Stack | Descrição | Doc |
|---|---|---|
| [`mariadb`](mariadb/) | MariaDB compartilhado | [README](mariadb/README.md) |
| [`postgres-pgvector`](postgres-pgvector/) | PostgreSQL + pgvector nativo (IA/RAG) | [README](postgres-pgvector/README.md) |
| [`redis`](redis/) | Redis (cache/memória) compartilhado | [README](redis/README.md) |
| [`mongodb`](mongodb/) | MongoDB (NoSQL) compartilhado | [README](mongodb/README.md) |
| [`mongo-express`](mongo-express/) | Admin web do MongoDB | [README](mongo-express/README.md) |
| [`redisinsight`](redisinsight/) | GUI de administração do Redis | [README](redisinsight/README.md) |
| [`elasticsearch`](elasticsearch/) | Busca/índice (single-node) compartilhado | [README](elasticsearch/README.md) |

### IA
| Stack | Descrição | Doc |
|---|---|---|
| [`ollama`](ollama/) | Runtime de LLMs | [README](ollama/README.md) |
| [`litellm`](litellm/) | Gateway OpenAI-compatible para LLMs | [README](litellm/README.md) |
| [`chromadb`](chromadb/) | Vector database (RAG) | [README](chromadb/README.md) |
| [`qdrant`](qdrant/) | Vector database de alta performance (rede `data`) | [README](qdrant/README.md) |
| [`supabase`](supabase/) | Backend self-hosted (Postgres+Auth+API+Studio...) | [README](supabase/README.md) |
| [`flowise`](flowise/) | Builder de agentes de IA (LLM/RAG) — usa `litellm`/`ollama` | [README](flowise/README.md) |
| [`open-webui`](open-webui/) | UI de chat para Ollama/OpenAI-compatible | [README](open-webui/README.md) |
| [`librechat`](librechat/) | UI multi-provedor (RAG/agentes) — usa `mongodb` + Meilisearch | [README](librechat/README.md) |
| [`anythingllm`](anythingllm/) | RAG tudo-em-um (chat + documentos) | [README](anythingllm/README.md) |
| [`dify`](dify/) | LLMOps/RAG — usa `postgres-pgvector` + `redis` + `qdrant` | [README](dify/README.md) |
| [`ragflow`](ragflow/) | Motor de RAG — usa `mariadb`+`redis`+`elasticsearch`+`minio` | [README](ragflow/README.md) |
| [`langfuse`](langfuse/) | Observabilidade/tracing de LLM — usa `postgres-pgvector` | [README](langfuse/README.md) |
| [`searxng`](searxng/) | Meta-busca (web search para agentes/RAG) | [README](searxng/README.md) |
| [`comfyui`](comfyui/) | Geração de imagem (Stable Diffusion) — requer GPU | [README](comfyui/README.md) |

> Ordem sugerida de deploy: **balancer** primeiro (cria o ponto de entrada). Depois as demais em qualquer ordem; `error-pages`/`authelia` viram middlewares que você aplica nas outras stacks.

## Convenções
- **Proxy:** Traefik v3 na rede overlay externa `web`, `exposedByDefault=false` (todo serviço
  exposto declara `traefik.enable=true` em `deploy.labels`).
- **TLS:** Let's Encrypt via httpchallenge (`certresolver=letsencryptresolver`); o DNS de cada
  FQDN deve apontar para o host (porta 80 acessível).
- **Swarm:** labels do Traefik ficam em `deploy.labels`. Bancos/cache ficam fora da rede `web`.
- **Banco de dados:** o padrão **preferido** é **cada stack embarcar o próprio banco**
  (mariadb/postgres/mongo) na rede `default` da stack — volume isolado, fácil de **migrar de host**
  copiando só aquele volume. O banco também entra na rede `data`, mas **só** para as ferramentas de
  administração (`phpmyadmin`, `pgadmin4`, `redisinsight`, `mongo-express`) o alcançarem como
  `<stack>_db`. As stacks de banco compartilhado (`mariadb`, `postgres-pgvector`, `redis`,
  `mongodb`) seguem disponíveis como **opção** para quem preferir um DB central.
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
- Rede `data` (ferramentas de admin alcançando bancos embarcados, ou bancos compartilhados): `docker network create --driver overlay --attachable data`.
- Stack `balancer` (Traefik) em execução no manager.

## Segurança
- Segredos **não** são versionados — passe-os como variáveis de ambiente da stack no Portainer.
- O self-service-password (stack `account`) usa **Docker configs externos**; veja o README da stack.
