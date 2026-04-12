<?php
/**
 * Template Part: Seção de Depoimentos (Scroll Infinito — 3 colunas)
 *
 * Para usar em qualquer página:
 *   get_template_part('template-parts/section-depoimentos');
 *
 * CSS necessário: classes wcb-testi__* em style.css
 * Design: branco, 3 colunas scrolling, estrelas douradas, badge com ponto azul
 *
 * @package wcb-theme
 *
 * Exceção ao padrão `.wcb-section` (governança de secções):
 * Este bloco permanece em `wcb-testi` / `wcb-testi__header` por ser componente legado com
 * padding, fundo e tipografia próprios. Unificar com `.wcb-section__header` + tokens exige
 * refactor dedicado (CSS + markup) para evitar dobro de espaçamento e regressões visuais.
 */

/**
 * Etiquetas curtas por tipo de produto (home — depoimentos), em vez do título completo do catálogo.
 */
$wcb_testi_display_tags = array(
	'Pod System',
	'Pod Refil',
	'Cartucho Pod',
	'Coil',
	'Coil Mesh',
	'Algodão',
	'Bateria 18650',
	'Bateria 21700',
	'Mod Box',
	'Mod Mecânico',
	'AIO',
	'Atomizador RTA',
	'Atomizador RDA',
	'Atomizador RDTA',
	'Tank Subohm',
	'Drip Tip',
	'Carregador',
	'Juice 30ml',
	'Juice 60ml',
	'Salt Nic',
);

$testimonials = array(
	array(
		'name' => 'Camila Torres',
		'role' => 'São Paulo, SP',
		'text' => 'Melhor custo-benefício que já encontrei. O clube de assinatura vale cada centavo. Sabores incríveis e sempre cheios. Nunca tive problema com nenhum pedido!',
	),
	array(
		'name' => 'Guilherme Santos',
		'role' => 'Curitiba, PR',
		'text' => 'Atendimento impecável, produtos originais e preço competitivo. Já tentei outros sites mas sempre volto à White Cloud. O cupom de desconto é um diferencial incrível para fidelizar clientes.',
	),
	array(
		'name' => 'Lucas Ferreira',
		'role' => 'Porto Alegre, RS',
		'text' => 'Comprei meu primeiro pod aqui e fiquei encantado. Suporte super ativo no WhatsApp, entrega em 2 dias, produto novo com nota fiscal. Já indiquei para vários amigos!',
	),
	array(
		'name' => 'Mariana Oliveira',
		'role' => 'Belo Horizonte, MG',
		'text' => 'Estou no clube há 6 meses e só melhora. A seleção de sabores é incrível e o atendimento é muito pessoal. Sinto que sou uma cliente valorizada aqui.',
	),
	array(
		'name' => 'Pedro Alves',
		'role' => 'Brasília, DF',
		'text' => 'Preço justo, entrega rápida e embalagem muito cuidadosa. Recebi em perfeito estado. Site fácil de navegar e checkout simples. Recomendo sem dúvidas!',
	),
	array(
		'name' => 'Isabela Ramos',
		'role' => 'Recife, PE',
		'text' => 'Fiz meu primeiro pedido com receio mas foi uma experiência ótima. O produto chegou antes do prazo, original e com nota fiscal. Já voltei mais 3 vezes!',
	),
	array(
		'name' => 'Rafael Costa',
		'role' => 'São Paulo, SP',
		'text' => 'Descobri o Club WCB e não saio mais. Todo mês recebo sabores surpreendentes com desconto especial. Atendimento via WhatsApp resolve qualquer dúvida em minutos.',
	),
	array(
		'name' => 'Fernanda Lima',
		'role' => 'Rio de Janeiro, RJ',
		'text' => 'A qualidade dos produtos é incomparável. Tudo original, com nota e rápido. A embalagem discreta também foi um detalhe que adorei. Vou continuar comprando aqui!',
	),
	array(
		'name' => 'Thiago Nascimento',
		'role' => 'Salvador, BA',
		'text' => 'Sabia que ia ser boa mas superou expectativas. Chegou mega rápido, original com nota, exatamente o produto anunciado. Preço melhor que qualquer outro site que pesquisei.',
	),
);

$n_tags = count( $wcb_testi_display_tags );
foreach ( $testimonials as $wcb_testi_i => &$wcb_testi_row ) {
	$wcb_testi_row['tag'] = $n_tags > 0
		? $wcb_testi_display_tags[ $wcb_testi_i % $n_tags ]
		: __( 'Produto em destaque', 'wcb-theme' );
	$wcb_testi_row['img'] = 'https://api.dicebear.com/7.x/notionists/png?seed=' . rawurlencode( $wcb_testi_row['name'] ) . '&size=128';
}
unset( $wcb_testi_row );

$col1 = array_slice($testimonials, 0, 3);
$col2 = array_slice($testimonials, 3, 3);
$col3 = array_slice($testimonials, 6, 3);
?>

<!-- ==================== DEPOIMENTOS ==================== -->
<section class="wcb-testi" id="wcb-depoimentos" aria-labelledby="wcb-testi-heading">
    <div class="wcb-container">

        <div class="wcb-testi__header">
            <div class="wcb-testi__rating-stars" aria-label="5 estrelas">★ ★ ★ ★ ★</div>
            <div class="wcb-testi__badge">
                <span class="wcb-testi__badge-dot"></span>
                EM BREVE · CLOUD PRIME
            </div>
            <h2 id="wcb-testi-heading" class="wcb-testi__title">Depoimentos que vão virar benefícios</h2>
            <div class="wcb-testi__sub-wrap">
                <p class="wcb-testi__sub"><?php echo wp_kses( __( 'Em breve o <strong>Cloud Prime</strong> recompensa quem compra aqui: avaliações bem feitas viram vantagens exclusivas no programa. Quanto mais o seu feedback ajudar, mais você ganha.', 'wcb-theme' ), array( 'strong' => array() ) ); ?></p>
            </div>
        </div>

        <div class="wcb-testi__stage" role="region" aria-label="Depoimentos de clientes">
            <!-- Coluna 1 -->
            <div class="wcb-testi__col">
                <ul class="wcb-testi__track wcb-testi__track--1">
                    <?php foreach (array_merge($col1, $col1) as $t): ?>
                    <li class="wcb-testi__card">
                        <blockquote>
                            <div class="wcb-testi__stars">★★★★★</div>
                            <p class="wcb-testi__quote">"<?php echo esc_html($t['text']); ?>"</p>
                            <footer class="wcb-testi__footer">
                                <img src="<?php echo esc_url($t['img']); ?>" alt="<?php echo esc_attr($t['name']); ?>" width="40" height="40" loading="lazy" class="wcb-testi__avatar">
                                <div class="wcb-testi__meta">
                                    <cite class="wcb-testi__name"><?php echo esc_html($t['name']); ?></cite>
                                    <span class="wcb-testi__role"><?php echo esc_html($t['role']); ?></span>
                                </div>
                                <span class="wcb-testi__product-tag"><?php echo esc_html($t['tag']); ?></span>
                            </footer>
                        </blockquote>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Coluna 2 — hidden mobile -->
            <div class="wcb-testi__col wcb-testi__col--md">
                <ul class="wcb-testi__track wcb-testi__track--2">
                    <?php foreach (array_merge($col2, $col2) as $t): ?>
                    <li class="wcb-testi__card">
                        <blockquote>
                            <div class="wcb-testi__stars">★★★★★</div>
                            <p class="wcb-testi__quote">"<?php echo esc_html($t['text']); ?>"</p>
                            <footer class="wcb-testi__footer">
                                <img src="<?php echo esc_url($t['img']); ?>" alt="<?php echo esc_attr($t['name']); ?>" width="40" height="40" loading="lazy" class="wcb-testi__avatar">
                                <div class="wcb-testi__meta">
                                    <cite class="wcb-testi__name"><?php echo esc_html($t['name']); ?></cite>
                                    <span class="wcb-testi__role"><?php echo esc_html($t['role']); ?></span>
                                </div>
                                <span class="wcb-testi__product-tag"><?php echo esc_html($t['tag']); ?></span>
                            </footer>
                        </blockquote>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Coluna 3 — hidden tablet -->
            <div class="wcb-testi__col wcb-testi__col--lg">
                <ul class="wcb-testi__track wcb-testi__track--3">
                    <?php foreach (array_merge($col3, $col3) as $t): ?>
                    <li class="wcb-testi__card">
                        <blockquote>
                            <div class="wcb-testi__stars">★★★★★</div>
                            <p class="wcb-testi__quote">"<?php echo esc_html($t['text']); ?>"</p>
                            <footer class="wcb-testi__footer">
                                <img src="<?php echo esc_url($t['img']); ?>" alt="<?php echo esc_attr($t['name']); ?>" width="40" height="40" loading="lazy" class="wcb-testi__avatar">
                                <div class="wcb-testi__meta">
                                    <cite class="wcb-testi__name"><?php echo esc_html($t['name']); ?></cite>
                                    <span class="wcb-testi__role"><?php echo esc_html($t['role']); ?></span>
                                </div>
                                <span class="wcb-testi__product-tag"><?php echo esc_html($t['tag']); ?></span>
                            </footer>
                        </blockquote>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

    </div>
</section>
