# tools — phpMyAdmin

**phpMyAdmin** publicado via Traefik v3 com TLS. `PMA_ARBITRARY=1` permite informar o host do
banco na própria tela de login (útil para administrar vários MySQL/MariaDB do cluster).

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `PMA_FQDN` | sim | — | domínio público (ex.: `pma.exemplo.com`) |
| `PMA_UPLOAD_LIMIT` | não | `256M` | limite de upload de import |
| `PMA_IMAGE_TAG` | não | `latest` | tag da imagem phpMyAdmin |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

## Pré-requisitos
- Traefik (stack `balancer`) e rede `web` ativos.
- DNS de `PMA_FQDN` apontando para o host.
- O serviço precisa enxergar o(s) banco(s): para acessar bancos em outras stacks, anexe também a
  rede onde eles estão (ajuste a seção `networks` do serviço) ou use um host alcançável na `web`.

## Uso
Acesse `https://PMA_FQDN`, informe **servidor** (host do banco, ex.: `db` ou `<stack>_db`),
usuário e senha.

## Segurança
- Exponha apenas se necessário; considere proteger com middleware de autenticação no Traefik
  (basicauth) ou restrição de IP.
- `PMA_ARBITRARY=1` permite conectar a qualquer host alcançável pelo container — avalie o risco.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404/sem TLS | fora da `web` / DNS não aponta | conferir rede/labels e DNS |
| "mysqli::real_connect: No such host" | host do banco não resolve pela rede do container | anexar a rede do banco ao serviço `pma` |
| Import falha por tamanho | limite baixo | aumentar `PMA_UPLOAD_LIMIT` |
