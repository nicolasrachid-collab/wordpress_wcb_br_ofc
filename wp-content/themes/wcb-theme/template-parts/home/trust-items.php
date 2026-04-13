<?php
/**
 * Itens da trust bar — incluído duas vezes em front-page (letreiro contínuo, loop sem salto).
 *
 * @package wcb-theme
 *
 * @param array $args {
 *     @type bool $marquee_clone Segunda cópia decorativa (aria-hidden).
 * }
 */

$wcb_trust_clone = isset( $args ) && ! empty( $args['marquee_clone'] );
$wcb_grid_class  = 'wcb-trust__grid' . ( $wcb_trust_clone ? ' wcb-trust__grid--marquee-clone' : '' );
$wcb_grid_attrs  = $wcb_trust_clone ? ' aria-hidden="true"' : '';
?>
<div class="<?php echo esc_attr( $wcb_grid_class ); ?>"<?php echo $wcb_grid_attrs; ?>>

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Frete Rápido para Todo o Brasil</strong>
          <span>Entrega ágil e garantida</span>
      </div>
  </div>

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Compra Protegida</strong>
          <span>Segurança total no pagamento</span>
      </div>
  </div>

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Experiência Sem Risco</strong>
          <span>Troca simples, rápida e garantida</span>
      </div>
  </div>

  <div class="wcb-trust__item">
      <div class="wcb-trust__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
          </svg>
      </div>
      <div class="wcb-trust__text">
          <strong>Original de Verdade</strong>
          <span>Sem réplicas, sem surpresas</span>
      </div>
  </div>

</div>
