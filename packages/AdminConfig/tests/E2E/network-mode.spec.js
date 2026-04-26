const { test, expect } = require( '@playwright/test' );
const {
	clickAndWaitForAdminConfigTransport,
	login,
	openNetworkOptionsPage,
	saveOptionsPage,
} = require( './helpers/wp-admin' );

const isMultisite = process.env.LERM_ADMIN_CONFIG_MULTISITE === '1';
const fieldName = 'site_acme_demo_network_settings';

test.skip( ! isMultisite, 'Network smoke suite only runs against a multisite environment.' );

test( 'network options page replays nested validation errors and saves network settings', async ( { page } ) => {
	await login( page );
	await openNetworkOptionsPage( page, 'acme-demo-network-settings' );

	await expect( page.locator( '[data-lerm-save]:visible' ).first() ).toBeVisible();

	const feedSlug = page.locator( `input[name="${ fieldName }[shared_library][feed_slug]"]` );

	await feedSlug.fill( 'x' );
	const validationResponse = await clickAndWaitForAdminConfigTransport(
		page,
		page.locator( '[data-lerm-save]:visible' ).first(),
		'lerm_admin_config_ajax_save_',
		{ transport: 'rest' }
	);

	expect( validationResponse.status() ).toBe( 422 );
	expect( decodeURIComponent( validationResponse.url() ) ).toContain(
		'lerm-admin-config/v1/schema/acme-demo-network-settings/save'
	);

	await expect( page.locator( `.lerm-fieldset__item.is-invalid input[name="${ fieldName }[shared_library][feed_slug]"]` ) ).toHaveValue( 'x' );
	await expect( page.locator( '[data-lerm-status]' ).first() ).toContainText( /highlighted fields/i );

	await page.locator( `input[name="${ fieldName }[template_endpoint]"]` ).fill( 'https://example.com/network-templates.json' );
	await feedSlug.fill( 'shared-library-hub' );
	const saveResponse = await saveOptionsPage( page, { transport: 'rest' } );

	expect( saveResponse.ok() ).toBe( true );
	expect( decodeURIComponent( saveResponse.url() ) ).toContain(
		'lerm-admin-config/v1/schema/acme-demo-network-settings/save'
	);

	await page.reload();
	await expect( page.locator( `input[name="${ fieldName }[template_endpoint]"]` ) ).toHaveValue( 'https://example.com/network-templates.json' );
	await expect( feedSlug ).toHaveValue( 'shared-library-hub' );
} );
