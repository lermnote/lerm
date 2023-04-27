document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('login-form');
    const loginMessage = document.getElementById('login-message');

    loginForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(loginForm);
        formData.append('action', 'ajax_login'); // WordPress action，用于验证

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    // 登录成功，刷新页面
                    location.reload();
                } else {
                    // 显示错误信息
                    loginMessage.textContent = response.data.message;
                }
            })
            .catch(() => {
                // 显示错误信息
                loginMessage.textContent = 'Login error';
            });
    });
});