<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Checkout - DeFast</title>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Raleway:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/checkout.css">
</head>
<body class="antialiased text-gray-600 min-h-screen flex items-center justify-center p-4 sm:p-8">

    <!-- Main Container -->
    <div class="max-w-5xl w-full min-h-[600px] bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row transition-all duration-300">
        
        
        <!-- Left Side: Order Summary (oculto durante modo trial) -->
        <div id="left-panel" class="w-full md:w-5/12 bg-gray-50 p-8 lg:p-12 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 relative transition-all duration-500 hidden">
            
            <!-- Back Button -->
            <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-800 transition-colors mb-8 group w-fit">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar
            </button>

            <!-- Product Info -->
            <div class="mt-4 flex-grow">
                <div id="plan-badge" class="text-sm font-semibold tracking-wider text-brand-purple uppercase mb-2 transition-colors">Assinatura</div>
                <h1 id="plan-name" class="text-2xl font-bold text-gray-900 mb-2">Plano </h1>
                <p id="plan-description" class="text-gray-500 mb-6 leading-relaxed">Descrição.</p>
                
                <!-- Plan Selection Options — rendered dynamically by checkout.js -->
                <div id="plans-container" class="mb-8 space-y-3">
                    <!-- Plans will be injected here by hydratePlans() -->
                </div>

                <!-- Features/Items -->
                <ul id="plan-features" class="space-y-4 mb-8">
                    <!-- Rendered dynamically by selectPlanCard() -->
                </ul>
            </div>

            <!-- Total -->
            <div class="pt-6 border-t border-gray-200 mt-auto">
                <div class="flex justify-between items-center text-gray-900 font-medium">
                    <span>Total a pagar hoje</span>
                    <span id="total-price" class="text-xl font-bold transition-all">R$ 0,00</span>
                </div>
                <p id="total-price-note" class="text-xs text-gray-400 italic font-normal mt-1 text-right hidden"></p>
            </div>
        </div>

        <!-- Right Side: Forms -->
        <div class="w-full md:w-full p-8 lg:p-12 bg-white flex flex-col justify-center relative">

            <!-- Toast Notification -->
            <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3 pointer-events-none max-w-[380px]"></div>
            
            <!-- Step 1: Signup Form -->
            <form id="signup-form" class="w-full max-w-md mx-auto">
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Crie sua conta</h2>
                <p class="text-sm text-gray-500 mb-6">Crie sua conta e comece a usar o DeFast gratuitamente por 30 dias.</p>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                    <input type="email" id="email" placeholder="seu@email.com" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple sm:text-sm transition-shadow">
                </div>


                <div class="mb-6">
                    <label for="mobile-phone" class="block text-sm font-medium text-gray-700 mb-2">Telefone com DDD</label>
                    <input type="tel" id="mobile-phone" placeholder="(11) 99999-9999" required maxlength="15"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple sm:text-sm transition-shadow">
                </div>

                <div class="mb-4">
                    <label for="account-password" class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                    <input type="password" id="account-password" placeholder="Minimo 6 caracteres" required minlength="6"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple sm:text-sm transition-shadow">
                </div>

                <div class="mb-6">
                    <label for="account-password-confirm" class="block text-sm font-medium text-gray-700 mb-2">Confirme sua senha</label>
                    <input type="password" id="account-password-confirm" placeholder="Repita sua senha" required minlength="6"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-brand-purple focus:border-brand-purple sm:text-sm transition-shadow">
                    <p class="text-xs text-gray-500 mt-2">Use ao menos 6 caracteres.</p>
                </div>

                <button type="submit" class="w-full bg-brand-purple hover:opacity-90 text-white font-medium py-3 px-4 rounded-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-900 flex items-center justify-center">
                    Criar conta e começar teste grátis
                </button>
                <p class="text-xs text-gray-400 text-center mt-3">30 dias grátis · Sem cartão de crédito · Cancele quando quiser</p>
            </form>

            <!-- Step 2: Payment Form (desabilitado durante modo trial) -->
            <form id="payment-form" class="w-full max-w-md mx-auto hidden">
                <div class="flex items-center mb-6">
                    <button type="button" id="btn-back-step1" class="text-gray-400 hover:text-gray-600 mr-3 transition-colors" title="Voltar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <h2 id="payment-step-title" class="text-xl font-semibold text-gray-900">Pagamento</h2>
                </div>

                <!-- Seção de assinatura recorrente (MP Preapproval redirect) -->
                <div id="mp-subscription-section" class="hidden mb-6">
                    <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 mb-4 flex items-start gap-3">
                        <svg class="h-5 w-5 text-sky-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-sky-800">Você será redirecionado para o <strong>Mercado Pago</strong> para autorizar a assinatura recorrente com segurança.</p>
                    </div>
                </div>

                <!-- Payment Brick do Mercado Pago (planos únicos) -->
                <div id="paymentBrick_container" class="hidden mb-6"></div>

                <!-- Submit Button (assinatura recorrente) -->
                <button type="submit" id="submit-button" class="hidden w-full bg-brand-purple hover:opacity-90 hover:scale-[1.02] transition-all duration-200 text-white font-medium py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-900 flex items-center justify-center">
                    <span id="button-text">Assinar via Mercado Pago</span>
                </button>

                <!-- Secure Payment Footer -->
                <div class="mt-6 flex items-center justify-center text-xs text-gray-500 space-x-1">
                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Pagamento seguro via <strong>Mercado Pago</strong></span>
                </div>

            </form>

            <div id="validation-step" class="hidden w-full max-w-md mx-auto">
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Validar assinatura</h2>
                <p class="text-sm text-gray-500 mb-6">Confira os dados abaixo antes de confirmar.</p>

                <div class="bg-gray-50 border border-gray-200 rounded-xl divide-y divide-gray-200">
                    <div class="p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">E-mail da conta</p>
                        <p id="validation-email" class="text-sm font-semibold text-gray-900 mt-1">--</p>
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Plano escolhido</p>
                        <p id="validation-plan" class="text-sm font-semibold text-gray-900 mt-1">--</p>
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Forma de pagamento</p>
                        <p id="validation-method" class="text-sm font-semibold text-gray-900 mt-1">--</p>
                    </div>
                </div>

                <p id="validation-note" class="text-xs text-gray-500 mt-4"></p>

                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <button type="button" id="validation-back-button" class="w-full sm:w-auto flex-1 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-3 px-4 rounded-md transition-colors">Voltar e editar</button>
                    <button type="button" id="validation-confirm-button" class="w-full sm:w-auto flex-1 bg-brand-purple hover:opacity-90 text-white font-medium py-3 px-4 rounded-md transition-all duration-200">Assinar</button>
                </div>
            </div>

            <!-- Success Message (Hidden by default) -->
            <div id="success-message" class="hidden w-full flex-col items-center justify-center text-center py-16 px-8">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6 text-green-500 mx-auto">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 id="success-title" class="text-3xl font-bold text-gray-900 mb-3">Conta Criada com Sucesso!</h2>
                <p id="success-desc" class="text-gray-500 mb-4 max-w-sm mx-auto text-base">Seu teste grátis de 30 dias foi ativado. Aproveite todas as funcionalidades do DeFast!</p>
                <div id="success-extra" class="w-full max-w-sm mx-auto mb-8"></div>
                <a href="/login?next=%2Farea-cliente" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-purple to-brand-accent text-white font-semibold px-8 py-3 rounded-lg hover:opacity-90 hover:scale-[1.02] transition-all duration-200 shadow-md">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span id="success-dashboard-link-text">Ir para a minha conta</span>
                </a>
            </div>

        </div>
    </div>

    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="/assets/js/checkout.js"></script>
</body>
</html>
