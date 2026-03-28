<?php
/**
 * Template Name: Rastrear Pedido
 * WCB Theme — Página de rastreamento de pedido (v3 — UX refinado)
 *
 * @package WCB_Theme
 */

get_header();
?>

<div class="wcb-track-v2">

    <!-- ── HERO ─────────────────────────────────────────────── -->
    <div class="wcb-track-v2__hero">
        <div class="wcb-track-v2__hero-inner wcb-container">

            <nav class="wcb-track-v2__breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Início
                </a>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                <span>Rastrear Pedido</span>
            </nav>

            <div class="wcb-track-v2__hero-title">
                <div class="wcb-track-v2__hero-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div>
                    <h1>Rastrear Pedido</h1>
                    <p>Acompanhe seu pedido em tempo real</p>
                </div>
            </div>

        </div>
    </div>

    <!-- ── BODY ─────────────────────────────────────────────── -->
    <div class="wcb-track-v2__body wcb-container">

        <!-- LEFT: Main Tracking Card -->
        <main class="wcb-track-v2__main">
            <div class="wcb-track-v2__card wcb-track-v2__card--main">

                <div class="wcb-track-v2__card-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Consultar Status do Pedido
                </div>

                <div class="wcb-track-v2__card-body">
                    <p class="wcb-track-v2__hint">
                        Informe o <strong>ID do pedido</strong> e o <strong>e-mail de cobrança</strong>. Você encontra essas informações no e-mail de confirmação que recebeu.
                    </p>

                    <?php if (class_exists('WooCommerce')): ?>
                        <div class="wcb-track-v2__form-wrap">
                            <?php echo do_shortcode('[woocommerce_order_tracking]'); ?>
                        </div>
                    <?php else: ?>
                        <p class="wcb-track-v2__error">WooCommerce não está ativo.</p>
                    <?php endif; ?>
                </div>

            </div>
        </main>

        <!-- RIGHT: Help Sidebar -->
        <aside class="wcb-track-v2__sidebar">

            <!-- Where to find Order ID -->
            <div class="wcb-track-v2__card">
                <div class="wcb-track-v2__card-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Onde encontro meu ID?
                </div>
                <div class="wcb-track-v2__card-body">
                    <ul class="wcb-track-v2__help-list">
                        <li>
                            <span class="wcb-track-v2__help-icon wcb-track-v2__help-icon--blue">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <div>
                                <strong>E-mail de confirmação</strong>
                                <span>Enviado logo após a compra</span>
                            </div>
                        </li>
                        <li>
                            <span class="wcb-track-v2__help-icon wcb-track-v2__help-icon--purple">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <div>
                                <strong><a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">Meus Pedidos</a></strong>
                                <span>Na área da sua conta</span>
                            </div>
                        </li>
                        <li>
                            <span class="wcb-track-v2__help-icon wcb-track-v2__help-icon--green">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </span>
                            <div>
                                <strong>Nota fiscal</strong>
                                <span>Ou comprovante de compra</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Delivery Times -->
            <div class="wcb-track-v2__card">
                <div class="wcb-track-v2__card-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Prazo estimado de entrega
                </div>
                <div class="wcb-track-v2__card-body">
                    <ul class="wcb-track-v2__delivery-list">
                        <li>
                            <span class="wcb-track-v2__badge wcb-track-v2__badge--green">Capitais</span>
                            <span>2–5 dias úteis</span>
                        </li>
                        <li>
                            <span class="wcb-track-v2__badge wcb-track-v2__badge--blue">Interior</span>
                            <span>5–10 dias úteis</span>
                        </li>
                        <li>
                            <span class="wcb-track-v2__badge wcb-track-v2__badge--orange">Zonas remotas</span>
                            <span>10–15 dias úteis</span>
                        </li>
                    </ul>
                    <p class="wcb-track-v2__delivery-note">Prazos contados após confirmação de pagamento.</p>
                </div>
            </div>

            <!-- Support -->
            <div class="wcb-track-v2__card wcb-track-v2__card--support">
                <div class="wcb-track-v2__support-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                </div>
                <h3>Precisa de ajuda?</h3>
                <p>Nossa equipe está pronta para resolver qualquer dúvida sobre seu pedido.</p>
                <a href="<?php echo esc_url(home_url('/central-de-ajuda/')); ?>" class="wcb-track-v2__support-btn">
                    Falar com suporte
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>

        </aside>

    </div><!-- /.wcb-track-v2__body -->
</div><!-- /.wcb-track-v2 -->

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        // Autofocus first field
        var firstInput = document.querySelector('.wcb-track-v2__form-wrap input');
        if (firstInput) { firstInput.focus(); firstInput.select(); }

        // Hide WooCommerce's native paragraph (duplicates our hint)
        var wcHint = document.querySelector('.woocommerce-form-track-order > p:first-child');
        if (wcHint) wcHint.style.display = 'none';

        // Make form fields 2-column
        var form = document.querySelector('.woocommerce-form-track-order');
        if (!form) return;
        var ps = form.querySelectorAll('p.form-row');
        if (ps.length >= 2) {
            var wrapper = document.createElement('div');
            wrapper.className = 'wcb-track-v2__fields-row';
            ps[0].parentNode.insertBefore(wrapper, ps[0]);
            wrapper.appendChild(ps[0]);
            wrapper.appendChild(ps[1]);
        }

        // Add placeholder text
        var orderInput = document.querySelector('[name="orderid"]');
        if (orderInput) orderInput.placeholder = 'Ex: 1234';
        var emailInput = document.querySelector('[name="order_email"]');
        if (emailInput) emailInput.placeholder = 'Ex: seu@email.com';

        // Add search icon to button
        var btn = document.querySelector('.woocommerce-form-track-order button[type="submit"]');
        if (btn) {
            btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> ' + btn.textContent.trim();
        }

        // Inline validation
        form.addEventListener('submit', function () {
            form.querySelectorAll('input').forEach(function (inp) {
                if (!inp.value.trim()) inp.classList.add('wcb-track-v2__input--error');
                else inp.classList.remove('wcb-track-v2__input--error');
            });
        });
        form.querySelectorAll('input').forEach(function (inp) {
            inp.addEventListener('input', function () { inp.classList.remove('wcb-track-v2__input--error'); });
        });
    });
})();
</script>

<?php get_footer(); ?>
