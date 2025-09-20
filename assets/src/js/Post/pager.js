// src/js/post-renderer.js
import DOMPurify from 'dompurify';
// 期望全局 lermData 已由 wp_localize_script 注入（rest root + nonce）
import { fadeIn, fadeOut, crossFadeReplace } from '../utils.js';

// prefer imported DOMPurify but accept global fallback
let DOMPurifyInstance = (typeof DOMPurify !== 'undefined') ? DOMPurify : (typeof window !== 'undefined' ? window.DOMPurify : null);

export function attachDOMPurify(dp) {
  DOMPurifyInstance = dp;
}

/* Helper: 获取特色图 URL（优先使用我们通过 register_rest_field 注入的字段） */
function getFeaturedImageUrl(post) {
  if (!post) return '';
  if (post.featured_image_url) return post.featured_image_url;
  if (post._embedded && post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0]) {
    return post._embedded['wp:featuredmedia'][0].source_url || '';
  }
  return '';
}

function stripHtml(html = '') {
  const tmp = document.createElement('div');
  tmp.innerHTML = html || '';
  return tmp.textContent || tmp.innerText || '';
}

function sanitizeHtml(html, opts) {
  if (!html) return '';
  if (DOMPurifyInstance && typeof DOMPurifyInstance.sanitize === 'function') {
    try {
      return DOMPurifyInstance.sanitize(html, opts || {});
    } catch (e) {
      console.warn('DOMPurify.sanitize failed, falling back to stripTags', e);
    }
  }
  // Fallback: conservative - strip all tags (safe but removes formatting)
  return String(html).replace(/<[^>]*>?/gm, '');
}

function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/* 单个卡片的 template 渲染（返回 article 元素） */
export function renderPostCard(post) {
  const imgUrl = getFeaturedImageUrl(post);
  const titleHtml = post.title && post.title.rendered ? post.title.rendered : '';
  const excerptHtml = post.excerpt && post.excerpt.rendered ? post.excerpt.rendered
    : (post.content && post.content.rendered ? post.content.rendered.split(' ').slice(0, 30).join(' ') + '…' : '');

  // sanitize server-provided HTML
  const safeTitle = sanitizeHtml(titleHtml, { ALLOWED_TAGS: ['strong', 'em', 'b', 'i', 'a', 'span'] });
  const safeExcerpt = sanitizeHtml(excerptHtml, { ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'a', 'ul', 'li', 'ol', 'span'] });

  // Note: if you can provide actual width/height from server, replace aspect-ratio with explicit width/height attributes
  const imageBlock = imgUrl ? `
    <div class="col-md-3 d-none d-md-block">
      <div class="p-2 h-100">
        <div style="width:100%; aspect-ratio:16/9; overflow:hidden; border-radius:0.375rem;">
          <img src="${escapeHtml(imgUrl)}" alt="${(post.title && post.title.rendered) ? escapeHtml(stripHtml(post.title.rendered)) : ''}"
               class="img-fluid rounded h-100" loading="lazy"
               style="width:100%; height:100%; object-fit:cover; display:block;" />
        </div>
      </div>
    </div>` : '';

  const html = `
    <article id="post-${post.id}" class="card summary mb-3 h-100 p-0 p-md-3" data-post-id="${post.id}">
      <div class="row g-0 align-items-stretch">
        ${imageBlock}
        <div class="${imgUrl ? 'col-md-9' : 'col-12'} d-flex">
          <div class="card-body p-2 d-flex flex-column">
            <h2 class="entry-title card-title mb-1">
              <a href="${escapeHtml(post.link || '#')}" rel="bookmark">${safeTitle || '(无标题)'}</a>
              ${post.sticky ? `<span class="badge bg-danger ms-2">Sticky</span>` : ''}
            </h2>
            <div class="mb-2">${safeExcerpt}</div>
            <div class="mt-auto small text-muted">发布于 ${post.date ? new Date(post.date).toLocaleDateString() : ''} ${escapeHtml(post._embedded?.author?.[0]?.name || '')} ${escapeHtml(post.category || '')}</div>
          </div>
        </div>
      </div>
    </article>
  `.trim();

  const template = document.createElement('template');
  template.innerHTML = html;
  return template.content.firstElementChild;
}

/* 占位骨架：生成与最终布局同级的 col（避免跳动） */
function renderPlaceholders(mount, count = 6) {
  mount.innerHTML = '';
  const frag = document.createDocumentFragment();
  for (let i = 0; i < count; i++) {
    const col = document.createElement('div');
    // 与实际卡片列匹配（默认在 grid 使用 col-12 col-md-6 col-lg-4）
    col.className = 'col-12 col-md-12 col-lg-12 mb-3';
    col.innerHTML = `
      <div class="card placeholder-glow summary mb-0 h-100 p-0 p-md-3">
        <div class="d-flex flex-column p-2" style="min-height:160px;">
          <h2 class="entry-title card-title mb-2"><span class="placeholder w-50 d-block" style="height:1.1rem;"></span></h2>
          <div class="card-text mb-2">
            <span class="placeholder w-100 d-block mb-2" style="height:0.9rem;"></span>
            <span class="placeholder w-75 d-block mb-2" style="height:0.9rem;"></span>
            <span class="placeholder w-50 d-block" style="height:0.9rem;"></span>
          </div>
          <div class="mt-auto">
            <span class="placeholder btn disabled w-25" style="height:2rem; display:inline-block;"></span>
          </div>
        </div>
      </div>
    `;
    frag.appendChild(col);
  }
  mount.appendChild(frag);
}

/* 渲染一个列表（挂载到 container），并使用 crossFadeReplace 做无闪烁替换 */
export async function fetchAndRenderPosts({ containerSelector = '#app', per_page = 6, embed = true, minSkeletonMs = 250 } = {}) {
  const mount = document.querySelector(containerSelector);
  if (!mount) return;

  renderPlaceholders(mount, per_page);

  const t0 = Date.now();
  const url = `${lermData.root}wp/v2/posts?per_page=${per_page}${embed ? '&_embed' : ''}`;
  try {
    const res = await fetch(url, {
      headers: lermData && lermData.nonce ? { 'X-WP-Nonce': lermData.nonce } : {},
      credentials: 'same-origin'
    });
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);

    const posts = await res.json();

    // ensure skeleton shows at least minSkeletonMs so it isn't a blink
    const elapsed = Date.now() - t0;
    if (elapsed < minSkeletonMs) await new Promise(r => setTimeout(r, minSkeletonMs - elapsed));

    // build new content as a row container (same structure as mount children)
    const newRow = document.createElement('div');
    newRow.className = 'row g-4';
    const frag = document.createDocumentFragment();
    posts.forEach(p => {
      const col = document.createElement('div');
      col.className = 'col-12 col-md-6 col-lg-4 mb-3';
      const card = renderPostCard(p);
      col.appendChild(card);
      frag.appendChild(col);
    });
    newRow.appendChild(frag);

    // crossFadeReplace will insert newRow into mount and fade out old placeholders
    await crossFadeReplace(mount, newRow, { duration: 240 });
  } catch (err) {
    mount.innerHTML = `<div class="col-12"><div class="alert alert-danger" role="alert">拉取失败：${escapeHtml(err.message)}</div></div>`;
  }
}

/* 渲染单篇文章（通过id）并替换指定容器的内容 */
export default async function fetchAndRenderSinglePost(postId, containerSelector = '#post-single') {
  const mount = document.querySelector(containerSelector);
  if (!mount) return;
  mount.innerHTML = `<div class="text-center my-4">加载中…</div>`;
  const url = `${lermData.root}wp/v2/posts/${postId}?_embed`;
  try {
    const res = await fetch(url, {
      headers: lermData && lermData.nonce ? { 'X-WP-Nonce': lermData.nonce } : {},
      credentials: 'same-origin'
    });
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    const post = await res.json();
    const card = renderPostCard(post);
    const container = document.createElement('div');
    container.className = 'row';
    const col = document.createElement('div');
    col.className = 'col-12';
    col.appendChild(card);
    container.appendChild(col);
    await crossFadeReplace(mount, container, { duration: 200 });
  } catch (err) {
    mount.innerHTML = `<div class="alert alert-danger">拉取失败：${escapeHtml(err.message)}</div>`;
  }
}

/* -----------------------------
   Pagination / Load More logic
   ----------------------------- */

let _pagerState = {
  page: 1,
  per_page: 6,
  totalPages: null,
  loading: false,
  embed: true,
  containerSelector: '#app',
  buttonContainer: '#load-more-container',
  statusContainer: '#load-status',
  autoScroll: false, // set true to enable infinite scroll
  observer: null,
  currentFetchController: null,
};

// helper to create the load more button
function createLoadMoreButton() {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'btn btn-outline-primary';
  btn.id = 'load-more-btn';
  btn.textContent = '加载更多';
  btn.setAttribute('aria-live', 'polite');
  btn.setAttribute('aria-controls', _pagerState.containerSelector.replace('#', ''));
  return btn;
}

// append posts (used for load-more). newPosts is array of post objects.
async function appendPostsToGrid(newPosts, mountSelector = _pagerState.containerSelector) {
  const mount = document.querySelector(mountSelector);
  if (!mount || !Array.isArray(newPosts) || newPosts.length === 0) return;

  // create fragment of columns
  const frag = document.createDocumentFragment();
  const cardEls = [];
  for (const p of newPosts) {
    const col = document.createElement('div');
    col.className = 'col-12 col-md-6 col-lg-4 mb-3';
    const card = renderPostCard(p);
    // start invisible for animation
    card.style.opacity = '0';
    card.style.transform = 'translateY(6px)';
    col.appendChild(card);
    frag.appendChild(col);
    cardEls.push(card);
  }

  // append once (reduces reflow)
  mount.appendChild(frag);

  // wait for images inside the newly appended cards to load to avoid layout jump when animating in
  await waitForImagesIn(cardEls);

  // fade in each new card sequentially (small stagger)
  for (let i = 0; i < cardEls.length; i++) {
    const el = cardEls[i];
    await rafPromise();
    try {
      if (typeof fadeIn === 'function') {
        // fadeIn signature assumed: element, { duration, translateY }
        await fadeIn(el, { duration: 220, translateY: 0 });
      } else {
        el.style.transition = 'opacity 220ms ease, transform 220ms ease';
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
        await wait(240);
      }
    } catch (e) {
      el.style.opacity = '';
      el.style.transform = '';
    }
    await wait(36);
  }
}

// fetch posts page (returns {posts, totalPages})
async function fetchPostsPage(page = 1, per_page = 6, embed = true, { signal } = {}) {
  if (!window.lermData || !lermData.root) {
    throw new Error('lermData 未初始化');
  }
  const url = `${lermData.root}wp/v2/posts?per_page=${per_page}&page=${page}${embed ? '&_embed' : ''}`;
  const headers = lermData && lermData.nonce ? { 'X-WP-Nonce': lermData.nonce } : {};
  const res = await fetch(url, { headers, credentials: 'same-origin', signal });
  if (!res.ok) {
    const txt = await res.text().catch(() => '');
    throw new Error(`HTTP ${res.status} ${res.statusText} ${txt}`);
  }
  const posts = await res.json();
  const totalPagesHeader = res.headers.get('X-WP-TotalPages') || res.headers.get('x-wp-totalpages');
  const totalPages = totalPagesHeader ? parseInt(totalPagesHeader, 10) : null;
  return { posts, totalPages };
}

// update button UI based on state
function updateLoadMoreButton(btn) {
  if (!btn) return;
  if (_pagerState.loading) {
    btn.disabled = true;
    btn.textContent = '加载中…';
    btn.setAttribute('aria-busy', 'true');
  } else {
    btn.disabled = false;
    btn.setAttribute('aria-busy', 'false');
    if (_pagerState.totalPages !== null && _pagerState.page >= _pagerState.totalPages) {
      btn.style.display = 'none';
    } else {
      btn.style.display = '';
      btn.textContent = '加载更多';
    }
  }

  // update status region
  const status = document.querySelector(_pagerState.statusContainer);
  if (status) {
    status.textContent = _pagerState.totalPages
      ? `第 ${_pagerState.page} / ${_pagerState.totalPages} 页`
      : `已加载 ${_pagerState.page * _pagerState.per_page} 条（总数未知）`;
  }
}

// public initializer
export function initLoadMore(opts = {}) {
  _pagerState.containerSelector = opts.containerSelector || _pagerState.containerSelector;
  _pagerState.per_page = opts.per_page || _pagerState.per_page;
  _pagerState.buttonContainer = opts.buttonContainer || _pagerState.buttonContainer;
  _pagerState.statusContainer = opts.statusContainer || _pagerState.statusContainer;
  _pagerState.embed = (typeof opts.embed === 'boolean') ? opts.embed : _pagerState.embed;
  _pagerState.autoScroll = !!opts.autoScroll;

  const btnContainer = document.querySelector(_pagerState.buttonContainer);
  if (!btnContainer) {
    console.warn('LoadMore: button container not found:', _pagerState.buttonContainer);
    return;
  }
  btnContainer.innerHTML = '';

  // create status region if missing
  let status = document.querySelector(_pagerState.statusContainer);
  if (!status) {
    status = document.createElement('div');
    status.id = _pagerState.statusContainer.replace('#', '');
    status.setAttribute('role', 'status');
    status.className = 'visually-hidden';
    btnContainer.appendChild(status);
  }

  const btn = createLoadMoreButton();
  btnContainer.appendChild(btn);

  btn.addEventListener('click', async () => {
    await handleLoadMore(btn);
  });

  // optionally set up infinite scroll via IntersectionObserver watching the button
  if (_pagerState.autoScroll && 'IntersectionObserver' in window) {
    if (_pagerState.observer) {
      _pagerState.observer.disconnect();
      _pagerState.observer = null;
    }
    let ticking = false;
    _pagerState.observer = new IntersectionObserver(entries => {
      for (const e of entries) {
        if (e.isIntersecting && !_pagerState.loading && !ticking) {
          ticking = true;
          handleLoadMore(btn).catch(err => console.error('auto load error', err)).finally(() => {
            setTimeout(() => { ticking = false; }, 300);
          });
        }
      }
    }, { root: null, rootMargin: '300px', threshold: 0.1 });
    _pagerState.observer.observe(btn);
  }

  updateLoadMoreButton(btn);
}

// handles the click / auto invocation
async function handleLoadMore(btn) {
  if (_pagerState.loading) return;
  _pagerState.loading = true;
  updateLoadMoreButton(btn);

  // abort previous fetch if running
  if (_pagerState.currentFetchController) {
    try { _pagerState.currentFetchController.abort(); } catch (e) { /* ignore */ }
    _pagerState.currentFetchController = null;
  }
  const controller = new AbortController();
  _pagerState.currentFetchController = controller;

  const nextPage = _pagerState.page + 1;

  try {
    const { posts, totalPages } = await fetchPostsPage(nextPage, _pagerState.per_page, _pagerState.embed, { signal: controller.signal });
    if (typeof totalPages === 'number') _pagerState.totalPages = totalPages;

    if (!Array.isArray(posts) || posts.length === 0) {
      _pagerState.page = (_pagerState.totalPages !== null) ? _pagerState.totalPages : nextPage;
      btn.style.display = 'none';
      if (_pagerState.observer) _pagerState.observer.disconnect();
      return;
    }

    await appendPostsToGrid(posts, _pagerState.containerSelector);

    _pagerState.page = nextPage;

    if (_pagerState.totalPages !== null && _pagerState.page >= _pagerState.totalPages) {
      btn.style.display = 'none';
      if (_pagerState.observer) {
        _pagerState.observer.disconnect();
      }
    }
  } catch (err) {
    if (err.name === 'AbortError') {
      console.warn('LoadMore fetch aborted');
    } else {
      console.error('LoadMore error', err);
      const prev = btn.parentNode.querySelector('.loadmore-error');
      if (prev) prev.remove();
      const errEl = document.createElement('div');
      errEl.className = 'text-danger small mt-2 loadmore-error';
      errEl.textContent = `加载失败：${err.message}`;
      btn.parentNode.appendChild(errEl);
    }
  } finally {
    _pagerState.currentFetchController = null;
    _pagerState.loading = false;
    updateLoadMoreButton(btn);
  }
}

// optional helper: load initial content (if you want loadMore to perform the first load instead of server side render)
export async function loadInitialAndInit(opts = {}) {
  _pagerState.page = 0; // so first load increments to 1
  initLoadMore(opts);
  const btn = document.querySelector('#load-more-btn');
  if (btn) await handleLoadMore(btn);
}

// public utility: reset pager state (useful when switching context/categories)
export function resetPager({ page = 1, totalPages = null } = {}) {
  _pagerState.page = page;
  _pagerState.totalPages = totalPages;
  _pagerState.loading = false;
  if (_pagerState.observer) {
    _pagerState.observer.disconnect();
    _pagerState.observer = null;
  }
  if (_pagerState.currentFetchController) {
    try { _pagerState.currentFetchController.abort(); } catch (e) {}
    _pagerState.currentFetchController = null;
  }
}

/* -----------------------------
   small helpers
   ----------------------------- */
function wait(ms) {
  return new Promise(r => setTimeout(r, ms));
}
function rafPromise() {
  return new Promise(r => requestAnimationFrame(r));
}
function waitForImagesIn(elements) {
  const imgs = [];
  elements.forEach(el => {
    imgs.push(...Array.from(el.querySelectorAll('img')));
  });
  const uniq = Array.from(new Set(imgs));
  if (uniq.length === 0) return Promise.resolve();
  return Promise.all(uniq.map(img => {
    if (img.complete) return Promise.resolve();
    return new Promise(resolve => {
      img.addEventListener('load', resolve, { once: true });
      img.addEventListener('error', resolve, { once: true });
      setTimeout(resolve, 3000);
    });
  }));
}
