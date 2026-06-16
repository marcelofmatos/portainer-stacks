# stirlingpdf — Stirling-PDF

**Stirling-PDF** publicado via Traefik v3 com TLS. Suite local e self-hosted de ferramentas de PDF:
converter, mesclar, dividir, comprimir, OCR, assinar, rotacionar, adicionar/remover senha e mais.
Todo o processamento acontece no próprio container — nenhum arquivo sai para serviços externos.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `STIRLING_FQDN` | sim | — | domínio público (ex.: `pdf.exemplo.com`) |
| `STIRLING_ENABLE_SECURITY` | não | `false` | habilita login/contas de usuário (`DOCKER_ENABLE_SECURITY`) |
| `STIRLING_LANGS` | não | `pt_BR,en_US` | idiomas disponíveis na interface (`LANGS`) |
| `STIRLING_IMAGE_TAG` | não | `latest` | tag da imagem `frooodle/s-pdf` |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `WORKER_HOSTNAME` | não | — | hostname do worker para fixar o serviço (cluster multi-worker; ver NOTA) |

## Pré-requisitos
- Docker Swarm inicializado e stack `balancer` (Traefik) com a rede `web` ativos.
- DNS de `STIRLING_FQDN` apontando para o host (porta 80 acessível para o desafio Let's Encrypt).

## Uso
1. No Portainer, faça deploy da stack informando ao menos `STIRLING_FQDN`.
2. Acesse `https://STIRLING_FQDN`.
3. Com `STIRLING_ENABLE_SECURITY=true`, na primeira execução é criado um usuário admin padrão
   (`admin` / `stirling`) — altere a senha logo após o primeiro login.

### Persistência
- `/configs` (volume `configs`): configurações e ajustes da aplicação.
- `/usr/share/fonts/opentype/custom` (volume `fonts`): fontes OpenType customizadas para uso nas
  operações de PDF.

> **NOTA (volumes em Swarm):** os volumes são locais ao nó. Em cluster com mais de um worker, fixe
> o serviço definindo `WORKER_HOSTNAME` e descomentando o constraint `node.hostname` no compose —
> caso contrário, ao reagendar o serviço em outro nó, `/configs` e as fontes customizadas aparecerão
> vazios (o volume é recriado no novo nó).

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | serviço fora da `web` / DNS não aponta | conferir rede, `deploy.labels` e DNS de `STIRLING_FQDN` |
| Certificado não emite | porta 80 inacessível para o desafio Let's Encrypt | liberar a porta 80 no host/firewall e conferir DNS |
| Idioma não aparece | não listado em `LANGS` | incluir o código (ex.: `pt_BR`) em `STIRLING_LANGS` e redeployar |
| Configuração/fontes "somem" após update | serviço reagendou em outro worker | fixar via `WORKER_HOSTNAME` (ver NOTA) |
| Não pede login | `DOCKER_ENABLE_SECURITY=false` | definir `STIRLING_ENABLE_SECURITY=true` e redeployar |
