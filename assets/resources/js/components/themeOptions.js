/**
 * themeOptions.js
 *
 * Reads lermData (injected by Enqueue::enqueue_scripts) and wires up
 * all PHP-controlled front-end behaviours that relate to the CSS variable
 * system:
 *
 *   • Dark mode  — persistent toggle, system-preference sync
 *   • Sticky header — adds .is-sticky + optional .is-shrunk on scroll
 *   • Transparent header — removes bg until user scrolls past hero
 *   • Reading progress bar — updates #reading-progress width on scroll
 *   • Back-to-top button — shows/hides #scroll-up by threshold
 *   • QQ live chat button — builds the link href from qq_chat_number
 *
 * All scroll-driven features share a single passive scroll listener via
 * a shared handler array to minimise main-thread work.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Constants
// ─────────────────────────────────────────────────────────────────────────────

const DATA    = window.lermData || {};
const STORAGE = 'lerm-theme';
const ATTR    = 'data-theme';

// ─────────────────────────────────────────────────────────────────────────────
// Shared scroll dispatcher
// ─────────────────────────────────────────────────────────────────────────────

/** @type {Array<(scrollY: number) => void>} */
const scrollHandlers = [];
let   scrollTicking  = false;

const onScrollDispatch = () => {
	if ( scrollTicking ) return;
	scrollTicking = true;
	requestAnimationFrame( () => {
		const y = window.scrollY;
		scrollHandlers.forEach( fn => fn( y ) );
		scrollTicking = false;
	} );
};

const registerScrollHandler = ( fn ) => {
	if ( scrollHandlers.length === 0 ) {
		window.addEventListener( 'scroll', onScrollDispatch, { passive: true } );
	}
	scrollHandlers.push( fn );
};

// ─────────────────────────────────────────────────────────────────────────────
// Dark mode
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Apply a colour scheme to <html data-theme="light|dark">.
 * @param {'light'|'dark'} scheme
 */
const applyScheme = ( scheme ) => {
	document.documentElement.setAttribute( ATTR, scheme );
};

/**
 * Resolve the initial colour scheme:
 *   1. Saved user preference in localStorage
 *   2. Back-end default (lermData.darkModeDefault)
 *   3. System preference
 */
const resolveInitialScheme = () => {
	const saved = localStorage.getItem( STORAGE );
	if ( saved === 'light' || saved === 'dark' ) return saved;

	const def = DATA.darkModeDefault || 'system';
	if ( def === 'light' ) return 'light';
	if ( def === 'dark'  ) return 'dark';

	// 'system' — follow prefers-color-scheme
	return window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
};

/**
 * Toggle the current scheme and persist it.
 */
const toggleScheme = () => {
	const current = document.documentElement.getAttribute( ATTR ) || 'light';
	const next    = current === 'dark' ? 'light' : 'dark';
	applyScheme( next );
	localStorage.setItem( STORAGE, next );
};

/**
 * Build and insert the toggle button element.
 * Position is controlled by lermData.darkModeToggle ('navbar' or 'floating').
 */
const buildToggleButton = () => {
	const btn = document.createElement( 'button' );
	btn.id          = 'dark-mode-toggle';
	btn.type        = 'button';
	btn.className   = 'btn btn-sm btn-custom dark-mode-toggle';
	btn.setAttribute( 'aria-label', 'Toggle colour scheme' );
	btn.innerHTML   = '<i class="fa fa-moon" aria-hidden="true"></i>';

	btn.addEventListener( 'click', () => {
		toggleScheme();
		// Swap icon
		const icon = btn.querySelector( 'i' );
		if ( icon ) {
			const dark = document.documentElement.getAttribute( ATTR ) === 'dark';
			icon.className = dark ? 'fa fa-sun' : 'fa fa-moon';
		}
	} );

	return btn;
};

const initDarkMode = () => {
	if ( ! DATA.darkMode ) return;

	// Apply immediately (before paint) to avoid flash
	applyScheme( resolveInitialScheme() );

	// Sync toggle icon once DOM is available
	const btn = buildToggleButton();
	const pos = DATA.darkModeToggle || 'navbar';
	const dark = document.documentElement.getAttribute( ATTR ) === 'dark';
	const icon = btn.querySelector( 'i' );
	if ( icon ) icon.className = dark ? 'fa fa-sun' : 'fa fa-moon';

	if ( pos === 'navbar' ) {
		// Append inside .navbar-collapse > .navbar-nav, or fallback to end of .navbar
		const nav = document.querySelector( '.navbar-nav' ) || document.querySelector( '.navbar' );
		if ( nav ) {
			const li = document.createElement( 'li' );
			li.className = 'nav-item d-flex align-items-center ms-1';
			li.appendChild( btn );
			nav.appendChild( li );
		}
	} else {
		// Floating — absolute button beside the scroll-up button
		btn.classList.add( 'btn-custom' );
		const container = document.querySelector( '.position-fixed.d-grid' );
		if ( container ) {
			container.prepend( btn );
		} else {
			const wrap = document.createElement( 'div' );
			wrap.style.cssText = 'position:fixed;bottom:4rem;right:1rem;z-index:1040;';
			wrap.appendChild( btn );
			document.body.appendChild( wrap );
		}
	}

	// Follow system preference change if user has not explicitly chosen
	window.matchMedia( '(prefers-color-scheme: dark)' ).addEventListener( 'change', ( e ) => {
		if ( localStorage.getItem( STORAGE ) ) return; // user has a saved preference
		applyScheme( e.matches ? 'dark' : 'light' );
	} );
};

// ─────────────────────────────────────────────────────────────────────────────
// Sticky header
// ─────────────────────────────────────────────────────────────────────────────

const initStickyHeader = () => {
	if ( ! DATA.stickyHeader ) return;

	const header = document.querySelector( '#site-header' );
	if ( ! header ) return;

	header.classList.add( 'sticky-top' );

	if ( DATA.stickyHeaderShrink ) {
		const SHRINK_AT = 80; // px scrolled before shrinking
		registerScrollHandler( ( y ) => {
			header.classList.toggle( 'is-shrunk', y > SHRINK_AT );
		} );
	}
};

// ─────────────────────────────────────────────────────────────────────────────
// Transparent header on hero
// ─────────────────────────────────────────────────────────────────────────────

const initTransparentHeader = () => {
	if ( ! DATA.transparentHeader ) return;

	const header = document.querySelector( '#site-header' );
	if ( ! header ) return;

	// Only activate if a carousel/hero element exists directly below the header
	const hero = document.querySelector( '.carousel, .hero-banner' );
	if ( ! hero ) return;

	const heroBottom = () => hero.getBoundingClientRect().bottom + window.scrollY;

	header.classList.add( 'header-transparent' );

	registerScrollHandler( ( y ) => {
		header.classList.toggle( 'header-transparent', y < heroBottom() );
	} );
};

// ─────────────────────────────────────────────────────────────────────────────
// Reading progress bar
// ─────────────────────────────────────────────────────────────────────────────

const initReadingProgress = () => {
	if ( ! DATA.readingProgress ) return;

	const bar = document.getElementById( 'reading-progress' );
	if ( ! bar ) return;

	const update = () => {
		const doc    = document.documentElement;
		const body   = document.body;
		const total  = Math.max( doc.scrollHeight, body.scrollHeight ) - doc.clientHeight;
		const pct    = total > 0 ? Math.min( ( window.scrollY / total ) * 100, 100 ) : 0;
		bar.style.width = pct.toFixed( 2 ) + '%';
		bar.setAttribute( 'aria-valuenow', Math.round( pct ) );
	};

	registerScrollHandler( update );
	update(); // initialise immediately
};

// ─────────────────────────────────────────────────────────────────────────────
// Back-to-top button
// ─────────────────────────────────────────────────────────────────────────────

const initBackToTop = () => {
	const btn = document.getElementById( 'scroll-up' );
	if ( ! btn ) return;

	// If disabled via options, hide permanently
	if ( DATA.backToTop === false ) {
		btn.style.display = 'none';
		return;
	}

	const THRESHOLD = Number( DATA.backToTopThreshold ) || 400;

	// Show / hide based on scroll position
	const toggle = ( y ) => {
		btn.classList.toggle( 'd-none', y < THRESHOLD );
	};

	// Start hidden
	btn.classList.add( 'd-none' );
	registerScrollHandler( toggle );
};

// ─────────────────────────────────────────────────────────────────────────────
// QQ live chat button
// ─────────────────────────────────────────────────────────────────────────────

const initQQChat = () => {
	if ( ! DATA.qqChatEnable || ! DATA.qqChatNumber ) return;

	const btn = document.querySelector( 'a[href*="wpa.qq.com"]' );
	if ( ! btn ) return;

	const num  = encodeURIComponent( DATA.qqChatNumber );
	btn.href   = `https://wpa.qq.com/msgrd?v=3&uin=${num}&site=qq&menu=yes`;
	btn.hidden = false;
};

// ─────────────────────────────────────────────────────────────────────────────
// CSS variable live preview (Block Editor / Customizer)
// Allows future Customizer integration to see token changes in real time.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Set one CSS custom property on :root.
 * Called from a Customizer postMessage transport or a future colour picker.
 *
 * @param {string} name  Full var name, e.g. '--lerm-color-primary'
 * @param {string} value CSS value string
 */
export const setCSSVariable = ( name, value ) => {
	document.documentElement.style.setProperty( name, value );
};

/**
 * Batch-update multiple tokens.
 * @param {Record<string, string>} map  { '--lerm-xxx': 'value', … }
 */
export const setCSSVariables = ( map ) => {
	const root = document.documentElement;
	Object.entries( map ).forEach( ( [ name, value ] ) => {
		root.style.setProperty( name, value );
	} );
};

// ─────────────────────────────────────────────────────────────────────────────
// Public initialiser
// ─────────────────────────────────────────────────────────────────────────────

let _initialized = false;

export const initializeThemeOptions = () => {
	if ( _initialized ) return;
	_initialized = true;

	// Dark mode is applied before DOMContentLoaded to prevent FOUC,
	// so we call it unconditionally here and it guards itself.
	initDarkMode();
	initStickyHeader();
	initTransparentHeader();
	initReadingProgress();
	initBackToTop();
	initQQChat();
};

// Apply dark mode scheme as early as possible (synchronous, before DOM ready)
// so there is no flash of wrong colour scheme on page load.
if ( DATA.darkMode ) {
	applyScheme( resolveInitialScheme() );
}
