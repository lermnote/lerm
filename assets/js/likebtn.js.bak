/* global document, window, simpleLikes */

// Utilities
var timeout = null;
var ready = (callback) => {
    if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) callback();
    else document.addEventListener("DOMContentLoaded", callback);
};
// Start button processing
var btnSwitch = function (btns, buttons, settings, x) {
    for (var i = 0, len = buttons.length; i < len; i++) {
        // Maybe show loading animation for linked buttons
        if (settings.killLoader !== 1 && settings.noUnlike !== 1) {
            buttons[i].classList.add('sl-loading');
        }
        // Maybe show the tooltip on linked buttons while processing
        if (!buttons[i].classList.contains('always-on') && settings.tooltip) {
            buttons[i].classList.add('tooltip-on');
        }
    }
    // Disable all buttons while processing
    for (var i = 0, len = btns.length; i < len; i++) {
        if (x === 1) {
            btns[i].setAttribute('disabled', true);
        } else {
            btns[i].removeAttribute('disabled');
        }
    }
};
// Process Like/Unlike
var btnProcess = function (btns, status, count, num, text, tooltip, display) {
    var always = false;
    for (var i = 0, len = btns.length; i < len; i++) {
        if (status === 'unliked') {
            btns[i].classList.remove('liked', 'sl-clicked');
        } else {
            if (status === 'liked') {
                btns[i].classList.add('liked', 'sl-clicked');
            } else {
                window.console.log('Error: Button like was not processed.');
            }
        }
        btns[i].classList.remove('sl-loading');
        if (tooltip) {
            btns[i].setAttribute('data-tooltip', count);
            if (btns[i].classList.contains('always-on')) {
                always = true;
            }
        } else {
            btns[i].setAttribute('title', text);
            var countClass = btns[i].querySelector('.sl-count');
            if (typeof (countClass) != 'undefined' && countClass != null) {
                countClass.innerHTML = count;
                if (num === 0 && display === 'zero') {
                    countClass.classList.add('sr-only');
                } else {
                    countClass.classList.remove('sr-only');
                }
            }
        }
    }
    if (!always && tooltip) {
        timeout = window.setTimeout(function () {
            for (var i = 0, len = btns.length; i < len; i++) {
                btns[i].classList.remove('tooltip-on');
            }
        }, 1500);
    }
}
var prepData = function (settings) {
    // Serialize Data
    var serialize = [];
    var obj = {
        action: 'sl_process',
        security: settings.nonce,
        id: settings.id,
        type: settings.type,
        logged: settings.logged
    };
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            serialize.push(encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]));
        }
    }
    return serialize.join("&");
}

// When document ready
ready(() => {
    // Variables
    var settings = {
        killLoader: parseInt(simpleLikes.loader),
        loggedOnly: parseInt(simpleLikes.loggedOnly),
        noUnlike: parseInt(simpleLikes.noUnlike),
        loginURL: simpleLikes.loginURL,
        ajaxURL: simpleLikes.ajaxURL,
        nonce: simpleLikes.security,
        likeText: simpleLikes.like,
        unlikeText: simpleLikes.unlike,
        display: simpleLikes.display
    };
    var widgetAttrMap = {
        'time-period': 'period',
        'amount': 'amount',
        'types': 'types',
        'show-number': 'showNumber',
        'separator': 'sep',
        'include-comments': 'yesComments',
        'include-topics': 'yesTopics',
        'include-activities': 'yesActivities',
        'link-type': 'linkType'
    };
    // Listen for button click on AJAX/server-rendered buttons
    document.addEventListener('click', function (e) {
        if (e.target && e.target.matches('button.sl-btn')) {
            e.preventDefault();
            // Setup variables
            var tooltip;
            if (!e.target.hasAttribute('data-tooltip')) {
                tooltip = false;
            } else {
                tooltip = true;
                // Clear timeout if it exists
                if (timeout) {
                    window.clearTimeout(timeout);
                }
            }
            settings.tooltip = tooltip;
            settings.id = e.target.getAttribute('data-id');
            settings.logged = parseInt(e.target.getAttribute('data-logged'));
            settings.type = e.target.getAttribute('data-type');
            // Stop if no ID
            if (typeof settings.id === typeof undefined && settings.id === false) return;
            // Redirect if user is anonymous & "registered only" is true
            if (settings.logged === 0 && settings.loggedOnly === 1) {
                window.location.href = settings.loginURL;
            } else {
                // Get all instances of buttons
                var btns = document.querySelectorAll('.sl-btn');
                // Get matching buttons (multiple buttons on page for same post)
                var buttons = document.querySelectorAll(".sl-" + settings.type + "-" + settings.id);
                // Fire it up
                btnSwitch(btns, buttons, settings, tooltip, 1);
                // Start button AJAX
                var rqst = new XMLHttpRequest();
                rqst.open('POST', settings.ajaxURL, true);
                rqst.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
                rqst.onload = function () {
                    if (this.status >= 200 && this.status < 400) {
                        var rsp = JSON.parse(this.response);
                        if (rsp.status !== 403) { // Do nothing if "no unlike"
                            var text;
                            var num = parseInt(rsp.number);
                            if (rsp.status === 'unliked') {
                                text = settings.likeText;
                            } else {
                                text = settings.unlikeText;
                            }
                            btnProcess(buttons, rsp.status, rsp.count, num, text, tooltip, settings.display);
                        }
                    } else {
                        btnProcess(buttons, 'error', 'Error', 'Error', 'Error', tooltip, settings.display);
                    }
                    btnSwitch(btns, 0);
                };
                rqst.onerror = function () {
                    btnProcess(buttons, 'error', 'Error', 'Error', 'Error', tooltip, settings.display);
                    btnSwitch(btns, 0);
                };
                var data = prepData(settings);
                rqst.send(data);
            }
        }
    });
});