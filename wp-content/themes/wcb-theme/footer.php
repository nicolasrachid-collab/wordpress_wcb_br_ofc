</main><!-- /.wcb-main -->

<?php
/**
 * WCB Theme — Footer Clean Premium v3
 * @package WCB_Theme
 */
?>

<?php
$wcb_nl4_wa   = get_theme_mod( 'wcb_nl4_whatsapp_url', 'https://chat.whatsapp.com/SEU-LINK-AQUI' );
$wcb_nl4_wa   = $wcb_nl4_wa ? $wcb_nl4_wa : 'https://chat.whatsapp.com/SEU-LINK-AQUI';
$wcb_privacy  = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
$wcb_nl4_nonce = wp_create_nonce( 'wcb_nl4' );
?>
<!-- ==================== NEWSLETTER PREMIUM ==================== -->
<section class="wcb-nl4" id="wcb-newsletter"
    data-whatsapp="<?php echo esc_url( $wcb_nl4_wa ); ?>"
    data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
    data-nonce="<?php echo esc_attr( $wcb_nl4_nonce ); ?>">
    <div class="wcb-nl4__glow"></div>
    <div class="wcb-container">
        <div class="wcb-nl4__inner">

            <!-- Lado esquerdo: texto -->
            <div class="wcb-nl4__left">
                <span class="wcb-nl4__badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Oferta exclusiva
                </span>
                <h2 class="wcb-nl4__title">Receba <span>5% OFF</span> na sua primeira compra</h2>
                <p class="wcb-nl4__sub">Registramos seu e-mail para enviar o cupom e novidades. Em seguida, entre no grupo exclusivo do WhatsApp com ofertas diárias.</p>
                <div class="wcb-nl4__proof">
                    <div class="wcb-nl4__avatars wcb-nl4__avatars--animated" aria-hidden="true">
                        <?php
                        /**
                         * Fotos de rosto (pravatar.cc — conjunto fixo para demo; troque por imagens locais em /images/ se preferir).
                         */
                        $wcb_nl4_avatar_ids = array( 12, 33, 47, 58 );
                        foreach ( $wcb_nl4_avatar_ids as $wcb_av_img ) {
                            $wcb_av_url = 'https://i.pravatar.cc/128?img=' . (int) $wcb_av_img;
                            echo '<img class="wcb-nl4__av" src="' . esc_url( $wcb_av_url ) . '" alt="" width="34" height="34" loading="lazy" decoding="async" />';
                        }
                        ?>
                    </div>
                    <span class="wcb-nl4__proof-text">+10.000 clientes já aproveitando</span>
                </div>
            </div>

            <!-- Lado direito: form -->
            <div class="wcb-nl4__right">
                <!-- Estado: form -->
                <form class="wcb-nl4__form" id="wcb-nl4-form" action="#" method="post" novalidate>
                    <div class="wcb-nl4__field-wrap">
                        <label class="wcb-nl4__label" for="wcb-nl4-email"><?php esc_html_e( 'E-mail', 'wcb-theme' ); ?></label>
                        <div class="wcb-nl4__field">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <input type="email" id="wcb-nl4-email" name="email" class="wcb-nl4__input" placeholder="<?php echo esc_attr__( 'seu@email.com', 'wcb-theme' ); ?>" required autocomplete="email" inputmode="email" aria-describedby="wcb-nl4-error wcb-nl4-privacy-hint" aria-invalid="false">
                        </div>
                    </div>
                    <p class="wcb-nl4__error" id="wcb-nl4-error" role="alert" aria-live="polite" hidden></p>
                    <button type="submit" class="wcb-nl4__btn" id="wcb-nl4-btn">
                        <span class="wcb-nl4__btn-text"><?php esc_html_e( 'Quero meu desconto', 'wcb-theme' ); ?></span>
                        <span class="wcb-nl4__btn-loading" aria-hidden="true"><?php esc_html_e( 'Enviando…', 'wcb-theme' ); ?></span>
                        <svg class="wcb-nl4__btn-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                    <p class="wcb-nl4__privacy" id="wcb-nl4-privacy-hint">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <?php
                        esc_html_e( 'Sem spam. Pode cancelar quando quiser.', 'wcb-theme' );
                        if ( $wcb_privacy ) :
                            echo ' ';
                            ?>
                        <a class="wcb-nl4__privacy-link" href="<?php echo esc_url( $wcb_privacy ); ?>"><?php esc_html_e( 'Política de privacidade', 'wcb-theme' ); ?></a>
                            <?php
                        endif;
                        ?>
                    </p>
                </form>

                <!-- Estado: sucesso -->
                <div class="wcb-nl4__success" id="wcb-nl4-success" style="display:none;" role="status" aria-live="polite">
                    <div class="wcb-nl4__success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="wcb-nl4__success-title"><?php esc_html_e( 'E-mail registrado!', 'wcb-theme' ); ?></p>
                    <p class="wcb-nl4__success-sub"><?php esc_html_e( 'Abra o WhatsApp pelo botão abaixo. Se nada abrir, desative o bloqueador de pop-ups para este site.', 'wcb-theme' ); ?></p>
                    <a class="wcb-nl4__wa-btn" id="wcb-nl4-wa-btn" href="<?php echo esc_url( $wcb_nl4_wa ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Abrir grupo no WhatsApp', 'wcb-theme' ); ?></a>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
(function () {
    var form    = document.getElementById('wcb-nl4-form');
    var success = document.getElementById('wcb-nl4-success');
    var section = document.getElementById('wcb-newsletter');
    var errEl   = document.getElementById('wcb-nl4-error');
    var btn     = document.getElementById('wcb-nl4-btn');
    var waBtn   = document.getElementById('wcb-nl4-wa-btn');
    if (!form || !section) return;

    var waLink = section.dataset.whatsapp || '';
    var ajaxUrl = section.dataset.ajax || '';
    var nonce = section.dataset.nonce || '';

    function setError(msg) {
        if (!errEl) return;
        if (msg) {
            errEl.textContent = msg;
            errEl.hidden = false;
        } else {
            errEl.textContent = '';
            errEl.hidden = true;
        }
    }

    function setLoading(loading) {
        if (!btn) return;
        btn.disabled = !!loading;
        btn.setAttribute('aria-busy', loading ? 'true' : 'false');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var emailInput = document.getElementById('wcb-nl4-email');
        if (!emailInput) return;

        setError('');
        emailInput.setAttribute('aria-invalid', 'false');

        var val = (emailInput.value || '').trim();
        if (!val) {
            setError('<?php echo esc_js( __( 'Informe seu e-mail.', 'wcb-theme' ) ); ?>');
            emailInput.setAttribute('aria-invalid', 'true');
            emailInput.focus();
            return;
        }
        if (!emailInput.checkValidity()) {
            setError('<?php echo esc_js( __( 'Digite um e-mail válido.', 'wcb-theme' ) ); ?>');
            emailInput.setAttribute('aria-invalid', 'true');
            emailInput.focus();
            return;
        }

        if (!ajaxUrl || !nonce) {
            setError('<?php echo esc_js( __( 'Formulário indisponível. Atualize a página.', 'wcb-theme' ) ); ?>');
            return;
        }

        setLoading(true);
        var body = new URLSearchParams();
        body.set('action', 'wcb_nl4_subscribe');
        body.set('nonce', nonce);
        body.set('email', val);

        fetch(ajaxUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                setLoading(false);
                if (!data || !data.success) {
                    var m = (data && data.data && data.data.message) ? data.data.message : '<?php echo esc_js( __( 'Não foi possível concluir. Tente de novo.', 'wcb-theme' ) ); ?>';
                    setError(m);
                    emailInput.setAttribute('aria-invalid', 'true');
                    return;
                }
                form.style.display = 'none';
                success.style.display = 'flex';
                if (waBtn && waLink) {
                    waBtn.href = waLink;
                }
                setTimeout(function () {
                    if (waLink) {
                        var w = window.open(waLink, '_blank', 'noopener,noreferrer');
                        if (!w) {
                            /* popup bloqueado — utilizador usa o botão */
                        }
                    }
                }, 400);
            })
            .catch(function () {
                setLoading(false);
                setError('<?php echo esc_js( __( 'Erro de rede. Tente de novo.', 'wcb-theme' ) ); ?>');
            });
    });
})();
</script>

<!-- ==================== FOOTER PRINCIPAL ==================== -->
<footer class="wcb-f3" id="wcb-footer">

    <div class="wcb-f3__body">
        <div class="wcb-container">
            <div class="wcb-f3__grid">

                <!-- Brand -->
                <div class="wcb-f3__brand">
                    <div class="wcb-f3__logo">
                        <?php wcb_get_logo(); ?>
                    </div>
                    <p class="wcb-f3__brand-desc"><?php esc_html_e( 'A escolha de quem busca qualidade e experiência em vape.', 'wcb-theme' ); ?></p>
                    <div class="wcb-f3__social">
                        <a class="wcb-f3__wa-cta"
                            href="https://api.whatsapp.com/send/?phone=595994872020&text&type=phone_number&app_absent=0"
                            target="_blank"
                            rel="noopener"
                            aria-label="<?php esc_attr_e( 'Acesse o grupo VIP de ofertas no WhatsApp', 'wcb-theme' ); ?>">
                            <span class="wcb-f3__wa-cta__icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                            </span>
                            <span class="wcb-f3__wa-cta__text">
                                <span class="wcb-f3__wa-cta__label"><?php esc_html_e( 'Acesse o grupo VIP de ofertas', 'wcb-theme' ); ?></span>
                                <span class="wcb-f3__wa-cta__sub"><?php esc_html_e( 'Cupons e promoções exclusivas no WhatsApp.', 'wcb-theme' ); ?></span>
                            </span>
                        </a>
                    </div>
                    <div class="wcb-f3__brand-stars" role="group" aria-label="<?php echo esc_attr__( 'Avaliação média 4,9 de 5 estrelas; mais de 800 avaliações de clientes', 'wcb-theme' ); ?>">
                        <div class="wcb-f3__brand-stars__row">
                            <span class="wcb-f3__brand-stars__visual" aria-hidden="true">
                                <span class="stars">★★★★★</span>
                            </span>
                            <span class="wcb-f3__brand-stars__score-wrap">
                                <strong class="wcb-f3__brand-stars__score">4,9</strong>
                                <span class="wcb-f3__brand-stars__outof"><?php esc_html_e( 'de 5', 'wcb-theme' ); ?></span>
                            </span>
                            <span class="wcb-f3__brand-stars__meta"><?php esc_html_e( '+800 avaliações de clientes', 'wcb-theme' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Coluna: Links -->
                <div class="wcb-f3__col">
                    <h4 class="wcb-f3__heading">Informações</h4>
                    <nav>
                        <a href="<?php echo esc_url(home_url('/sobre/')); ?>">Sobre Nós</a>
                        <a href="<?php echo esc_url(home_url('/politica-de-privacidade/')); ?>">Privacidade</a>
                        <a href="<?php echo esc_url(home_url('/termos-de-uso/')); ?>">Termos de Uso</a>
                        <a href="<?php echo esc_url(home_url('/trocas-e-devolucoes/')); ?>">Trocas e Devoluções</a>
                        <a href="https://api.whatsapp.com/send/?phone=595994872020&text&type=phone_number&app_absent=0" target="_blank" rel="noopener">Contato</a>
                    </nav>
                </div>

                <!-- Coluna: Conta -->
                <div class="wcb-f3__col">
                    <h4 class="wcb-f3__heading">Minha Conta</h4>
                    <nav>
                        <?php if (class_exists('WooCommerce')): ?>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>">Login / Conta</a>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">Meus Pedidos</a>
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>">Carrinho</a>
                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>">Finalizar Compra</a>
                        <a href="<?php echo esc_url(home_url('/rastrear-pedido/')); ?>">Rastrear Pedido</a>
                        <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/minha-conta/')); ?>">Login / Conta</a>
                        <a href="<?php echo esc_url(home_url('/minha-conta/orders/')); ?>">Meus Pedidos</a>
                        <a href="<?php echo esc_url(home_url('/carrinho/')); ?>">Carrinho</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Coluna: Pagamentos -->
                <div class="wcb-f3__col wcb-f3__col--pay">
                    <h4 class="wcb-f3__heading">Pagamento Seguro</h4>
                    <div class="wcb-f3__payments" role="list" aria-label="<?php esc_attr_e( 'Formas de pagamento aceitas', 'wcb-theme' ); ?>">
                        <span class="wcb-f3__pay" role="listitem"><?php esc_html_e( 'PIX', 'wcb-theme' ); ?></span>
                        <span class="wcb-f3__pay" role="listitem"><?php esc_html_e( 'Visa', 'wcb-theme' ); ?></span>
                        <span class="wcb-f3__pay" role="listitem"><?php esc_html_e( 'Mastercard', 'wcb-theme' ); ?></span>
                        <span class="wcb-f3__pay" role="listitem"><?php esc_html_e( 'Elo', 'wcb-theme' ); ?></span>
                        <span class="wcb-f3__pay" role="listitem"><?php esc_html_e( 'Boleto', 'wcb-theme' ); ?></span>
                    </div>
                    <div class="wcb-f3__trust">
                        <span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Compra 100% segura</span>
                        <span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Produtos originais</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Divider -->
    <div class="wcb-f3__divider"></div>

    <!-- Bottom Bar -->
    <div class="wcb-f3__bottom">
        <div class="wcb-container">
            <div class="wcb-f3__bottom-inner">
                <span class="wcb-f3__copy">&copy; <?php echo esc_html( (string) date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
            </div>
        </div>
    </div>

</footer>

<!-- Floating Cart Button (mobile) -->
<?php if (class_exists('WooCommerce')): ?>
    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="wcb-fab-cart" id="wcb-fab-cart" aria-label="Ver carrinho">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
            <line x1="3" y1="6" x2="21" y2="6" />
            <path d="M16 10a4 4 0 0 1-8 0" />
        </svg>
        <span class="wcb-fab-cart__count"><?php echo wcb_cart_count(); ?></span>
    </a>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function initQtyButtons() {
            const qtyInputs = document.querySelectorAll('div.quantity:not(.buttons_added):not(.wcb-pdp-qty-ready)');
            qtyInputs.forEach(function (qtyDiv) {
                qtyDiv.classList.add('buttons_added');
                const input = qtyDiv.querySelector('input.qty');
                if (!input) return;
                input.setAttribute('type', 'text');
                input.setAttribute('inputmode', 'numeric');
                const minusBtnEl = document.createElement('button');
                minusBtnEl.type = 'button';
                minusBtnEl.className = 'wcb-qty-btn wcb-minus';
                minusBtnEl.innerHTML = '−';
                const plusBtnEl = document.createElement('button');
                plusBtnEl.type = 'button';
                plusBtnEl.className = 'wcb-qty-btn wcb-plus';
                plusBtnEl.innerHTML = '+';
                qtyDiv.insertBefore(minusBtnEl, input);
                qtyDiv.appendChild(plusBtnEl);
                qtyDiv.addEventListener('click', function (e) {
                    if (e.target.closest('.wcb-qty-btn')) {
                        const btn = e.target.closest('.wcb-qty-btn');
                        let currentVal = parseFloat(input.value) || 0;
                        const min = parseFloat(input.min) || 0;
                        const max = parseFloat(input.max) || Infinity;
                        const step = parseFloat(input.step) || 1;
                        if (btn.classList.contains('wcb-plus')) { if (currentVal < max) input.value = currentVal + step; }
                        else if (btn.classList.contains('wcb-minus')) { if (currentVal > min) input.value = currentVal - step; }
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
        }
        initQtyButtons();
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('updated_cart_totals', function () { initQtyButtons(); });
        }
    });
</script>

<?php
$wcb_mbar_fav_count = 0;
if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
    $wcb_mbar_fav_list = get_user_meta( get_current_user_id(), '_wcb_wishlist', true );
    $wcb_mbar_fav_count = is_array( $wcb_mbar_fav_list ) ? count( $wcb_mbar_fav_list ) : 0;
}
$wcb_mbar_fav_url = esc_url( home_url( '/minha-conta/favoritos/' ) );
?>
<!-- ==================== MOBILE STICKY BOTTOM BAR (antes de wp_footer: scripts enxergam #wcb-mbar-menu) ==================== -->
<nav class="wcb-mobile-bottom-bar" id="wcb-mobile-bottom-bar" aria-label="Navegação mobile">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="wcb-mobile-bottom-bar__item" id="wcb-mbar-home">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Home</span>
    </a>
    <button class="wcb-mobile-bottom-bar__item" id="wcb-mbar-search" aria-label="Buscar">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Busca</span>
    </button>
    <a href="<?php echo $wcb_mbar_fav_url; ?>" class="wcb-mobile-bottom-bar__item" id="wcb-mbar-fav" aria-label="Favoritos">
        <span class="wcb-mobile-bottom-bar__icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span id="wcb-mbar-fav-count" class="wcb-mobile-bottom-bar__badge" <?php echo 0 === $wcb_mbar_fav_count ? 'style="display:none"' : ''; ?>><?php echo (int) $wcb_mbar_fav_count; ?></span>
        </span>
        <span>Favoritos</span>
    </a>
    <a href="#" id="wcb-mbar-cart" class="wcb-mobile-bottom-bar__item" aria-label="Carrinho">
        <span class="wcb-mobile-bottom-bar__icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span id="wcb-mbar-cart-count" class="wcb-mobile-bottom-bar__badge" style="display:none">0</span>
        </span>
        <span>Carrinho</span>
    </a>
    <a href="<?php echo esc_url( home_url( '/loja/' ) ); ?>" class="wcb-mobile-bottom-bar__item" id="wcb-mbar-menu" aria-label="<?php echo esc_attr__( 'Abrir menu', 'wcb-theme' ); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        <span><?php esc_html_e( 'Menu', 'wcb-theme' ); ?></span>
    </a>
</nav>

<script>
(function() {
    'use strict';
    function syncCartBadge() {
        var headerCount = document.querySelector('.wcb-header__cart-count, .wcb-fab-cart__count');
        var barBadge = document.getElementById('wcb-mbar-cart-count');
        if (!barBadge) return;
        if (headerCount) {
            var qty = parseInt(headerCount.textContent, 10) || 0;
            if (qty > 0) { barBadge.textContent = qty; barBadge.style.display = 'flex'; }
            else { barBadge.style.display = 'none'; }
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        syncCartBadge();
        var headerCount = document.querySelector('.wcb-header__cart-count, .wcb-fab-cart__count');
        if (headerCount && window.MutationObserver) {
            new MutationObserver(syncCartBadge).observe(headerCount, { childList: true, characterData: true, subtree: true });
        }
        var searchBtn = document.getElementById('wcb-mbar-search');
        if (searchBtn) { searchBtn.addEventListener('click', function() { var si = document.querySelector('.wcb-header__search-input, input[type="search"]'); if (si) { si.scrollIntoView({ behavior: 'smooth' }); si.focus(); } }); }
        var cartBtn = document.getElementById('wcb-mbar-cart');
        if (cartBtn) { cartBtn.addEventListener('click', function(e) { e.preventDefault(); var mb = document.getElementById('wcb-cart-toggle') || document.getElementById('wcb-mini-cart-trigger'); if (mb) mb.click(); else window.location.href = '<?php echo esc_url(wc_get_cart_url()); ?>'; }); }
    });
})();
</script>

<?php wp_footer(); ?>

<?php if ( class_exists('Alg_WC_Wish_List_Toggle_Btn') ): ?>
<script>
(function() {
    var WCB_TOAST_OPTS = { position:'bottomRight',layout:1,theme:'light',timeout:4000,backgroundColor:'#ffffff',progressBar:true,progressBarColor:'#2563eb',resetOnHover:true,drag:false,transitionIn:'fadeInUp',transitionOut:'fadeOut',class:'wcb-toast alg-wc-wl-izitoast',zindex:9999,onClose:function(s,t,c){if(typeof jQuery!=='undefined'){jQuery('body').trigger({type:'alg_wc_wl_notification_close',message:jQuery(t).find('p.slideIn')});}}};
    function applyWcbToast(){if(typeof iziToast!=='undefined'){iziToast.settings(WCB_TOAST_OPTS);}}
    document.addEventListener('DOMContentLoaded',applyWcbToast);
    window.addEventListener('load',applyWcbToast);
})();
</script>
<?php endif; ?>

</body>
</html>