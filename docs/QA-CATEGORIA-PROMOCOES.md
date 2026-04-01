# QA dirigido — categoria `promocoes`

Objetivo: validar o comportamento do `pre_get_posts` em [functions.php](../wp-content/themes/wcb-theme/functions.php) (slug `promocoes`).

## Comportamento implementado

1. **Até N IDs em promoção** (`wc_get_product_ids_on_sale()`, N = filtro `wcb_promocoes_post_in_max`, default **500**): query com `post__in` = IDs on sale; `tax_query` limpo; ordenação `date DESC`.
2. **Acima de N:** `meta_query` em `_sale_price != ''` (evita `post__in` gigante).

## Cenários a testar manualmente

| # | Cenário | Passos | Critério de sucesso |
|---|---------|--------|---------------------|
| 1 | Produto simples em promoção | Garantir produto simples com sale price; visitar `/product-category/promocoes/` | Aparece na listagem |
| 2 | Produto variável — promoção só na variação | Variável com desconto apenas numa variação (pai sem sale no post pai) | Confirmar se aparece ou não vs expectativa de negócio; `meta_query` no pai pode divergir de `wc_get_product_ids_on_sale()` |
| 3 | Paginação | Mais de 12 produtos em promoção | Páginas 2+ coerentes; sem duplicados óbvios |
| 4 | Ordenação | Usar dropdown de ordenação Woo (se visível) | Sem fatal errors; resultados razoáveis |
| 5 | Filtros combinados | Sidebar `wcb-filter` + categoria promoções | Resultados intersecionam de forma esperada |
| 6 | Consistência post__in vs meta_query | Ambiente de staging: forçar >500 on sale ou baixar `wcb_promocoes_post_in_max` para 1 com muitos produtos | Comparar contagem aproximada entre os dois modos; documentar diferenças em variações |
| 7 | Catálogo vazio em promoção | Nenhum produto on sale | Página não deve quebrar; pode mostrar vazio |

## Riscos conhecidos

- `meta_query` em `_sale_price` no **post** do produto pode não refletir promoções apenas em variações (comportamento WooCommerce).
- `meta_query` pode ser mais pesada que `post__in` em alguns índices — monitorizar TTFB em catálogos muito grandes.

## Registo

Preencher após o QA:

- Data: _______________
- Responsável: _______________
- Ambiente (URL): _______________
- Desvios encontrados: _______________
