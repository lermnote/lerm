// import {
//     debounce,
//     DOMReady,
// } from './util'

// Utility functions
const DOMReady = callback => {
    if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }
};
/**
 * Limit the frequency of calls to the click event handler function.
 * @param {*} func
 * @param {*} wait
 * @returns
 */
const debounce = (func, wait) => {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(this, args);
        }, wait);
    };
};

/**
 * Switches the state and style of buttons.
 *
 * @param {NodeList} btns - List of button nodes
 * @param {Object} buttons - Collection of button objects
 * @param {Object} settings - Settings object
 * @param {number} x - Button state identifier (1: disable buttons, others: enable buttons)
 */
const btnSwitch = (btns, buttons, settings, x) => {
    Object.keys(buttons).forEach(key => {
        if (settings.killLoader !== 1 && settings.noUnlike !== 1) {
            buttons[key].classList.add('sl-loading');
        }
        if (!buttons[key].classList.contains('always-on') && settings.tooltip) {
            buttons[key].classList.add('tooltip-on');
        }
    });
    btns.forEach(btn => {
        btn.disabled = x === 1;
    });
};

// Process Like/Unlike
const btnProcess = (btns, status, count, text, tooltip, display) => {
    let always = false;
    btns.forEach(button => {
        if (status === 'unliked') {
            button.classList.remove('liked', 'sl-clicked', 'btn-outline-danger');
        } else if (status === 'liked') {
            button.classList.add('liked', 'sl-clicked', 'btn-danger');
            button.classList.remove('btn-outline-danger');
        } else {
            console.error('Error: Button like was not processed.');
        }
        button.classList.remove('sl-loading');
        if (tooltip) {
            button.setAttribute('data-tooltip', count);
            if (button.classList.contains('always-on')) {
                always = true;
            }
        } else {
            button.setAttribute('title', text);
            const countClass = button.querySelector('.count');
            if (countClass) {
                countClass.textContent = count;
            }
        }
    });
    if (!always && tooltip) {
        setTimeout(() => {
            btns.forEach(button => {
                button.classList.remove('tooltip-on');
            });
        }, 1500);
    }
};

const prepareData = (settings) => {
    return new URLSearchParams({
        action: 'post_like',
        security: settings.nonce,
        post_id: settings.id,
        type: settings.type,
        logged: settings.logged
    });
};
// Variables
const settings = {
    killLoader: parseInt(lermAjax.loader),
    loggedOnly: parseInt(lermAjax.loggedOnly),
    noUnlike: parseInt(lermAjax.noUnlike),
    loginURL: lermAjax.loginURL,
    ajaxURL: lermAjax.ajaxURL,
    nonce: lermAjax.nonce,
    likeText: lermAjax.like,
    unlikeText: lermAjax.unlike,
    display: lermAjax.display
};

DOMReady(() => {
    // Listen for button click on AJAX/server-rendered buttons
    document.addEventListener('click', debounce(async (e) => {
        const target = e.target.closest('button.like-button');
        if (!target) return;
        // console.log('111',target);
        e.preventDefault();
        e.stopPropagation();

        const { id, type, logged } = target.dataset;
        if (!id) return;

        settings.id = id;
        settings.type = type;
        settings.logged = parseInt(logged, 10);

        if (settings.logged === 0 && settings.loggedOnly === 1) {
            window.location.href = settings.loginURL;
            return;
        }

        // Redirect if user is anonymous & "registered only" is true
        if (settings.logged === 0 && settings.loggedOnly === 1) {
            window.location.href = settings.loginURL;
            return;
        }

        const btns = document.querySelectorAll('.like-button');
        const buttons = document.querySelectorAll(`.like-${settings.type}-${settings.id}`);
        const tooltip = target.hasAttribute('data-tooltip');

        if (tooltip && timeout) clearTimeout(timeout);

        // Fire it up
        btnSwitch(btns, buttons, settings, tooltip, 1);

        const requestData = prepareData(settings);

        try {
            const response = await fetch(settings.ajaxURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: requestData
            });

            if (!response.ok) throw new Error('Network response was not ok');
            const { success, data } = await response.json();

            if (success && data.status !== 403) {
                const text = data.status === 'unliked' ? settings.likeText : settings.unlikeText;
                btnProcess(buttons, data.status, data.count, text, tooltip, settings.display);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            btnProcess(buttons, 'error', 'Error', 'Error', 'Error', tooltip, settings.display);
        } finally {
            btnSwitch(btns, 0);
        }
    }, 250));
})
