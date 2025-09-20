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

// utils.js （替换或新增此函数）
export async function crossFadeReplace(mount, newContent, { duration = 240, easing = 'cubic-bezier(.2,.8,.2,1)' } = {}) {
  if (!mount) return;
  // ensure mount is positioned so absolute layers can overlay
  const prevPosition = mount.style.position || '';
  if (getComputedStyle(mount).position === 'static') {
    mount.style.position = 'relative';
  }

  // Keep a min-height so layout doesn't collapse during crossfade
  const mountRect = mount.getBoundingClientRect();
  if (!mount.style.minHeight) {
    mount.style.minHeight = `${Math.max(64, Math.round(mountRect.height))}px`;
  }

  // Create overlay layers
  const oldLayer = document.createElement('div');
  const newLayer = document.createElement('div');
  oldLayer.className = newLayer.className = 'xfade-layer';
  // Put existing children into oldLayer
  while (mount.firstChild) {
    oldLayer.appendChild(mount.firstChild);
  }
  // Put newContent into newLayer (clone if you want to keep original)
  newLayer.appendChild(newContent);

  // initial styles: newLayer invisible
  newLayer.style.opacity = '0';
  newLayer.style.pointerEvents = 'none';
  oldLayer.style.pointerEvents = 'none';

  // append layers
  mount.appendChild(oldLayer);
  mount.appendChild(newLayer);

  // wait for images in newLayer to load (prevents layout jumps)
  await waitForImages(newLayer);

  // hint to browser for compositing
  oldLayer.style.willChange = newLayer.style.willChange = 'opacity, transform';
  oldLayer.style.transform = newLayer.style.transform = 'translateZ(0)';

  // animate with Web Animations API (works well and returns a Promise)
  let anims = [];
  try {
    const fadeIn = newLayer.animate([{ opacity: 0 }, { opacity: 1 }], { duration, easing, fill: 'forwards' });
    const fadeOut = oldLayer.animate([{ opacity: 1 }, { opacity: 0 }], { duration, easing, fill: 'forwards' });
    anims = [fadeIn, fadeOut];

    await Promise.all(anims.map(a => a.finished));
  } catch (e) {
    // if WAAPI not available or fails, fallback to immediate swap
    newLayer.style.opacity = '1';
    oldLayer.style.opacity = '0';
  }

  // remove old layer and move new content back to normal flow
  // (we append its children into a fresh container to preserve structure)
  const finalContainer = document.createElement('div');
  // keep same classes as your expected outer wrapper (e.g. row)
  finalContainer.className = newContent.className || '';
  while (newLayer.firstChild) {
    finalContainer.appendChild(newLayer.firstChild);
  }

  // cleanup mount and restore
  mount.innerHTML = '';
  mount.appendChild(finalContainer);

  // cleanup styles we changed
  if (!prevPosition) mount.style.position = '';
  mount.style.minHeight = '';
}
  
// helper: wait for images to finish loading (resolve even if errored)
function waitForImages(node) {
  const imgs = Array.from(node.querySelectorAll('img'));
  if (imgs.length === 0) return Promise.resolve();
  return Promise.all(imgs.map(img => {
    if (img.complete) return Promise.resolve();
    return new Promise(resolve => {
      img.addEventListener('load', resolve, { once: true });
      img.addEventListener('error', resolve, { once: true });
      // if not fired in 3s, resolve (defensive)
      setTimeout(resolve, 3000);
    });
  }));
}
