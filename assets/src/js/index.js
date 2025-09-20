
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import 'animate.css';
import WOW from 'wowjs';

// main.js
import { DOMContentLoaded, safeRequestIdleCallback, initializeWOW, lazyLoadImages, codeHighlight, calendarAddClass, offCanvasMenu, navigationToggle, scrollTop } from './utils.js';
import { likeBtnHandle, loadMoreHandle, handleCommentSuccess, handleLoginSuccess, handleUpdateProfileSuccess } from './components.js';
import FormService from './services/FormService.js';
import BaseService from './services/BaseService.js';
import { fetchAndRenderPosts, initLoadMore, attachDOMPurify } from './Post/post.js';
import DOMPurify from 'dompurify';
// import { attachDOMPurify } from './renderPostCard.template';
attachDOMPurify(DOMPurify);
// import LoadPageService from './services/LoadPageService.js';

// const loadPageService = new LoadPageService({
//   apiUrl: lermData.url,
//   containerId: "page-ajax",
//   headers: { 'X-WP-Nonce': lermData.nonce },
//   action: 'load_page_content',
//   ignoreUrls: ["/regist", "/reset/", "/wp-admin/", "/wp-login.php"],
//   cacheExpiry: 1 * 60 * 1000,
//   errorText: "Failed to load the content. Please try again later."
// });

// const loadRegistService = new LoadPageService({
//   apiUrl: lermData.url,
//   containerId: "myTabContent",
//   action: 'load_form',
//   allowUrls: ["/regist", "/login/", "/reset/"],
//   errorText: "Failed to load the content. Please try again later."
// });

const formAjaxHandle = () => {
  const formConfigs = [
    // { formId: 'login', action: lermData.login_action, security: lermData.login_nonce },
    // { formId: 'reset', action: lermData.reset_action, security: lermData.reset_nonce },
    // { formId: 'regist', action: lermData.regist_action, security: lermData.regist_nonce, passwordToggle: true },
    { formId: 'commentform', action: lermData.comment_action, security: lermData.nonce }
  ];
  if (lermData.loggedin) {
    formConfigs.push({ formId: 'update-profile', action: lermData.profile_action, security: lermData.profile_nonce });
  }

  formConfigs.forEach(config => {
    const form = document.getElementById(config.formId);
    if (!form) return;
    const FormHandle = new FormService({
      ...config,
      apiUrl: lermData.rest_url,
      messageId: `${config.formId}-msg`,
    });
    if (config.formId === 'commentform') FormHandle.afterSubmitSuccess = handleCommentSuccess;
    if (config.formId === 'login') FormHandle.afterSubmitSuccess = handleLoginSuccess;
    if (config.formId === 'update-profile') FormHandle.afterSubmitSuccess = handleUpdateProfileSuccess;
  });
};

DOMContentLoaded(async () => {
  // fetchAndRenderPosts({ containerSelector: '#app', per_page: 9 });
  initLoadMore({ containerSelector: '#app', per_page: 6, buttonContainer: '#load-more-container', autoScroll: false });

  safeRequestIdleCallback(() => {
    initializeWOW();
    lazyLoadImages();
    codeHighlight();
    calendarAddClass();
    offCanvasMenu();
    navigationToggle();
  });

  scrollTop();
  formAjaxHandle();
  likeBtnHandle();
  // loadMoreHandle();
});

// Re-run some init on dynamic content loads
document.addEventListener('contentLoaded', () => {
  scrollTop();
  formAjaxHandle();
});
