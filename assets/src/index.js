// main.js
import { DOMContentLoaded, safeRequestIdleCallback, initializeWOW, lazyLoadImages, codeHighlight, calendarAddClass, offCanvasMenu, navigationToggle, scrollTop } from './utils.js';
import { likeBtnHandle, loadMoreHandle, handleCommentSuccess, handleLoginSuccess, handleUpdateProfileSuccess } from './components.js';
import FormService from './services/FormService.js';
import LoadPageService from './services/LoadPageService.js';

const loadPageService = new LoadPageService({
  apiUrl: lermData.url,
  containerId: "page-ajax",
  headers: { 'X-WP-Nonce': lermData.nonce },
  action: 'load_page_content',
  ignoreUrls: ["/regist", "/reset/", "/wp-admin/", "/wp-login.php"],
  cacheExpiry: 1 * 60 * 1000,
  errorText: "Failed to load the content. Please try again later."
});

const loadRegistService = new LoadPageService({
  apiUrl: lermData.url,
  containerId: "myTabContent",
  action: 'load_form',
  allowUrls: ["/regist", "/login/", "/reset/"],
  errorText: "Failed to load the content. Please try again later."
});

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
  // 1) 先创建实例（确保传入正确的 apiUrl / action / containerId）
  // const svc = new LoadPageService({
  //   apiUrl: (window.lermData?.rest_url ?? '/wp-json/lerm/v1') + '/page', // 或 '/wp-admin/admin-ajax.php'
  //   action: '', // admin-ajax 情况下填 'my_load_page'
  //   containerId: "page-ajax",
  //   allowUrls: [],
  //   ignoreUrls: ['/wp-login.php', '/wp-admin/'],
  //   cacheExpiry: 5 * 60 * 1000,
  // });

  // // 2) 暴露实例供调试 & 手动清缓存
  // window.lermLoadPage = svc;

  // // 3) 等待 init 完成 —— 确保 delegate、popstate 等已注册
  // try {
  //   await svc.init();
  // } catch (err) {
  //   console.error('LoadPageService init failed:', err);
  //   // 可选回退：location.reload();
  // }
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
  loadMoreHandle();
});

// Re-run some init on dynamic content loads
document.addEventListener('contentLoaded', () => {
  scrollTop();
  formAjaxHandle();
});
