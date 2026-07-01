# onedrive — cliente de sincronização Microsoft OneDrive

Sincroniza uma pasta local (volume) com o **Microsoft OneDrive**, usando o cliente open source
[`abraunegg/onedrive`](https://github.com/abraunegg/onedrive) (imagem `driveone/onedrive`).

> **Não é serviço web** — é um **daemon de sincronização** em background (sem Traefik, sem porta).
> Por isso a stack não usa a rede `web` nem publica domínio.

## Componentes

| Volume | Caminho no container | Função |
|---|---|---|
| `onedrive-conf` | `/onedrive/conf` | token de acesso + estado (`items.sqlite3`) — **não apague** |
| `onedrive-data` | `/onedrive/data` | pasta local sincronizada com o OneDrive |

## Variáveis de ambiente

| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `ONEDRIVE_UID` | não | `1000` | UID dono dos arquivos (a imagem **exige** rodar como não-root) |
| `ONEDRIVE_GID` | não | `1000` | GID dono dos arquivos |
| `ONEDRIVE_VERBOSE` | não | `1` | verbosidade do log |
| `ONEDRIVE_IMAGE_TAG` | não | `latest` | tag da imagem `driveone/onedrive` |

Flags opcionais (descomente no compose): `ONEDRIVE_DOWNLOADONLY`, `ONEDRIVE_UPLOADONLY`,
`ONEDRIVE_RESYNC` — ver a [docs do projeto](https://github.com/abraunegg/onedrive/blob/master/docs/docker.md).

## Uso

### 1. Autenticar (uma vez, interativo — OAuth)
O cliente precisa ser autorizado **antes** de rodar em modo monitor. Faça o login OAuth uma vez,
gravando o token no volume de conf **da própria stack**:

```bash
# descubra o nome do volume de conf criado pela stack (prefixado pelo nome da stack):
docker volume ls | grep onedrive-conf         # ex.: onedrive_onedrive-conf

# rode o container interativamente apontando para ESSE volume:
docker run -it --rm -v <nome_do_volume_conf>:/onedrive/conf driveone/onedrive:latest
```
O cliente mostra uma **URL** — abra no navegador, faça login na conta Microsoft, e cole de volta a
**URL de resposta** (a que o navegador redireciona). O token fica salvo em `onedrive-conf`.

### 2. Subir a stack
Com o token gravado, suba a stack (App Template ou `docker-compose.yml`). O container passa a
**monitorar** `onedrive-data` e sincronizar com o OneDrive automaticamente.

### 3. Verificar
```bash
docker logs -f <container_onedrive>    # acompanhe a sincronização
```

## Swarm vs standalone

| Arquivo | Modo | App Template |
|---|---|---|
| `docker-compose.yml` | Docker **Swarm** | type 2 |
| `docker-compose.standalone.yml` | Docker **standalone** | type 3 (`restart` no lugar de `deploy`) |

Imagem, volumes, env e autenticação são idênticos nos dois.

## Troubleshooting

| Sintoma | Causa | Ação |
|---|---|---|
| `refresh_token: Permission denied` | UID/GID incorretos no volume conf | ajuste `ONEDRIVE_UID`/`ONEDRIVE_GID` para o dono real e recrie |
| Container reinicia sem sincronizar | não autenticado | faça a autenticação interativa (passo 1) no volume de conf |
| "auth URL" repetida no log | token ausente/expirado | reautentique (passo 1); em modo detach não há stdin para o fluxo interativo |
| Quer forçar reindexação | mudança grande/estado inconsistente | suba uma vez com `ONEDRIVE_RESYNC=1` e depois remova o flag |
| Migrar para outro host | token e estado ficam em `onedrive-conf` | copie os volumes `onedrive-conf` e `onedrive-data` |
