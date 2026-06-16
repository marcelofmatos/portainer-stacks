# chromadb — ChromaDB

**ChromaDB** (banco de dados vetorial open-source) publicado via Traefik v3 com TLS Let's
Encrypt. Indicado para armazenar embeddings e fazer busca por similaridade (RAG, IA, etc.).
A API HTTP responde internamente na porta `8000`.

> ⚠️ **ATENÇÃO — segurança:** por padrão (sem `CHROMA_AUTH_TOKEN`) a **API fica totalmente
> aberta**: qualquer um que alcance o FQDN pode ler e escrever dados. **Recomenda-se definir
> `CHROMA_AUTH_TOKEN`** (autenticação por token) e/ou proteger o roteador no Traefik
> (basicauth, restrição de IP, etc.). Não exponha publicamente sem alguma proteção.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `CHROMA_FQDN` | sim | — | domínio público (ex.: `chroma.exemplo.com`) |
| `CHROMA_AUTH_TOKEN` | não | — | token de autenticação; se definido, a API exige `Authorization: Bearer <token>` |
| `CHROMA_AUTH_PROVIDER` | não | — | provider de autenticação; para usar token defina `chromadb.auth.token_authn.TokenAuthenticationServerProvider` |
| `CHROMA_ANONYMIZED_TELEMETRY` | não | `FALSE` | habilita/desabilita telemetria anônima |
| `CHROMA_IMAGE_TAG` | não | `latest` | tag da imagem ChromaDB |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `WORKER_HOSTNAME` | não | — | hostname do worker para fixar o volume (cluster multi-worker) |

## Pré-requisitos
- Docker Swarm inicializado e stack `balancer` (Traefik) + rede `web` ativos.
- DNS de `CHROMA_FQDN` apontando para o host (porta 80 acessível para o desafio Let's Encrypt).

## Uso
1. Defina ao menos `CHROMA_FQDN`. Para proteger a API, defina também `CHROMA_AUTH_TOKEN` e
   `CHROMA_AUTH_PROVIDER=chromadb.auth.token_authn.TokenAuthenticationServerProvider`.
2. Faça o deploy (Portainer App Template ou manualmente):
   ```bash
   export CHROMA_FQDN=chroma.exemplo.com
   # opcional: habilitar token
   export CHROMA_AUTH_TOKEN=... CHROMA_AUTH_PROVIDER=chromadb.auth.token_authn.TokenAuthenticationServerProvider
   docker stack deploy -c chromadb/docker-compose.yml chromadb
   ```
3. Teste o heartbeat:
   ```bash
   curl https://CHROMA_FQDN/api/v2/heartbeat
   # com token:
   curl -H "Authorization: Bearer SEU_TOKEN" https://CHROMA_FQDN/api/v2/heartbeat
   ```

## Persistência
Os dados ficam no volume `chroma-data` montado em `/data`. Em Swarm os volumes são locais ao nó:
em cluster com mais de um worker, fixe o serviço definindo `WORKER_HOSTNAME` e descomentando o
constraint `node.hostname` no `docker-compose.yml`, senão o dado pode "sumir" ao reagendar o
container em outro nó.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404 / sem TLS | serviço fora da `web` / DNS não aponta | conferir rede, labels do Traefik e DNS |
| API aberta sem pedir token | `CHROMA_AUTH_TOKEN`/`CHROMA_AUTH_PROVIDER` não definidos | definir as duas variáveis e redeployar |
| `401 Unauthorized` em toda chamada | token ausente/errado no header | enviar `Authorization: Bearer <token>` correto |
| Dados sumiram após reagendamento | volume local em outro nó (multi-worker) | fixar o serviço via `WORKER_HOSTNAME` |
| 502 Bad Gateway | container ainda subindo ou porta incorreta | aguardar/checar logs e a porta `8000` no label |
