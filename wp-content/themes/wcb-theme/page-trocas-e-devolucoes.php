<?php
/**
 * Template Name: Garantia, Trocas e Devoluções
 * Template para a página slug: trocas-e-devolucoes
 */
get_header();
?>
<style>
/* Crítico — garante layout 2 colunas sem conflito com CSS global do tema */
.pp-layout {
    display: grid !important;
    grid-template-columns: 260px 1fr !important;
    gap: 32px !important;
    align-items: start !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
.pp-sidebar {
    display: flex !important;
    flex-direction: column !important;
    gap: 16px !important;
    min-width: 0 !important;
}
.pp-content { min-width: 0 !important; }
.pp-grid-2 {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 16px !important;
}
.pp-hero__badges, .pp-meta-row { display: flex !important; flex-wrap: wrap !important; gap: 10px !important; }
@media (max-width: 900px) {
    .pp-layout { grid-template-columns: 1fr !important; }
    .pp-sidebar { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 12px !important; }
}
@media (max-width: 640px) {
    .pp-layout { display: block !important; }
    .pp-sidebar { display: block !important; }
    .pp-grid-2 { grid-template-columns: 1fr !important; }
}
/* Bloco de prazo de arrependimento */
.gtd-prazo-card {
    background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
    border: 2px solid #bfdbfe;
    border-radius: 14px;
    padding: 28px;
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}
.gtd-prazo-card__icon {
    width: 52px; height: 52px;
    background: #3b82f6;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fff;
}
.gtd-prazo-card__num {
    font-size: 2rem;
    font-weight: 900;
    color: #1d4ed8;
    line-height: 1;
    font-family: 'Inter', sans-serif;
    letter-spacing: -0.04em;
}
.gtd-prazo-card__label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #3b82f6;
    font-family: 'Inter', sans-serif;
}
.gtd-prazo-card__text {
    font-size: 0.85rem;
    color: #374151;
    line-height: 1.7;
    font-family: 'Inter', sans-serif;
    margin: 6px 0 0;
}
/* Lista "Não aprovado" */
.pp-list--no li::before {
    background: #ef4444 !important;
}
.pp-list--no li {
    color: #7f1d1d !important;
    font-weight: 500;
}
</style>

<!-- ==================== HERO ==================== -->
<section class="pp-hero" aria-label="Garantia, Trocas e Devoluções">
    <div class="pp-container">
        <nav class="pp-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Garantia, Trocas e Devoluções</span>
        </nav>
        <p class="pp-hero__label">SEUS DIREITOS ASSEGURADOS</p>
        <h1 class="pp-hero__title">Garantia, Trocas e Devoluções</h1>
        <div class="pp-hero__badges">
            <span class="pp-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Código de Defesa do Consumidor
            </span>
            <span class="pp-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Leitura: ~5 min
            </span>
            <span class="pp-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Atualizado em Mar 2026
            </span>
        </div>
    </div>
</section>

<!-- ==================== BODY ==================== -->
<div class="pp-body">
    <div class="pp-container pp-layout">

        <!-- ── SIDEBAR ── -->
        <aside class="pp-sidebar" aria-label="Navegação da página">
            <div class="pp-sidebar__card">
                <p class="pp-sidebar__label">NAVEGAÇÃO</p>
                <nav>
                    <ul class="pp-sidebar__list">
                        <li><a href="#s1" class="pp-toc-link active"><span class="pp-toc-num">01</span> Introdução</a></li>
                        <li><a href="#s2" class="pp-toc-link"><span class="pp-toc-num">02</span> Ao Receber sua Encomenda</a></li>
                        <li><a href="#s3" class="pp-toc-link"><span class="pp-toc-num">03</span> Termos da Garantia</a></li>
                        <li><a href="#s4" class="pp-toc-link"><span class="pp-toc-num">04</span> Defeito de Fabricação</a></li>
                        <li><a href="#s5" class="pp-toc-link"><span class="pp-toc-num">05</span> Troca e Devolução</a></li>
                        <li><a href="#s6" class="pp-toc-link"><span class="pp-toc-num">06</span> Prazo de Arrependimento</a></li>
                        <li><a href="#s7" class="pp-toc-link"><span class="pp-toc-num">07</span> Ressarcimento</a></li>
                    </ul>
                </nav>
            </div>
            <div class="pp-sidebar__support">
                <p class="pp-sidebar__label">DÚVIDAS?</p>
                <p class="pp-sidebar__support-text">Fale com nosso time de suporte</p>
                <a href="mailto:suporte@whitecloudbrasil.com.br" class="pp-sidebar__support-link">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    suporte@wcb.com.br
                </a>
            </div>
        </aside>

        <!-- ── CONTEÚDO PRINCIPAL ── -->
        <main class="pp-content" id="gtd-main">

            <!-- S1: Introdução -->
            <section id="s1" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">01 — Introdução</h2>
                </div>
                <div class="pp-card">
                    <p class="pp-text">A <strong>White Cloud Brasil</strong> se compromete a oferecer produtos 100% originais, com procedência garantida e qualidade verificada. Trabalhamos com as principais marcas do mercado de vape, e todos os itens comercializados passam por rigoroso controle antes de chegar até você.</p>
                    <p class="pp-text">Esta página descreve de forma clara e transparente todos os direitos que você possui como consumidor, bem como os processos de garantia, troca e devolução aplicáveis às compras realizadas em nossa loja, em conformidade com o <strong>Código de Defesa do Consumidor (Lei nº 8.078/90)</strong>.</p>
                    <p class="pp-text">Leia com atenção as orientações abaixo antes de iniciar qualquer solicitação. Isso agilizará o atendimento e garantirá uma experiência mais eficiente para você.</p>
                    <blockquote class="pp-quote">
                        "Sua satisfação é nossa prioridade. Estamos aqui para garantir que cada pedido chegue perfeito e que qualquer problema seja resolvido com agilidade e transparência."
                    </blockquote>
                </div>
            </section>

            <!-- S2: Ao receber -->
            <section id="s2" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">02 — Ao Receber Sua Encomenda</h2>
                </div>
                <p class="pp-text pp-text--lead">Siga estes passos ao receber seu pedido para garantir seus direitos em caso de problemas.</p>

                <div class="pp-card">
                    <p class="pp-text"><strong>Verifique as condições da embalagem</strong> antes de assinar o protocolo de entrega. Se a embalagem apresentar sinais visíveis de avaria, amassado, umidade ou violação, <strong>recuse o recebimento imediatamente</strong> e comunique o transportador, registrando o motivo na nota de devolução.</p>
                    <p class="pp-text">Ao recusar a entrega, entre em contato com nosso suporte para que possamos providenciar o reenvio do produto em condições adequadas sem qualquer custo adicional para você.</p>
                    <p class="pp-text">Caso aceite o pacote mesmo com indícios de avaria, a White Cloud Brasil não poderá ser responsabilizada por danos externos ao produto decorrentes do transporte.</p>
                </div>

                <div class="pp-alert pp-alert--blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <strong>Recomendação importante:</strong> Grave um vídeo ao abrir sua encomenda. Essa prática simples comprova as condições em que o produto foi recebido e agiliza qualquer solicitação futura de troca ou devolução.
                    </div>
                </div>
            </section>

            <!-- S3: Termos da garantia -->
            <section id="s3" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">03 — Termos e Condições da Garantia</h2>
                </div>
                <p class="pp-text pp-text--lead">A White Cloud Brasil oferece <strong>30 dias de garantia</strong> contra defeitos de fabricação, contados a partir da data de recebimento do produto.</p>

                <p class="pp-text">A garantia cobre exclusivamente defeitos de fabricação comprovados, como falhas estruturais, componentes com mau funcionamento de fábrica ou produtos que apresentem problemas técnicos em condições normais de uso.</p>

                <div class="pp-alert pp-alert--yellow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <strong>A garantia NÃO cobre:</strong> danos causados por mau uso, quedas, contato com líquidos, modificações não autorizadas, uso de líquidos impróprios, exposição a temperatura extrema ou qualquer avaria decorrente de uso indevido.
                    </div>
                </div>

                <p class="pp-sidebar__label" style="margin: 20px 0 10px; font-size: 0.68rem; letter-spacing: 0.1em; color: #64748b; font-family: 'Inter', sans-serif;">NÃO SERÁ APROVADO EM NENHUMA HIPÓTESE</p>
                <ul class="pp-list pp-list--no">
                    <li>Produto com sinal de queda, impacto ou dano físico externo</li>
                    <li>Produto com marcas de uso de líquidos inadequados ou adulterados</li>
                    <li>Produto com modificação, desmonte ou tentativa de reparo não autorizado</li>
                    <li>Produto sem número de série original ou com embalagem violada pelo consumidor</li>
                    <li>Solicitação realizada fora do prazo de 30 dias</li>
                    <li>Produto enviado sem comunicação prévia ao suporte</li>
                </ul>
            </section>

            <!-- S4: Defeito de fabricação -->
            <section id="s4" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">04 — Produtos com Defeito de Fabricação</h2>
                </div>
                <p class="pp-text pp-text--lead">Identificamos como defeito de fabricação qualquer falha que comprometa o funcionamento do produto em condições normais de uso, sem que tenha havido erro, negligência ou uso inadequado por parte do consumidor.</p>

                <p class="pp-text">Para acionar a garantia por defeito de fabricação, o cliente deve entrar em contato com nosso suporte <strong>dentro do prazo de 30 dias</strong> a partir da data de recebimento, informando:</p>
                <ul class="pp-list pp-list--spaced">
                    <li>Número do pedido</li>
                    <li>Descrição detalhada do defeito</li>
                    <li>Fotos ou vídeo demonstrando o problema</li>
                    <li>Data de recebimento do produto</li>
                </ul>

                <div class="pp-alert pp-alert--yellow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>A garantia não cobre desgaste natural do produto, baterias consumidas pelo uso normal, nem líquidos ou consumíveis utilizados no dispositivo.</div>
                </div>

                <div class="pp-alert pp-alert--blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <div>
                        <strong>O que a garantia cobre:</strong> Defeitos confirmados pela nossa equipe técnica resultarão, a critério da White Cloud Brasil, na substituição do produto por outro idêntico ou equivalente, sem custo algum para o cliente.
                    </div>
                </div>
            </section>

            <!-- S5: Troca e Devolução -->
            <section id="s5" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">05 — Troca e Devolução</h2>
                </div>
                <p class="pp-text pp-text--lead">Para solicitar troca ou devolução, o produto deve atender obrigatoriamente a todas as condições abaixo.</p>

                <h3 class="pp-subsection">Condições obrigatórias</h3>
                <ul class="pp-list pp-list--spaced">
                    <li>Produto sem sinais de uso, em perfeito estado</li>
                    <li>Na embalagem original, lacrada ou sem violação</li>
                    <li>Com todos os acessórios, manuais e brindes inclusos no pedido original</li>
                    <li>Acompanhado da nota fiscal ou comprovante de compra</li>
                    <li>Solicitação realizada dentro do prazo legal de 7 dias corridos</li>
                </ul>

                <h3 class="pp-subsection">Como solicitar</h3>
                <p class="pp-text">Entre em contato com nosso suporte pelo e-mail <a href="mailto:suporte@whitecloudbrasil.com.br" class="pp-link">suporte@whitecloudbrasil.com.br</a> ou pelo WhatsApp. Nossa equipe irá avaliar o pedido e, se aprovado, enviará as instruções para o retorno do produto.</p>
                <p class="pp-text">O frete de retorno será de responsabilidade do cliente, exceto nos casos em que a solicitação decorra de erro da White Cloud Brasil (produto errado, defeito de fabricação confirmado ou avaria no transporte).</p>

                <div class="pp-alert pp-alert--yellow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <strong>A troca será recusada quando:</strong> o produto apresentar sinais de uso, estiver com embalagem violada, com acessórios faltantes, ou quando a solicitação for realizada fora do prazo estabelecido.
                    </div>
                </div>
            </section>

            <!-- S6: Prazo de arrependimento -->
            <section id="s6" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">06 — Prazo de Arrependimento</h2>
                </div>
                <p class="pp-text pp-text--lead">Conforme o Art. 49 do Código de Defesa do Consumidor, compras realizadas fora do estabelecimento comercial (inclusive compras online) asseguram o direito de arrependimento.</p>

                <div class="gtd-prazo-card">
                    <div class="gtd-prazo-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="gtd-prazo-card__num">7 dias</div>
                        <div class="gtd-prazo-card__label">Prazo de arrependimento — corridos a partir do recebimento</div>
                        <p class="gtd-prazo-card__text">Você tem o direito de desistir da compra em até 7 dias corridos após o recebimento do produto, sem necessidade de justificativa. Após esse prazo, somente são aceitas solicitações por defeito de fabricação dentro da garantia.</p>
                    </div>
                </div>

                <div class="pp-alert pp-alert--blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>Para exercer o direito de arrependimento, o produto deve ser devolvido <strong>sem uso</strong>, na embalagem original e com todos os acessórios. O estorno será realizado após a conferência do produto em nosso estoque.</div>
                </div>
            </section>

            <!-- S7: Ressarcimento -->
            <section id="s7" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">07 — Ressarcimento do Valor da Compra</h2>
                </div>
                <p class="pp-text pp-text--lead">Após a confirmação e aprovação da devolução, o <strong>reembolso será processado em até 7 dias úteis</strong> a partir da chegada do produto ao nosso estoque.</p>

                <p class="pp-text">O valor será estornado na mesma forma de pagamento utilizada na compra original. Pedimos que observe os prazos específicos de cada modalidade:</p>

                <div class="pp-grid-2">
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">💳 CARTÃO DE CRÉDITO</p>
                        <ul class="pp-list">
                            <li>Estorno processado em até 7 dias úteis</li>
                            <li>Reflexo na fatura: 1 a 2 ciclos</li>
                            <li>Parcelas: estornadas na mesma quantidade</li>
                        </ul>
                    </div>
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">💰 PIX / BANCA</p>
                        <ul class="pp-list">
                            <li>Estorno em até 3 dias úteis</li>
                            <li>Transferência direta para a chave PIX cadastrada</li>
                            <li>Necessário validar dados bancários</li>
                        </ul>
                    </div>
                </div>

                <div class="pp-alert pp-alert--blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <strong>Mercado Pago e cartão:</strong> O prazo de ressarcimento pode variar de acordo com as políticas do seu banco ou operadora de cartão. A White Cloud Brasil não tem ingerência sobre esses prazos após o processamento do estorno.
                    </div>
                </div>

                <!-- CTA Final -->
                <div class="pp-cta">
                    <p class="pp-cta__sub">AINDA TEM DÚVIDAS?</p>
                    <h2 class="pp-cta__title">Ficou com alguma dúvida?</h2>
                    <p class="pp-cta__text">Nossa equipe de suporte está pronta para te ajudar. Atendemos via e-mail e WhatsApp em horário comercial.</p>
                    <a href="mailto:suporte@whitecloudbrasil.com.br" class="pp-cta__btn">
                        Entrar em Contato
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </section>

        </main>
    </div>
</div>

<!-- Barra de progresso de leitura -->
<div class="pp-progress-bar" id="pp-progress" role="progressbar" aria-label="Progresso de leitura"></div>

<!-- Botão voltar ao topo -->
<button class="pp-back-top" id="pp-back-top" aria-label="Voltar ao topo" title="Voltar ao topo">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<script>
(function () {
    'use strict';

    /* ── 1. Smooth scroll para âncoras ── */
    document.querySelectorAll('.pp-toc-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var href = this.getAttribute('href');
            var target = document.querySelector(href);
            if (!target) return;
            e.preventDefault();
            var offset = 80;
            var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
            document.querySelectorAll('.pp-toc-link').forEach(function (l) { l.classList.remove('active'); });
            link.classList.add('active');
            history.pushState(null, '', href);
        });
    });

    /* ── 2. Highlight automático ao scrollar ── */
    var sections    = document.querySelectorAll('.pp-section[id]');
    var tocLinks    = document.querySelectorAll('.pp-toc-link');
    var tocObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                var id = entry.target.getAttribute('id');
                tocLinks.forEach(function (l) {
                    l.classList.toggle('active', l.getAttribute('href') === '#' + id);
                });
            }
        });
    }, { rootMargin: '-15% 0px -75% 0px' });
    sections.forEach(function (s) { tocObserver.observe(s); });

    /* ── 3. Fade-in nas seções ao entrar na viewport ── */
    var fadeObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('pp-visible');
                fadeObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });
    sections.forEach(function (s) { fadeObserver.observe(s); });

    /* ── 4. Barra de progresso de leitura ── */
    var progressBar = document.getElementById('pp-progress');
    function updateProgress() {
        var doc    = document.documentElement;
        var body   = document.body;
        var scrollTop    = doc.scrollTop  || body.scrollTop;
        var scrollHeight = (doc.scrollHeight || body.scrollHeight) - doc.clientHeight;
        var pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
        if (progressBar) progressBar.style.width = pct + '%';
    }

    /* ── 5. Botão Voltar ao Topo ── */
    var backTop = document.getElementById('pp-back-top');
    function onScroll() {
        updateProgress();
        if (backTop) {
            var show = (window.pageYOffset || document.documentElement.scrollTop) > 400;
            backTop.classList.toggle('visible', show);
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    if (backTop) {
        backTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ── Init ── */
    sections.forEach(function (s) {
        var rect = s.getBoundingClientRect();
        if (rect.top < window.innerHeight) s.classList.add('pp-visible');
    });
    updateProgress();
})();
</script>


<?php get_footer(); ?>
