<?php include 'config.php'; defast_set_private_no_store_headers(); ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Redefinir Senha - DeFast</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="description" content="">
  <meta name="keywords" content="">

  <link href="assets/img/favicon.png" rel="icon">

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" integrity="sha384-HtMZLkYo+pR5/u7zCzXxMJP6QoNnQJt1qkHM0EaOPvGDIzaVZbmYr/TlvUZ/sKAg" crossorigin="anonymous">
  <script src="https://unpkg.com/@supabase/supabase-js@2.98.0/dist/umd/supabase.js" integrity="sha384-NRo2jhGGHu91p1IOcVC3UWI5Vnd+xGXfD/8N7Hr9+aGTK0d/Pl0i+kUZsB/zIlrK" crossorigin="anonymous"></script>
  <?php defast_render_client_config(); ?>
</head>

<body class="index-page">
  <header id="header" class="header d-flex align-items-center sticky-top">
    <?php include 'header.php'; ?>
  </header>

  <main class="main">
    <section id="reset-password" class="features section">
      <div class="container section-title" data-aos="fade-up">
        <h2>Redefinir Senha</h2>
      </div>

      <div class="flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
          <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900" style="color: #4A148C">Nova Senha</h2>
            <p class="mt-2 text-sm text-gray-600">Digite sua nova senha abaixo para recuperar o acesso.</p>
          </div>

          <div id="msg-reset" class="hidden p-4 mb-4 rounded-md text-sm text-center"></div>

          <form id="form-reset" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nova Senha</label>
              <input type="password" id="new-password" required minlength="8" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Mínimo 8 caracteres">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Confirme a nova senha</label>
              <input type="password" id="confirm-password" required minlength="8" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Repita a nova senha">
            </div>
            <button type="submit" id="btn-reset" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium btn-brand focus:outline-none">
              Atualizar senha
            </button>
          </form>

          <div class="mt-6 text-center">
            <a href="area-cliente" class="text-sm text-brand">
              Voltar para o login
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="assets/js/reset-senha.js"></script>
  <?php include 'footer.php'; ?>
</body>

</html>
