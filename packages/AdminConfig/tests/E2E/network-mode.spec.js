const { test, expect } = require( '@playwright/test' );
const {
	clickAndWaitForAjax,
	login,
	openNetworkOptionsPage,
	saveOptionsPage,
} = require( './helpers/wp-admin' );

const isMultisite = process.env.LERM_ADMIN_CONFIG_MULTISITE === '1';

test.skip( ! isMultisite, 'Network smoke suite only runs against a multisite environment.' );

test( 'network options page replays nested validation errors and saves network settings', async ( { page } ) => {
	await login( page );
	await openNetworkOptionsPage( page, 'acme-demo-network-settings' );

	await expect( page.locator( '[data-lerm-save]' ).first() ).toBeVisible();

	const feedSlug = page.locator( 'input[name="acme_demo_network_settings[shared_library][feed_slug]"]:visible' ).first();

	await feedSlug.fill( 'x' );
	await clickAndWaitForAjax( page, page.locator( '[data-lerm-save]' ).first(), 'lerm_admin_config_ajax_save_' );

	await expect( page.locator( '.lerm-settings-row.is-invalid input[name="acme_demo_network_settings[shared_library][feed_slug]"]' ) ).toHaveValue( 'x' );
	await expect( page.locator( '[data-lerm-status]' ).first() ).toContainText( /highlighted fields/i );

	await page.locator( 'input[name="acme_demo_network_settings[template_endpoint]"]:visible' ).first().fill( 'https://example.com/network-templates.json' );
	await feedSlug.fill( 'shared-library-hub' );
	await saveOptionsPage( page );

	await page.reload();
	await expect( page.locator( 'input[name="acme_demo_network_settings[template_endpoint]"]' ) ).toHaveValue( 'https://example.com/network-templates.json' );
	await expect( feedSlug ).toHaveValue( 'shared-library-hub' );
} );
