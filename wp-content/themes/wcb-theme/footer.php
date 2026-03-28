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
                    <div class="wcb-nl4__avatars" aria-hidden="true">
                        <span class="wcb-nl4__av" style="background-image:url(<?php echo get_template_directory_uri(); ?>/images/nl-avatars.png); background-size: 400% auto; background-position: 0% center;"></span>
                        <span class="wcb-nl4__av" style="background-image:url(<?php echo get_template_directory_uri(); ?>/images/nl-avatars.png); background-size: 400% auto; background-position: 33.33% center;"></span>
                        <span class="wcb-nl4__av" style="background-image:url(<?php echo get_template_directory_uri(); ?>/images/nl-avatars.png); background-size: 400% auto; background-position: 66.66% center;"></span>
                        <span class="wcb-nl4__av" style="background-image:url(<?php echo get_template_directory_uri(); ?>/images/nl-avatars.png); background-size: 400% auto; background-position: 100% center;"></span>
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
                    <p class="wcb-f3__brand-desc"><?php esc_html_e( 'Sua loja premium de produtos para vape.', 'wcb-theme' ); ?></p>
                    <div class="wcb-f3__brand-stars" role="group" aria-label="<?php echo esc_attr__( 'Avaliação média 4,9 de 5 estrelas; mais de 800 avaliações de clientes', 'wcb-theme' ); ?>">
                        <div class="wcb-f3__brand-stars__row">
                            <span class="wcb-f3__brand-stars__visual" aria-hidden="true">
                                <span class="stars">★★★★★</span>
                            </span>
                            <span class="wcb-f3__brand-stars__score-wrap">
                                <strong class="wcb-f3__brand-stars__score">4,9</strong>
                                <span class="wcb-f3__brand-stars__outof"><?php esc_html_e( 'de 5', 'wcb-theme' ); ?></span>
                            </span>
                        </div>
                        <span class="wcb-f3__brand-stars__meta"><?php esc_html_e( '+800 avaliações de clientes', 'wcb-theme' ); ?></span>
                    </div>
                    <div class="wcb-f3__social">
                        <a href="https://api.whatsapp.com/send/?phone=595994872020&text&type=phone_number&app_absent=0" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Falar no WhatsApp', 'wcb-theme' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                        </a>
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
                    <div class="wcb-f3__payments">
                        <span class="wcb-f3__pay wcb-f3__pay--brand" title="PIX">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="22" height="22" role="img" aria-hidden="true" focusable="false">
                                <path fill="#32BCAD" d="M378.7 395.3l-89.8-89.8c-8.1-8.1-21.4-8.1-29.5 0l-89.8 89.8c-4 4-9.5 6.3-15.2 6.3H121l113.3 113.3c12 12 31.6 12 43.6 0L391 401.6h-12.4c-5.7 0-11.2-2.3-15.2-6.3zm0-278.6c4 4 6.3 9.5 6.3 15.2v13.4L277.6 38c-12-12-31.6-12-43.6 0L120.9 151.3h33.3c5.7 0 11.2 2.3 15.2 6.3l89.8 89.8c8.1 8.1 21.4 8.1 29.5 0l89.8-89.8zM120 281.9l-95.4-95.4c-12-12-12-31.6 0-43.6l28.2-28.2 109.5 109.5c4 4 6.3 9.5 6.3 15.2v15.6c0 5.7-2.3 11.2-6.3 15.2l-42.4 42.4V281.9zm272 0v30.6l-42.4-42.4c-4-4-6.3-9.5-6.3-15.2v-15.6c0-5.7 2.3-11.2 6.3-15.2L459.1 115l28.2 28.2c12 12 12 31.6 0 43.6L392 281.9z"/>
                            </svg>
                            <span>PIX</span>
                        </span>
                        <span class="wcb-f3__pay wcb-f3__pay--brand" title="Visa">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 83" width="38" height="14"><path fill="#1434CB" d="M132.4 2.5L115.7 80h-21.3L111 2.5h21.4zM220.3 52.1l11.3-31 6.5 31h-17.8zm23.8 27.9h19.7L245.8 2.5h-18.2c-4.1 0-7.5 2.4-9 6L190 80h22l4.4-12h26.8l2.5 12h.4zM185.2 54.8c.1-20.7-28.7-21.8-28.5-31.1.1-2.8 2.7-5.8 8.6-6.6 2.9-.4 10.9-.7 20 3.4l3.6-16.6C184 2.1 177.5.3 169.4.3c-20.7 0-35.3 11-35.4 26.7-.2 11.6 10.4 18.1 18.3 22 8.1 3.9 10.9 6.5 10.8 10-.1 5.4-6.5 7.8-12.5 7.9-10.4.2-16.5-2.8-21.3-5.1l-3.8 17.5c4.8 2.2 13.8 4.2 23 4.3 22 0 36.4-10.9 36.7-27.8zM97.6 2.5L63.8 80H41.4L24.8 18.8c-1-3.9-1.9-5.4-5-7C14.9 9.1 4.3 6.5 0 5l.5-2.5h35.5c4.5 0 8.6 3 9.6 8.3l8.8 46.7L75.5 2.5h22.1z"/></svg>
                        </span>
                        <span class="wcb-f3__pay wcb-f3__pay--brand" title="Mastercard">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 199" width="28" height="20"><circle fill="#EB001B" cx="88.5" cy="99.5" r="88.5"/><circle fill="#F79E1B" cx="167.5" cy="99.5" r="88.5"/><path fill="#FF5F00" d="M128 30.5c24.4 19.8 40 49.8 40 83.5s-15.6 63.7-40 83.5c-24.4-19.8-40-49.8-40-83.5s15.6-63.7 40-83.5z"/></svg>
                        </span>
                        <span class="wcb-f3__pay wcb-f3__pay--brand" title="Elo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="20" height="20"><circle fill="#00A4E0" cx="128" cy="128" r="120"/><path fill="#FFF" d="M128 60c-37.6 0-68 30.4-68 68s30.4 68 68 68 68-30.4 68-68-30.4-68-68-68zm0 108c-22.1 0-40-17.9-40-40s17.9-40 40-40 40 17.9 40 40-17.9 40-40 40z"/><circle fill="#FFCB05" cx="185" cy="85" r="22"/><circle fill="#EF4123" cx="185" cy="171" r="22"/><circle fill="#1B1B1B" cx="71" cy="128" r="22"/></svg>
                        </span>
                        <span class="wcb-f3__pay wcb-f3__pay--brand" title="Boleto">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="18" viewBox="0 0 24 20" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"><rect x="1" y="1" width="22" height="18" rx="2"/><line x1="4" y1="5" x2="4" y2="15"/><line x1="7" y1="5" x2="7" y2="15"/><line x1="10" y1="5" x2="10" y2="12"/><line x1="13" y1="5" x2="13" y2="15"/><line x1="16" y1="5" x2="16" y2="12"/><line x1="19" y1="5" x2="19" y2="15"/></svg>
                            <span>Boleto</span>
                        </span>
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
                <span class="wcb-f3__copy">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?> · CNPJ 00.000.000/0001-00</span>
                <div class="wcb-f3__bottom-links">
                    <a href="<?php echo esc_url(home_url('/politica-de-privacidade/')); ?>">Privacidade</a>
                    <a href="<?php echo esc_url(home_url('/termos-de-uso/')); ?>">Termos</a>
                </div>
                <span class="wcb-f3__credit">Feito com ❤️ no Brasil</span>
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

<!-- ==================== MOBILE STICKY BOTTOM BAR ==================== -->
<nav class="wcb-mobile-bottom-bar" id="wcb-mobile-bottom-bar" aria-label="Navegação mobile">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="wcb-mobile-bottom-bar__item" id="wcb-mbar-home">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Home</span>
    </a>
    <button class="wcb-mobile-bottom-bar__item" id="wcb-mbar-search" aria-label="Buscar">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Busca</span>
    </button>
    <a href="<?php echo esc_url(home_url('/loja/')); ?>" class="wcb-mobile-bottom-bar__item" id="wcb-mbar-cats">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        <span>Categorias</span>
    </a>
    <a href="#" id="wcb-mbar-cart" class="wcb-mobile-bottom-bar__item" aria-label="Carrinho">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span id="wcb-mbar-cart-count" class="wcb-mobile-bottom-bar__badge" style="display:none">0</span>
        <span>Carrinho</span>
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

</body>
</html>