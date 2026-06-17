# litellm — LiteLLM Proxy

**LiteLLM Proxy** publicado via Traefik v3 com TLS. Gateway **OpenAI-compatible** que expõe um único
endpoint (`/v1/...`) para vários provedores de LLM (OpenAI, Anthropic, Azure, etc.). Os modelos
disponíveis são definidos em um `config.yaml` montado como **Docker config externo**. Serviço
**stateless** (sem volume).

## Arquitetura

```mermaid
flowchart LR
    cliente((Cliente API)) -->|HTTPS LITELLM_FQDN| traefik[Traefik · web]
    traefik --> proxy[litellm · proxy]
    proxy --> cfg[/Docker config · config.yaml/]
    proxy -.->|DATABASE_URL opcional| db[(PostgreSQL externo)]
```

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `LITELLM_FQDN` | sim | — | domínio público (ex.: `llm.exemplo.com`) |
| `LITELLM_MASTER_KEY` | sim | — | chave mestra do proxy (autentica chamadas ao gateway; use `sk-...`) |
| `LITELLM_SALT_KEY` | sim | — | salt para criptografar credenciais no banco (NÃO mudar depois de definido) |
| `DATABASE_URL` | não | vazio | conexão Postgres opcional (ex.: `postgresql://user:pass@host/db`) para virtual keys/logs |
| `OPENAI_API_KEY` | não | vazio | chave OpenAI usada pelo `config.yaml` via `os.environ/OPENAI_API_KEY` |
| `LITELLM_CONFIG_NAME` | não | `litellm_config_v1` | nome do Docker config com o `config.yaml` |
| `LITELLM_IMAGE_TAG` | não | `main-stable` | tag da imagem LiteLLM |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

> Para cada provedor adicional referenciado no `config.yaml` (ex.: `os.environ/ANTHROPIC_API_KEY`),
> adicione a variável correspondente na seção `environment` do serviço no `docker-compose.yml`.

## Pré-requisitos
- Traefik (stack `balancer`) e rede `web` ativos.
- DNS de `LITELLM_FQDN` apontando para o host (porta 80 acessível para o desafio Let's Encrypt).
- Docker config externo com o `config.yaml` criado (abaixo).

## Pré-requisito: Docker config externo do config.yaml
O serviço espera 1 Docker config já existente no Swarm, montado em `/app/config.yaml`. Crie-o a
partir do arquivo [`config/config.yaml`](config/config.yaml) (ajuste a `model_list` antes):

```bash
# 1) edite config/config.yaml (model_list dos modelos que quer expor)
docker config create litellm_config_v1 config/config.yaml
```

> Docker config é **imutável**. Para alterar depois, crie uma nova versão
> (`litellm_config_v2`), aponte `LITELLM_CONFIG_NAME` para ela e atualize a stack.

As chaves dos provedores **não** ficam no `config.yaml`: ele usa `os.environ/<VAR>` e as variáveis
são injetadas no ambiente do serviço (ex.: `OPENAI_API_KEY`).

## Uso
1. Crie o Docker config (acima) e faça o deploy passando as variáveis obrigatórias.
2. Teste o gateway (a `LITELLM_MASTER_KEY` autentica as chamadas):
   ```bash
   curl https://LITELLM_FQDN/v1/chat/completions \
     -H "Authorization: Bearer $LITELLM_MASTER_KEY" \
     -H "Content-Type: application/json" \
     -d '{"model":"gpt-4o-mini","messages":[{"role":"user","content":"ping"}]}'
   ```
3. Aponte qualquer cliente OpenAI para `https://LITELLM_FQDN` usando a `LITELLM_MASTER_KEY` como API key.

## Segurança
- A `LITELLM_MASTER_KEY` dá acesso total ao gateway — trate como segredo e prefira virtual keys
  (requer `DATABASE_URL`) para clientes individuais.
- Não mude a `LITELLM_SALT_KEY` após o primeiro uso com banco: credenciais já criptografadas
  ficarão ilegíveis.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | fora da `web` / DNS não aponta | conferir rede/labels e DNS de `LITELLM_FQDN` |
| Deploy falha por config inexistente | Docker config não criado | criar `litellm_config_*` antes do deploy |
| `401`/`Invalid API key` ao chamar | header sem a `LITELLM_MASTER_KEY` | enviar `Authorization: Bearer <LITELLM_MASTER_KEY>` |
| Modelo retorna erro de auth do provedor | env var da chave ausente/errada | conferir `OPENAI_API_KEY` (ou outra) e o `os.environ/<VAR>` no `config.yaml` |
| Mudança no `config.yaml` não aplica | Docker config é imutável | criar `_v2`, atualizar `LITELLM_CONFIG_NAME` e redeploy |
