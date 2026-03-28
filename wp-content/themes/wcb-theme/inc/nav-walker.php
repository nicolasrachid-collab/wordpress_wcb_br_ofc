<?php
/**
 * WCB Nav Walker — Mega Menu v3 (All-Columns Layout)
 *
 * Estrutura: Menu principal (depth 0) → Colunas (depth 1) com filhos (depth 2)
 *
 * Regras de subitens (depth 2):
 *   1) Ordem alfabética
 *   2) Sem quebra de linha (inline)
 *   3) Máximo 5 itens por linha (CSS grid)
 *   4) Máximo 15 subitens por coluna
 *
 * @package WCB_Theme
 */

if (!defined('ABSPATH')) exit;

class WCB_Nav_Walker extends Walker_Nav_Menu
{
    /** Controle interno por item depth=1 */
    private int    $d1_child_count  = 0;
    private int    $d1_item_id      = 0;
    private string $d1_item_url     = '#';
    private string $d1_item_title   = '';

    /** Buffer para coletar filhos depth=2 (para ordenação) */
    private array  $d2_buffer       = [];
    private bool   $d2_collecting   = false;
    private string $d1_target       = '';

    /* ══════════════════════════════════════════════
       ABRIR <li>
    ══════════════════════════════════════════════ */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes      = empty($item->classes) ? [] : (array) $item->classes;
        $has_children = in_array('menu-item-has-children', $classes, true);
        $title        = apply_filters('the_title', $item->title, $item->ID);
        $href         = !empty($item->url) ? $item->url : '#';
        $target       = !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';

        /* ── depth 0: item do menu principal ── */
        if ($depth === 0) {
            $li_classes = ['wcb-nav__item'];
            if ($has_children) {
                $li_classes[] = 'wcb-nav__item--mega';
            }
            $output .= '<li class="' . esc_attr(implode(' ', $li_classes)) . '" role="none">';

            $extra_link = [];
            foreach ($classes as $cls) {
                if (str_starts_with($cls, 'wcb-nav__link--')) {
                    $extra_link[] = $cls;
                }
            }
            $lc = array_merge(['wcb-nav__link'], $extra_link);
            if ($has_children) {
                $lc[] = 'wcb-nav__link--has-mega';
            }

            $icon    = $this->get_category_icon($title, $href, $classes);
            $chevron = $has_children
                ? '<svg class="wcb-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>'
                : '';

            $output .= sprintf(
                '<a href="%s" class="%s" role="menuitem"%s aria-haspopup="%s" aria-expanded="false">%s%s%s</a>',
                esc_url($href),
                esc_attr(implode(' ', $lc)),
                $target,
                $has_children ? 'true' : 'false',
                $icon,
                esc_html($title),
                $chevron
            );
            return;
        }

        /* ── depth 1: subcategoria = coluna no mega menu ── */
        if ($depth === 1) {
            $this->d1_item_id     = $item->ID;
            $this->d1_item_url    = $href;
            $this->d1_item_title  = $title;
            $this->d1_child_count = 0;
            $this->d2_buffer      = [];
            $this->d1_target      = $target;

            // Cada subcategoria é uma coluna (título será renderizado em end_lvl com badge)
            $output .= sprintf(
                '<div class="wcb-mega__col" data-group-id="%s">',
                esc_attr($item->ID)
            );
            return;
        }

        /* ── depth 2: filho da subcategoria (coleta no buffer) ── */
        if ($depth === 2) {
            $this->d2_buffer[] = [
                'title'  => $title,
                'href'   => $href,
                'target' => $target,
            ];
        }
    }

    /* ══════════════════════════════════════════════
       FECHAR </li>
    ══════════════════════════════════════════════ */
    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '</li>';
        }
        // depth 1 closing is handled in end_lvl
        // depth 2: não renderiza </li> aqui pois usamos buffer
    }

    /* ══════════════════════════════════════════════
       ABRIR <ul> do dropdown
    ══════════════════════════════════════════════ */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        /* depth 0 → 1: abre o painel mega com layout em colunas */
        if ($depth === 0) {
            $output .= '<div class="wcb-mega" role="menu" aria-hidden="true">';
            $output .= '<div class="wcb-mega__inner">';
            $output .= '<div class="wcb-mega__columns">';
            return;
        }

        /* depth 1 → 2: inicia coleta dos filhos */
        if ($depth === 1) {
            $this->d2_collecting = true;
            $this->d2_buffer     = [];
            return;
        }
    }

    /* ══════════════════════════════════════════════
       FECHAR </ul> do dropdown
    ══════════════════════════════════════════════ */
    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        /* depth 1: renderiza título + filhos ordenados + botão "ver todos" + fecha coluna */
        if ($depth === 1) {
            $this->d2_collecting = false;

            // 1) Ordenar alfabeticamente
            usort($this->d2_buffer, function ($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });

            // 2) Renderizar todos (ocultar extras via CSS se >15)
            $total_all = count($this->d2_buffer);
            $has_extras = $total_all > 15;

            // Título da coluna com badge de contagem
            $output .= sprintf(
                '<a href="%s" class="wcb-mega__col-title" role="menuitem"%s>%s<span class="wcb-mega__col-count">%d</span></a>',
                esc_url($this->d1_item_url),
                $this->d1_target,
                esc_html($this->d1_item_title),
                $total_all
            );

            // Renderizar a lista
            if ($total_all > 0) {
                $output .= '<ul class="wcb-mega__children" role="none">';
                foreach ($this->d2_buffer as $i => $child) {
                    $hidden_class = ($has_extras && $i >= 15) ? ' wcb-mega__child--hidden' : '';
                    $output .= '<li class="wcb-mega__child' . $hidden_class . '" role="none">';
                    $output .= sprintf(
                        '<a href="%s" class="wcb-mega__child-link" role="menuitem"%s>%s</a>',
                        esc_url($child['href']),
                        $child['target'],
                        esc_html($child['title'])
                    );
                    $output .= '</li>';
                }
                $output .= '</ul>';

                if ($has_extras) {
                    // Botão toggle para expandir/colapsar
                    $output .= sprintf(
                        '<button type="button" class="wcb-mega__see-all-btn wcb-mega__see-all-btn--toggle" data-expanded="false" data-href="%s">'
                        . '<span class="wcb-mega__see-all-text">Ver todos (+%d)</span>'
                        . '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>'
                        . '</button>',
                        esc_url($this->d1_item_url),
                        $total_all - 15
                    );
                } else {
                    // Link normal para a URL da categoria
                    $output .= sprintf(
                        '<a href="%s" class="wcb-mega__see-all-btn"><span>Ver todos</span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>',
                        esc_url($this->d1_item_url)
                    );
                }
            }

            $output .= '</div>'; // .wcb-mega__col

            // Reset
            $this->d2_buffer = [];
            return;
        }

        /* depth 0: fecha container de colunas + painel mega */
        if ($depth === 0) {
            $output .= '</div>'; // .wcb-mega__columns
            $output .= '</div>'; // .wcb-mega__inner
            $output .= '</div>'; // .wcb-mega
        }
    }

    /* ══════════════════════════════════════════════
       ÍCONE DE CATEGORIA
    ══════════════════════════════════════════════ */
    private function get_category_icon(string $title, string $href, array $classes): string
    {
        $is_promo = in_array('wcb-nav__link--promo', $classes, true);
        $t        = mb_strtolower($title);
        $a        = 'class="wcb-nav__icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"';

        if ($is_promo || str_contains($t, 'promo') || str_contains($t, 'oferta')) {
            return "<svg $a xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M13.5 0.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z\"/></svg>";
        }
        return '';
    }
}
