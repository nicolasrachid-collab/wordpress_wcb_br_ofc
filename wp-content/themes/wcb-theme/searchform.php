<?php
/**
 * WCB Theme — Custom Search Form
 * Searches all WooCommerce products by title and description
 *
 * @package WCB_Theme
 */
?>
<form role="search" method="get" class="wcb-header__search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="search" class="wcb-header__search-input" id="wcb-search-input"
        placeholder="<?php echo esc_attr('Buscar produtos...'); ?>" value="<?php echo esc_attr(get_search_query()); ?>"
        name="s" autocomplete="off" aria-label="Buscar produtos">
    <input type="hidden" name="post_type" value="product">
    <button type="submit" class="wcb-header__search-btn" aria-label="Buscar">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>
    </button>
</form>