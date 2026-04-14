<?php
/**
 * WCB Theme — Custom Search Form
 * Searches all WooCommerce products by title and description
 *
 * @package WCB_Theme
 */
?>
<form role="search" method="get" class="wcb-header__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>"
    aria-label="<?php echo esc_attr__( 'Buscar no site', 'wcb-theme' ); ?>">
    <label class="screen-reader-text" for="wcb-search-input"><?php echo esc_html__( 'Buscar produtos', 'wcb-theme' ); ?></label>
    <input type="search" class="wcb-header__search-input" id="wcb-search-input"
        placeholder="<?php echo esc_attr__( 'Buscar produtos...', 'wcb-theme' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>"
        name="s" autocomplete="off" aria-label="<?php echo esc_attr__( 'Buscar produtos', 'wcb-theme' ); ?>">
    <input type="hidden" name="post_type" value="product">
    <button type="submit" class="wcb-header__search-btn" aria-label="<?php echo esc_attr__( 'Buscar', 'wcb-theme' ); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>
    </button>
</form>