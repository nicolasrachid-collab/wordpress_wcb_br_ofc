# Go-live — `wp-config.php` e `WCB_DEV`

O ficheiro `wp-config.php` **não** deve ser versionado com segredos reais. Use este checklist no servidor de produção.

## Segurança e ambiente

1. **`WCB_DEV`:** definir explicitamente `false` **antes** de carregar o tema:
   ```php
   define( 'WCB_DEV', false );
   ```
   Efeito: não carrega `setup-demo-products`, import de imagens, `apply-images-logo` nem [wcb-dev-tools-admin.php](../wp-content/themes/wcb-theme/inc/wcb-dev-tools-admin.php).

2. **Debug:** `WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY` desligados em produção.

3. **Salts e keys:** valores únicos por ambiente; não reutilizar os exemplos de repositório.

4. **Opcional WordPress:** `DISALLOW_FILE_EDIT` como `true` para desativar editor de ficheiros no admin.

5. **Cache:** manter coerente com plugin/hosting (`WP_CACHE`, LiteSpeed, etc.).

## Ferramentas dev

- Confirmar que o menu **Ferramentas → WCB Dev** não aparece com `WCB_DEV` false.
- Não expor URLs de import/demo em documentação pública.

## Observabilidade

- Logs PHP/Woo apenas onde o hosting permitir e com rotação.
- Monitorizar `admin-ajax.php` (taxa 4xx/5xx) após deploy se houver endurecimento de nonces.

## Referência local

No XAMPP, `wp-config.php` pode manter `WCB_DEV` true para desenvolvimento; em produção deve ser false.
