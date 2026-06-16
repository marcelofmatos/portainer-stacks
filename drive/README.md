# drive — ownCloud

Servidor de arquivos **ownCloud** (server 10.x) com MySQL e Redis, publicado via Traefik v3 com
TLS Let's Encrypt. Pode autenticar usuários de um diretório LDAP (ver stack `account`).

## Componentes
| Serviço | Imagem | Função |
|---|---|---|
| `www` | `owncloud/server` | aplicação web (porta interna 8080) |
| `db` | `mysql:5.7` | banco de dados |
| `redis` | `webhippie/redis` | cache/locking |

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `DRIVE_FQDN` | sim | — | domínio público (ex.: `drive.exemplo.com`) |
| `OWNCLOUD_ADMIN_PASSWORD` | sim | — | senha do admin inicial |
| `OWNCLOUD_DB_PASSWORD` | sim | — | senha do usuário do banco |
| `MYSQL_ROOT_PASSWORD` | sim | — | senha root do MySQL |
| `OWNCLOUD_ADMIN_USERNAME` | não | `admin` | login do admin |
| `OWNCLOUD_DB_NAME` | não | `owncloud` | nome do banco |
| `OWNCLOUD_DB_USERNAME` | não | `owncloud` | usuário do banco |
| `OWNCLOUD_DEFAULT_LANGUAGE` | não | `pt_BR` | idioma padrão |
| `OWNCLOUD_MAX_UPLOAD` | não | `100G` | limite de upload |
| `OWNCLOUD_IMAGE_TAG` | não | `10.15.0` | tag da imagem ownCloud |
| `MYSQL_IMAGE_TAG` | não | `5.7` | tag da imagem MySQL |
| `REDIS_IMAGE_TAG` | não | `latest` | tag da imagem Redis |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `LDAP_NET` | não | `ldap` | rede externa compartilhada com a stack `account` |
| `WORKER_HOSTNAME` | não | — | hostname do worker para fixar volumes (multi-worker) |

## Pré-requisitos
- Rede externa do proxy (`web`) e o Traefik (stack `balancer`) já em execução.
- DNS de `DRIVE_FQDN` apontando para o host público (o Let's Encrypt usa httpchallenge na porta 80).
- Para autenticação LDAP: rede externa `ldap` e a stack `account` no ar.

## Uso
1. Deploy da stack (Portainer ou `docker stack deploy`).
2. Acesse `https://DRIVE_FQDN` e entre com o admin.
3. (Opcional) Integração LDAP — habilitar e configurar o app `user_ldap` (ver seção abaixo).

## Integração LDAP (opcional)
Com a stack `account` ativa e ambos os serviços na rede `ldap`, o ownCloud alcança o LDAP pelo
nome de serviço `account_ldap:389`. Configure o app `user_ldap` (via UI em
*Configurações > Administração > Autenticação do usuário* ou via `occ`):

```
occ app:enable user_ldap
occ ldap:create-empty-config
occ ldap:set-config s01 ldapHost account_ldap
occ ldap:set-config s01 ldapPort 389
occ ldap:set-config s01 ldapBase "dc=exemplo,dc=com"
occ ldap:set-config s01 ldapBaseUsers "ou=People,dc=exemplo,dc=com"
occ ldap:set-config s01 ldapAgentName "cn=admin,dc=exemplo,dc=com"
occ ldap:set-config s01 ldapAgentPassword "<senha-admin-ldap>"
occ ldap:set-config s01 ldapLoginFilter "(&(objectclass=inetOrgPerson)(uid=%uid))"
occ ldap:set-config s01 ldapUserFilter "(objectclass=inetOrgPerson)"
occ ldap:set-config s01 ldapUserDisplayName cn
occ ldap:set-config s01 ldapExpertUsernameAttr uid
occ ldap:set-config s01 ldapConfigurationActive 1
occ ldap:test-config s01
occ user:sync 'OCA\User_LDAP\User_Proxy' --missing-account-action disable   # pré-provisiona
```
Usuários do LDAP passam a logar no drive (conta criada no 1º login).

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| 404 do Traefik | serviço fora da rede `web` ou sem `traefik.enable` | conferir rede/labels |
| Certificado TLS não emite | DNS não aponta pro host / porta 80 bloqueada | `getent hosts DRIVE_FQDN`; httpchallenge usa `:80` |
| `occ` falha com `appconfig doesn't exist` | `occ` por `docker exec` não herda env do entrypoint (prefixo de tabela) | passar no exec: `OWNCLOUD_DB_PREFIX=oc_`, `OWNCLOUD_VOLUME_FILES=/mnt/data`, e as vars de DB |
| `occ`: data dir inválido / falta `.ocdata` | idem acima | passar `OWNCLOUD_VOLUME_FILES=/mnt/data` e `touch /mnt/data/.ocdata` |
| `status.php` diz `installed:true` mas app falha | `status.php` lê só o `config.php`, não o banco | validar com `occ status` |
| Dados "somem" ao reagendar | volume não fixado num nó (multi-worker) | definir `WORKER_HOSTNAME` e fixar o `node.hostname` |
| Login LDAP falha | `account_ldap` não resolve / `ldap:test-config` inválido | conferir rede `ldap` em ambos os serviços |

> `occ` deve rodar como usuário `www-data`, em `/var/www/owncloud`.
