# account — OpenLDAP + LAM + self-service-password

Diretório de identidades **OpenLDAP** com duas interfaces web:
- **LAM** (LDAP Account Manager) — administração de usuários/grupos;
- **self-service-password** (LTB) — troca de senha pelo próprio usuário.

Expõe uma rede externa `ldap` para que outras stacks (ex.: `drive`/ownCloud) autentiquem no diretório.

## Componentes
| Serviço | Imagem | URL | Função |
|---|---|---|---|
| `manager` | `ghcr.io/ldapaccountmanager/lam` | `LAM_FQDN` | admin de usuários LDAP |
| `ldap` | `osixia/openldap` | interno (`account_ldap:389`) | diretório |
| `user` | `ltbproject/self-service-password` | `SSP_FQDN` | troca de senha |

## Variáveis de ambiente
| Variável | Obrigatória | Default | Descrição |
|---|---|---|---|
| `LAM_FQDN` | sim | — | domínio do LAM (ex.: `account.exemplo.com`) |
| `SSP_FQDN` | sim | — | domínio do self-service-password (ex.: `senha.exemplo.com`) |
| `LDAP_DOMAIN` | sim | `example.com` | domínio LDAP (vira `dc=...`) |
| `LDAP_ORGANISATION` | sim | `Example Org` | nome da organização |
| `LDAP_ADMIN_PASSWORD` | sim | — | senha do `cn=admin,dc=...` |
| `SSP_CONFIG_NAME` | não | `account_ssp_config_v1` | nome do Docker config do SSP |
| `SSP_CSS_NAME` | não | `account_ssp_css_custom_v1` | nome do Docker config do CSS |
| `SSP_LANGUAGE` | não | `pt_BR` | idioma do SSP |
| `LAM_IMAGE_TAG` / `OPENLDAP_IMAGE_TAG` / `SSP_IMAGE_TAG` | não | `9.5` / `latest` / `latest` | tags das imagens |
| `PROXY_NET` | não | `web` | rede externa do Traefik |
| `LDAP_NET` | não | `ldap` | rede externa compartilhada |
| `WORKER_HOSTNAME` | não | — | hostname do worker para fixar volumes (multi-worker) |

## Pré-requisito: Docker configs externos do self-service-password
O serviço `user` espera 2 Docker configs já existentes no Swarm. Crie-os a partir dos arquivos em
[`config/`](config/) (ajuste domínio/segredos antes):

```bash
# 1) edite config/ssp-config.inc.local.php (ldap_base, bind, keyphrase) e config/ssp-custom.css
docker config create account_ssp_config_v1     config/ssp-config.inc.local.php
docker config create account_ssp_css_custom_v1 config/ssp-custom.css
```
> Docker config é **imutável**. Para alterar depois, crie uma nova versão
> (`account_ssp_config_v2`), aponte `SSP_CONFIG_NAME` para ela e atualize a stack.

O `config.inc.local.php` precisa de (mínimo):
- `$ldap_url = "ldap://ldap:389";`
- `$ldap_binddn` / `$ldap_bindpw` (admin do LDAP)
- `$ldap_base = "ou=People,dc=<seu-dominio>";`  ← **base onde estão os usuários**
- `$who_change_password = "user";`

## Uso
1. Crie os Docker configs (acima) e faça o deploy.
2. **LAM**: acesse `https://LAM_FQDN`, configure o perfil apontando para `ldap://ldap:389`,
   base `dc=...`, admin `cn=admin,dc=...`. Crie a OU de usuários (ex.: `ou=People`) e os usuários.
3. **Troca de senha**: usuários acessam `https://SSP_FQDN`, informam a senha atual e a nova.

## Troubleshooting
| Sintoma | Causa | Ação |
|---|---|---|
| Deploy falha por config inexistente | Docker configs não criados | criar `account_ssp_config_*` e `account_ssp_css_custom_*` |
| Troca de senha "usuário não encontrado" | `$ldap_base` aponta para OU errada | usar a OU real dos usuários (ex.: `ou=People`) |
| 404/sem TLS | fora da rede `web` / DNS não aponta | conferir rede/labels e DNS dos FQDNs |
| Outra stack não acha o LDAP | não está na rede `ldap` | anexar o serviço à rede externa `ldap`; host = `account_ldap` |
| Mudança no SSP não aplica | Docker config é imutável | criar `_v2` e atualizar `SSP_CONFIG_NAME` + redeploy |
