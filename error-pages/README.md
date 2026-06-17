# error-pages — páginas de erro customizadas do Traefik

[`tarampampam/error-pages`](https://github.com/tarampampam/error-pages) servido via Traefik v3.
O propósito principal **não** é ser um site, e sim ser o **backend de um middleware Traefik
`errors`**: quando qualquer outra stack devolve um status 4xx/5xx, o Traefik intercepta a resposta
e serve no lugar uma página de erro bonita (template configurável). Opcionalmente, expõe um router
de preview com TLS para visualizar as páginas no navegador.

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `ERRORPAGES_TEMPLATE` | não | `ghost` | template das páginas (ex.: `ghost`, `l7-dark`, `l7-light`, `shuffle`, `noise`, `matrix`, `app-down`, `cats`, `lost-in-space`, `orient`, `connection`, `hacker-terminal`) |
| `ERRORPAGES_FQDN` | não | — | domínio público para preview (ex.: `errors.exemplo.com`); se omitido, não há router público útil |
| `ERRORPAGES_IMAGE_TAG` | não | `3` | tag da imagem `ghcr.io/tarampampam/error-pages` |
| `PROXY_NET` | não | `web` | rede externa do Traefik |

## Pré-requisitos
- **Hardware mínimo:** 0.25 vCPU · 64 MB RAM · 2 GB disco
- **Hardware ideal:** 0.5 vCPU · 128 MB RAM · 5 GB disco
- Traefik (stack `balancer`) e rede `web` ativos.
- Para usar o router de preview: DNS de `ERRORPAGES_FQDN` apontando para o host (porta 80 acessível
  para o desafio HTTP do Let's Encrypt).

## Uso

### 1. Subir esta stack
```bash
export ERRORPAGES_TEMPLATE=ghost            # opcional
export ERRORPAGES_FQDN=errors.exemplo.com   # opcional (apenas para o preview)
docker stack deploy -c error-pages/docker-compose.yml error-pages
```
A stack registra no Traefik:
- o **serviço** `error-pages` (porta interna 8080);
- o **middleware** `error-pages` do tipo `errors`, que captura status `400-599` e busca a página
  `/{status}.html` no serviço acima.

### 2. Aplicar o middleware nas OUTRAS stacks
Em qualquer outro serviço exposto pelo Traefik, basta referenciar o middleware pelo nome,
qualificado com o provider `@swarm`:

```yaml
deploy:
  labels:
    - traefik.enable=true
    - traefik.http.routers.<r>.rule=Host(`app.exemplo.com`)
    - traefik.http.routers.<r>.entrypoints=websecure
    - traefik.http.routers.<r>.tls=true
    - traefik.http.routers.<r>.tls.certresolver=letsencryptresolver
    - traefik.http.services.<r>.loadbalancer.server.port=<porta>
    # captura 4xx/5xx e serve a página customizada:
    - traefik.http.routers.<r>.middlewares=error-pages@swarm
```

A partir daí, quando `app.exemplo.com` responder, por exemplo, 404 ou 503, o usuário verá a página
do template configurado em vez da página de erro padrão do app.

### 3. Preview (opcional)
Se `ERRORPAGES_FQDN` estiver definido e o DNS apontando para o host, acesse
`https://ERRORPAGES_FQDN/404.html` (ou outro status) para visualizar o template escolhido.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| Outra stack continua mostrando o erro padrão | middleware não aplicado / nome errado | conferir `traefik.http.routers.<r>.middlewares=error-pages@swarm` no router do serviço |
| `middleware "error-pages@swarm" does not exist` | stack `error-pages` não está no ar ou ainda não foi descoberta pelo Traefik | subir/aguardar a stack `error-pages`; conferir que o serviço está na rede `web` |
| Página de erro genérica em vez do template | template inválido | usar um valor válido de `ERRORPAGES_TEMPLATE` (ver tabela) e redeployar |
| Preview dá 404/sem TLS | `ERRORPAGES_FQDN` não definido / DNS não aponta | definir `ERRORPAGES_FQDN` e conferir DNS apontando para o host |
| Middleware não captura alguns status | faixa `errors.status` não cobre o código | a faixa padrão é `400-599`; ajustar a label se necessário |
