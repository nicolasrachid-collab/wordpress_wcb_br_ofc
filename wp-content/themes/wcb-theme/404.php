<?php
/**
 * WCB Theme — 404 Page
 *
 * @package WCB_Theme
 */

get_header();
?>

<div class="wcb-container" style="text-align: center; padding: 5rem 1rem;">
    <div style="font-size: 6rem; font-weight: 900; color: var(--wcb-blue); line-height: 1; margin-bottom: 1rem;">404
    </div>
    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--wcb-gray-900); margin-bottom: 0.75rem;">Página não
        encontrada</h1>
    <p
        style="font-size: 0.95rem; color: var(--wcb-gray-500); margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
        A página que você está procurando pode ter sido movida ou não existe mais.
    </p>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="wcb-btn wcb-btn--primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        Voltar ao Início
    </a>
</div>

<?php
get_footer();
