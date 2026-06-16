# portainer-stacks

Coleção de stacks Docker Swarm prontas para deploy via **Portainer App Templates** ou
`docker stack deploy`. Cada pasta contém um `docker-compose.yml` e um `README.md` com uso,
variáveis de ambiente e troubleshooting.

Toda a customização é feita por **variáveis de ambiente** (com defaults sensatos); nenhum segredo
fica no repositório.

## Stacks

| Stack | Descrição | Doc |
|---|---|---|
| [`balancer`](balancer/) | Reverse proxy Traefik v3 + TLS Let's Encrypt | [README](balancer/README.md) |
| [`drive`](drive/) | ownCloud (arquivos) + MySQL + Redis | [README](drive/README.md) |
| [`account`](account/) | OpenLDAP + LAM + self-service-password | [README](account/README.md) |
| [`tools`](tools/) | phpMyAdmin | [README](tools/README.md) |

> Ordem sugerida de deploy: **balancer** → (account) → **drive** / **tools**.

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
- Stack `balancer` (Traefik) em execução no manager.

## Segurança
- Segredos **não** são versionados — passe-os como variáveis de ambiente da stack no Portainer.
- O self-service-password (stack `account`) usa **Docker configs externos**; veja o README da stack.
