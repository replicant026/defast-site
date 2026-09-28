(function () {
    'use strict';

    const logoutBtn = document.getElementById('header-logout-btn');

    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', async function () {
        logoutBtn.disabled = true;
        try {
            let csrfToken = '';
            const csrfResp = await fetch('/api/auth/csrf', { credentials: 'same-origin' });
            if (csrfResp.ok) {
                const data = await csrfResp.json();
                csrfToken = data.csrfToken || '';
            }
            await fetch('/api/auth/logout', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({}),
            });
        } catch (_) {
            // best-effort
        }
        window.location.href = '/login';
    });
})();
