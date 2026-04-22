const { test, expect } = require( '@playwright/test' );
const { login, saveOptionsPage } = require( './helpers/wp-admin' );

test( 'embedded mode saves nested theme settings', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/themes.php?page=acme-theme-style-kit' );

	const surfaceTitle = page.locator( 'input[name="acme_theme_style_kit[surface_tabs][default][title]"]' );
	await surfaceTitle.fill( 'Editorial surface updated' );

	await saveOptionsPage( page );

	await page.reload();
	await expect( page.locator( 'input[name="acme_theme_style_kit[surface_tabs][default][title]"]' ) ).toHaveValue( 'Editorial surface updated' );
} );
