# Testes automáticos mínimos — WCB

Stack leve: **PHP** (asserts sem framework, sem WordPress) + **Node 18+** (`fetch`) contra o site em execução. Sem Playwright, Docker nem base de dados de testes.

## O que está coberto

| Área | Ficheiro / comando | Conteúdo |
|------|-------------------|----------|
| Helpers puros | `npm run test:php` | `wcb_normalize_cep_digits`, `wcb_promocoes_should_use_post_in` |
| HTTP / AJAX | `npm run test:http` | `wcb_get_wishlist`, `wcb_mini_cart`, `wcb_save_abandoned_cart`, `wcb_toggle_wishlist` (visitante), `wcb_live_search` (nonce inválido); smoke de `/`, loja, carrinho, PDP se detetável |
| PHPUnit (opcional) | `npm run test:phpunit` | `tests/php/PureHelpersTest.php` — requer `composer require --dev phpunit/phpunit` (normalmente **ext-zip** no PHP) |

## Pré-requisitos

- PHP na CLI (ex.: `C:\xampp\php\php.exe` no PATH como `php`).
- Node **>= 18**.
- Apache/MySQL a servir o site (ex.: `http://localhost/wcb/`) para `test:http`.

## Comandos

Na pasta do tema:

```bash
cd wp-content/themes/wcb-theme
npm run test:php
npm run test:http
npm test
```

Variáveis úteis para HTTP:

| Variável | Efeito |
|----------|--------|
| `WCB_BASE_URL` | URL base (default `http://localhost/wcb`) |
| `WCB_TEST_PRODUCT_URL` | PDP fixa se a loja não tiver links de produto no HTML |
| `WCB_TEST_AUTH_COOKIE` | Cookie de sessão WordPress (logado) |
| `WCB_TEST_PRODUCT_ID` | ID de produto para teste opcional de `wcb_toggle_wishlist` com cookie |
| `WCB_FETCH_TIMEOUT_MS` | Timeout por pedido (default `15000`) |

## Limitações

- **Nonces válidos** só aparecem no HTML se a página carregar o tema com `wcb-main` / `wcbWishlist` (p.ex. loja com Woo ativo). Páginas “em breve” ou sem scripts do tema fazem **SKIP** nos testes que precisam de nonce — os testes **sem nonce** (rejeição) continuam a correr.
- **PHPUnit** não está em `require-dev` por defeito (instalação via Composer falha em muitos XAMPP sem `extension=zip`). O runner `tests/php/run-pure-tests.php` cobre a mesma lógica.
- **Smoke** de categoria `promocoes` e carrinho: **404** é aceite com aviso (slugs diferentes por idioma/ambiente).
- **Carga / rate limit**: muitas execuções seguidas podem atingir transientes de rate limit nos AJAX.

## Próximos passos sugeridos

- Ativar `extension=zip` no `php.ini` e `composer require --dev phpunit/phpunit` para usar `test:phpunit`.
- Em CI: subir WordPress, import mínimo de produtos, correr `npm test` com `WCB_BASE_URL` apontando para o container.
- Acrescentar um único teste HTTP para `wcb_quick_view` (nonce inválido), espelhando `wcb_live_search`.
