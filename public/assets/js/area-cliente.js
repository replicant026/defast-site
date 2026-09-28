const state = {
    csrfToken: '',
    customer: null,
    subscription: null,
    payments: [],
    licenseStatus: null,
};

const statusMap = {
    ACTIVE: { label: 'Ativo', classes: 'bg-green-100 text-green-800' },
    INACTIVE: { label: 'Inativo', classes: 'bg-gray-100 text-gray-700' },
    EXPIRED: { label: 'Expirado', classes: 'bg-amber-100 text-amber-800' },
    PENDING: { label: 'Pendente', classes: 'bg-amber-100 text-amber-800' },
    RECEIVED: { label: 'Recebido', classes: 'bg-green-100 text-green-800' },
    CONFIRMED: { label: 'Confirmado', classes: 'bg-green-100 text-green-800' },
    OVERDUE: { label: 'Vencido', classes: 'bg-red-100 text-red-700' },
    REFUNDED: { label: 'Estornado', classes: 'bg-gray-100 text-gray-700' },
    RECEIVED_IN_CASH: { label: 'Recebido em dinheiro', classes: 'bg-green-100 text-green-800' },
    REFUND_REQUESTED: { label: 'Estorno solicitado', classes: 'bg-amber-100 text-amber-800' },
    REFUND_IN_PROGRESS: { label: 'Estorno em andamento', classes: 'bg-amber-100 text-amber-800' },
    CHARGEBACK_REQUESTED: { label: 'Chargeback solicitado', classes: 'bg-red-100 text-red-700' },
    CHARGEBACK_DISPUTE: { label: 'Em disputa', classes: 'bg-red-100 text-red-700' },
    AWAITING_CHARGEBACK_REVERSAL: { label: 'Aguardando reversão', classes: 'bg-amber-100 text-amber-800' },
    DUNNING_REQUESTED: { label: 'Cobrança solicitada', classes: 'bg-amber-100 text-amber-800' },
    DUNNING_RECEIVED: { label: 'Cobrança recebida', classes: 'bg-green-100 text-green-800' },
    AWAITING_RISK_ANALYSIS: { label: 'Análise de risco', classes: 'bg-amber-100 text-amber-800' },
};

function escHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatMoney(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(amount);
}

function formatDate(dateStr) {
    if (!dateStr) {
        return '--';
    }

    const raw = String(dateStr).trim();
    const parsed = raw.includes('T')
        ? new Date(raw)
        : new Date(raw + 'T00:00:00');
    if (Number.isNaN(parsed.getTime())) {
        return String(dateStr);
    }

    return parsed.toLocaleDateString('pt-BR');
}

function cycleLabel(cycle) {
    const normalized = String(cycle || '').toUpperCase();
    if (normalized === 'MONTHLY') {
        return 'mês';
    }
    if (normalized === 'YEARLY') {
        return 'ano';
    }
    if (normalized === 'WEEKLY') {
        return 'semana';
    }
    if (normalized === 'BIWEEKLY') {
        return 'quinzena';
    }
    if (normalized === 'BIMONTHLY') {
        return 'bimestre';
    }
    if (normalized === 'QUARTERLY') {
        return 'trimestre';
    }
    if (normalized === 'SEMIANNUALLY') {
        return 'semestre';
    }
    return normalized !== '' ? normalized.toLowerCase() : 'período';
}

function billingTypeLabel(type) {
    const normalized = String(type || '').toUpperCase();
    if (normalized === 'CREDIT_CARD') {
        return 'Cartão';
    }
    if (normalized === 'PIX') {
        return 'Pix';
    }
    if (normalized === 'BOLETO') {
        return 'Boleto';
    }
    return normalized !== '' ? normalized : 'Outro';
}

function statusInfo(status) {
    const key = String(status || '').toUpperCase();
    return statusMap[key] || {
        label: key !== '' ? key : 'Indefinido',
        classes: 'bg-gray-100 text-gray-700',
    };
}

function isOpenChargeStatus(status) {
    const normalized = String(status || '').toUpperCase();
    return ['PENDING', 'OVERDUE', 'AWAITING_RISK_ANALYSIS', 'DUNNING_REQUESTED'].includes(normalized);
}

function formatPlanIdLabel(planId) {
    const normalized = String(planId || '').trim().toLowerCase();
    if (normalized === '') {
        return '';
    }

    if (normalized === 'annual') {
        return 'Plano Anual';
    }

    if (normalized === 'monthly') {
        return 'Plano Mensal';
    }

    if (normalized === 'onetime') {
        return 'Acesso Único DeFast';
    }

    if (normalized === 'teste grátis' || normalized === 'teste gratis') {
        return 'Teste Grátis';
    }

    const normalizedLabel = normalized
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    return normalizedLabel
        .split(' ')
        .map((part) => part.charAt(0).toLocaleUpperCase('pt-BR') + part.slice(1))
        .join(' ');
}

function resolvePendingChargeContext() {
    if (state.subscription) {
        return null;
    }

    const billing = state.licenseStatus?.billing || null;
    const charge = billing?.currentCharge || null;
    if (!charge) {
        return null;
    }

    const chargeStatus = String(charge.status || '').toUpperCase();
    if (!isOpenChargeStatus(chargeStatus)) {
        return null;
    }

    const license = state.licenseStatus?.license || {};
    const planName = formatPlanIdLabel(license.planId) || String(charge.description || '').trim() || 'Plano selecionado';
    const planInterval = String(license.planInterval || '').toUpperCase();
    const amount = Number(charge.value || 0);
    const isOnetime = planInterval === 'ONETIME';
    const recurringSuffix = planInterval !== '' ? (' por ' + cycleLabel(planInterval)) : ' cobrança em aberto';
    const planPriceLabel = amount > 0
        ? (isOnetime ? (formatMoney(amount) + ' pagamento único') : (formatMoney(amount) + recurringSuffix))
        : (isOnetime ? 'Pagamento único' : 'Cobrança pendente');

    return {
        planName,
        planPriceLabel,
        dueDate: String(charge.dueDate || ''),
        billingType: String(charge.billingType || '').toUpperCase(),
        chargeUrl: safeExternalUrl(String(charge.invoiceUrl || charge.bankSlipUrl || '')),
    };
}

function safeExternalUrl(url) {
    const raw = String(url || '').trim();
    if (!raw) {
        return '';
    }

    try {
        const parsed = new URL(raw);
        if (parsed.protocol !== 'https:') {
            return '';
        }
        return parsed.toString();
    } catch (error) {
        return '';
    }
}

function showFeedback(message, type = 'info') {
    const el = document.getElementById('account-feedback');
    if (!el) {
        return;
    }

    const classes = {
        success: 'border-green-200 bg-green-50 text-green-700',
        error: 'border-red-200 bg-red-50 text-red-700',
        info: 'border-blue-200 bg-blue-50 text-blue-700',
    };

    el.className = 'rounded-md border px-4 py-3 text-sm ' + (classes[type] || classes.info);
    el.textContent = message;
    el.classList.remove('hidden');
}

function hideFeedback() {
    const el = document.getElementById('account-feedback');
    if (!el) {
        return;
    }
    el.classList.add('hidden');
    el.textContent = '';
}

function switchTab(tabId) {
    const tabIds = ['subscription', 'billing', 'security', 'support', 'download'];
    tabIds.forEach((id) => {
        const tab = document.getElementById('tab-' + id);
        const button = document.getElementById('tab-btn-' + id);
        if (!tab || !button) {
            return;
        }

        const isActive = id === tabId;
        tab.classList.toggle('hidden', !isActive);
        button.classList.toggle('tab-active', isActive);
        button.classList.toggle('tab-inactive', !isActive);
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
}

async function fetchCsrfToken() {
    try {
        const response = await fetch('/api/auth/csrf', { credentials: 'same-origin' });
        if (!response.ok) {
            state.csrfToken = '';
            return '';
        }

        const data = await response.json().catch(() => ({}));
        state.csrfToken = String(data?.csrfToken || '');
        return state.csrfToken;
    } catch (error) {
        state.csrfToken = '';
        return '';
    }
}

async function ensureAuthenticated() {
    try {
        const response = await fetch('/api/auth/me', { credentials: 'same-origin' });
        if (!response.ok) {
            window.location.href = '/login?next=' + encodeURIComponent('/area-cliente');
            return false;
        }

        const data = await response.json().catch(() => ({}));
        state.customer = data?.customer || null;
        if (data?.csrfToken) {
            state.csrfToken = String(data.csrfToken);
        }

        return true;
    } catch (error) {
        window.location.href = '/login?next=' + encodeURIComponent('/area-cliente');
        return false;
    }
}

async function apiRequest(path, options = {}) {
    const method = String(options.method || 'GET').toUpperCase();
    const body = options.body ?? null;
    const retryOnCsrf = options.retryOnCsrf !== false;

    const headers = { 'Content-Type': 'application/json' };
    if (method !== 'GET' && state.csrfToken) {
        headers['X-CSRF-Token'] = state.csrfToken;
    }

    const response = await fetch(path, {
        method,
        headers,
        credentials: 'same-origin',
        ...(body !== null ? { body: JSON.stringify(body) } : {}),
    });

    if (response.status === 401) {
        window.location.href = '/login?next=' + encodeURIComponent('/area-cliente');
        throw new Error('Não autenticado.');
    }

    if (response.status === 403 && method !== 'GET' && retryOnCsrf) {
        await fetchCsrfToken();
        return apiRequest(path, { ...options, retryOnCsrf: false });
    }

    const data = await response.json().catch(() => ({}));
    if (data?.csrfToken) {
        state.csrfToken = String(data.csrfToken);
    }

    if (!response.ok) {
        const error = new Error(String(data?.error || 'Falha na comunicação com a API.'));
        error.status = response.status;
        error.details = data?.details || [];
        throw error;
    }

    return data;
}

function renderSubscription() {
    const activeView = document.getElementById('active-plan-view');
    const noPlanView = document.getElementById('no-plan-view');
    const subscription = state.subscription;
    const pendingCharge = resolvePendingChargeContext();

    if (!subscription && !pendingCharge) {
        if (activeView) {
            activeView.classList.add('hidden');
        }
        if (noPlanView) {
            noPlanView.classList.remove('hidden');
        }
        return;
    }

    if (activeView) {
        activeView.classList.remove('hidden');
    }
    if (noPlanView) {
        noPlanView.classList.add('hidden');
    }

    const planName = document.getElementById('current-plan-name');
    const planPrice = document.getElementById('current-plan-price');
    const nextChargeDate = document.getElementById('next-charge-date');
    const statusBadge = document.getElementById('subscription-status-badge');
    const paymentBrand = document.getElementById('payment-method-brand');
    const paymentSummary = document.getElementById('payment-method-summary');
    const paymentExtra = document.getElementById('payment-method-extra');
    const subscriptionCardTitle = document.getElementById('subscription-card-title');
    const nextChargeLabel = document.getElementById('next-charge-label');
    const paymentSectionTitle = document.getElementById('payment-section-title');
    const pendingChargeActionLink = document.getElementById('pending-charge-action-link');
    const cancelButton = document.getElementById('open-cancel-modal-button');
    const upgradeButton = document.getElementById('open-upgrade-modal-button');
    const openCardModalButton = document.getElementById('open-payment-method-modal-button');

    if (pendingCharge) {
        if (subscriptionCardTitle) {
            subscriptionCardTitle.textContent = 'Aguardando confirmação';
        }

        if (planName) {
            planName.textContent = pendingCharge.planName;
        }

        if (planPrice) {
            planPrice.textContent = pendingCharge.planPriceLabel;
        }

        if (nextChargeLabel) {
            nextChargeLabel.textContent = 'Vencimento da cobrança';
        }

        if (nextChargeDate) {
            nextChargeDate.textContent = formatDate(pendingCharge.dueDate);
        }

        if (statusBadge) {
            statusBadge.textContent = 'Aguardando confirmação';
            statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800';
        }

        if (paymentSectionTitle) {
            paymentSectionTitle.textContent = 'Cobrança em aberto';
        }

        if (paymentBrand) {
            paymentBrand.textContent = billingTypeLabel(pendingCharge.billingType).slice(0, 8);
        }

        if (paymentSummary) {
            paymentSummary.textContent = 'Pagamento pendente via ' + billingTypeLabel(pendingCharge.billingType);
        }

        if (paymentExtra) {
            const dueDateLabel = formatDate(pendingCharge.dueDate);
            paymentExtra.textContent = dueDateLabel !== '--'
                ? 'Vencimento em ' + dueDateLabel
                : 'Aguardando confirmação do pagamento';
        }

        if (pendingChargeActionLink) {
            if (pendingCharge.chargeUrl) {
                pendingChargeActionLink.href = pendingCharge.chargeUrl;
                pendingChargeActionLink.classList.remove('hidden');
            } else {
                pendingChargeActionLink.href = '#';
                pendingChargeActionLink.classList.add('hidden');
            }
        }

        if (cancelButton) {
            cancelButton.classList.add('hidden');
        }

        if (upgradeButton) {
            upgradeButton.classList.add('hidden');
        }

        if (openCardModalButton) {
            openCardModalButton.classList.add('hidden');
        }

        return;
    }

    if (subscriptionCardTitle) {
        subscriptionCardTitle.textContent = 'Assinatura ativa';
    }

    if (nextChargeLabel) {
        nextChargeLabel.textContent = 'Próxima cobrança';
    }

    if (paymentSectionTitle) {
        paymentSectionTitle.textContent = 'Método de pagamento padrão';
    }

    if (pendingChargeActionLink) {
        pendingChargeActionLink.href = '#';
        pendingChargeActionLink.classList.add('hidden');
    }

    const plan = subscription.plan || {};
    const cycle = String(plan.cycle || subscription.cycle || '').toUpperCase();

    if (planName) {
        planName.textContent = String(plan.name || 'Assinatura DeFast');
    }

    if (planPrice) {
        planPrice.textContent = formatMoney(subscription.value) + ' por ' + cycleLabel(cycle);
    }

    if (nextChargeDate) {
        nextChargeDate.textContent = formatDate(subscription.nextDueDate);
    }

    if (statusBadge) {
        const info = statusInfo(subscription.status);
        statusBadge.textContent = info.label;
        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + info.classes;
    }

    if (cancelButton) {
        cancelButton.classList.toggle('hidden', !subscription.canCancel);
    }

    if (upgradeButton) {
        const alreadyAnnual = cycle === 'YEARLY';
        upgradeButton.classList.toggle('hidden', !subscription.canChangePlan || alreadyAnnual);
    }

    const paymentMethod = subscription.paymentMethod || null;
    if (paymentMethod) {
        if (paymentBrand) {
            paymentBrand.textContent = String(paymentMethod.brand || billingTypeLabel(paymentMethod.type)).slice(0, 8);
        }
        if (paymentSummary) {
            paymentSummary.textContent = String(paymentMethod.summary || 'Método de pagamento ativo');
        }
        if (paymentExtra) {
            paymentExtra.textContent = String(paymentMethod.extra || '');
        }

        if (openCardModalButton) {
            const canUpdateCard = String(paymentMethod.type || '').toUpperCase() === 'CREDIT_CARD';
            openCardModalButton.classList.toggle('hidden', !canUpdateCard);
        }
    } else {
        if (paymentBrand) {
            paymentBrand.textContent = '--';
        }
        if (paymentSummary) {
            paymentSummary.textContent = 'Método de pagamento não identificado';
        }
        if (paymentExtra) {
            paymentExtra.textContent = '';
        }
        if (openCardModalButton) {
            openCardModalButton.classList.add('hidden');
        }
    }
}

function renderTrialStatusCard() {
    const card = document.getElementById('trial-status-card');
    const titleEl = document.getElementById('trial-status-title');
    const messageEl = document.getElementById('trial-status-message');
    const extraEl = document.getElementById('trial-status-extra');
    const daysBadge = document.getElementById('trial-days-badge');
    const actions = document.getElementById('trial-status-actions');
    const boletoLink = document.getElementById('trial-boleto-link');
    const pixWrapper = document.getElementById('trial-pix-wrapper');
    const pixPayloadInput = document.getElementById('trial-pix-payload');

    if (!card) {
        return;
    }

    const licenseStatus = state.licenseStatus || null;
    const trial = licenseStatus?.trial || null;
    if (!trial || !trial.enabled) {
        card.classList.add('hidden');
        if (actions) {
            actions.classList.add('hidden');
        }
        return;
    }

    const trialDays = Math.max(0, Number(trial.trialDays || 0));
    const daysLeft = Math.max(0, Number(trial.daysLeft || 0));
    const inTrial = Boolean(trial.inTrial);

    const billing = licenseStatus?.billing || {};
    const currentCharge = billing.currentCharge || null;
    const dueRef = String(currentCharge?.dueDate || licenseStatus?.subscription?.nextDueDate || trial.endsAt || '');
    const dueDateLabel = formatDate(dueRef);
    const hasDueDate = dueDateLabel !== '--';
    const billingType = String(currentCharge?.billingType || licenseStatus?.subscription?.billingType || '').toUpperCase();

    card.classList.remove('hidden');

    if (titleEl) {
        titleEl.textContent = inTrial ? 'Teste grátis ativo' : 'Período de teste encerrado';
    }

    if (messageEl) {
        if (inTrial) {
            messageEl.textContent = hasDueDate
                ? 'Seu teste de ' + trialDays + ' dias termina em ' + daysLeft + ' dia(s). A primeira cobrança vence em ' + dueDateLabel + '.'
                : 'Seu teste de ' + trialDays + ' dias termina em ' + daysLeft + ' dia(s).';
        } else {
            messageEl.textContent = hasDueDate
                ? 'Seu período de teste terminou. A primeira cobrança venceu em ' + dueDateLabel + '.'
                : 'Seu período de teste terminou.';
        }
    }

    if (daysBadge) {
        daysBadge.textContent = inTrial ? (daysLeft + ' dia(s) restantes') : 'Teste encerrado';
        daysBadge.className = inTrial
            ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800'
            : 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700';
    }

    if (extraEl) {
        if (billingType === 'PIX') {
            extraEl.textContent = 'Pix já disponível: você pode pagar agora ou até o vencimento.';
        } else if (billingType === 'BOLETO') {
            extraEl.textContent = 'Boleto já disponível: você pode pagar agora ou até o vencimento.';
        } else if (billingType === 'CREDIT_CARD') {
            extraEl.textContent = 'No cartão, a cobrança será processada automaticamente na data de vencimento.';
        } else {
            extraEl.textContent = '';
        }
    }

    const safeBoleto = safeExternalUrl(String(billing.bankSlipUrl || currentCharge?.bankSlipUrl || ''));
    const pixPayload = String(billing?.pixQrCode?.payload || '');

    let hasAction = false;

    if (boletoLink) {
        if (safeBoleto) {
            boletoLink.href = safeBoleto;
            boletoLink.classList.remove('hidden');
            hasAction = true;
        } else {
            boletoLink.href = '#';
            boletoLink.classList.add('hidden');
        }
    }

    if (pixWrapper && pixPayloadInput) {
        if (pixPayload) {
            pixPayloadInput.value = pixPayload;
            pixWrapper.classList.remove('hidden');
            hasAction = true;
        } else {
            pixPayloadInput.value = '';
            pixWrapper.classList.add('hidden');
        }
    }

    if (actions) {
        actions.classList.toggle('hidden', !hasAction);
    }
}

function renderPayments() {
    const loading = document.getElementById('billing-loading');
    const empty = document.getElementById('billing-empty');
    const list = document.getElementById('billing-list');

    if (!loading || !empty || !list) {
        return;
    }

    loading.classList.add('hidden');
    list.innerHTML = '';

    const payments = Array.isArray(state.payments) ? [...state.payments] : [];
    payments.sort((a, b) => {
        const aRef = String(a?.dueDate || a?.paymentDate || '');
        const bRef = String(b?.dueDate || b?.paymentDate || '');
        const aTs = aRef ? Date.parse(aRef) : 0;
        const bTs = bRef ? Date.parse(bRef) : 0;
        return Number.isFinite(bTs - aTs) ? (bTs - aTs) : 0;
    });

    if (payments.length === 0) {
        empty.classList.remove('hidden');
        list.classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');
    list.classList.remove('hidden');

    payments.forEach((payment) => {
        const info = statusInfo(payment.status);
        const safeInvoiceUrl = safeExternalUrl(payment.invoiceUrl);
        const safeBankSlipUrl = safeExternalUrl(payment.bankSlipUrl);

        const actions = [];
        if (safeInvoiceUrl) {
            actions.push('<a href="' + escHtml(safeInvoiceUrl) + '" target="_blank" rel="noopener noreferrer" class="text-brand-purple hover:text-brand-dark font-medium">Fatura</a>');
        }
        if (safeBankSlipUrl) {
            actions.push('<a href="' + escHtml(safeBankSlipUrl) + '" target="_blank" rel="noopener noreferrer" class="text-brand-purple hover:text-brand-dark font-medium">Boleto</a>');
        }

        list.insertAdjacentHTML('beforeend',
            '<div class="py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">' +
                '<div>' +
                    '<p class="text-sm font-semibold text-gray-900">' + escHtml(formatMoney(payment.value)) + '</p>' +
                    '<p class="text-xs text-gray-500 mt-1">Vencimento: ' + escHtml(formatDate(payment.dueDate)) + ' • ' + escHtml(billingTypeLabel(payment.billingType)) + '</p>' +
                    '<p class="text-xs text-gray-500 mt-1">ID: ' + escHtml(payment.id || '--') + '</p>' +
                '</div>' +
                '<div class="flex flex-col md:items-end gap-2">' +
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + escHtml(info.classes) + '">' + escHtml(info.label) + '</span>' +
                    '<div class="flex items-center gap-3 text-xs">' + actions.join('') + '</div>' +
                '</div>' +
            '</div>'
        );
    });
}

async function loadOverview() {
    const loading = document.getElementById('billing-loading');
    const empty = document.getElementById('billing-empty');
    const list = document.getElementById('billing-list');

    if (loading) {
        loading.classList.remove('hidden');
    }
    if (empty) {
        empty.classList.add('hidden');
    }
    if (list) {
        list.classList.add('hidden');
        list.innerHTML = '';
    }

    const data = await apiRequest('/api/license/status', { method: 'GET' });
    state.licenseStatus = data || null;
    state.customer = data?.customer || state.customer;
    state.subscription = data?.subscription || null;
    state.payments = Array.isArray(data?.payments) ? data.payments : [];

    renderSubscription();
    renderTrialStatusCard();
    renderPayments();
}

function fillPaymentModalDefaults() {
    const holderNameInput = document.getElementById('card-holder-name');
    if (holderNameInput && !holderNameInput.value) {
        holderNameInput.value = String(state.customer?.name || '').trim();
    }
}

async function confirmCancel() {
    const button = document.getElementById('confirm-cancel-button');
    if (button) {
        button.disabled = true;
    }

    try {
        const data = await apiRequest('/api/customer-area/subscription/cancel', {
            method: 'POST',
            body: {},
        });
        closeModal('cancel-modal');
        showFeedback(String(data?.message || 'Assinatura cancelada com sucesso.'), 'success');
        await loadOverview();
    } catch (error) {
        showFeedback(String(error?.message || 'Não foi possível cancelar a assinatura.'), 'error');
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
}

async function confirmUpgrade() {
    const button = document.getElementById('confirm-upgrade-button');
    if (button) {
        button.disabled = true;
    }

    try {
        const data = await apiRequest('/api/customer-area/subscription/change-plan', {
            method: 'POST',
            body: { planId: 'annual' },
        });
        closeModal('upgrade-modal');
        showFeedback(String(data?.message || 'Plano atualizado com sucesso.'), 'success');
        await loadOverview();
    } catch (error) {
        showFeedback(String(error?.message || 'Não foi possível atualizar o plano.'), 'error');
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
}

function normalizeCardNumberInput(value) {
    return String(value || '')
        .replace(/\D/g, '')
        .slice(0, 19)
        .replace(/(\d{4})(?=\d)/g, '$1 ')
        .trim();
}

function normalizeExpiryInput(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 4);
    if (digits.length <= 2) {
        return digits;
    }
    return digits.slice(0, 2) + '/' + digits.slice(2);
}

function normalizePostalCodeInput(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
    if (digits.length <= 5) {
        return digits;
    }
    return digits.slice(0, 5) + '-' + digits.slice(5);
}

function normalizePhoneInput(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
    if (digits.length <= 2) {
        return digits;
    }
    if (digits.length <= 6) {
        return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
    }
    if (digits.length <= 10) {
        return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 6) + '-' + digits.slice(6);
    }
    return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7);
}

async function handlePaymentMethodSubmit(event) {
    event.preventDefault();

    const errorEl = document.getElementById('payment-method-error');
    const submitButton = document.getElementById('submit-payment-method-button');
    if (errorEl) {
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
    }

    const holderName = String(document.getElementById('card-holder-name')?.value || '').trim();
    const cardNumber = String(document.getElementById('card-number-update')?.value || '').replace(/\D/g, '');
    const expiryRaw = String(document.getElementById('card-expiry-update')?.value || '').replace(/\D/g, '');
    const ccv = String(document.getElementById('card-cvc-update')?.value || '').replace(/\D/g, '');
    const postalCode = String(document.getElementById('postal-code-update')?.value || '').replace(/\D/g, '');
    const addressNumber = String(document.getElementById('address-number-update')?.value || '').trim();
    const phone = String(document.getElementById('phone-update')?.value || '').replace(/\D/g, '');

    if (expiryRaw.length < 4) {
        if (errorEl) {
            errorEl.textContent = 'Informe a validade no formato MM/AA.';
            errorEl.classList.remove('hidden');
        }
        return;
    }

    const expiryMonth = expiryRaw.slice(0, 2);
    let expiryYear = expiryRaw.slice(2);
    if (expiryYear.length === 2) {
        expiryYear = '20' + expiryYear;
    }

    if (submitButton) {
        submitButton.disabled = true;
    }

    try {
        const data = await apiRequest('/api/customer-area/subscription/payment-method', {
            method: 'POST',
            body: {
                holderName,
                cardNumber,
                expiryMonth,
                expiryYear,
                ccv,
                postalCode,
                addressNumber,
                phone,
            },
        });

        closeModal('payment-method-modal');
        const form = document.getElementById('payment-method-form');
        if (form) {
            form.reset();
        }

        showFeedback(String(data?.message || 'Cartão atualizado com sucesso.'), 'success');
        await loadOverview();
    } catch (error) {
        if (errorEl) {
            errorEl.textContent = String(error?.message || 'Não foi possível atualizar o cartão.');
            errorEl.classList.remove('hidden');
        }
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
        }
    }
}

async function logoutSession() {
    if (!state.csrfToken) {
        await fetchCsrfToken();
    }

    try {
        await apiRequest('/api/auth/logout', {
            method: 'POST',
            body: {},
        });
    } catch (error) {
        // no-op
    }

    window.location.href = '/login';
}

function bindUiEvents() {
    document.querySelectorAll('.js-tab-button').forEach((button) => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab') || 'subscription';
            switchTab(tabId);
        });
    });

    document.querySelectorAll('.js-open-modal').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-id');
            if (modalId) {
                openModal(modalId);
            }
        });
    });

    document.querySelectorAll('.js-close-modal').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-id');
            if (modalId) {
                closeModal(modalId);
            }
        });
    });

    document.querySelectorAll('[id$="-modal"]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });


    const confirmCancelButton = document.getElementById('confirm-cancel-button');
    if (confirmCancelButton) {
        confirmCancelButton.addEventListener('click', confirmCancel);
    }

    const confirmUpgradeButton = document.getElementById('confirm-upgrade-button');
    if (confirmUpgradeButton) {
        confirmUpgradeButton.addEventListener('click', confirmUpgrade);
    }

    const openPaymentMethodModalButton = document.getElementById('open-payment-method-modal-button');
    if (openPaymentMethodModalButton) {
        openPaymentMethodModalButton.addEventListener('click', () => {
            fillPaymentModalDefaults();
            openModal('payment-method-modal');
        });
    }

    const paymentMethodForm = document.getElementById('payment-method-form');
    if (paymentMethodForm) {
        paymentMethodForm.addEventListener('submit', handlePaymentMethodSubmit);
    }

    const copyPixButton = document.getElementById('trial-copy-pix');
    if (copyPixButton) {
        copyPixButton.addEventListener('click', async () => {
            const pixPayload = String(document.getElementById('trial-pix-payload')?.value || '');
            if (!pixPayload) {
                showFeedback('Não há código Pix disponível para copiar.', 'info');
                return;
            }

            const copied = await copyToClipboard(pixPayload);
            if (copied) {
                showFeedback('Código Pix copiado com sucesso.', 'success');
            } else {
                showFeedback('Não foi possível copiar o código Pix.', 'error');
            }
        });
    }

    const cardNumberInput = document.getElementById('card-number-update');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', (event) => {
            event.target.value = normalizeCardNumberInput(event.target.value);
        });
    }

    const expiryInput = document.getElementById('card-expiry-update');
    if (expiryInput) {
        expiryInput.addEventListener('input', (event) => {
            event.target.value = normalizeExpiryInput(event.target.value);
        });
    }

    const cvcInput = document.getElementById('card-cvc-update');
    if (cvcInput) {
        cvcInput.addEventListener('input', (event) => {
            event.target.value = String(event.target.value || '').replace(/\D/g, '').slice(0, 4);
        });
    }

    const postalCodeInput = document.getElementById('postal-code-update');
    if (postalCodeInput) {
        postalCodeInput.addEventListener('input', (event) => {
            event.target.value = normalizePostalCodeInput(event.target.value);
        });
    }

    const phoneInput = document.getElementById('phone-update');
    if (phoneInput) {
        phoneInput.addEventListener('input', (event) => {
            event.target.value = normalizePhoneInput(event.target.value);
        });
    }

    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const currentPassword = String(document.getElementById('current-password')?.value || '');
            const newPassword = String(document.getElementById('new-password')?.value || '');
            const confirmPassword = String(document.getElementById('confirm-password')?.value || '');
            const successEl = document.getElementById('password-success');
            const submitButton = passwordForm.querySelector('button[type="submit"]');

            if (successEl) {
                successEl.classList.add('hidden');
            }

            if (newPassword !== confirmPassword) {
                showFeedback('As novas senhas não conferem.', 'error');
                return;
            }

            if (newPassword.length < 4) {
                showFeedback('A nova senha precisa ter ao menos 4 caracteres.', 'error');
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const data = await apiRequest('/api/auth/password', {
                    method: 'POST',
                    body: {
                        currentPassword,
                        newPassword,
                    },
                });

                passwordForm.reset();
                if (successEl) {
                    successEl.textContent = String(data?.message || 'Senha atualizada com sucesso!');
                    successEl.classList.remove('hidden');
                }
                showFeedback(String(data?.message || 'Senha atualizada com sucesso.'), 'success');
            } catch (error) {
                showFeedback(String(error?.message || 'Não foi possível atualizar a senha.'), 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    }
}

async function copyToClipboard(text) {
    const value = String(text || '');
    if (!value) {
        return false;
    }

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        try {
            await navigator.clipboard.writeText(value);
            return true;
        } catch (error) {
            // fallback below
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', 'readonly');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.pointerEvents = 'none';
    document.body.appendChild(textarea);
    textarea.select();

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch (error) {
        copied = false;
    }

    document.body.removeChild(textarea);
    return copied;
}

async function initPage() {
    bindUiEvents();
    switchTab('subscription');

    const authenticated = await ensureAuthenticated();
    if (!authenticated) {
        return;
    }

    if (!state.csrfToken) {
        await fetchCsrfToken();
    }

    hideFeedback();

    try {
        await loadOverview();
    } catch (error) {
        showFeedback(String(error?.message || 'Não foi possível carregar os dados da área do cliente.'), 'error');
    }
}

initPage();
