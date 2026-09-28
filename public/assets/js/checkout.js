// Form Steps Logic
const signupForm = document.getElementById('signup-form');
const paymentForm = document.getElementById('payment-form');
const btnBackStep1 = document.getElementById('btn-back-step1');
const signupSubmitButton = signupForm ? signupForm.querySelector('button[type="submit"]') : null;

let csrfToken = '';
let checkoutAccountRegistered = false;
let checkoutAccountEmail = '';
let checkoutAccountName = '';
let pendingValidationIntent = null;

async function fetchCsrfToken() {
    try {
        const response = await fetch('/api/auth/csrf', { credentials: 'same-origin' });
        if (!response.ok) {
            csrfToken = '';
            return '';
        }

        const data = await response.json().catch(() => ({}));
        csrfToken = String(data?.csrfToken || '');
        return csrfToken;
    } catch (error) {
        csrfToken = '';
        return '';
    }
}

async function registerCheckoutAccount(payload) {
    if (!csrfToken) {
        await fetchCsrfToken();
    }

    const headers = { 'Content-Type': 'application/json' };
    if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
    }

    let response = await fetch('/api/auth/register', {
        method: 'POST',
        headers,
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    if (response.status === 403) {
        await fetchCsrfToken();
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }

        response = await fetch('/api/auth/register', {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
    }

    const data = await response.json().catch(() => ({}));
    if (data?.csrfToken) {
        csrfToken = String(data.csrfToken);
    }

    return { ok: response.ok, data };
}

// Safely escape HTML special characters to prevent XSS
function escHtml(str) {
    const el = document.createElement('span');
    el.textContent = String(str ?? '');
    return el.innerHTML;
}

function buildCheckoutName(email) {
    const localPart = String(email || '').split('@')[0] || '';
    const normalized = localPart
        .replace(/[^a-zA-Z0-9._ -]/g, ' ')
        .replace(/[._-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    if (!normalized) {
        return 'Cliente DeFast';
    }

    const words = normalized
        .split(' ')
        .filter(Boolean)
        .slice(0, 3)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1));

    const candidate = words.join(' ').trim();
    return candidate.length >= 3 ? candidate : 'Cliente DeFast';
}

function formatPhoneForInput(value) {
    let digits = String(value || '').replace(/\D/g, '');
    digits = digits.substring(0, 11);

    if (digits.length === 11) {
        return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }

    if (digits.length >= 7) {
        return digits.replace(/(\d{2})(\d{4,5})(\d{0,4})/, '($1) $2-$3');
    }

    if (digits.length >= 3) {
        return digits.replace(/(\d{2})(\d{0,5})/, '($1) $2');
    }

    if (digits.length > 0) {
        return '(' + digits;
    }

    return '';
}

async function ensureCheckoutAuthenticatedSession() {
    try {
        const response = await fetch('/api/auth/me', { credentials: 'same-origin' });
        if (!response.ok) {
            return false;
        }

        const data = await response.json().catch(() => ({}));
        const customer = data?.customer || {};
        const email = String(customer.email || '').trim();

        if (!email) {
            return false;
        }

        checkoutAccountRegistered = true;
        checkoutAccountEmail = email.toLowerCase();
        checkoutAccountName = String(customer.name || '').trim() || buildCheckoutName(email);

        const fullNameInput = document.getElementById('full-name');
        if (fullNameInput && checkoutAccountName) {
            fullNameInput.value = checkoutAccountName;
        }

        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.value = email;
            emailInput.readOnly = true;
            emailInput.classList.add('bg-gray-50', 'cursor-not-allowed');
        }

        const phoneDigits = String(customer.phone || '').replace(/\D/g, '');
        const phoneInput = document.getElementById('mobile-phone');
        if (phoneInput && phoneDigits !== '') {
            phoneInput.value = formatPhoneForInput(phoneDigits);
        }

        if (data?.csrfToken) {
            csrfToken = String(data.csrfToken);
        }

        if (signupForm && paymentForm) {
            signupForm.classList.add('hidden');
            paymentForm.classList.remove('hidden');
        }

        if (btnBackStep1) {
            btnBackStep1.classList.add('hidden');
        }

        return true;
    } catch (error) {
        return false;
    }
}

// Toast Notification System
function showToast(message, type = 'error') {
    const container = document.getElementById('toast-container');
    const id = 'toast-' + Date.now();
    const isError = type === 'error';

    const colors = isError
        ? 'bg-red-50 border-red-200 text-red-800'
        : 'bg-green-50 border-green-200 text-green-800';
    const iconColor = isError ? 'text-red-500' : 'text-green-500';
    const icon = isError
        ? `<svg class="h-5 w-5 ${iconColor} flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
        : `<svg class="h-5 w-5 ${iconColor} flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;

    const toast = document.createElement('div');
    toast.id = id;
    toast.className = `pointer-events-auto flex items-start gap-3 p-4 rounded-lg border shadow-lg ${colors} transition-all duration-300 opacity-0 translate-y-2`;

    const iconEl = document.createElement('span');
    iconEl.innerHTML = icon;

    const messageEl = document.createElement('p');
    messageEl.className = 'text-sm font-medium flex-1';
    messageEl.textContent = String(message || 'Erro inesperado.');

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = `${iconColor} hover:opacity-70 transition-opacity flex-shrink-0 mt-0.5`;
    closeButton.innerHTML = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
    closeButton.addEventListener('click', () => toast.remove());

    toast.appendChild(iconEl.firstElementChild || iconEl);
    toast.appendChild(messageEl);
    toast.appendChild(closeButton);

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', 'translate-y-2');
        });
    });

    // Auto-dismiss after 5s
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 5000);
}

signupForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const email = String(document.getElementById('email')?.value || '').trim();
    const name = buildCheckoutName(email);
    const phone = String(document.getElementById('mobile-phone')?.value || '').replace(/\D/g, '');
    const password = String(document.getElementById('account-password')?.value || '');
    const passwordConfirm = String(document.getElementById('account-password-confirm')?.value || '');

    if (password !== passwordConfirm) {
        showToast('As senhas nao conferem.');
        return;
    }

    if (password.length < 6) {
        showToast('A senha precisa ter ao menos 6 caracteres.');
        return;
    }

    if (signupSubmitButton) {
        signupSubmitButton.disabled = true;
        signupSubmitButton.classList.add('opacity-80', 'cursor-not-allowed');
        signupSubmitButton.textContent = 'Criando conta...';
    }

    try {
        const registration = await registerCheckoutAccount({
            email,
            name,
            phone,
            password,
        });

        if (!registration.ok) {
            const message = registration?.data?.error || registration?.data?.details?.[0]?.description || 'Nao foi possivel criar a conta.';
            showToast(message);
            return;
        }

        checkoutAccountRegistered = true;
        checkoutAccountEmail = email.toLowerCase();
        checkoutAccountName = String(registration?.data?.customer?.name || name || '').trim() || buildCheckoutName(email);

        // Mostrar etapa de pagamento
        signupForm.classList.add('hidden');
        const leftPanel = document.getElementById('left-panel');
        if (leftPanel) leftPanel.classList.remove('hidden');
        if (paymentForm) paymentForm.classList.remove('hidden');
        if (btnBackStep1) btnBackStep1.classList.remove('hidden');
        updatePaymentStepUi();
    } catch (error) {
        showToast('Erro de comunicacao ao criar conta. Tente novamente.');
    } finally {
        if (signupSubmitButton) {
            signupSubmitButton.disabled = false;
            signupSubmitButton.classList.remove('opacity-80', 'cursor-not-allowed');
            signupSubmitButton.textContent = 'Criar conta e começar teste grátis';
        }
    }
});

btnBackStep1.addEventListener('click', function() {
    paymentForm.classList.add('hidden');
    signupForm.classList.remove('hidden');
});

['email', 'full-name', 'account-password', 'account-password-confirm'].forEach((fieldId) => {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.addEventListener('input', () => {
        checkoutAccountRegistered = false;
        checkoutAccountName = '';
    });
});

document.getElementById('mobile-phone').addEventListener('input', function (e) {
    e.target.value = formatPhoneForInput(e.target.value);
});

// MP — referencias dos elementos de pagamento
const btnText = document.getElementById('button-text');
const paymentStepTitle = document.getElementById('payment-step-title');
const validationStep = document.getElementById('validation-step');
const validationEmailEl = document.getElementById('validation-email');
const validationPlanEl = document.getElementById('validation-plan');
const validationMethodEl = document.getElementById('validation-method');
const validationNoteEl = document.getElementById('validation-note');
const validationBackButton = document.getElementById('validation-back-button');
const validationConfirmButton = document.getElementById('validation-confirm-button');
// Elementos legados (mantidos para compatibilidade com showResultUI / resetForm)
const cardContent = document.getElementById('card-content') || document.createElement('div');
const pixContent = document.getElementById('pix-content') || document.createElement('div');
const boletoContent = document.getElementById('boleto-content') || document.createElement('div');
const cardInputs = [];

// Plan Selection — rendered dynamically from /api/plans
const totalPrice = document.getElementById('total-price');
const planBadge = document.getElementById('plan-badge');

// Tracks the currently selected plan object
let selectedPlan = null;
// Instância do Payment Brick do MP (para poder destruir e recriar)
let mpBrickController = null;

function isOnetimePlan() {
    return String(selectedPlan?.cycle || '').toUpperCase() === 'ONETIME';
}

function isSubscriptionPlan() {
    return !isOnetimePlan();
}

// Método sempre "mp" agora que o Brick controla a escolha internamente
function selectedPaymentMethod() {
    return 'mp';
}

function updatePaymentStepUi() {
    const subSection = document.getElementById('mp-subscription-section');
    const brickContainer = document.getElementById('paymentBrick_container');
    const submitBtn = document.getElementById('submit-button');

    if (isSubscriptionPlan()) {
        if (subSection) subSection.classList.remove('hidden');
        if (brickContainer) brickContainer.classList.add('hidden');
        if (submitBtn) submitBtn.classList.remove('hidden');
        if (paymentStepTitle) paymentStepTitle.textContent = 'Assinar plano';
        if (btnText) btnText.textContent = 'Assinar via Mercado Pago';
    } else {
        if (subSection) subSection.classList.add('hidden');
        if (submitBtn) submitBtn.classList.add('hidden');
        if (brickContainer) brickContainer.classList.remove('hidden');
        if (paymentStepTitle) paymentStepTitle.textContent = 'Pagamento';
        renderMpPaymentBrick();
    }
}

function paymentMethodSummaryLabel() {
    return isOnetimePlan() ? 'Mercado Pago (unico)' : 'Mercado Pago (recorrente)';
}

function validationConfirmLabel() {
    return isOnetimePlan() ? 'Confirmar pagamento' : 'Assinar';
}

function planSummaryLabel(plan) {
    if (!plan) {
        return '--';
    }

    const planLabel = String(plan.display?.label || plan.name || 'Plano DeFast').trim();
    const amount = Number(plan.price || 0);
    const formattedAmount = formatBRL(amount);
    const cycle = String(plan.cycle || '').toUpperCase();

    if (cycle === 'ONETIME') {
        return `${planLabel} (${formattedAmount} pagamento unico)`;
    }

    return `${planLabel} (${formattedAmount})`;
}

function setFocusedLayout(active) {
    const leftPanel = document.getElementById('left-panel');
    if (leftPanel) {
        leftPanel.style.display = active ? 'none' : '';
    }

    const rightPanel = successMessage?.parentElement;
    if (!rightPanel) {
        return;
    }

    if (active) {
        rightPanel.classList.remove('md:w-7/12');
        rightPanel.classList.add('md:w-full');
    } else {
        rightPanel.classList.remove('md:w-full');
        rightPanel.classList.add('md:w-7/12');
    }
}

function hideValidationStep(showPaymentForm = true) {
    if (validationStep) {
        validationStep.classList.add('hidden');
    }

    if (validationBackButton) {
        validationBackButton.disabled = false;
        validationBackButton.classList.remove('opacity-80', 'cursor-not-allowed');
    }

    if (validationConfirmButton) {
        validationConfirmButton.disabled = false;
        validationConfirmButton.classList.remove('opacity-80', 'cursor-not-allowed');
        validationConfirmButton.textContent = validationConfirmLabel(selectedPaymentMethod());
    }

    pendingValidationIntent = null;

    if (showPaymentForm && paymentForm) {
        paymentForm.classList.remove('hidden');
    }

    setFocusedLayout(false);
    updatePaymentStepUi();
}

function showValidationStep(intent) {
    if (!validationStep) {
        return;
    }

    pendingValidationIntent = intent;

    if (validationEmailEl) {
        validationEmailEl.textContent = intent.baseData.email || '--';
    }

    if (validationPlanEl) {
        validationPlanEl.textContent = planSummaryLabel(selectedPlan);
    }

    if (validationMethodEl) {
        validationMethodEl.textContent = paymentMethodSummaryLabel(intent.selectedMethod);
    }

    if (validationNoteEl) {
        const trialDays = Number(selectedPlan?.trialDays || 0);
        const isOnetime = isOnetimePlan();

        if (isOnetime) {
            validationNoteEl.textContent = 'O plano sera ativado somente apos a confirmacao do pagamento via Mercado Pago.';
        } else if (trialDays > 0) {
            validationNoteEl.textContent = `A conta ja esta criada e a primeira cobranca sera feita em ate ${trialDays} dias.`;
        } else {
            validationNoteEl.textContent = 'A conta ja esta criada. Ao confirmar, sua assinatura sera processada.';
        }
    }

    if (paymentForm) {
        paymentForm.classList.add('hidden');
    }

    if (validationConfirmButton) {
        validationConfirmButton.textContent = validationConfirmLabel(intent.selectedMethod);
    }

    validationStep.classList.remove('hidden');
    setFocusedLayout(true);
}

function buildCheckoutIntent() {
    const emailStr = document.getElementById('email')?.value || '';
    const normalizedEmail = String(emailStr).trim().toLowerCase();

    if (!checkoutAccountRegistered || checkoutAccountEmail === '' || checkoutAccountEmail !== normalizedEmail) {
        showToast('Crie sua conta nesta sessao antes de continuar para o pagamento.');
        return null;
    }

    return {
        selectedMethod: 'mp',
        planId: selectedPlan?.id || 'annual',
        baseData: { email: checkoutAccountEmail },
    };
}

function generateIdempotencyKey(method, planId, documentId) {
    const seed = `${method}|${planId || 'plan'}|${documentId || 'doc'}|${Date.now()}|${Math.random()}`;
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return `${method}-${window.crypto.randomUUID()}`;
    }

    return btoa(seed).replace(/[^a-zA-Z0-9]/g, '').substring(0, 40);
}

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function selectPlanCard(planId, allPlans) {
    selectedPlan = allPlans.find(p => p.id === planId) || allPlans[0];

    // Update all label styles
    allPlans.forEach(p => {
        const lbl = document.getElementById('plan-label-' + p.id);
        if (!lbl) return;
        if (p.id === selectedPlan.id) {
            lbl.classList.add('border-brand-purple', 'bg-brand-light');
            lbl.classList.remove('border-gray-200', 'bg-white', 'hover:border-gray-300');
        } else {
            lbl.classList.remove('border-brand-purple', 'bg-brand-light');
            lbl.classList.add('border-gray-200', 'bg-white', 'hover:border-gray-300');
        }
    });

    // Update plan title and description
    const planNameEl = document.getElementById('plan-name');
    const planDescEl = document.getElementById('plan-description');
    if (planNameEl) planNameEl.textContent = selectedPlan.name;
    if (planDescEl) planDescEl.textContent = selectedPlan.description;

    // Update total price display
    const priceNote = document.getElementById('total-price-note');
    const trialDays = selectedPlan.trialDays ?? 0;
    if (trialDays > 0) {
        // Subscription with free trial — show R$0 today
        if (totalPrice) totalPrice.textContent = formatBRL(0);
        if (priceNote) {
            priceNote.textContent = `Assinatura será cobrada em ${trialDays} dias`;
            priceNote.classList.remove('hidden');
        }
    } else {
        // One-time or no trial — show real price
        if (totalPrice) totalPrice.textContent = formatBRL(selectedPlan.price);
        if (priceNote) priceNote.classList.add('hidden');
    }

    // Update badge
    if (planBadge) {
        planBadge.textContent = selectedPlan.display?.badge || selectedPlan.name;
        const isSub = selectedPlan.cycle !== 'ONETIME';
        planBadge.classList.toggle('text-brand-purple', isSub);
        planBadge.classList.toggle('text-gray-500', !isSub);
    }

    updatePaymentStepUi();

    // Update feature list — use DOM methods to avoid XSS
    const featuresList = document.getElementById('plan-features');
    if (featuresList && Array.isArray(selectedPlan.features)) {
        featuresList.innerHTML = '';
        selectedPlan.features.forEach(f => {
            const li = document.createElement('li');
            li.className = 'flex items-start';
            li.innerHTML = `<svg class="h-5 w-5 text-brand-purple mr-3 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`;
            const span = document.createElement('span');
            span.className = 'text-sm text-gray-700';
            span.textContent = f; // safe: textContent never executes HTML
            li.appendChild(span);
            featuresList.appendChild(li);
        });
    }
}

async function hydratePlans() {
    const container = document.getElementById('plans-container');
    if (!container) return;

    try {
        const res = await fetch('/api/plans');
        if (!res.ok) throw new Error('API indisponível');
        const plans = await res.json();
        if (!plans || plans.length === 0) return;

        // Render plan cards — escape dynamic values to prevent XSS
        container.innerHTML = plans.map((plan, index) => {
            const priceFormatted = formatBRL(plan.price);
            const isFirst = index === 0;
            const discountBadge = plan.display?.discountBadge
                ? `<span class="block text-[10px] text-green-600 font-bold mt-0.5 uppercase tracking-wide">${escHtml(plan.display.discountBadge)}</span>`
                : '';

            return `
            <label id="plan-label-${escHtml(plan.id)}"
                class="flex items-center justify-between p-4 border-2 ${isFirst ? 'border-brand-purple bg-brand-light' : 'border-gray-200 bg-white hover:border-gray-300'} rounded-xl cursor-pointer transition-all">
                <div class="flex items-center">
                    <input type="radio" name="plan-type" value="${escHtml(plan.id)}" ${isFirst ? 'checked' : ''}
                        class="h-4 w-4 text-brand-purple focus:ring-brand-purple border-gray-300 cursor-pointer">
                    <div class="ml-3">
                        <span class="block text-sm font-bold text-gray-900">${escHtml(plan.display?.label || plan.name)}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">${escHtml(plan.display?.sublabel || plan.description)}</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="block text-sm font-bold text-gray-900">${priceFormatted}</span>
                    ${discountBadge}
                </div>
            </label>`;
        }).join('');

        // Wire up change events
        container.querySelectorAll('input[name="plan-type"]').forEach(radio => {
            radio.addEventListener('change', () => selectPlanCard(radio.value, plans));
        });

        // Set initial selection
        selectPlanCard(plans[0].id, plans);

        // Update plan name/description from the first plan
        const planNameEl = document.getElementById('plan-name');
        const planDescEl = document.getElementById('plan-description');
        if (planNameEl && plans[0].name) planNameEl.textContent = plans[0].name;
        if (planDescEl && plans[0].description) planDescEl.textContent = plans[0].description;

    } catch (e) {
        console.warn('[hydratePlans] Erro ao carregar planos:', e.message);
    }
}



// API Integration Logic (Ported from public/main.js)
const form = document.getElementById('payment-form');
const submitBtn = document.getElementById('submit-button');
const successMessage = document.getElementById('success-message');
const successTitle = document.getElementById('success-title');
const successDesc = document.getElementById('success-desc');
const successExtra = document.getElementById('success-extra');
const successDashboardLinkText = document.getElementById('success-dashboard-link-text');

function updateSuccessCtaLabel(isSubscription) {
    if (!successDashboardLinkText) {
        return;
    }

    successDashboardLinkText.textContent = isSubscription
        ? 'Ir para dashboard'
        : 'Ir para a minha conta';
}

if (!successExtra) {
    const container = successDesc.parentNode;
    const el = document.createElement('div');
    el.id = 'success-extra';
    el.className = 'w-full mb-8 flex flex-col items-center';
    if (container) {
        container.appendChild(el);
    }
}

async function initCheckoutPage() {
    await fetchCsrfToken();

    // Tratar retorno após autorização MP (assinatura recorrente)
    const params = new URLSearchParams(window.location.search);
    if (params.get('status') === 'mp_success' || params.get('status') === 'approved') {
        const leftPanel = document.getElementById('left-panel');
        if (leftPanel) leftPanel.classList.remove('hidden');
        await hydratePlans();
        showResultUI(
            'Assinatura confirmada!',
            'Sua assinatura foi ativada com sucesso. Bem-vindo ao DeFast!',
            ''
        );
        updateSuccessCtaLabel(true);
        return;
    }

    const alreadyAuth = await ensureCheckoutAuthenticatedSession();
    await hydratePlans();
    if (!alreadyAuth) {
        updatePaymentStepUi();
    }
}

initCheckoutPage();

async function handleMpSubscriptionRedirect(processingButton, originalText) {
    try {
        if (!csrfToken) await fetchCsrfToken();

        const res = await fetch('/api/mp/subscription', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ planId: selectedPlan?.id || 'annual' }),
        });

        const data = await res.json().catch(() => ({}));
        if (data?.csrfToken) csrfToken = String(data.csrfToken);

        if (!res.ok) {
            const msg = data?.error || 'Erro ao iniciar assinatura.';
            showToast(msg);
            if (processingButton) {
                processingButton.disabled = false;
                processingButton.classList.remove('opacity-80', 'cursor-not-allowed');
                processingButton.textContent = originalText;
            }
            if (validationBackButton) {
                validationBackButton.disabled = false;
                validationBackButton.classList.remove('opacity-80', 'cursor-not-allowed');
            }
            return;
        }

        const initPoint = String(data?.initPoint || '').trim();
        if (!initPoint) {
            showToast('Link de assinatura não encontrado.');
            return;
        }

        window.location.href = initPoint;
    } catch (err) {
        showToast('Erro de comunicação. Tente novamente.');
        if (processingButton) {
            processingButton.disabled = false;
            processingButton.classList.remove('opacity-80', 'cursor-not-allowed');
            processingButton.textContent = originalText;
        }
        if (validationBackButton) {
            validationBackButton.disabled = false;
            validationBackButton.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }
}

async function renderMpPaymentBrick() {
    if (mpBrickController) {
        try { await mpBrickController.unmount(); } catch (_) {}
        mpBrickController = null;
    }

    const container = document.getElementById('paymentBrick_container');
    if (!container) return;
    while (container.firstChild) container.removeChild(container.firstChild);

    try {
        const pkRes = await fetch('/api/mp/public-key', { credentials: 'same-origin' });
        if (!pkRes.ok) { showToast('Erro ao inicializar pagamento.'); return; }
        const pkData = await pkRes.json().catch(() => ({}));
        const publicKey = String(pkData?.publicKey || '');
        if (!publicKey) { showToast('Chave de pagamento não configurada.'); return; }

        const mp = new MercadoPago(publicKey, { locale: 'pt-BR' });
        const bricks = mp.bricks();
        const amount = Number(selectedPlan?.price || 0);

        mpBrickController = await bricks.create('payment', 'paymentBrick_container', {
            initialization: {
                amount,
                payer: { email: checkoutAccountEmail || undefined },
            },
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    debitCard: 'all',
                    ticket: 'all',
                    bankTransfer: 'all',
                    mercadoPago: 'all',
                },
            },
            callbacks: {
                onReady: () => {},
                onSubmit: async ({ formData }) => {
                    try {
                        if (!csrfToken) await fetchCsrfToken();
                        const res = await fetch('/api/mp/payment', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ formData, planId: selectedPlan?.id || 'onetime' }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (data?.csrfToken) csrfToken = String(data.csrfToken);

                        if (!res.ok) {
                            const msg = data?.error || 'Erro ao processar pagamento.';
                            showToast(msg);
                            return;
                        }

                        showResultUI(
                            'Pagamento aprovado!',
                            'Seu plano foi ativado. Bem-vindo ao DeFast!',
                            ''
                        );
                        updateSuccessCtaLabel(false);
                    } catch (err) {
                        showToast('Erro de comunicação com o servidor. Tente novamente.');
                    }
                },
                onError: (error) => {
                    console.error('[MP Brick] Erro:', error);
                },
            },
        });
    } catch (err) {
        console.error('[renderMpPaymentBrick] Erro:', err);
        showToast('Erro ao inicializar o módulo de pagamento.');
    }
}

async function processCheckoutIntent(intent, source) {
    const isValidationSource = source === 'validation';
    const processingButton = isValidationSource ? validationConfirmButton : submitBtn;

    if (!processingButton) return;

    const originalText = processingButton.textContent;
    processingButton.disabled = true;
    processingButton.classList.add('opacity-80', 'cursor-not-allowed');

    if (isValidationSource) {
        if (validationBackButton) {
            validationBackButton.disabled = true;
            validationBackButton.classList.add('opacity-80', 'cursor-not-allowed');
        }
        processingButton.textContent = 'Redirecionando...';
    } else if (btnText) {
        btnText.textContent = 'Redirecionando...';
    }

    await handleMpSubscriptionRedirect(processingButton, originalText);
}

form.addEventListener('submit', async function(e) {
    e.preventDefault();

    const intent = buildCheckoutIntent();
    if (!intent) {
        return;
    }

    if (!validationStep) {
        await processCheckoutIntent(intent, 'form');
        return;
    }

    showValidationStep(intent);
});

if (validationBackButton) {
    validationBackButton.addEventListener('click', () => {
        hideValidationStep(true);
    });
}

if (validationConfirmButton) {
    validationConfirmButton.addEventListener('click', async () => {
        if (!pendingValidationIntent) {
            hideValidationStep(true);
            return;
        }

        await processCheckoutIntent(pendingValidationIntent, 'validation');
    });
}

async function apiFetch(url, body, idempotencyKey) {
    const headers = { "Content-Type": "application/json" };
    if (idempotencyKey) {
        headers["Idempotency-Key"] = idempotencyKey;
    }

    const response = await fetch(url, {
        method: "POST",
        headers,
        credentials: 'same-origin',
        body: JSON.stringify(body)
    });
    const data = await response.json().catch(() => ({}));
    
    if (!response.ok) {
        const msg = data?.error || data?.details?.[0]?.description || "Erro ao processar pagamento.";
        return { ok: false, errorUrl: msg, data }; 
    }
    return { ok: true, data };
}

function bindCopyButton(buttonId, inputId) {
    const button = document.getElementById(buttonId);
    const input = document.getElementById(inputId);
    if (!button || !input) {
        return;
    }

    button.addEventListener('click', () => {
        navigator.clipboard.writeText(input.value).then(() => {
            const original = button.textContent;
            button.textContent = 'Copiado!';
            setTimeout(() => {
                button.textContent = original;
            }, 2000);
        }).catch(() => {
            showToast('Nao foi possivel copiar automaticamente.', 'error');
        });
    });
}

function formatDate(dateStr) {
    if (!dateStr) return "";

    const raw = String(dateStr).trim();
    if (!raw) {
        return "";
    }

    const dateOnlyMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (dateOnlyMatch) {
        return `${dateOnlyMatch[3]}/${dateOnlyMatch[2]}/${dateOnlyMatch[1]}`;
    }

    const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
    const parsed = new Date(normalized);
    if (!Number.isNaN(parsed.getTime())) {
        const hasTime = /[T\s]\d{2}:\d{2}/.test(raw);
        if (hasTime) {
            return parsed.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        return parsed.toLocaleDateString('pt-BR');
    }

    return raw;
}

function showResultUI(title, descHtml, extraHtml) {
    if (validationStep) {
        validationStep.classList.add('hidden');
    }

    pendingValidationIntent = null;
    form.classList.add('hidden');
    successTitle.textContent = title;
    successDesc.innerHTML = descHtml;
    const extra = document.getElementById('success-extra');
    if (extra) extra.innerHTML = extraHtml || '';

    setFocusedLayout(true);

    successMessage.classList.remove('hidden');
    successMessage.classList.add('flex');
}

// hydratePlans() above handles all plan loading and display.

function resetForm() {
    form.reset();
    document.getElementById('signup-form').reset();
    checkoutAccountRegistered = false;
    checkoutAccountEmail = '';
    checkoutAccountName = '';
    fetchCsrfToken();

    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.readOnly = false;
        emailInput.classList.remove('bg-gray-50', 'cursor-not-allowed');
    }

    if (btnBackStep1) {
        btnBackStep1.classList.remove('hidden');
    }
    
    // Destruir Brick MP se ativo
    if (mpBrickController) {
        try { mpBrickController.unmount(); } catch (_) {}
        mpBrickController = null;
    }

    // Re-render plans (resets selection to first)
    hydratePlans();

    if (validationStep) {
        validationStep.classList.add('hidden');
    }
    pendingValidationIntent = null;

    if (validationBackButton) {
        validationBackButton.disabled = false;
        validationBackButton.classList.remove('opacity-80', 'cursor-not-allowed');
    }

    if (validationConfirmButton) {
        validationConfirmButton.disabled = false;
        validationConfirmButton.classList.remove('opacity-80', 'cursor-not-allowed');
        validationConfirmButton.textContent = validationConfirmLabel(selectedPaymentMethod());
    }

    successMessage.classList.add('hidden');
    successMessage.classList.remove('flex');

    setFocusedLayout(false);

    signupForm.classList.remove('hidden');
    form.classList.add('hidden');
    
    submitBtn.disabled = false;
    submitBtn.classList.remove('opacity-80', 'cursor-not-allowed');
    updateSuccessCtaLabel(false);
    updatePaymentStepUi();
}
