<?php // self-service-password — template (config.inc.local.php)
// Conteúdo do Docker config externo `account_ssp_config_v1`.
// Substitua os <placeholders> pelos seus valores e ajuste o domínio LDAP.
// NÃO versione este arquivo com segredos reais — crie o Docker config diretamente no Swarm.
$keyphrase = "<KEYPHRASE_ALEATORIA>";
$debug = false;
$ldap_url = "ldap://ldap:389";
$ldap_binddn = "cn=admin,dc=example,dc=com";
$ldap_bindpw = "<LDAP_ADMIN_PASSWORD>";
$who_change_password = "user";        // usuário autentica com a senha atual e troca a própria
$ldap_base = "ou=People,dc=example,dc=com";
$ldap_scope = "sub";
$shadow_options['update_shadowLastChange'] = true;
$pwd_min_length = 6;
$pwd_min_lower = 1;
$pwd_min_upper = 1;
$pwd_min_digit = 1;
$pwd_min_special = 1;
$use_questions = false;
$use_tokens = false;
$use_sms = false;
$hash = 'auto';
$custom_css = "css/custom.css";
?>
