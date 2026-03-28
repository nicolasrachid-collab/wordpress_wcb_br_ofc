<?php
/**
 * Template Name: Política de Privacidade
 * Template para a página slug: politica-de-privacidade
 */
get_header();
?>
<style>
/* CSS crítico da Política de Privacidade — injetado inline para garantir prioridade */
body.page-template-page-politica-de-privacidade .pp-layout,
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
.pp-content {
    min-width: 0 !important;
}
.pp-grid-2 {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 16px !important;
}
.pp-rights-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px !important;
}
.pp-meta-row {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 20px !important;
}
.pp-hero__badges {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
}
@media (max-width: 900px) {
    .pp-layout {
        grid-template-columns: 1fr !important;
    }
    .pp-sidebar {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
    }
}
@media (max-width: 640px) {
    .pp-layout { display: block !important; }
    .pp-sidebar { display: block !important; }
    .pp-grid-2 { grid-template-columns: 1fr !important; }
    .pp-rights-grid { grid-template-columns: 1fr !important; }
}
</style>


<!-- ==================== HERO ==================== -->
<section class="pp-hero" aria-label="Cabeçalho da Política de Privacidade">
    <div class="pp-container">
        <nav class="pp-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Política de Privacidade</span>
        </nav>
        <p class="pp-hero__label">DOCUMENTOS LEGAIS</p>
        <h1 class="pp-hero__title">Política de Privacidade</h1>
        <div class="pp-hero__badges">
            <span class="pp-badge"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> LGPD / Lei 13.709/2018</span>
            <span class="pp-badge"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Atualizado em 2024</span>
            <span class="pp-badge"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Aplicável à White Cloud Brasil</span>
        </div>
    </div>
</section>

<!-- ==================== BODY ==================== -->
<div class="pp-body">
    <div class="pp-container pp-layout">

        <!-- ── SIDEBAR ── -->
        <aside class="pp-sidebar" aria-label="Sumário da página">
            <div class="pp-sidebar__card">
                <p class="pp-sidebar__label">SUMÁRIO</p>
                <nav>
                    <ul class="pp-sidebar__list" id="pp-toc">
                        <li><a href="#s1" class="pp-toc-link active"><span class="pp-toc-num">01</span> Resumo da Política</a></li>
                        <li><a href="#s2" class="pp-toc-link"><span class="pp-toc-num">02</span> Dados Coletados</a></li>
                        <li><a href="#s3" class="pp-toc-link"><span class="pp-toc-num">03</span> Uso dos Dados</a></li>
                        <li><a href="#s4" class="pp-toc-link"><span class="pp-toc-num">04</span> Compartilhamento</a></li>
                        <li><a href="#s5" class="pp-toc-link"><span class="pp-toc-num">05</span> Armazenamento e Segurança</a></li>
                        <li><a href="#s6" class="pp-toc-link"><span class="pp-toc-num">06</span> Cookies e Rastreamento</a></li>
                        <li><a href="#s7" class="pp-toc-link"><span class="pp-toc-num">07</span> Transferência Internacional</a></li>
                        <li><a href="#s8" class="pp-toc-link"><span class="pp-toc-num">08</span> Seus Direitos</a></li>
                        <li><a href="#s9" class="pp-toc-link"><span class="pp-toc-num">09</span> Retenção e Exclusão</a></li>
                        <li><a href="#s10" class="pp-toc-link"><span class="pp-toc-num">10</span> Antifraude WCB</a></li>
                        <li><a href="#s11" class="pp-toc-link"><span class="pp-toc-num">11</span> Alterações</a></li>
                        <li><a href="#s12" class="pp-toc-link"><span class="pp-toc-num">12</span> Fale Conosco</a></li>
                    </ul>
                </nav>
            </div>

            <div class="pp-sidebar__support">
                <p class="pp-sidebar__label">SUPORTE</p>
                <p class="pp-sidebar__support-text">Dúvidas sobre sua privacidade?</p>
                <a href="mailto:privacidade@whitecloudbrasil.com.br" class="pp-sidebar__support-link">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    privacidade@wcb.com.br
                </a>
            </div>
        </aside>

        <!-- ── CONTEÚDO PRINCIPAL ── -->
        <main class="pp-content" id="pp-main">

            <!-- S1: Resumo -->
            <section id="s1" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">01 — Resumo da Política</h2>
                </div>
                <div class="pp-card">
                    <p class="pp-text">A White Cloud Brasil está comprometida com a proteção da sua privacidade e a segurança dos seus dados pessoais. Esta política explica de forma transparente como coletamos, usamos, armazenamos e protegemos suas informações, em conformidade com a Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018).</p>

                    <div class="pp-meta-row">
                        <div class="pp-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span>Dado protegido</span>
                        </div>
                        <div class="pp-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                            <span>LGPD aplicada</span>
                        </div>
                        <div class="pp-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                            <span>Controle real</span>
                        </div>
                    </div>

                    <p class="pp-text">Ao utilizar nossos serviços, produtos ou qualquer funcionalidade disponibilizada pela White Cloud Brasil, você concorda com os termos descritos nesta política. Recomendamos a leitura completa deste documento.</p>
                    <p class="pp-text">Esta política aplica-se a todos os canais de atendimento, plataformas digitais, aplicativos, redes sociais e ambientes físicos onde a White Cloud Brasil opera.</p>

                    <blockquote class="pp-quote">
                        "Sua privacidade não é apenas uma obrigação legal — é um compromisso ético que assumimos com cada cliente."
                    </blockquote>
                </div>
            </section>

            <!-- S2: Dados Coletados -->
            <section id="s2" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">02 — Quais Dados Coletamos</h2>
                </div>
                <p class="pp-text pp-text--lead">Coletamos apenas os dados estritamente necessários para oferecer nossos serviços com qualidade e segurança.</p>

                <div class="pp-grid-2">
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">CADASTRO</p>
                        <ul class="pp-list">
                            <li>Nome completo</li>
                            <li>CPF / CNPJ</li>
                            <li>Data de nascimento</li>
                            <li>E-mail e telefone</li>
                            <li>Senha (criptografada)</li>
                        </ul>
                    </div>
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">ENTREGA E PAGAMENTO</p>
                        <ul class="pp-list">
                            <li>Endereço completo</li>
                            <li>CEP e Cidade/UF</li>
                            <li>Dados de cartão (tokenizados)</li>
                            <li>Histórico de pedidos</li>
                            <li>Notas fiscais</li>
                        </ul>
                    </div>
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">DADOS E MONITORAMENTO</p>
                        <ul class="pp-list">
                            <li>Endereço IP</li>
                            <li>Geolocalização (aproximada)</li>
                            <li>Device e sistema operacional</li>
                            <li>Tipo de navegador</li>
                        </ul>
                    </div>
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">NAVEGAÇÃO</p>
                        <ul class="pp-list">
                            <li>Páginas visitadas</li>
                            <li>Produtos visualizados</li>
                            <li>Tempo de sessão</li>
                            <li>Origem do acesso</li>
                            <li>Cliques e interações</li>
                        </ul>
                    </div>
                </div>

                <div class="pp-alert pp-alert--yellow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <strong>Dados de parceiros e antifraude:</strong> Podemos receber dados de parceiros, redes de afiliados ou plataformas de análise de risco para fins de prevenção à fraude, validação de identidade e segurança das transações.
                    </div>
                </div>
            </section>

            <!-- S3: Uso dos Dados -->
            <section id="s3" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">03 — Como Utilizamos os Seus Dados</h2>
                </div>
                <p class="pp-text pp-text--lead">Utilizamos seus dados para prestar o melhor serviço possível, dentro dos limites legais e com total transparência.</p>

                <h3 class="pp-subsection">Dados cadastrais</h3>
                <ul class="pp-list pp-list--spaced">
                    <li>Criar e gerenciar sua conta na plataforma</li>
                    <li>Processar e entregar seus pedidos</li>
                    <li>Emitir notas fiscais e documentos obrigatórios</li>
                    <li>Comunicar promoções, novidades e ofertas (com opção de descadastro)</li>
                    <li>Prestar suporte ao cliente</li>
                </ul>

                <h3 class="pp-subsection">Geolocalização</h3>
                <ul class="pp-list pp-list--spaced">
                    <li>Calcular frete e estimar prazo de entrega</li>
                    <li>Exibir lojas e parceiros próximos</li>
                    <li>Detectar acessos suspeitos ou atípicos</li>
                </ul>

                <div class="pp-alert pp-alert--blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <strong>Sobre a geolocalização:</strong> A coleta de localização é realizada apenas quando necessária para funcionalidades específicas. Você pode desativar esse recurso nas configurações do seu dispositivo a qualquer momento.
                    </div>
                </div>

                <h3 class="pp-subsection">Dados de navegação</h3>
                <ul class="pp-list pp-list--spaced">
                    <li>Personalizar a experiência de compra</li>
                    <li>Recomendar produtos relevantes</li>
                    <li>Melhorar a performance e usabilidade da plataforma</li>
                    <li>Analisar comportamento agregado para decisões de negócio</li>
                </ul>
            </section>

            <!-- S4: Compartilhamento -->
            <section id="s4" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">04 — Com Quem Compartilhamos Seus Dados</h2>
                </div>
                <p class="pp-text pp-text--lead">Não vendemos seus dados. Compartilhamos apenas com parceiros essenciais à operação, mediante contratos de confidencialidade.</p>

                <div class="pp-grid-2">
                    <div class="pp-card pp-card--icon">
                        <div class="pp-card__icon pp-card__icon--blue">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                        </div>
                        <h3 class="pp-card__title">Prestadores de serviço</h3>
                        <ul class="pp-list">
                            <li>Transportadoras e Correios</li>
                            <li>Gateways de pagamento</li>
                            <li>Plataformas de e-mail marketing</li>
                            <li>Provedores de cloud e infraestrutura</li>
                            <li>Ferramentas de analytics</li>
                        </ul>
                    </div>
                    <div class="pp-card pp-card--icon">
                        <div class="pp-card__icon pp-card__icon--gray">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <h3 class="pp-card__title">Autoridades governamentais</h3>
                        <ul class="pp-list">
                            <li>Receita Federal e Sefaz</li>
                            <li>Órgãos de segurança pública</li>
                            <li>Poder judiciário (quando exigido)</li>
                            <li>ANPD — Autoridade Nacional de Proteção de Dados</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- S5: Armazenamento e Segurança -->
            <section id="s5" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">05 — Armazenamento e Segurança</h2>
                </div>
                <p class="pp-text">Seus dados são armazenados em servidores seguros, localizados no Brasil ou em países com nível de proteção equivalente. Utilizamos criptografia SSL/TLS em todas as transmissões de dados, e adotamos controles de acesso rigorosos internamente.</p>
                <p class="pp-text">As senhas dos usuários são armazenadas com hash unidirecional (bcrypt), e os dados de cartão são tokenizados diretamente pelos gateways de pagamento — jamais armazenados em nossos servidores.</p>

                <div class="pp-alert pp-alert--blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <div>
                        <strong>Compromisso com boas práticas:</strong> Seguimos os padrões ISO 27001, PCI-DSS e as diretrizes da ANPD para garantir a segurança dos seus dados em todas as etapas de processamento, desde a coleta até o descarte seguro.
                    </div>
                </div>
            </section>

            <!-- S6: Cookies -->
            <section id="s6" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">06 — Cookies e Tecnologias de Monitoramento</h2>
                </div>
                <p class="pp-text pp-text--lead">Utilizamos diversas tecnologias de rastreamento para melhorar sua experiência e analisar o desempenho da plataforma.</p>

                <div class="pp-grid-2">
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">🍪 COOKIES</p>
                        <p class="pp-text pp-text--sm">Pequenos arquivos de texto salvos no navegador para lembrar preferências, sessão de login e itens no carrinho.</p>
                    </div>
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">📡 PIXELS</p>
                        <p class="pp-text pp-text--sm">Imagens minúsculas usadas por plataformas de anúncios (Meta, Google) para rastrear conversões e segmentar campanhas.</p>
                    </div>
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">🔦 WEB BEACONS</p>
                        <p class="pp-text pp-text--sm">Recursos embutidos em e-mails para verificar se foram abertos, quantas vezes e em qual dispositivo.</p>
                    </div>
                    <div class="pp-card pp-card--sm">
                        <p class="pp-card__label">📊 ANALYTICS</p>
                        <p class="pp-text pp-text--sm">Ferramentas como Google Analytics e Hotjar para entender o comportamento de navegação e melhorar a usabilidade.</p>
                    </div>
                </div>
            </section>

            <!-- S7: Transferência Internacional -->
            <section id="s7" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">07 — Transferência Internacional de Dados</h2>
                </div>
                <p class="pp-text">Alguns de nossos parceiros tecnológicos operam servidores fora do Brasil, incluindo países como Estados Unidos, Irlanda e Alemanha. Nesses casos, garantimos que os dados sejam tratados com o mesmo nível de proteção exigido pela LGPD.</p>
                <p class="pp-text">Utilizamos instrumentos contratuais adequados (como cláusulas padrão de proteção de dados) e escolhemos apenas parceiros que demonstrem conformidade com regulamentações internacionais equivalentes, como o GDPR europeu.</p>
            </section>

            <!-- S8: Direitos -->
            <section id="s8" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">08 — Seus Direitos Como Titular dos Dados</h2>
                </div>
                <p class="pp-text pp-text--lead">A LGPD garante a você um conjunto robusto de direitos em relação aos seus dados pessoais.</p>

                <div class="pp-card">
                    <div class="pp-rights-grid">
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Acesso aos dados</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Correção de dados</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Exclusão de dados</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Portabilidade</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Anonimização</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Bloqueio do tratamento</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Revogação do consentimento</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Informação sobre compartilhamento</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Oposição ao tratamento</div>
                        <div class="pp-right-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Revisão de decisões automatizadas</div>
                    </div>
                </div>

                <div class="pp-alert pp-alert--blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>Para exercer qualquer um desses direitos, entre em contato conosco pelo e-mail <strong>privacidade@whitecloudbrasil.com.br</strong>. Responderemos em até 15 dias úteis, conforme previsto na LGPD.</div>
                </div>
            </section>

            <!-- S9: Retenção -->
            <section id="s9" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">09 — Retenção e Exclusão dos Seus Dados</h2>
                </div>
                <p class="pp-text">Mantemos seus dados pelo tempo necessário para cumprir as finalidades descritas nesta política, incluindo obrigações legais, fiscais e contratuais. Após o encerramento do relacionamento, os dados são retidos pelo prazo mínimo exigido pela legislação vigente.</p>
                <p class="pp-text">Para solicitar a exclusão dos seus dados, entre em contato: <a href="mailto:privacidade@whitecloudbrasil.com.br" class="pp-link">privacidade@whitecloudbrasil.com.br</a></p>

                <div class="pp-alert pp-alert--yellow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <strong>Atenção:</strong> A exclusão de dados pode impossibilitar o uso de determinados serviços ou a emissão de documentos legais vinculados a pedidos anteriores.
                    </div>
                </div>
            </section>

            <!-- S10: Antifraude -->
            <section id="s10" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">10 — Antifraude White Cloud Brasil</h2>
                </div>
                <p class="pp-text">Para garantir a segurança das transações e proteger nossos clientes e parceiros, utilizamos sistemas automatizados de análise de risco e prevenção à fraude. Esses sistemas avaliam padrões de comportamento, dados de navegação e informações de dispositivos para identificar atividades suspeitas.</p>
                <p class="pp-text">Os dados coletados para fins antifraude são tratados com base no legítimo interesse da White Cloud Brasil, conforme previsto no Art. 7º, IX da LGPD.</p>

                <div class="pp-alert pp-alert--yellow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>Caso sua conta seja bloqueada por suspeita de fraude, entre em contato com nosso suporte para revisão manual do caso.</div>
                </div>
            </section>

            <!-- S11: Alterações -->
            <section id="s11" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">11 — Alteração Desta Política</h2>
                </div>
                <p class="pp-text">Esta Política de Privacidade pode ser atualizada periodicamente para refletir mudanças em nossas práticas, nas leis aplicáveis ou em nossos serviços. Sempre que houver alterações relevantes, notificaremos você por e-mail ou por aviso em destaque na plataforma. Recomendamos revisitar este documento regularmente.</p>
            </section>

            <!-- S12: Fale Conosco -->
            <section id="s12" class="pp-section">
                <div class="pp-section__head">
                    <span class="pp-section__dot" aria-hidden="true"></span>
                    <h2 class="pp-section__title">12 — Fale Conosco</h2>
                </div>
                <p class="pp-text">Para exercer seus direitos, esclarecer dúvidas ou reportar incidentes relacionados à privacidade, entre em contato pelos canais abaixo:</p>
                <ul class="pp-list pp-list--spaced">
                    <li><strong>E-mail:</strong> <a href="mailto:privacidade@whitecloudbrasil.com.br" class="pp-link">privacidade@whitecloudbrasil.com.br</a></li>
                    <li><strong>WhatsApp:</strong> Disponível no ícone flutuante da plataforma</li>
                    <li><strong>Encarregado de Dados (DPO):</strong> <a href="mailto:dpo@whitecloudbrasil.com.br" class="pp-link">dpo@whitecloudbrasil.com.br</a></li>
                </ul>

                <!-- CTA Final -->
                <div class="pp-cta">
                    <p class="pp-cta__sub">AINDA TEM DÚVIDAS?</p>
                    <h2 class="pp-cta__title">Nossa equipe está pronta para ajudar</h2>
                    <p class="pp-cta__text">Fale com nosso time de privacidade e proteção de dados. Respondemos em até 15 dias úteis, com total transparência.</p>
                    <a href="mailto:privacidade@whitecloudbrasil.com.br" class="pp-cta__btn">
                        Entrar em Contato
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </section>

        </main><!-- /.pp-content -->
    </div><!-- /.pp-layout -->
</div><!-- /.pp-body -->

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
        var scrollTop  = doc.scrollTop  || body.scrollTop;
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

    /* ── Init: ativa seções já visíveis ── */
    sections.forEach(function (s) {
        var rect = s.getBoundingClientRect();
        if (rect.top < window.innerHeight) s.classList.add('pp-visible');
    });
    updateProgress();
})();
</script>

<?php get_footer(); ?>
