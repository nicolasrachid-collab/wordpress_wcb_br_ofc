<?php
/**
 * Template Part: Seção de Depoimentos (Scroll Infinito — 3 colunas)
 *
 * Para usar em qualquer página:
 *   get_template_part('template-parts/section-depoimentos');
 *
 * CSS necessário: classes wcb-testi__* em style.css
 * Design: branco, 3 colunas scrolling, estrelas douradas, badge com ponto azul
 */

$testimonials = [
    ['name'=>'Camila Torres',    'role'=>'Influenciadora · São Paulo, SP',  'tag'=>'Cloud & Vape',    'img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Melhor custo-benefício que já encontrei. O clube de assinatura vale cada centavo. Sabores incríveis e sempre cheios. Nunca tive problema com nenhum pedido!'],
    ['name'=>'Guilherme Santos', 'role'=>'Cliente Premium · Curitiba, PR', 'tag'=>'Yoop 8000 Puffs', 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Atendimento impecável, produtos originais e preço competitivo. Já tentei outros sites mas sempre volto à White Cloud. O cupom de desconto é um diferencial incrível para fidelizar clientes.'],
    ['name'=>'Lucas Ferreira',   'role'=>'Vaper entusiasta · Porto Alegre', 'tag'=>'Vaporesso 3800',  'img'=>'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Comprei meu primeiro pod aqui e fiquei encantado. Suporte super ativo no WhatsApp, entrega em 2 dias, produto novo com nota fiscal. Já indiquei para vários amigos!'],
    ['name'=>'Mariana Oliveira', 'role'=>'Designer · Belo Horizonte, MG',  'tag'=>'Lost Mary 3500',  'img'=>'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Estou no clube há 6 meses e só melhora. A seleção de sabores é incrível e o atendimento é muito pessoal. Sinto que sou uma cliente valorizada aqui.'],
    ['name'=>'Pedro Alves',      'role'=>'Engenheiro · Brasília, DF',       'tag'=>'Elfbar 600',      'img'=>'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Preço justo, entrega rápida e embalagem muito cuidadosa. Recebi em perfeito estado. Site fácil de navegar e checkout simples. Recomendo sem dúvidas!'],
    ['name'=>'Isabela Ramos',    'role'=>'Professora · Recife, PE',         'tag'=>'Uwell Caliburn',  'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Fiz meu primeiro pedido com receio mas foi uma experiência ótima. O produto chegou antes do prazo, original e com nota fiscal. Já voltei mais 3 vezes!'],
    ['name'=>'Rafael Costa',     'role'=>'Músico · São Paulo, SP',          'tag'=>'Vivo X Salt',     'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Descobri o Club WCB e não saio mais. Todo mês recebo sabores surpreendentes com desconto especial. Atendimento via WhatsApp resolve qualquer dúvida em minutos.'],
    ['name'=>'Fernanda Lima',    'role'=>'Médica · Rio de Janeiro, RJ',     'tag'=>'Smok Nord 4',     'img'=>'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'A qualidade dos produtos é incomparável. Tudo original, com nota e rápido. A embalagem discreta também foi um detalhe que adorei. Vou continuar comprando aqui!'],
    ['name'=>'Thiago Nascimento','role'=>'Advogado · Salvador, BA',         'tag'=>'Geek Bar Pulse',  'img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&q=80&w=100&h=100', 'text'=>'Sabia que ia ser boa mas superou expectativas. Chegou mega rápido, original com nota, exatamente o produto anunciado. Preço melhor que qualquer outro site que pesquisei.'],
];
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
                +50.000 CLIENTES SATISFEITOS
            </div>
            <h2 id="wcb-testi-heading" class="wcb-testi__title">O que nossos clientes dizem</h2>
            <p class="wcb-testi__sub">A White Cloud já conquistou a confiança de mais de<br><strong>50 mil clientes</strong> em todo o Brasil.</p>
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
