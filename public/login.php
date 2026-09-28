<?php // Sem lógica PHP — autenticação feita via API em login.js ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - Área do Cliente</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
</head>
<body class="index-page antialiased text-gray-700">

    <header id="header" class="header d-flex align-items-center sticky-top">
        <?php include 'header.php'; ?>
    </header>

    <main class="flex items-center justify-center p-4" style="min-height: calc(100vh - 80px);">
        <div class="w-full max-w-md bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-900">Área do Cliente</h1>
                <p class="text-sm text-gray-500 mt-2">Entre com e-mail e senha.</p>
            </div>

            <form id="login-form" class="space-y-4" novalidate>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input id="email" type="email" required placeholder="seu@email.com" class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-700 focus:border-purple-700">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input id="password" type="password" required placeholder="Sua senha" class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-700 focus:border-purple-700">
                </div>
                <button id="submit-button" type="submit" class="w-full bg-purple-900 text-white font-medium py-2.5 rounded-md hover:opacity-90 transition-opacity">Entrar</button>
                <p id="error-msg" class="hidden text-sm text-red-600"></p>
            </form>

            <div class="mt-4 text-center">
                <button type="button" id="show-forgot-btn" class="text-sm underline" style="background:none;border:none;cursor:pointer;color:var(--brand-purple);">Esqueci minha senha</button>
            </div>

            <div id="forgot-panel" class="hidden mt-4 border-t border-gray-100 pt-4">
                <p class="text-sm text-gray-600 mb-3">Digite seu e-mail e enviaremos um link para redefinir sua senha.</p>
                <form id="forgot-form" class="space-y-3" novalidate>
                    <input id="forgot-email" type="email" required placeholder="seu@email.com" class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-700 focus:border-purple-700 text-sm">
                    <button type="submit" id="forgot-submit" class="w-full bg-purple-900 text-white font-medium py-2 rounded-md hover:opacity-90 transition-opacity text-sm">Enviar link</button>
                    <p id="forgot-msg" class="hidden text-sm text-center"></p>
                    <div class="mt-3 text-center">
                        <button type="button" id="hide-forgot-btn" class="text-sm underline text-purple-900 hover:text-purple-700" style="background:none;border:none;cursor:pointer;">Voltar ao login</button>
                    </div>
                </form>
            </div>

        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="/assets/js/login.js"></script>
</body>
</html>
