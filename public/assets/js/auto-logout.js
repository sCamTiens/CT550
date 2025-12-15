/**
 * Enhanced Auto Logout with Debug Logging
 */

(function () {
    'use strict';

    // Intercept fetch API
    const originalFetch = window.fetch;
    window.fetch = function (...args) {
        return originalFetch.apply(this, args)
            .then(response => {

                if (response.status === 401) {
                    handleUnauthorized();
                }
                return response;
            })
            .catch(error => {
                throw error;
            });
    };

    // Handler khi phát hiện unauthorized
    function handleUnauthorized() {

        if (window._logoutInProgress) {
            return;
        }
        window._logoutInProgress = true;

        const currentPath = window.location.pathname;
        const isAdmin = currentPath.startsWith('/admin');
        const logoutUrl = isAdmin ? '/admin/logout' : '/logout';
        const loginUrl = isAdmin ? '/admin/login' : '/login';

        // Try to call logout
        fetch(logoutUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => {
                return response.json();
            })
            .finally(() => {
                setTimeout(() => {
                    sessionStorage.clear();
                    localStorage.clear();

                    // Clear cookies
                    document.cookie.split(";").forEach(function (c) {
                        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                    });

                    window.location.replace(loginUrl);
                }, 2000);
            });
    }

    // Fallback toast
    function showLogoutToast() {
        const toastHTML = `
            <div id="auto-logout-toast" style="
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                background: #EF4444;
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-size: 14px;
                font-weight: 600;
            ">
                Phiên đăng nhập đã hết hạn. Đang chuyển về trang đăng nhập...
            </div>
        `;

        const existing = document.getElementById('auto-logout-toast');
        if (existing) existing.remove();

        document.body.insertAdjacentHTML('beforeend', toastHTML);
    }

})();
