function parseNextUrl() {
    const params = new URLSearchParams(window.location.search);
    const next = params.get('next');
    if (!next || typeof next !== 'string') {
        return '/area-cliente';
    }

    if (!next.startsWith('/')) {
        return '/area-cliente';
    }

    if (next.startsWith('//')) {
        return '/area-cliente';
    }

    return next;
}

const nextUrl = parseNextUrl();
let csrfToken = '';

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

async function ensureAlreadyAuthenticated() {
    try {
        const response = await fetch('/api/auth/me', { credentials: 'same-origin' });
        if (response.ok) {
            window.location.href = nextUrl;
        }
    } catch (error) {
        // no-op
    }
}

document.getElementById('login-form').addEventListener('submit', async function (event) {
    event.preventDefault();

    const submitButton = document.getElementById('submit-button');
    const errorMsg = document.getElementById('error-msg');

    const payload = {
        email: String(document.getElementById('email').value || '').trim(),
        password: String(document.getElementById('password').value || ''),
    };

    submitButton.disabled = true;
    submitButton.textContent = 'Entrando...';
    errorMsg.classList.add('hidden');

    try {
        if (!csrfToken) {
            await fetchCsrfToken();
        }

        const loginHeaders = { 'Content-Type': 'application/json' };
        if (csrfToken) {
            loginHeaders['X-CSRF-Token'] = csrfToken;
        }

        let response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: loginHeaders,
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        if (response.status === 403) {
            await fetchCsrfToken();
            if (csrfToken) {
                loginHeaders['X-CSRF-Token'] = csrfToken;
            }

            response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: loginHeaders,
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
        }

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            errorMsg.textContent = data.error || 'Não foi possível autenticar agora.';
            errorMsg.classList.remove('hidden');
            return;
        }

        csrfToken = String(data?.csrfToken || csrfToken || '');

        window.location.href = nextUrl;
    } catch (error) {
        errorMsg.textContent = 'Erro de conexão. Tente novamente.';
        errorMsg.classList.remove('hidden');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Entrar';
    }
});

const showForgotBtn = document.getElementById('show-forgot-btn');
const hideForgotBtn = document.getElementById('hide-forgot-btn');
const forgotPanel   = document.getElementById('forgot-panel');
const forgotForm    = document.getElementById('forgot-form');
const forgotMsg     = document.getElementById('forgot-msg');
const loginForm     = document.getElementById('login-form');

if (showForgotBtn && forgotPanel && loginForm) {
    showForgotBtn.addEventListener('click', function () {
        forgotPanel.classList.remove('hidden');
        loginForm.classList.add('hidden');
        showForgotBtn.parentElement.classList.add('hidden');
    });
}

if (hideForgotBtn && forgotPanel && loginForm) {
    hideForgotBtn.addEventListener('click', function () {
        forgotPanel.classList.add('hidden');
        loginForm.classList.remove('hidden');
        showForgotBtn.parentElement.classList.remove('hidden');
    });
}

if (forgotForm) {
    forgotForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = document.getElementById('forgot-submit');
        const email = String(document.getElementById('forgot-email').value || '').trim();

        forgotMsg.className = 'hidden text-sm text-center';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        try {
            if (!csrfToken) {
                await fetchCsrfToken();
            }

            const headers = { 'Content-Type': 'application/json' };
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
            }

            const response = await fetch('/api/auth/forgot-password', {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify({ email }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                forgotMsg.textContent = data.error || 'Não foi possível enviar o e-mail agora.';
                forgotMsg.className = 'text-sm text-center text-red-600';
            } else {
                forgotMsg.textContent = data.message || 'E-mail enviado! Verifique sua caixa de entrada.';
                forgotMsg.className = 'text-sm text-center text-green-600';
                forgotForm.reset();
            }
        } catch (_) {
            forgotMsg.textContent = 'Erro de conexão. Tente novamente.';
            forgotMsg.className = 'text-sm text-center text-red-600';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar link';
        }
    });
}

fetchCsrfToken();
ensureAlreadyAuthenticated();
