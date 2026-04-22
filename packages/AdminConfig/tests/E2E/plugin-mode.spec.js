const { test, expect } = require( '@playwright/test' );
const { login, saveOptionsPage } = require( './helpers/wp-admin' );

test( 'plugin mode saves changes across sections with one global save', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/options-general.php?page=acme-demo-settings' );

	await page.locator( 'select[name="acme_demo_settings[tone_preset]"]' ).selectOption( 'bold' );

	await page.getByRole( 'link', { name: /Extensions/i } ).click();

	const releaseSlug = page.locator( 'input[name="acme_demo_settings[release_slug]"]' );
	await releaseSlug.fill( 'spring-launch-2026' );

	await saveOptionsPage( page );

	await page.reload();
	await expect( page.locator( 'select[name="acme_demo_settings[tone_preset]"]' ) ).toHaveValue( 'bold' );

	await page.getByRole( 'link', { name: /Extensions/i } ).click();
	await expect( page.locator( 'input[name="acme_demo_settings[release_slug]"]' ) ).toHaveValue( 'spring-launch-2026' );
} );
