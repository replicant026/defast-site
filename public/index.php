<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>DeFast - Plugin de Detalhamento para Revit</title>
    <meta
      name="description"
      content="O DeFast é o melhor plugin para Revit focado em detalhamento elétrico. Automatize conduítes, numeração, fiação, diagramas unifilares e dimensionamento."
    />
    <meta
      name="keywords"
      content="Plugin Revit, Detalhamento Elétrico, BIM, Automação, Diagrama Unifilar, Conduítes"
    />

    <link href="assets/img/favicon.png" rel="icon" />

    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />

    <!-- CSS carregado de forma assíncrona para não bloquear a renderização -->
    <link
      rel="preload"
      href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      as="style"
      onload="this.onload=null;this.rel='stylesheet'"
    />
    <noscript>
      <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet" />
    </noscript>

    <link
      rel="preload"
      href="assets/vendor/bootstrap/css/bootstrap.min.css"
      as="style"
      onload="this.onload=null;this.rel='stylesheet'"
    />
    <noscript><link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" /></noscript>

    <link
      rel="preload"
      href="assets/vendor/bootstrap-icons/bootstrap-icons.css"
      as="style"
      onload="this.onload=null;this.rel='stylesheet'"
    />
    <noscript><link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" /></noscript>

    <link
      rel="preload"
      href="assets/vendor/aos/aos.css"
      as="style"
      onload="this.onload=null;this.rel='stylesheet'"
    />
    <noscript><link href="assets/vendor/aos/aos.css" rel="stylesheet" /></noscript>

    <link
      rel="preload"
      href="assets/css/main.css"
      as="style"
      onload="this.onload=null;this.rel='stylesheet'"
    />
    <noscript><link href="assets/css/main.css" rel="stylesheet" /></noscript>
    <meta
      name="google-site-verification"
      content="vLo6Iz9D4zp8YNjEc_hHTK30nw8aI6Y5dN5GwtP-cHw"
    />

    <script type="application/ld+json"<?php echo defast_script_nonce_attr(); ?>>
      {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "DeFast",
        "operatingSystem": "Windows 10/11",
        "applicationCategory": "DesignApplication",
        "offers": {
          "@type": "Offer",
          "price": "49.90",
          "priceCurrency": "BRL"
        },
        "description": "Plugin para Revit focado em detalhamento elétrico, automação de conduítes, diagramas unifilares e dimensionamento de circuitos."
      }
    </script>
  </head>

  <body class="index-page">
    <header id="header" class="header d-flex align-items-center sticky-top">
      <?php include 'header.php'; ?>
    </header>

    <main class="main">
      <section id="hero" class="hero section">
        <div class="container">
          <div class="row gy-4">
            <div
              class="col-lg-8 order-2 order-lg-1 d-flex flex-column justify-content-center"
            >
              <h1>Aumente sua produtividade</h1>
              <p class="col-lg-8">
                Automatize tarefas repetitivas e foque no que realmente importa
                com o melhor Plugin de Revit
              </p>
            </div>
            <div class="col-lg-4 order-1 order-lg-2 hero-img">
              <img
                src="assets/img/hero-img.svg"
                class="img-fluid animated"
                alt="Ilustração de recursos de automação BIM no Revit com o Plugin DeFast"
                width="520"
                height="400"
                fetchpriority="high"
                loading="eager"
              />
            </div>
          </div>
        </div>
      </section>
      <section id="funcionalidades" class="features section">
        <div class="container section-title" data-aos="fade-up">
          <h2>De horas para cliques</h2>
          <p>
            Descubra como o DeFast vai acelerar seu fluxo de trabalho.<br />Clique
            e veja cada funcionalidade em ação no mesmo ribbon do seu Revit.
          </p>
        </div>
        <div class="container"></div>
        <div class="ribbon-container">
          <div class="ribbon-tabs">
            <div class="tab tab-file">Arquivo</div>
            <div class="tab">Arquitetura</div>
            <div class="tab">Sistemas</div>
            <div class="tab">Inserir</div>
            <div class="tab">Anotar</div>
            <div class="tab">Analisar</div>
            <div class="tab">Colaborar</div>
            <div class="tab">Vista</div>
            <div class="tab">Gerenciar</div>
            <div class="tab">Complementos</div>
            <div class="tab active">DeFast</div>
            <div class="tab">Modificar</div>
          </div>

          <div class="ribbon-content">
            <div class="ribbon-panel">
              <div class="panel-items">
                <div class="ribbon-btn" style="cursor: default">
                  <img
                    src="assets/img/Resources/icon-user.png"
                    alt="Ícone Conta"
                  />
                  <div class="text-with-dropdown">
                    <span class="btn-text">Conta</span>
                    <div class="dropdown-arrow"></div>
                  </div>
                </div>
              </div>
              <div class="panel-title">&nbsp;</div>
            </div>

            <div class="panel-separator"></div>

            <div class="ribbon-panel">
              <div class="panel-items">
                <div class="ribbon-btn" data-feature="filtrar-tags">
                  <img
                    src="assets/img/Resources/icon-filtrar-tags.png"
                    alt="Ícone Filtrar Tags"
                  />
                  <span class="btn-text">Filtrar<br />Tags</span>
                </div>

                <div class="ribbon-btn" data-feature="colorir-tags">
                  <img
                    src="assets/img/Resources/icon-colorir-tags.png"
                    alt="Ícone Colorir Tags"
                  />
                  <span class="btn-text">Colorir<br />Tags</span>
                </div>

                <div class="ribbon-btn" data-feature="campo-visao">
                  <img
                    src="assets/img/Resources/icon-camera-fov.png"
                    alt="Ícone Campo de Visão"
                  />
                  <span class="btn-text">Campo<br />de Visão</span>
                </div>

                <div class="ribbon-btn" data-feature="exportar-lote">
                  <img
                    src="assets/img/Resources/icon-exportar-lote.png"
                    alt="Ícone Exportar Lote"
                  />
                  <span class="btn-text">Exportar<br />em Lote</span>
                </div>
              </div>
              <div class="panel-title">Geral</div>
            </div>

            <div class="panel-separator"></div>

            <div class="ribbon-panel">
              <div class="panel-items">
                <div class="dropdown">
                  <div class="ribbon-btn" data-action="toggle-dropdown">
                    <img
                      src="assets/img/Resources/icon-tag-tomadas.png"
                      alt="Ícone TAG Tomadas"
                    />
                    <div class="text-with-dropdown">
                      <span class="btn-text">TAGs</span>
                      <div class="dropdown-arrow"></div>
                    </div>
                  </div>
                  <div class="dropdown-content">
                    <div
                      class="dropdown-item"
                      data-feature="tags-tomadas"
                    >
                      <img
                        src="assets/img/Resources/icon-tag-tomadas.png"
                        alt="Ícone TAG Tomadas"
                      />
                      <span>TAGs Tomadas</span>
                    </div>
                    <div
                      class="dropdown-item"
                      data-feature="tags-luminarias"
                    >
                      <img
                        src="assets/img/Resources/icon-tag-lum.png"
                        alt="Ícone TAG Luminarias"
                      />
                      <span>TAGs Luminarias</span>
                    </div>
                    <div
                      class="dropdown-item"
                      data-feature="tags-interruptores"
                    >
                      <img
                        src="assets/img/Resources/icon-tag-interruptores.png"
                        alt="Ícone TAG Interruptores"
                      />
                      <span>TAGs Interruptores</span>
                    </div>
                    <div
                      class="dropdown-item"
                      data-feature="numerar-interruptores"
                    >
                      <img
                        src="assets/img/Resources/icon-numerar-interruptores.png"
                        alt="Ícone Numerar Interruptores"
                      />
                      <span>Numerar Interruptores</span>
                    </div>
                    <div
                      class="dropdown-item"
                      data-feature="tags-fiacao"
                    >
                      <img
                        src="assets/img/Resources/icon-tag-fiacao.png"
                        alt="Ícone TAG Fiação"
                      />
                      <span>TAGs Fiação</span>
                    </div>
                  </div>
                </div>

                <div
                  class="ribbon-btn"
                  data-feature="criar-conduites"
                >
                  <img
                    src="assets/img/Resources/icon-criar-conduites.png"
                    alt="Ícone Criar Conduítes"
                  />
                  <span class="btn-text">Criar<br />Conduítes</span>
                </div>

                <div class="dropdown">
                  <div class="ribbon-btn" data-action="toggle-dropdown">
                    <img
                      src="assets/img/Resources/icon-dim-circuitos.png"
                      alt="Ícone Dimensionar Circuitos"
                    />
                    <div class="text-with-dropdown">
                      <span class="btn-text">Dimensionar</span>
                      <div class="dropdown-arrow"></div>
                    </div>
                  </div>
                  <div class="dropdown-content">
                    <div
                      class="dropdown-item"
                      data-feature="dimensionar-circuitos"
                    >
                      <img
                        src="assets/img/Resources/icon-dim-circuitos.png"
                        alt="Ícone Dimensionar Circuitos"
                      />
                      <span>Dimensionar Circuitos</span>
                    </div>
                    <div
                      class="dropdown-item"
                      data-feature="balancear-fases"
                    >
                      <img
                        src="assets/img/Resources/icon-balancear-fases.png"
                        alt="Ícone Balancear Fases"
                      />
                      <span>Balancear Fases</span>
                    </div>
                  </div>
                </div>

                <div class="dropdown">
                  <div class="ribbon-btn" data-action="toggle-dropdown">
                    <img
                      src="assets/img/Resources/icon-diag-unifilar.png"
                      alt="Ícone Unifilar"
                    />
                    <div class="text-with-dropdown">
                      <span class="btn-text">Diagramas</span>
                      <div class="dropdown-arrow"></div>
                    </div>
                  </div>
                  <div class="dropdown-content">
                    <div
                      class="dropdown-item"
                      data-feature="diagrama-unifilar"
                    >
                      <img
                        src="assets/img/Resources/icon-diag-unifilar.png"
                        alt="Ícone Unifilar"
                      />
                      <span>Diagrama Unifilar</span>
                    </div>
                    <div
                      class="dropdown-item"
                      data-feature="diagrama-multifilar"
                    >
                      <img
                        src="assets/img/Resources/icon-diag-multifilar.png"
                        alt="Ícone Multifilar"
                      />
                      <span>Diagrama Multifilar</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="panel-title">Elétrica</div>
            </div>
          </div>
        </div>

        <div
          id="feature-details"
          class="feature-details"
          style="display: none; padding-top: 0px"
        >
          <div class="feature-close" data-action="close-details">✕</div>

          <div class="feature-media">
            <div
              id="feature-media-placeholder"
              class="feature-media-placeholder"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="#ccc"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <circle cx="12" cy="12" r="10"></circle>
                <polygon points="10 8 16 12 10 16 10 8" fill="#ccc"></polygon>
              </svg>
              <span>Preview da Ferramenta</span>
            </div>
          </div>

          <div class="feature-text">
            <h2 id="feature-title">Nome da Ferramenta</h2>
            <p id="feature-description">
              A descrição detalhada da ferramenta aparecerá aqui quando um botão
              for clicado no menu superior.
            </p>
          </div>
        </div>
      </section>
      <section id="comprar" class="pricing section">
        <div class="container section-title" data-aos="fade-up">
          <h2>Invista minutos, economize horas</h2>
          <p>
            O tempo que você economiza no primeiro projeto já paga a assinatura.
            <br />Aproveite o lançamento por 30 dias grátis
          </p>
        </div>
        <div
          class="container"
          style="max-width: 600px; justify-content: center"
        >
          <div class="row gy-4">
            <div class="col-lg-12" data-aos="zoom-in" data-aos-delay="50">
              <div class="pricing-item">
                <h3 class="text-center">Comece Agora Mesmo</h3>
                <div class="text-center">
                  <span class="old-price-hero"
                    >De <span class="old-price">R$ 89,90</span> por
                  </span>
                  <h4>
                    <sup>R$ </sup>0,00<span>
                      <span style="font-weight: 600"></span></span
                    >
                  </h4>
                  <div class="promo-alert">
                    <i class="bi bi-stopwatch"></i> Promoção de Lançamento
                  </div>
                </div>
                <ul>
                  <li>
                    <i class="bi bi-check"></i> <span>Teste 30 Dias Grátis</span>
                  </li>
                  <li>
                    <i class="bi bi-check"></i>
                    <span>Acesso a todas funcionalidades</span>
                  </li>
                  <li>
                    <i class="bi bi-check"></i>
                    <span>Sem cartão de crédito</span>
                  </li>
                </ul>
                <div class="text-center">
                  <a href="/checkout" class="buy-btn">Começar Teste Grátis</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section id="faq" class="faq section light-background">
        <div class="container section-title">
          <h2>Perguntas Frequentes</h2>
        </div>

        <div class="container">
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="25">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>O que é o plugin DeFast para Revit e como ele funciona?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                O DeFast é um plugin para Autodesk Revit que automatiza tarefas
                repetitivas de detalhamento. Em vez de gastar horas em processos
                manuais, você executa em poucos cliques. Hoje o plugin conta com
                ferramentas completas para detalhamento elétrico. Ele aparece
                como uma aba no ribbon do Revit, integrado ao seu fluxo de
                trabalho.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="35">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>O plugin funciona em quais versões?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                O DeFast é compatível com Revit 2022, 2023, 2024, 2025 e 2026.
                Quando novas versões do Revit são lançadas, atualizamos o plugin
                para manter a compatibilidade.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="45">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>Como funciona o teste grátis?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Ao clicar em 'Começar Teste Grátis', você terá 30 dias com acesso
                completo a todas as funcionalidades. Sem cobrança alguma.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="55">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>Ao assinar, terei acesso as atualizações futuras?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Sim. Ao assinar, você terá acesso a todas as atualizações
                futuras do plugin, garantindo que sempre tenha as últimas
                funcionalidades e melhorias disponíveis.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="65">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>A licença da acesso a quantos usuários?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Cada licença é individual, vinculada a um único usuário. Se seu
                escritório precisa de mais licenças, entre em contato conosco
                para condições especiais.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="75">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>Posso usar o plugin em mais de um computador?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Sim. Você pode instalar o DeFast em mais de um computador, mas
                apenas um pode ficar ativo por vez. Ao fazer login em um novo
                computador, o anterior é desconectado automaticamente. Ideal
                para quem usa no escritório e em casa.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="85">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>Preciso de um template específico para usar?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Não. O DeFast funciona com qualquer template do Revit. Você não
                precisa alterar seu projeto ou aprender um novo fluxo de
                trabalho. Ele se integra perfeitamente ao seu ambiente
                existente.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="95">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>E se eu cancelar minha assinatura?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Você pode cancelar a qualquer momento, após o cancelamento o
                acesso continua ativo até o fim do período já pago. Seus
                projetos no Revit não são afetados. Se decidir voltar, basta
                assinar novamente.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="105">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>O plugin deixa o Revit mais lento?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Não. O DeFast é leve e carrega apenas quando você utiliza suas
                ferramentas. Ele não interfere no desempenho do Revit durante a
                modelagem normal.
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="115">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>Como recebo suporte se tiver algum problema?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Nosso suporte é feito diretamente por quem desenvolve o plugin.
                Você pode entrar em contato diretamente por nosso grupo
                exclusivo no Telegram, onde respondemos dúvidas, coletamos
                feedbacks e ajudamos com qualquer problema que possa surgir.
                Estamos sempre prontos para ajudar!
              </p>
            </div>
          </div>
          <div class="row faq-item" data-aos="fade-up" data-aos-delay="125">
            <div class="col-lg-5 d-flex">
              <i class="bi bi-question-circle"></i>
              <h4>O DeFast segue a NBR 5410?</h4>
            </div>
            <div class="col-lg-7">
              <p>
                Sim. O dimensionamento de circuitos, fiação e proteção segue os
                critérios da NBR 5410. O plugin calcula automaticamente seção de
                condutores, capacidade de condução e queda de tensão conforme a
                norma, garantindo que seu projeto esteja sempre em conformidade
                com os requisitos técnicos e de segurança. Saiba mais sobre o
                dimensionamento na nossa seção de
                <a
                  href="https://defast.com.br/docs/dimensionar-circuitos/"
                  style="color: var(--brand-purple); font-weight: 500"
                  >documentação</a
                >.
              </p>
            </div>
          </div>
        </div>
      </section>
      <section id="download" class="contact section">
        <div class="container section-title" data-aos="fade-up">
          <h2>Download</h2>
          <p>Comece a usar o DeFast hoje mesmo.</p>
        </div>

        <div
          class="container position-relative"
          data-aos="fade-up"
          data-aos-delay="100"
        >
          <div class="row justify-content-center">
            <div class="col-lg-6">
              <div
                class="text-center p-5 shadow rounded"
                style="border-top: 5px solid var(--brand-purple)"
              >
                <i
                  class="bi bi-cloud-arrow-down-fill display-1 mb-3"
                  style="color: var(--brand-purple)"
                ></i>
                <h3 class="fw-bold mb-2">Versão <?php echo htmlspecialchars($defast_download_version, ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="text-muted mb-4">
                  <?php echo htmlspecialchars($defast_download_notes, ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <a
                  href="<?php echo htmlspecialchars($defast_download_url, ENT_QUOTES, 'UTF-8'); ?>"
                  class="px-5 py-3 rounded-pill text-white d-inline-flex align-items-center gap-2 btn-download"
                >
                  <i class="bi bi-windows"></i> Baixar Instalador
                </a>
                <p class="mt-3 small text-muted"><?php echo htmlspecialchars($defast_download_size, ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
    <?php include 'footer.php'; ?>
    <script src="assets/js/index-features.js" defer></script>
  </body>
</html>
