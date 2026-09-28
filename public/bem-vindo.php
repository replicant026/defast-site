<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Bem-vindo ao DeFast - Configure sua conta</title>
  <meta name="robots" content="noindex, nofollow">
  
  <link href="assets/img/favicon.png" rel="icon">

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  
  <link href="assets/css/main.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" integrity="sha384-HtMZLkYo+pR5/u7zCzXxMJP6QoNnQJt1qkHM0EaOPvGDIzaVZbmYr/TlvUZ/sKAg" crossorigin="anonymous">

  <style>
    .success-icon {
      color: #10B981;
      font-size: 4rem;
    }
    .step-card {
        border-top: 4px solid #4A148C;
    }
  </style>
</head>

<body class="index-page">
  <header id="header" class="header d-flex align-items-center sticky-top">
    <?php include 'header.php'; ?>
  </header>

  <main class="main">
    
    <section id="welcome" class="section py-5">
      <div class="container" data-aos="fade-up">
        
        <div class="text-center mb-5">
            <i class="bi bi-check-circle-fill success-icon mb-3"></i>
            <h1 class="display-5 fw-bold text-gray-900">Pagamento confirmado!</h1>
            <p class="lead text-gray-600">Obrigado por adquirir o DeFast. Seu acesso está quase pronto.</p>
        </div>

        <div class="row gy-4 justify-content-center">
            
            <div class="col-lg-5 col-md-6">
                <div class="bg-white p-8 rounded-lg shadow-md h-100 step-card">
                    <h3 class="text-2xl font-bold mb-2 text-gray-800">2. Acesse sua conta</h3>
                    <p class="text-gray-600 mb-4 text-sm">Sua conta e sua licença são criadas automaticamente após a confirmação da compra.</p>
                    <div class="d-grid gap-2">
                        <a href="/login?next=%2Farea-cliente" class="bg-purple-900 text-white font-medium py-3 rounded-md hover:bg-purple-800 transition-colors text-center w-full" style="font-size: 1rem;">
                            <i class="bi bi-person-check me-2"></i> Entrar na Área do Cliente
                        </a>
                        <a href="reset-senha" class="btn btn-outline-secondary text-center py-2">
                            Definir ou redefinir senha
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-md-6">
                <div class="bg-white p-8 rounded-lg shadow-md h-100 step-card">
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">1. Baixe o Plugin</h3>
                    <p class="text-gray-600 mb-6">Faça o download da versão mais recente do instalador do DeFast para Revit.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="<?php echo htmlspecialchars($defast_download_url, ENT_QUOTES, 'UTF-8'); ?>" class="bg-purple-900 text-white font-medium py-3 rounded-md hover:bg-purple-800 transition-colors text-center w-full" style="font-size: 1.1rem;">
                            <i class="bi bi-download me-2"></i> Download Agora
                        </a>
                    </div>
                    <p class="text-xs text-gray-400 mt-3 text-center">Versão <?php echo htmlspecialchars($defast_download_version, ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($defast_download_notes, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="/login?next=%2Farea-cliente" class="text-sm underline" style="color: var(--brand-purple);">Já tem conta? Faça login aqui.</a>
        </div>

      </div>
    </section>

  </main>

  <?php include 'footer.php'; ?>
  
</body>
</html>
