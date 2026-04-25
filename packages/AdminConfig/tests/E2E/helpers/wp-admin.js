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

async function waitForAdminAjax( page, actionFragment = '' ) {
	return page.waitForResponse(
		( response ) =>
			response.url().includes( 'admin-ajax.php' ) &&
			response.request().method() === 'POST' &&
			( actionFragment === '' || response.request().postData()?.includes( actionFragment ) ),
		{ timeout: 20_000 }
	);
}

function restRouteForAction( actionFragment = '' ) {
	if ( actionFragment.includes( 'save_' ) ) return '/save';
	if ( actionFragment.includes( 'reset_' ) ) return '/reset';
	if ( actionFragment.includes( 'export_' ) ) return '/export';
	if ( actionFragment.includes( 'import_' ) ) return '/import';
	if ( actionFragment.includes( 'data_source' ) ) return '/data-source';

	return '';
}

function isAdminConfigRestResponse( response, actionFragment = '' ) {
	const route = restRouteForAction( actionFragment );
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

function isAdminConfigAjaxResponse( response, actionFragment = '' ) {
	return (
		response.url().includes( 'admin-ajax.php' ) &&
		response.request().method() === 'POST' &&
		( actionFragment === '' || response.request().postData()?.includes( actionFragment ) )
	);
}

async function waitForAdminConfigTransport( page, actionFragment = '', options = {} ) {
	const transport = options.transport || 'any';

	return page.waitForResponse(
		( response ) => {
			if ( transport === 'rest' ) {
				return isAdminConfigRestResponse( response, actionFragment );
			}

			if ( transport === 'ajax' ) {
				return isAdminConfigAjaxResponse( response, actionFragment );
			}

			return isAdminConfigRestResponse( response, actionFragment ) || isAdminConfigAjaxResponse( response, actionFragment );
		},
		{ timeout: options.timeout || 20_000 }
	);
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
	const saveRequest = waitForAdminConfigTransport( page, 'lerm_admin_config_ajax_save_', options );
	await saveButton.click();
	const response = await saveRequest;
	await expect( page.locator( '[data-lerm-status]' ).first() ).toContainText( /Synced|Saved/ );

	return response;
}

async function clickAndWaitForAdminConfigTransport( page, locator, actionFragment = '', options = {} ) {
	const request = waitForAdminConfigTransport( page, actionFragment, options );

	await locator.click();

	return request;
}

async function clickAndWaitForAjax( page, locator, actionFragment = '', options = {} ) {
	return clickAndWaitForAdminConfigTransport( page, locator, actionFragment, options );
}

async function resetOptionsPage( page, scope = 'section', options = {} ) {
	const resetButton = page.locator( `[data-lerm-reset="${ scope }"]` ).first();

	await expect( resetButton ).toBeVisible();
	acceptNextDialog( page );

	return clickAndWaitForAdminConfigTransport( page, resetButton, 'lerm_admin_config_ajax_reset_', options );
}

async function exportBackupSnapshot( page, options = {} ) {
	const exportButton = page.locator( '[data-lerm-backup-export]' ).first();

	await expect( exportButton ).toBeVisible();
	const response = await clickAndWaitForAdminConfigTransport( page, exportButton, 'lerm_admin_config_ajax_export_', options );

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

	return clickAndWaitForAdminConfigTransport( page, button, 'lerm_admin_config_ajax_import_', options );
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
	const request = waitForAdminConfigTransport( page, 'lerm_admin_config_data_source', options );
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
	clickAndWaitForAjax,
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
	waitForAdminAjax,
};
