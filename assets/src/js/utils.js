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

export const codeHighlight = () => {
  if (typeof hljs !== "undefined") {
    document.querySelectorAll("pre code").forEach((block) => {
      hljs.highlightBlock(block);
    });
  }
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

export const initializeWOW = () => {
  if (typeof WOW === "undefined") return;
  const wow = new WOW({
    boxClass: "loading-animate",
    animateClass: "animated",
    offset: 0,
    mobile: true,
    live: true
  });
  wow.init();
};
