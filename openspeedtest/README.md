# openspeedtest — OpenSpeedTest

**OpenSpeedTest** publicado via Traefik v3 com TLS. Teste de velocidade de rede self-hosted
(HTML5, sem Flash/Java), útil para medir banda entre clientes e o servidor. Serviço único,
**stateless** (sem volume e sem banco).

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `OPENSPEEDTEST_FQDN` | sim | — | domínio público (ex.: `speedtest.exemplo.com`) |
| `OPENSPEEDTEST_IMAGE_TAG` | não | `latest` | tag da imagem `openspeedtest/latest` |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

## Pré-requisitos
- **Hardware mínimo:** 0.5 vCPU · 256 MB RAM · 2 GB disco
- **Hardware ideal:** 1 vCPU · 512 MB RAM · 5 GB disco
- Traefik (stack `balancer`) e rede `web` ativos.
- DNS de `OPENSPEEDTEST_FQDN` apontando para o host (porta 80 acessível para o desafio HTTP do
  Let's Encrypt).

## Uso
Acesse `https://OPENSPEEDTEST_FQDN` e clique em **Start** para medir download, upload e ping.
Para resultados fiéis, teste a partir de um cliente próximo (a medição reflete o caminho de rede
entre o navegador e o servidor).

Deploy manual (alternativa):
```bash
export OPENSPEEDTEST_FQDN=speedtest.exemplo.com
docker stack deploy -c openspeedtest/docker-compose.yml openspeedtest
```

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | serviço fora da `web` / DNS não aponta | conferir rede/labels e DNS |
| Certificado não emitido | porta 80 inacessível para o ACME | liberar/redirecionar 80 ao host do Traefik |
| Velocidade abaixo do esperado | gargalo no caminho de rede ou réplica em nó distante | testar de cliente próximo; conferir o nó onde a réplica subiu |
