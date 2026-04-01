# White Cloud Brasil — WordPress / WooCommerce

Site e loja na pasta de instalação WordPress. O trabalho de produto e design system concentra-se no **tema child/custom** `wcb-theme`.

## Repositório remoto

- **GitHub:** [nicolasrachid-collab/white-cloud-brasil-wp](https://github.com/nicolasrachid-collab/white-cloud-brasil-wp)
- Branch principal: `main`

## O que versionamos

| Área | Caminho |
|------|---------|
| Tema WCB | `wp-content/themes/wcb-theme/` |
| Documentação interna | `docs/` |
| `.gitignore` na raiz | regras de exclusão partilhadas |

Versão de assets do tema: constante **`WCB_VERSION`** em `wp-content/themes/wcb-theme/functions.php`.

## Desenvolvimento local

- Exemplo XAMPP: `http://localhost/wcb/`
- Credenciais e URLs reais ficam em `wp-config.php` / `.env` — **não** versionados (ver `.gitignore`).

## O que não entra no Git (resumo)

- `wp-config.php`, `.env*`, uploads (`wp-content/uploads/`)
- `node_modules/`, `vendor/` do tema (Composer), caches de plugins
- LiteSpeed em `wp-content/litespeed/` e plugin `litespeed-cache` (ambiente local / cache)
- Ficheiros de backup e ruído: `*.bk`, `.htaccess.bk`, `products-output.txt`, `.sfdx/`

Detalhe completo em [`.gitignore`](.gitignore).

## Documentação

Ver [`docs/`](docs/) — inclui auditoria técnica, endpoints AJAX, testes e notas de produção.

## Design system (tema)

- Tokens CSS `--wcb-*` em `style.css`
- Botões: `.wcb-btn` + modificadores (`--primary`, `--secondary`, `--ghost`)
- Secções: `.wcb-section`, `__header`, `__headline`, `__actions`, `__content`
- Card de produto: apenas `template-parts/product-card.php` nas listagens

---

*Repositório privado de uso da equipa WCB.*
