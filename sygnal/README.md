# sygnal — push gateway de referência do Matrix.org

**Sygnal** publicado via Traefik v3 com TLS. Recebe `POST /_matrix/push/v1/notify` de
**qualquer** homeserver Matrix e entrega o push de fato via **Firebase Cloud Messaging**
(Android direto; iOS por relay FCM → APNs) e/ou direto na **APNs** da Apple. Não é
específico de nenhum app — serve qualquer cliente Matrix que registre um pusher HTTP
apontando pra esta stack (Element, FluffyChat e forks, clientes próprios, etc.).

**Autocontido:** a config e a credencial do FCM vivem num volume (`sygnal-config`)
montado em `/sygnal` — dentro dele ficam `/sygnal/sygnal.yaml` (regras de `apps:`,
`project_id`) e `/sygnal/firebase-service-account.json`. Um volume novo nasce **vazio**;
popule-o com os arquivos do seu app (ver *Uso*).

## Arquitetura

```mermaid
flowchart LR
    hs[("Homeserver Matrix<br/>(qualquer)")] -->|POST push/v1/notify| traefik[Traefik · web]
    traefik -->|SYGNAL_FQDN| sygnal[sygnal]
    sygnal -->|HOME /sygnal| vol[(sygnal-config)]
    sygnal -->|FCM HTTP v1 · service_account| fcm[Firebase Cloud Messaging]
    fcm -.->|relay APNs| ios((iOS))
    fcm -->|entrega direta| android((Android))
    sygnal -.->|alternativa: direto, sem FCM| apns[APNs da Apple]
```

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `SYGNAL_FQDN` | sim | — | domínio público do gateway (ex.: `push.exemplo.com`) |
| `SYGNAL_IMAGE_TAG` | não | `v0.17.0` | tag da imagem `matrixdotorg/sygnal` |
| `WORKER_HOSTNAME` | não (Swarm) | — | fixa o nó em cluster multi-worker (o volume é local ao nó) |
| `SYGNAL_CONFIG_DIR` | não (standalone) | `./config` | pasta local montada em `/sygnal` (bind mount) |
| `SYGNAL_EXTERNAL_PORT` | não | `8105` | porta no host, só se habilitar o acesso direto (bloco `ports:` comentado) |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

## Pré-requisitos
- **Hardware mínimo:** 0.25 vCPU · 128 MB RAM · 1 GB disco
- **Hardware ideal:** 0.5 vCPU · 256 MB RAM · 2 GB disco
- Traefik (stack `balancer`) e rede `web` ativos.
- DNS de `SYGNAL_FQDN` apontando para o host.
- Um **projeto Firebase** com Cloud Messaging habilitado para o app que vai receber o
  push (gratuito, 1 projeto por app/organização, não por instalação do Sygnal).
- Se o app tiver usuários iOS: a **APNs Authentication Key** (`.p8`) da conta Apple
  Developer precisa estar cadastrada nesse projeto Firebase (Project Settings → Cloud
  Messaging → "Apple app configuration"). Isso é feito **no Firebase Console**, não
  nesta stack.
- Duas credenciais preparadas a partir dos `.example` em `config/` (ver *Uso*):
  1. `sygnal.yaml` — com o `app_id` (ou prefixo + `*`) e o `project_id` do seu app.
  2. `firebase-service-account.json` — gerado em Firebase Console → Project Settings
     → Service Accounts → "Generate new private key". **Nunca** commitar o arquivo
     real (só os `.example` com placeholders vão pro repo).

## Uso

### Docker Swarm (`type: 2`)
1. Deploy pela App Template do Portainer (ou `docker stack deploy`), informando
   `SYGNAL_FQDN`. O volume `sygnal-config` nasce vazio.
2. **Popule o volume** a partir do nó onde ele vive (ajuste o nome se o Portainer
   prefixou com o nome da stack, ex. `<stack>_sygnal-config` — confira com
   `docker volume ls`):
   ```bash
   cp config/sygnal.yaml.example sygnal.yaml                                     # edite os placeholders
   cp config/firebase-service-account.json.example firebase-service-account.json  # substitua pelo arquivo REAL baixado do Firebase
   docker run --rm \
     -v sygnal-config:/data \
     -v "$PWD":/source:ro \
     alpine sh -c 'cp /source/sygnal.yaml /source/firebase-service-account.json /data/'
   rm sygnal.yaml firebase-service-account.json   # não deixe as credenciais soltas no disco
   ```
3. Reinicie o serviço pra ele ler os arquivos novos (Sygnal só lê a config na
   inicialização): `docker service update --force <stack>_sygnal`.
4. Para atualizar depois, repita o passo 2 (sobrescreve os arquivos no volume) e o
   passo 3 — sem versão imutável, sem recriar Docker config.

### Standalone (`type: 3`)
```bash
cp config/sygnal.yaml.example config/sygnal.yaml                                     # edite os placeholders
cp config/firebase-service-account.json.example config/firebase-service-account.json  # substitua pelo arquivo REAL
docker network create web   # se ainda não existir
```
Aqui `config/` já é o bind mount (`SYGNAL_CONFIG_DIR`): edite os arquivos direto e só
reinicie o container (`docker compose restart sygnal`) pra aplicar.

### Depois de subir (as duas variantes)
1. No app Matrix, registre o pusher (`POST /_matrix/client/v3/pushers/set`) com
   `kind: http` e `data.url` = `https://<SYGNAL_FQDN>/_matrix/push/v1/notify`.
2. Confirme nos logs (`docker service logs <stack>_sygnal` / `docker compose logs -f`)
   que a notificação chegou e foi despachada (`dispatch_notification` sem erro de
   "unknown app ID" nem de credencial do FCM).
3. Teste ponta a ponta com um evento real antes de liberar para usuários.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| Container reinicia em loop / erro ao carregar config | volume `sygnal-config` ainda vazio (deploy inicial) | popular o volume (ver *Uso*) e reiniciar o serviço |
| Push nunca chega, sem erro visível no app | app registrou pusher com `gatewayUrl` vazio/errado | conferir se o app está apontando pro `SYGNAL_FQDN` certo |
| Log mostra "Got notification for unknown app ID" | `app_id` do pusher não casa com nenhuma chave em `apps:` | revisar o `app_id` real enviado pelo cliente e o padrão/glob em `sygnal.yaml` |
| Log mostra "Got notification for an ambiguous app ID" | duas chaves em `apps:` casam com o mesmo `app_id` (glob sobreposto) | revisar os padrões, deixar só um casando |
| Erro de autenticação/credencial do FCM | `service_account_file` errado, vazio ou projeto Firebase sem Cloud Messaging habilitado | regenerar a service account, sobrescrever no volume e reiniciar o serviço |
| Editei o arquivo no volume/pasta e nada mudou | Sygnal só lê a config na inicialização | reiniciar o serviço/container |
| iOS não recebe mas Android recebe | `.p8` da Apple não cadastrado no projeto Firebase, ou `fcm_options.apns.payload.aps` ausente | cadastrar a APNs key no Firebase Console; conferir o bloco `fcm_options` no `sygnal.yaml` |
| 404/sem TLS | fora da `web` / DNS não aponta pro host | conferir rede/labels e DNS de `SYGNAL_FQDN` |
