export function delegate(eventName, selector, handler, root = document) {
  root.addEventListener(eventName, function (event) {
    let target = event.target;
    while (target && target !== root) {
      if (target.matches && target.matches(selector)) {
        handler(event, target);
        return;
      }
      target = target.parentElement;
    }
  }, { passive: false });
}

export const DOMContentLoaded = (cb) => {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", cb, { once: true });
  } else {
    cb();
  }
};

export const safeRequestIdleCallback = (cb) => {
  if ('requestIdleCallback' in window) {
    requestIdleCallback(cb);
  } else {
    setTimeout(cb, 200);
  }
};

export const scrollTop = () => {
  delegate("click", "#scroll-up", (event) => {
    event.preventDefault();
    document.documentElement.scrollIntoView({ behavior: "smooth" });
  });
};

export const lazyLoadImages = (() => {
  let observer;
  return () => {
    if (!observer) {
      observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            observer.unobserve(img);
          }
        });
      }, { rootMargin: "0px 0px", threshold: 0 });
    }
    const images = document.querySelectorAll('.lazy');
    images.forEach(img => observer.observe(img));
  };
})();

// utils.js — 替换 initializeWOW，删除 index.js 顶部的 import WOW
export const initScrollAnimate = () => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        el.classList.add('animated');
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.loading-animate').forEach(el => observer.observe(el));
};

import hljs from 'highlight.js/lib/core';
import javascript from 'highlight.js/lib/languages/javascript';
import php from 'highlight.js/lib/languages/php';
import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import xml from 'highlight.js/lib/languages/xml';
import json from 'highlight.js/lib/languages/json';

hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('php', php);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('css', css);
hljs.registerLanguage('html', xml);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('json', json);

export const codeHighlight = () => {
  document.querySelectorAll('pre code').forEach(block => {
    hljs.highlightElement(block);
  });
};

export const calendarAddClass = () => {
  const calendar = document.querySelector("#wp-calendar");
  if (!calendar) return;
  const calendarLinks = document.querySelectorAll("tbody td a");
  if (calendarLinks.length === 0) return;
  calendarLinks.forEach(link => link.classList.add("has-posts"));
};

export const imageResize = (parentNode) => {
  const items = document.querySelectorAll(parentNode);
  if (items.length === 0) return;
  const item = items[0];
  const img = item.querySelector("img");
  if (!img) return;
  const offsetWidth = img.offsetWidth;
  const offsetHeight = img.offsetHeight;
  items.forEach((e) => {
    const im = e.querySelector("img");
    if (im) {
      im.style.width = offsetWidth + "px";
      im.style.height = offsetHeight + "px";
    }
  });
};

export const offCanvasMenu = () => {
  const windowWidth = document.body.clientWidth;
  const offCanvasMenu = document.querySelector("#offcanvasMenu");
  if (!offCanvasMenu) return;
  if (windowWidth < 992) {
    offCanvasMenu.style.top = parseFloat(getComputedStyle(document.documentElement).marginTop) + "px";
  }
};

export const navigationToggle = () => {
  delegate("click", ".navbar-toggler", (event, toggler) => {
    toggler.classList.toggle("active");
  });
};

// utils.js 新增
const SHARE_URLS = {
  twitter: (url, title) => `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
  facebook: (url) => `https://www.facebook.com/sharer/sharer.php?u=${url}`,
  weibo: (url, title) => `https://service.weibo.com/share/share.php?url=${url}&title=${title}`,
  telegram: (url, title) => `https://t.me/share/url?url=${url}&text=${title}`,
};

export const initSocialShare = () => {
  delegate('click', '[data-share]', (e, el) => {
    e.preventDefault();
    const platform = el.dataset.share;
    const builder = SHARE_URLS[platform];
    if (!builder) return;
    const url = encodeURIComponent(el.dataset.url || location.href);
    const title = encodeURIComponent(el.dataset.title || document.title);
    window.open(builder(url, title), '_blank', 'width=600,height=400');
  });
};

export const initLightbox = async () => {
  if (!document.querySelector('[data-glightbox]')) return;
  const { default: GLightbox } = await import('glightbox');
  GLightbox({ selector: '[data-glightbox]' });
};
