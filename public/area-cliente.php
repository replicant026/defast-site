<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/session.php';

// Reaproveita as mesmas variáveis de download sem puxar o config.php completo (CSP).
require_once __DIR__ . '/download-config.php';

initSecureSession();

$auth = $_SESSION['auth_customer'] ?? null;
$authUserId = is_array($auth)
    ? trim((string) ($auth['userId'] ?? $auth['customerId'] ?? ''))
    : '';

if ($authUserId === '') {
    header('Location: /login?next=' . rawurlencode('/area-cliente'), true, 302);
    exit;
}

$customerName = htmlspecialchars(trim((string) ($auth['name'] ?? 'Cliente')), ENT_QUOTES, 'UTF-8');
$customerEmail = htmlspecialchars(trim((string) ($auth['email'] ?? '')), ENT_QUOTES, 'UTF-8');
$initialRaw = preg_replace('/[^A-Za-z0-9]/', '', $customerName) ?? '';
$customerInitial = strtoupper(substr($initialRaw, 0, 1));
if ($customerInitial === '') {
    $customerInitial = 'C';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - DeFast</title>
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Raleway:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <link rel="stylesheet" href="/assets/css/area-cliente.css">
</head>
<body class="index-page antialiased text-gray-600">

    <header id="header" class="header d-flex align-items-center sticky-top">
        <?php include 'header.php'; ?>
    </header>


    <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div id="account-feedback" class="hidden rounded-md border px-4 py-3 text-sm"></div>
    </div>

    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row gap-8">
        <aside class="w-full md:w-64 flex-shrink-0">
            <nav class="space-y-1">
                <button type="button" id="tab-btn-subscription" class="js-tab-button tab-active w-full flex items-center px-3 py-2.5 text-sm rounded-md transition-colors" data-tab="subscription">
                    <svg class="flex-shrink-0 -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    Assinatura
                </button>
                <button type="button" id="tab-btn-billing" class="js-tab-button tab-inactive w-full flex items-center px-3 py-2.5 text-sm rounded-md transition-colors" data-tab="billing">
                    <svg class="flex-shrink-0 -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Histórico de cobranças
                </button>
                <button type="button" id="tab-btn-security" class="js-tab-button tab-inactive w-full flex items-center px-3 py-2.5 text-sm rounded-md transition-colors" data-tab="security">
                    <svg class="flex-shrink-0 -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    Segurança
                </button>
                <button type="button" id="tab-btn-support" class="js-tab-button tab-inactive w-full flex items-center px-3 py-2.5 text-sm rounded-md transition-colors" data-tab="support">
                    <svg class="flex-shrink-0 -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    Suporte
                </button>
                <button type="button" id="tab-btn-download" class="js-tab-button tab-inactive w-full flex items-center px-3 py-2.5 text-sm rounded-md transition-colors" data-tab="download">
                    <svg class="flex-shrink-0 -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download
                </button>
            </nav>
        </aside>

        <div class="flex-grow">
            <div id="tab-subscription" class="block space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gerenciar Assinatura</h1>
                    <p class="text-sm text-gray-500 mt-1">Olá, <?php echo $customerName; ?>. Gerencie seu plano e cobranças.</p>
                </div>

                <div id="trial-status-card" class="hidden border border-indigo-200 bg-indigo-50 rounded-xl p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div>
                            <p id="trial-status-title" class="text-sm font-semibold text-indigo-900">Teste grátis ativo</p>
                            <p id="trial-status-message" class="text-sm text-indigo-800 mt-1">Seu teste grátis termina em breve.</p>
                            <p id="trial-status-extra" class="text-xs text-indigo-700 mt-2"></p>
                        </div>
                        <span id="trial-days-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"></span>
                    </div>

                    <div id="trial-status-actions" class="hidden mt-4 space-y-3">
                        <a id="trial-boleto-link" href="#" target="_blank" rel="noopener noreferrer" class="hidden inline-flex items-center justify-center bg-white border border-indigo-200 text-indigo-800 hover:bg-indigo-100 font-medium py-2 px-3 rounded-md text-sm transition-colors w-full sm:w-auto">Abrir boleto da primeira cobrança</a>

                        <div id="trial-pix-wrapper" class="hidden">
                            <label for="trial-pix-payload" class="block text-xs font-medium text-indigo-800 mb-1">Código Pix Copia e Cola da primeira cobrança</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input id="trial-pix-payload" type="text" readonly class="w-full px-3 py-2 border border-indigo-200 rounded-md bg-white text-indigo-900 text-xs">
                                <button type="button" id="trial-copy-pix" class="bg-indigo-700 hover:bg-indigo-800 text-white font-medium py-2 px-3 rounded-md text-sm transition-colors whitespace-nowrap">Copiar Pix</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="active-plan-view" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <p id="subscription-card-title" class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Assinatura ativa</p>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div>
                                <div class="flex items-center space-x-3 mb-1">
                                    <h2 class="text-lg font-bold text-gray-900" id="current-plan-name">Plano Pro - Mensal</h2>
                                    <span id="subscription-status-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                                </div>
                                <p class="text-sm text-gray-500" id="current-plan-price">R$ 89,00 por mês</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p id="next-charge-label" class="text-sm font-medium text-gray-900">Próxima cobrança</p>
                                <p id="next-charge-date" class="text-sm text-gray-500">03 de Abril de 2026</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 id="payment-section-title" class="text-sm font-medium text-gray-900 mb-4">Método de pagamento padrão</h3>
                            <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div id="payment-method-brand" class="w-10 h-6 bg-white border border-gray-200 rounded flex items-center justify-center text-[10px] font-bold text-blue-800 mr-3 shadow-sm">VISA</div>
                                    <div>
                                        <p id="payment-method-summary" class="text-sm font-medium text-gray-900">Visa terminando em 4242</p>
                                        <p id="payment-method-extra" class="text-xs text-gray-500">Expira em 12/28</p>
                                        <a id="pending-charge-action-link" href="#" target="_blank" rel="noopener noreferrer" class="hidden text-xs text-brand-purple hover:text-brand-dark font-medium mt-1 inline-flex">Abrir cobrança</a>
                                    </div>
                                </div>
                                <button type="button" id="open-payment-method-modal-button" class="text-sm text-brand-purple hover:text-brand-purple font-medium transition-colors">Atualizar</button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button type="button" id="open-cancel-modal-button" class="js-open-modal text-sm text-red-600 hover:text-red-800 font-medium transition-colors order-2 sm:order-1 text-center sm:text-left" data-modal-id="cancel-modal">Cancelar assinatura</button>
                        <button type="button" id="open-upgrade-modal-button" class="js-open-modal bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md text-sm transition-colors shadow-sm order-1 sm:order-2 w-full sm:w-auto" data-modal-id="upgrade-modal">Alterar para Plano Anual</button>
                    </div>
                </div>

                <div id="no-plan-view" class="hidden bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden text-center p-8 sm:p-12">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Você não possui um plano ativo</h2>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">Retome seu acesso escolhendo um plano.</p>
                    <a href="/checkout" class="bg-gradient-to-r from-brand-purple to-brand-accent text-white font-medium py-2.5 px-6 rounded-md">Ir para checkout</a>
                </div>
            </div>

            <div id="tab-billing" class="hidden space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Histórico de Cobranças</h1>
                    <p class="text-sm text-gray-500 mt-1">Visualize seus pagamentos anteriores.</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden p-6">
                    <p id="billing-loading" class="text-sm text-gray-500">Carregando cobranças...</p>
                    <p id="billing-empty" class="hidden text-sm text-gray-500">Nenhuma cobrança encontrada para este cliente.</p>
                    <div id="billing-list" class="hidden divide-y divide-gray-200"></div>
                </div>
            </div>

            <div id="tab-security" class="hidden space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Segurança</h1>
                    <p class="text-sm text-gray-500 mt-1">Gerencie sua senha e sua sessão.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <form id="password-form" class="p-6 sm:p-8 space-y-6">
                        <div class="max-w-md">
                            <label for="current-password" class="block text-sm font-medium text-gray-700 mb-2">Senha atual</label>
                            <input type="password" id="current-password" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple sm:text-sm transition-shadow">
                        </div>

                        <div class="max-w-md border-t border-gray-100 pt-6">
                            <label for="new-password" class="block text-sm font-medium text-gray-700 mb-2">Nova senha</label>
                            <input type="password" id="new-password" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple sm:text-sm transition-shadow mb-4">

                            <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">Confirmar nova senha</label>
                            <input type="password" id="confirm-password" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple sm:text-sm transition-shadow">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-medium py-2.5 px-4 rounded-md transition-colors shadow-sm text-sm">Atualizar senha</button>
                        </div>

                        <p id="password-success" class="hidden text-sm text-green-600 font-medium mt-3">Senha atualizada com sucesso!</p>
                    </form>
                </div>
            </div>

            <!-- Tab Support -->
            <div id="tab-support" class="hidden space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Suporte</h1>
                    <p class="text-sm text-gray-500 mt-1">Precisa de ajuda? Entre em contato ou participe da nossa comunidade.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Email Card -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 flex flex-col items-start">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">E-mail de Suporte</h3>
                        <p class="text-sm text-gray-500 mb-4 flex-grow">Envie suas dúvidas, sugestões ou relate problemas. Nossa equipe responderá o mais rápido possível.</p>
                        <a href="mailto:suporte@defast.com.br" class="text-brand-purple hover:text-brand-dark font-medium text-sm border-b border-transparent hover:border-brand-purple transition-colors break-all">suporte@defast.com.br</a>
                    </div>

                    <!-- Telegram Card -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 flex flex-col items-start">
                        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mb-4">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.888-.662 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Grupo no Telegram</h3>
                        <p class="text-sm text-gray-500 mb-4 flex-grow">Junte-se ao nosso grupo exclusivo e tire dúvidas direto com os desenvolvedores.</p>
                        <a href="https://t.me/+lj9zMTyIcRFkYTNh" target="_blank" rel="noopener noreferrer" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md text-sm transition-colors shadow-sm inline-flex items-center gap-2">Entrar no Grupo</a>
                    </div>
                </div>

            </div>

            <div id="tab-download" class="hidden space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Download</h1>
                    <p class="text-sm text-gray-500 mt-1">Baixe a versão mais recente do DeFast.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 sm:p-8 text-center">
                        <i class="bi bi-cloud-arrow-down-fill display-1 mb-4 d-block" style="color: var(--brand-purple);"></i>
                        <h2 class="text-xl font-bold text-gray-900 mb-1">Versão <?php echo htmlspecialchars($defast_download_version, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="text-gray-500 mb-6"><?php echo htmlspecialchars($defast_download_notes, ENT_QUOTES, 'UTF-8'); ?></p>
                        <a href="<?php echo htmlspecialchars($defast_download_url, ENT_QUOTES, 'UTF-8'); ?>"
                           class="px-5 py-3 rounded-pill text-white d-inline-flex align-items-center gap-2 btn-download">
                            <i class="bi bi-windows"></i> Baixar Instalador
                        </a>
                        <p class="mt-3 small text-muted"><?php echo htmlspecialchars($defast_download_size, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="cancel-modal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden transform transition-all">
            <div class="p-6 sm:p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Cancelar assinatura?</h3>
                <p class="text-sm text-gray-500 mb-6">Você perderá o acesso premium.</p>
                <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button type="button" class="js-close-modal bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md text-sm transition-colors order-2 sm:order-1" data-modal-id="cancel-modal">Manter assinatura</button>
                    <button type="button" id="confirm-cancel-button" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md text-sm transition-colors order-1 sm:order-2 shadow-sm">Sim, cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="upgrade-modal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden transform transition-all">
            <div class="p-6 sm:p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Mudar para Plano Anual</h3>
                <p class="text-sm text-gray-500 mb-6">Confirme para migrar para o plano anual.</p>
                <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button type="button" class="js-close-modal bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md text-sm transition-colors order-2 sm:order-1" data-modal-id="upgrade-modal">Voltar</button>
                    <button type="button" id="confirm-upgrade-button" class="bg-gradient-to-r from-brand-purple to-brand-accent text-white font-medium py-2 px-4 rounded-md text-sm transition-colors order-1 sm:order-2 shadow-sm">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="payment-method-modal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden transform transition-all">
            <form id="payment-method-form" class="p-6 sm:p-8 space-y-4" novalidate>
                <h3 class="text-xl font-bold text-gray-900">Atualizar cartão da assinatura</h3>
                <p class="text-sm text-gray-500">Os dados abaixo serão enviados com segurança para o Asaas.</p>

                <div>
                    <label for="card-holder-name" class="block text-sm font-medium text-gray-700 mb-1">Nome no cartão</label>
                    <input id="card-holder-name" type="text" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label for="card-number-update" class="block text-sm font-medium text-gray-700 mb-1">Número do cartão</label>
                        <input id="card-number-update" type="text" inputmode="numeric" maxlength="23" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                    </div>
                    <div>
                        <label for="card-expiry-update" class="block text-sm font-medium text-gray-700 mb-1">Validade (MM/AA)</label>
                        <input id="card-expiry-update" type="text" inputmode="numeric" maxlength="5" placeholder="12/30" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                    </div>
                    <div>
                        <label for="card-cvc-update" class="block text-sm font-medium text-gray-700 mb-1">CVC</label>
                        <input id="card-cvc-update" type="text" inputmode="numeric" maxlength="4" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                    </div>
                    <div>
                        <label for="postal-code-update" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                        <input id="postal-code-update" type="text" inputmode="numeric" maxlength="9" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                    </div>
                    <div>
                        <label for="address-number-update" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                        <input id="address-number-update" type="text" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="phone-update" class="block text-sm font-medium text-gray-700 mb-1">Telefone com DDD</label>
                        <input id="phone-update" type="text" inputmode="numeric" maxlength="15" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                    </div>
                </div>

                <p id="payment-method-error" class="hidden text-sm text-red-600"></p>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2">
                    <button type="button" class="js-close-modal bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md text-sm transition-colors order-2 sm:order-1" data-modal-id="payment-method-modal">Cancelar</button>
                    <button type="submit" id="submit-payment-method-button" class="bg-brand-purple hover:bg-brand-dark text-white font-medium py-2 px-4 rounded-md text-sm transition-colors order-1 sm:order-2 shadow-sm">Salvar cartão</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="/assets/js/area-cliente.js"></script>
</body>
</html>
