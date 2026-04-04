import { initializeCalendar } from './calendar.js';
import { initializeCodeHighlight } from './codeHighlight.js';
import { initializeForms } from './forms.js';
import { initializeLazyImages } from './lazyImages.js';
import { likeBtnHandle, likeBtnSuccess } from './likes.js';
import { appendPostsToDOM, loadMoreHandle } from './loadMore.js';
import { initializeNavigation } from './navigation.js';
import { viewCountHandle, viewCountSuccess } from './views.js';
import { handleCommentSuccess } from './comments.js';
import { handleUpdateProfileSuccess } from './profile.js';
import { initializeScrollAnimate } from './scrollAnimate.js';
import { initializeScrollTop } from './scrollTop.js';
import{initializeSearch} from './search.js';
import { initializeThemeOptions, setCSSVariable, setCSSVariables } from './themeOptions.js';

export const initializePageComponents = () => {
	initializeThemeOptions();
	initializeNavigation();
	initializeSearch();
	initializeScrollTop();
	initializeForms();
	likeBtnHandle();
	viewCountHandle();
	loadMoreHandle();
};

export const initializeDynamicComponents = () => {
	initializeScrollAnimate();
	initializeLazyImages();
	initializeCodeHighlight();
	initializeCalendar();
};

export {
	appendPostsToDOM,
	handleCommentSuccess,
	handleUpdateProfileSuccess,
	initializeCalendar,
	initializeCodeHighlight,
	initializeLazyImages,
	initializeNavigation,
	initializeScrollAnimate,
	initializeScrollTop,
	initializeThemeOptions,
	likeBtnHandle,
	likeBtnSuccess,
	loadMoreHandle,
	setCSSVariable,
	setCSSVariables,
	viewCountHandle,
	viewCountSuccess,
};