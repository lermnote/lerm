/**
 *  Wordpress Ajax Login
 *
 * https://wp-tutorials.tech/optimise-wordpress/ajax-login-for-wordpress-without-a-plugin/
 */
document.addEventListener('DOMContentLoaded', function () {

    /**
    * If lermData hasn't been set using wp_localize_script() then don't do anything.
    */
    if (typeof lermData !== 'undefined') {

        /**
         * get loginForm.
         */
        const loginForm = document.getElementById('login-form');
        if(loginForm===null) return;
        const loginMessage = document.getElementById('login-message');

        /**
         * Listen for the "click" event on each login button.
         */
        loginForm.addEventListener('click', async (event) => {

            // ensure event target is submit button and within login form
            if (event.target.type === "submit" && event.target.closest("#login-form")) {
                event.preventDefault();
                event.target.setAttribute("disabled", "disabled");
                loginForm.classList.add('working');

                const loginData = new FormData(loginForm);
                loginData.append('action', lermData.action); // WordPress action，用于验证
                loginData.append("security", lermData.user_nonce);

                if (!loginData.get('username') || !loginData.get('password')) {
                    // Missing user name or password.
                    loginMessage.textContent = 'Missing user name or password';
                    return;
                }

                try {
                    const response = await fetch(lermData.ajaxUrl, {
                        method: 'POST',
                        body: loginData
                    });

                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }

                    const { success, data } = await response.json();

                    if (success && data.loggedin) {
                        // 登录成功，刷新页面
                        // location.reload();
                    } else {
                        // 显示错误信息
                        loginMessage.textContent = data.message;
                    }
                } catch (error) {
                    // There was an internal server error of some sort,so direct the user to the main WP Login page.
                    console.log(error);

                    if (lermData.frontDoor) {
                        window.location.href = lermData.frontDoor;
                    }
                    loginMessage.textContent = error;
                } finally {
                    setTimeout(() => event.target.removeAttribute("disabled"), 3000);
                    loginForm.classList.remove('working');
                };
            }
        })
    }
});

let login = () => {
    const loginForm = document.getElementById('login-form');
    const loginMessage = document.getElementById('login-message');
    if (loginForm === null) {
        //console.error("Can't find element with id 'login-form'");
        return;
    }
    loginForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(loginForm);
        formData.append('action', 'ajax_login'); // WordPress action，用于验证

        fetch(adminajax.url, {
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
            .catch((error) => {
                // 显示错误信息
                console.log(error);
                loginMessage.textContent = 'Login error';
            });
    });
}
let formFetchapi = (form, msg, action) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        formData.append('action', action); // WordPress action，用于验证

        try {
            const response = await fetch(adminajax.url, {
                method: "POST",
                body: formData,
            });

            // 显示错误信息
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const { success, data } = await response.json();

            if (success && data.length !== 0) {
                // 登录成功，刷新页面

            } else {
                // 显示错误信息
                msg.textContent = response.data.message;
            }
        } catch (error) {
            // 显示错误信息
            console.log(error);
            msg.textContent = 'Login error';
        } finally {
            console.log(meg);
        };
    });
}