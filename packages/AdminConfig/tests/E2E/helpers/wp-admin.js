const { expect } = require( '@playwright/test' );

async function login( page ) {
	await page.goto( '/wp-login.php' );

	if ( page.url().includes( '/wp-admin/' ) ) {
		return;
	}

	await page.locator( '#user_login' ).fill( 'admin' );
	await page.locator( '#user_pass' ).fill( 'password' );
	await page.locator( '#wp-submit' ).click();
	await expect( page ).toHaveURL( /\/wp-admin\// );
}

async function saveOptionsPage( page ) {
	const saveButton = page.locator( '[data-lerm-save]' ).first();

	await expect( saveButton ).toBeVisible();

	const saveRequest = page.waitForResponse(
		( response ) =>
			response.url().includes( 'admin-ajax.php' ) &&
			response.request().method() === 'POST' &&
			response.request().postData()?.includes( 'lerm_admin_config_ajax_save_' ),
		{ timeout: 20_000 }
	);

	await saveButton.click();
	await saveRequest;
	await expect( page.locator( '[data-lerm-status]' ).first() ).toContainText( /Synced|Saved/ );
}

module.exports = {
	login,
	saveOptionsPage,
};
