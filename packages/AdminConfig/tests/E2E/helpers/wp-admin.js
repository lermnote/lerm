const { expect } = require( '@playwright/test' );

const adminUser = process.env.LERM_ADMIN_CONFIG_ADMIN_USER || 'admin';
const adminPass = process.env.LERM_ADMIN_CONFIG_ADMIN_PASS || 'password';
const adminBase = '/wp-admin';

async function login( page ) {
	const loginUrl = `/wp-login.php?redirect_to=${ encodeURIComponent( `${ adminBase }/` ) }`;

	await page.goto( loginUrl );

	if ( page.url().includes( '/wp-admin/' ) ) {
		return;
	}

	await page.locator( '#user_login' ).fill( adminUser );
	await page.locator( '#user_pass' ).fill( adminPass );
	await page.locator( '#wp-submit' ).click();

	try {
		await expect( page ).toHaveURL( /\/wp-admin\// );
	} catch ( error ) {
		const loginError = await page.locator( '#login_error' ).textContent().catch( () => '' );
		const message = loginError ? ` Login error: ${ loginError.trim().replace( /\s+/g, ' ' ) }` : '';

		throw new Error( `WordPress login did not reach wp-admin.${ message }` );
	}
}

function acceptNextDialog( page ) {
	page.once( 'dialog', async ( dialog ) => {
		await dialog.accept();
	} );
}

function restRouteForEndpoint( endpoint = '' ) {
	if ( endpoint === 'save' ) return '/save';
	if ( endpoint === 'reset' ) return '/reset';
	if ( endpoint === 'export' ) return '/export';
	if ( endpoint === 'import' ) return '/import';
	if ( endpoint === 'data-source' ) return '/data-source';

	return '';
}

function isAdminConfigRestResponse( response, endpoint = '' ) {
	const route = restRouteForEndpoint( endpoint );
	let url = response.url();

	try {
		url = decodeURIComponent( url );
	} catch ( error ) {
		// Keep the original URL if a browser emits an invalid escape sequence.
	}

	return (
		(
			url.includes( '/wp-json/lerm-admin-config/v1/schema/' ) ||
			url.includes( 'rest_route=/lerm-admin-config/v1/schema/' )
		) &&
		( route === '' || url.includes( route ) )
	);
}

async function waitForAdminConfigTransport( page, endpoint = '', options = {} ) {
	return page.waitForResponse(
		( response ) => isAdminConfigRestResponse( response, endpoint ),
		{ timeout: options.timeout || 20_000 }
	);
}

function collectAdminConfigAjaxRequests( page ) {
	const requests = [];

	page.on( 'request', ( request ) => {
		const postData = request.postData() || '';

		if ( request.url().includes( 'admin-ajax.php' ) && postData.includes( 'lerm_admin_config' ) ) {
			requests.push( {
				method: request.method(),
				url: request.url(),
				postData,
			} );
		}
	} );

	return requests;
}

async function expectRestContractConfig( page ) {
	await expect
		.poll(
			() => page.evaluate( () => {
				const config = window.lermAdminConfig || {};

				return {
					hasAjaxUrl: Object.prototype.hasOwnProperty.call( config, 'ajaxUrl' ),
					hasLegacyAjaxEnabled: Object.prototype.hasOwnProperty.call( config, 'legacyAjaxEnabled' ),
					restUrl: config.restUrl || '',
					restNonce: config.restNonce || '',
				};
			} ),
			{ timeout: 10_000 }
		)
		.toMatchObject( {
			hasAjaxUrl: false,
			hasLegacyAjaxEnabled: false,
		} );

	const config = await page.evaluate( () => window.lermAdminConfig || {} );

	expect( config.restUrl || '' ).toContain( 'lerm-admin-config/v1' );
	expect( config.restNonce || '' ).not.toBe( '' );
}

async function openSettingsSection( page, labelPattern ) {
	const sectionNav = page.getByRole( 'navigation', { name: /Settings sections/i } ).first();
	const sectionLink = sectionNav.getByRole( 'link', { name: labelPattern } ).first();

	await expect( sectionLink ).toBeVisible();
	await sectionLink.click();
}

async function isVisibleWithin( locator, timeout = 3_000 ) {
	try {
		await expect( locator ).toBeVisible( { timeout } );
		return true;
	} catch ( error ) {
		return false;
	}
}

async function openNetworkOptionsPage( page, pageSlug ) {
	const encodedSlug = encodeURIComponent( pageSlug );
	const saveButton = page.locator( '[data-lerm-save]:visible' ).first();
	const directPaths = [
		`/wp-admin/network/settings.php?page=${ encodedSlug }`,
		`/wp-admin/network/admin.php?page=${ encodedSlug }`,
	];

	for ( const path of directPaths ) {
		await page.goto( path );

		if ( await isVisibleWithin( saveButton ) ) {
			return;
		}
	}

	await page.goto( '/wp-admin/network/' );

	const menuLink = page.locator( `#adminmenu a[href*="page=${ encodedSlug }"]` ).first();
	const menuHref = await menuLink.getAttribute( 'href', { timeout: 3_000 } ).catch( () => null );

	if ( ! menuHref ) {
		await expect( menuLink, `Network menu link for ${ pageSlug } should exist` ).toHaveAttribute( 'href', /page=/ );
		return;
	}

	const targetUrl = new URL( menuHref, new URL( '/wp-admin/network/', page.url() ) ).toString();

	await page.goto( targetUrl );
	await expect( saveButton ).toBeVisible();
}

async function saveOptionsPage( page, options = {} ) {
	const saveButton = page.locator( '[data-lerm-save]:visible' ).first();

	await expect( saveButton ).toBeVisible();
	const saveRequest = waitForAdminConfigTransport( page, 'save', options );
	await saveButton.click();
	const response = await saveRequest;
	await expect( page.locator( '[data-lerm-status]' ).first() ).toContainText( /Synced|Saved/ );

	return response;
}

async function clickAndWaitForAdminConfigTransport( page, locator, endpoint = '', options = {} ) {
	const request = waitForAdminConfigTransport( page, endpoint, options );

	await locator.click();

	return request;
}

async function resetOptionsPage( page, scope = 'section', options = {} ) {
	const resetButton = page.locator( `[data-lerm-reset="${ scope }"]` ).first();

	await expect( resetButton ).toBeVisible();
	acceptNextDialog( page );

	return clickAndWaitForAdminConfigTransport( page, resetButton, 'reset', options );
}

async function exportBackupSnapshot( page, options = {} ) {
	const exportButton = page.locator( '[data-lerm-backup-export]' ).first();

	await expect( exportButton ).toBeVisible();
	const response = await clickAndWaitForAdminConfigTransport( page, exportButton, 'export', options );

	const output = page.locator( '[data-lerm-backup-export-output]' ).first();

	await expect( output ).not.toHaveValue( '' );

	return {
		json: await output.inputValue(),
		response,
	};
}

async function importBackupSnapshot( page, json, options = {} ) {
	const input = page.locator( '[data-lerm-backup-import-input]' ).first();
	const button = page.locator( '[data-lerm-backup-import]' ).first();

	await input.fill( json );
	acceptNextDialog( page );

	return clickAndWaitForAdminConfigTransport( page, button, 'import', options );
}

async function submitClassicForm( page, submitSelector = '#submit' ) {
	const submitButton = page.locator( submitSelector ).first();
	const acceptUnloadDialog = async ( dialog ) => {
		await dialog.accept();
	};

	await expect( submitButton ).toBeVisible();

	page.once( 'dialog', acceptUnloadDialog );

	const navigation = page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 20_000 } )
		.then( () => true )
		.catch( () => false );

	await submitButton.click();

	const didNavigate = await navigation;

	page.off( 'dialog', acceptUnloadDialog );

	if ( ! didNavigate ) {
		await page.waitForLoadState( 'networkidle', { timeout: 5_000 } ).catch( () => {} );
	}
}

async function openPostEditor( page, title, postType = 'post' ) {
	const query = encodeURIComponent( title );
	const listingPath = `${ adminBase }/edit.php?post_status=all&post_type=${ encodeURIComponent( postType ) }&s=${ query }`;

	await page.goto( listingPath );

	const rowLink = page.locator( '#the-list .row-title', { hasText: title } ).first();

	await expect( rowLink ).toBeVisible();
	await rowLink.click();
	await page.waitForLoadState( 'domcontentloaded' );
}

async function openCategoryEditor( page, categoryName ) {
	const query = encodeURIComponent( categoryName );

	await page.goto( `${ adminBase }/edit-tags.php?taxonomy=category&post_type=post&s=${ query }` );

	const rowLink = page.locator( '#the-list .row-title', { hasText: categoryName } ).first();

	await expect( rowLink ).toBeVisible();
	await rowLink.click();
	await page.waitForLoadState( 'domcontentloaded' );
}

async function openCommentEditor( page, commentSignature ) {
	const query = encodeURIComponent( commentSignature );

	await page.goto( `${ adminBase }/edit-comments.php?s=${ query }` );

	const row = page.locator( '#the-comment-list tr', { hasText: commentSignature } ).first();

	await expect( row ).toBeVisible();
	await row.hover();
	await row.locator( 'span.edit a' ).first().click();
	await page.waitForLoadState( 'domcontentloaded' );
}

async function selectAjaxOption( page, fieldId, searchText, optionLabel, options = {} ) {
	const container = page.locator( `.lerm-ajax-select[data-target="${ fieldId }"]` ).first();
	const search = container.locator( '.lerm-ajax-select__search' );

	await expect( container ).toBeVisible();
	const request = waitForAdminConfigTransport( page, 'data-source', options );
	await search.fill( searchText );
	const response = await request;

	const option = container.locator( '[data-lerm-ajax-select-option]', { hasText: optionLabel } ).first();

	await expect( option ).toBeVisible();
	await option.click();

	return response;
}

module.exports = {
	acceptNextDialog,
	clickAndWaitForAdminConfigTransport,
	collectAdminConfigAjaxRequests,
	expectRestContractConfig,
	exportBackupSnapshot,
	importBackupSnapshot,
	login,
	openCategoryEditor,
	openCommentEditor,
	openNetworkOptionsPage,
	openPostEditor,
	openSettingsSection,
	resetOptionsPage,
	saveOptionsPage,
	selectAjaxOption,
	waitForAdminConfigTransport,
	submitClassicForm,
};
