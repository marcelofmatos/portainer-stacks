# excalidraw — Excalidraw

**Excalidraw** é um quadro branco virtual para desenhos e diagramas à mão livre, com edição
colaborativa. Publicado via Traefik v3 com TLS. Usa a imagem oficial pré-construída
`excalidraw/excalidraw` (porta interna 80).

Serviço **único e stateless**: não tem volume, banco nem segredos. Os desenhos ficam no
navegador (localStorage) do usuário; salas colaborativas dependem do servidor de colaboração
público da Excalidraw (não incluso nesta stack).

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `EXCALIDRAW_FQDN` | sim | — | domínio público (ex.: `draw.exemplo.com`) |
| `EXCALIDRAW_IMAGE_TAG` | não | `latest` | tag da imagem `excalidraw/excalidraw` |
| `EXCALIDRAW_REPLICAS` | não | `1` | número de réplicas do serviço |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

## Pré-requisitos
- Docker Swarm inicializado.
- Traefik (stack `balancer`) e rede `web` ativos.
- DNS de `EXCALIDRAW_FQDN` apontando para o host (porta 80 acessível para o Let's Encrypt).

## Uso
1. Defina ao menos `EXCALIDRAW_FQDN` e faça o deploy (via Portainer App Template ou
   `docker stack deploy`):
   ```bash
   export EXCALIDRAW_FQDN=draw.exemplo.com
   docker stack deploy -c excalidraw/docker-compose.yml excalidraw
   ```
2. Acesse `https://EXCALIDRAW_FQDN`.

Por ser stateless, o serviço pode ser escalado livremente (`EXCALIDRAW_REPLICAS`) — cada réplica
serve a aplicação estática de forma independente.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | serviço fora da `web` / DNS não aponta | conferir rede/labels e DNS do `EXCALIDRAW_FQDN` |
| Certificado não emite | porta 80 inacessível ou DNS errado | liberar porta 80 e validar resolução do FQDN |
| Página em branco | tag de imagem inválida | conferir `EXCALIDRAW_IMAGE_TAG` |
| Sala colaborativa não conecta | depende do servidor público da Excalidraw | verificar conectividade externa do navegador |
