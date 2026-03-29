import 'bootstrap';
import '../css/index.css';

import {
	initializeDynamicComponents,
	initializePageComponents,
} from './components/index.js';
import { DOMContentLoaded } from './utils/dom.js';
import { safeRequestIdleCallback } from './utils/scheduler.js';

DOMContentLoaded(() => {
	initializePageComponents();

	safeRequestIdleCallback(() => {
		initializeDynamicComponents();
	});
});

document.addEventListener('contentLoaded', () => {
	initializeDynamicComponents();
});
