# PHP — PHPCS + WordPress Coding Standards (tema `wcb-theme`)

## Requisitos

- PHP 7.4+ (alinhado ao ambiente WordPress)
- [Composer](https://getcomposer.org/) instalado globalmente

## Instalação (uma vez)

Na pasta do tema:

```bash
cd wp-content/themes/wcb-theme
composer install
```

O ficheiro `composer.json` do tema deve incluir (dev):

- `squizlabs/php_codesniffer`
- `wp-coding-standards/wpcs`
- `dealerdirect/phpcodesniffer-composer-installer` (regista o ruleset `WordPress-Core` no PHPCS)

## Executar o lint

```bash
cd wp-content/themes/wcb-theme
npm run lint:php
```

Ou diretamente (o projeto usa `memory_limit=512M` no script por causa do tamanho do tema):

```bash
php -d memory_limit=512M vendor/bin/phpcs --standard=phpcs.xml.dist
```

**PHP recomendado:** no Windows, se o `php` do PATH não tiver `openssl`/`zip`, use o executável do XAMPP, por exemplo:

`C:\xampp\php\php.exe -d memory_limit=512M vendor/bin/phpcs --standard=phpcs.xml.dist`

Ative `extension=zip` em `php.ini` do Composer para instalar dependências mais rápido (evita clone via Git).

## Validar que acusa erros reais

Após `composer install`, introduzir temporariamente uma violação óbvia (ex.: `eval(1);` num ficheiro PHP do tema) e confirmar que `npm run lint:php` falha com código de saída ≠ 0.

## Nota

O ruleset atual ([phpcs.xml.dist](../wp-content/themes/wcb-theme/phpcs.xml.dist)) referencia `WordPress-Core`. O projeto completo WordPress (core + plugins) é grande; o scan está limitado ao diretório do tema via `<file>.</file>` ao correr o comando **dentro** de `wcb-theme`.
