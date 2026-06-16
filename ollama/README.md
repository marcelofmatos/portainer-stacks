# ollama — Ollama (runtime de LLMs)

**Ollama** roda modelos de linguagem (LLMs) localmente, expondo uma API HTTP na porta `11434`
(compatível com clientes Ollama e com a API de chat estilo OpenAI). O estado (modelos baixados)
fica no volume `ollama-data` em `/root/.ollama`. A exposição pública via Traefik é **opcional**.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `OLLAMA_FQDN` | sim (só se expor via Traefik) | — | domínio público (ex.: `ollama.exemplo.com`) |
| `OLLAMA_IMAGE_TAG` | não | `latest` | tag da imagem `ollama/ollama` |
| `OLLAMA_KEEP_ALIVE` | não | `5m` | tempo que o modelo fica carregado em memória após uso |
| `WORKER_HOSTNAME` | não | — | nó fixo p/ o serviço com volume em cluster multi-worker |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

## Pré-requisitos
- Docker Swarm inicializado.
- Para expor via Traefik: stack `balancer` (Traefik) e rede `web` ativos, e DNS de `OLLAMA_FQDN`
  apontando para o host.
- (Opcional) **GPU NVIDIA**: `nvidia-container-toolkit` instalado no host e o runtime NVIDIA
  configurado no Docker daemon. Veja o bloco comentado em `deploy.resources.reservations.devices`
  no `docker-compose.yml`.

## Uso
1. Deploy:
   ```bash
   # sem exposição pública (consumo só dentro da rede do cluster)
   docker stack deploy -c ollama/docker-compose.yml ollama

   # expondo via Traefik
   export OLLAMA_FQDN=ollama.exemplo.com
   docker stack deploy -c ollama/docker-compose.yml ollama
   ```
2. Baixar e rodar um modelo (dentro do container):
   ```bash
   docker exec -it $(docker ps -qf name=ollama_ollama) ollama pull llama3
   docker exec -it $(docker ps -qf name=ollama_ollama) ollama run llama3
   ```
3. Consumir a API (se exposto): `POST https://OLLAMA_FQDN/api/generate`. Outras stacks no cluster
   podem chamar `http://ollama:11434` pela rede overlay interna.

## Segurança
- **ALERTA:** o Ollama **NÃO possui autenticação nativa** — qualquer um que alcance a porta `11434`
  pode baixar modelos, gerar texto e consumir CPU/GPU/disco do host.
- **Recomendação:** **não exponha publicamente** sem proteção. Se precisar expor via Traefik,
  proteja com um middleware **basicauth** (ou outro método de auth/IP allowlist). Exemplo de labels
  adicionais em `deploy.labels`:
  ```
  - traefik.http.routers.ollama.middlewares=ollama-auth
  - traefik.http.middlewares.ollama-auth.basicauth.users=usuario:$$apr1$$...
  ```
  (gere o hash com `htpasswd -nbB usuario senha` e **dobre os `$`** para `$$` no compose).
- Se o consumo for apenas interno ao cluster, mantenha o serviço **somente na rede `default`**
  (remova `web` e os labels do Traefik).

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS em `OLLAMA_FQDN` | fora da `web` / DNS não aponta / `OLLAMA_FQDN` não definido | conferir rede, labels, DNS e a variável |
| API acessível sem senha | exposto sem middleware de auth | aplicar basicauth no Traefik ou não expor |
| Geração muito lenta | rodando só em CPU | habilitar GPU (bloco `devices` + nvidia-container-toolkit) |
| `could not select device driver "nvidia"` | runtime/toolkit NVIDIA ausente no host | instalar `nvidia-container-toolkit` e configurar o runtime no daemon |
| Modelos somem após reagendar | volume local trocou de nó (multi-worker) | fixar o nó via `WORKER_HOSTNAME` (constraint `node.hostname`) |
| Out of memory ao carregar modelo | modelo maior que a RAM/VRAM disponível | usar modelo menor/quantizado ou reduzir `OLLAMA_KEEP_ALIVE` |
