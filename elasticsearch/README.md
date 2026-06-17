# elasticsearch — Elasticsearch (single-node)

**Elasticsearch** em modo single-node como backend **interno** de busca/índice. Não usa Traefik nem a
rede `web`: fica só na rede overlay `data` e as outras stacks conectam pelo host `elasticsearch:9200`.

## Arquitetura

```mermaid
flowchart LR
    app[app de outra stack] -->|9200 · data| es[(elasticsearch)]
```

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `ELASTIC_PASSWORD` | sim | — | senha do usuário `elastic` (segredo) |
| `ELASTIC_SECURITY` | não | `true` | habilita o xpack security (autenticação) |
| `ELASTIC_CLUSTER_NAME` | não | `portainer-stacks` | nome do cluster |
| `ELASTIC_JAVA_OPTS` | não | `-Xms512m -Xmx512m` | heap da JVM (ajuste à RAM do nó) |
| `ELASTIC_IMAGE_TAG` | não | `8.15.3` | tag da imagem oficial do Elasticsearch |
| `ELASTIC_PORT` | não | `9200` | porta publicada (só se descomentar o bloco `ports`) |
| `DATA_NET` | não | `data` | rede overlay dos serviços compartilhados |
| `WORKER_HOSTNAME` | não | — | fixa o volume num nó (cluster multi-worker) |

## Pré-requisitos
- **Hardware mínimo:** 2 vCPU · 2 GB RAM · 20 GB disco
- **Hardware ideal:** 2 vCPU · 4 GB RAM · 50 GB disco
- Rede `data`: `docker network create --driver overlay --attachable data`.
- **`vm.max_map_count`** no host (exigido pelo Elasticsearch):
  ```bash
  sudo sysctl -w vm.max_map_count=262144
  echo 'vm.max_map_count=262144' | sudo tee /etc/sysctl.d/99-elasticsearch.conf
  ```

## Uso
1. Ajuste `vm.max_map_count`, defina `ELASTIC_PASSWORD` e faça o deploy.
2. Outras stacks conectam em `http://elasticsearch:9200` com usuário `elastic` / `ELASTIC_PASSWORD`.
3. Teste de dentro da rede `data`:
   `curl -u elastic:<senha> http://elasticsearch:9200`.

## Segurança
- Mantenha o Elasticsearch **fora da `web`**. Para uma UI (ex.: Kibana) ou acesso externo, coloque-o
  atrás de autenticação (Traefik basicauth/authelia) — nunca exponha 9200 cru na internet.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| Container reinicia / `max virtual memory areas too low` | `vm.max_map_count` baixo | aplicar `sysctl -w vm.max_map_count=262144` |
| `bootstrap checks failed` (memory lock) | host sem permissão de memlock | manter `ES_JAVA_OPTS` modesto ou ajustar limites do host |
| 401 ao consultar | security ativo / senha errada | autenticar com `elastic:<ELASTIC_PASSWORD>` |
| Dados somem ao reagendar | volume local ao nó (multi-worker) | fixar `node.hostname` via `WORKER_HOSTNAME` |
