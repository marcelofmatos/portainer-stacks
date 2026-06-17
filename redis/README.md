# redis — cache compartilhado

**Redis** como cache/armazenamento em memória compartilhado entre stacks. Sem proxy (não é HTTP):
entra na rede overlay `data` e é alcançado por outras stacks pelo host `redis:6379`.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `REDIS_PASSWORD` | recomendada | _(vazio)_ | senha (AUTH). Vazio = sem autenticação |
| `REDIS_MAXMEMORY` | não | `256mb` | limite de memória |
| `REDIS_MAXMEMORY_POLICY` | não | `allkeys-lru` | política de evicção (cache: `allkeys-lru`) |
| `REDIS_APPENDONLY` | não | `yes` | persistência AOF (`no` para cache puro) |
| `REDIS_IMAGE_TAG` | não | `7-alpine` | tag da imagem Redis |
| `DATA_NET` | não | `data` | rede overlay externa compartilhada |
| `REDIS_PORT` | não | `6379` | porta no nó (só se habilitar o bloco `ports`) |
| `WORKER_HOSTNAME` | não | — | fixa o volume num nó (cluster multi-worker) |

## Pré-requisitos
- Rede `data` criada: `docker network create --driver overlay --attachable data`.

## Uso
Outras stacks anexam a rede `data` e conectam em `redis:6379`.

- **URI (com senha):** `redis://default:<REDIS_PASSWORD>@redis:6379/0`
- **URI (sem senha):** `redis://redis:6379/0`
- Use bancos lógicos distintos (`/0`, `/1`, ...) por aplicação para isolar chaves.

Teste rápido (de outro container na rede `data`):
```bash
redis-cli -h redis -a "<REDIS_PASSWORD>" ping   # -> PONG
```

## Segurança
- Defina `REDIS_PASSWORD` sempre que possível. Sem senha, qualquer serviço na rede `data` acessa o cache.
- Só habilite o bloco `ports` (acesso externo) se realmente necessário — e com senha forte.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| `NOAUTH Authentication required` | servidor com senha, cliente sem | passar a senha na URI/cliente |
| `Could not connect to redis` | rede `data` ausente ou serviço fora dela | criar a rede `data` e anexar os consumidores |
| Cache "perde" dados ao reagendar | volume local ao nó (multi-worker) | fixar `node.hostname` via `WORKER_HOSTNAME` |
| Memória estourando | `REDIS_MAXMEMORY` baixo / política inadequada | ajustar `REDIS_MAXMEMORY` e `REDIS_MAXMEMORY_POLICY` |
