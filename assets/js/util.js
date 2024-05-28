/**
 * --------------------------------------------------------------------------
 * Lerm (v4.0): util.js
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

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
}

/**
* animate function
*
* @param {*} options
*/
const animateScrolling = options => {
    let startTime = performance.now();

    let animate = (currentTime) => {
        let timeFraction = (currentTime - startTime) / options.duration;
        if (timeFraction > 1) timeFraction = 1;

        let progress = options.timing(timeFraction);
        options.draw(progress);

        if (timeFraction < 1) {
            requestAnimationFrame(animate);
        }
    };
    requestAnimationFrame(animate);
};

/**
* validate email
*
* @param {*} email
*/
const validateEmail = email => {
    const regExp = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,}$/;
    return regExp.test(email);
}

/**
 *Parse to DOM Array
 *
 * @param {*} string
 * @returns {array}
 */
const parseToDOM = string => {
    const div = document.createElement("div");
    if (typeof string === "string") {
        div.innerHTML = str;
    }
    return Array.from(div.childNodes);
}

const DOMReady = callback => {
    if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) callback();
    else document.addEventListener("DOMContentLoaded", callback);
};

export {
    debounce,
    animateScrolling,
    validateEmail,
    parseToDOM,
    DOMReady
}