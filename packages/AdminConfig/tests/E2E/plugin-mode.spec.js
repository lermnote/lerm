const { test, expect } = require( '@playwright/test' );
const {
	exportBackupSnapshot,
	importBackupSnapshot,
	login,
	openSettingsSection,
	resetOptionsPage,
	saveOptionsPage,
	selectAjaxOption,
} = require( './helpers/wp-admin' );

test( 'plugin mode saves changes across sections with one global save', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/options-general.php?page=acme-demo-settings' );

	await expect( page.locator( '[data-lerm-save]:visible' ).first() ).toBeVisible();
	await page.locator( 'select[name="acme_demo_settings[tone_preset]"]' ).selectOption( 'bold' );

	await openSettingsSection( page, /Extension API/i );

	const releaseSlug = page.locator( 'input[name="acme_demo_settings[release_slug]"]' );
	await releaseSlug.fill( 'spring-launch-2026' );

	await saveOptionsPage( page );

	await page.reload();
	await expect( page.locator( 'select[name="acme_demo_settings[tone_preset]"]' ) ).toHaveValue( 'bold' );

	await openSettingsSection( page, /Extension API/i );
	await expect( page.locator( 'input[name="acme_demo_settings[release_slug]"]' ) ).toHaveValue( 'spring-launch-2026' );
} );

test( 'plugin mode resets the whole page back to schema defaults', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/options-general.php?page=acme-demo-settings' );

	await page.locator( 'select[name="acme_demo_settings[tone_preset]"]' ).selectOption( 'vivid' );
	await openSettingsSection( page, /Extension API/i );
	await page.locator( 'input[name="acme_demo_settings[release_slug]"]' ).fill( 'global-reset-check' );

	// Navigate back to General so the sticky action bar is visible (hidden section bars take DOM priority).
	await openSettingsSection( page, /General/i );
	await resetOptionsPage( page, 'all' );

	await page.reload();
	await expect( page.locator( 'select[name="acme_demo_settings[tone_preset]"]' ) ).toHaveValue( 'calm' );

	await openSettingsSection( page, /Extension API/i );
	await expect( page.locator( 'input[name="acme_demo_settings[release_slug]"]' ) ).toHaveValue( 'spring-launch' );
} );

test( 'plugin mode imports snapshots and saves ajax select and advanced field values', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/options-general.php?page=acme-demo-settings' );

	await openSettingsSection( page, /Extension API/i );
	await selectAjaxOption( page, 'featured_campaign', 'studio', 'Studio Preview' );

	await openSettingsSection( page, /Advanced Fields/i );
	await page.locator( '[data-lerm-tabbed-target="secondary"]' ).first().click();
	await page.locator( 'input[name="acme_demo_settings[card_tabs][secondary][title]"]' ).fill( 'Imported secondary card' );
	await page.locator( 'button[data-lerm-accordion-trigger]', { hasText: /CTA Panel/i } ).first().click();
	await page.locator( 'input[name="acme_demo_settings[launch_accordion][cta][button_label]"]' ).fill( 'Ship it' );
	await page.locator( 'input[name="acme_demo_settings[brand_typography][font-size]"]' ).fill( '2.75' );

	await saveOptionsPage( page );

	await openSettingsSection( page, /Tools/i );
	// Wait for the Tools section content to be visible (hidden section containers take DOM priority).
	await expect( page.locator( '[data-lerm-backup-export]' ).first() ).toBeVisible();

	const snapshot = JSON.parse( ( await exportBackupSnapshot( page ) ).json );
	snapshot.release_slug = 'snapshot-imported';
	snapshot.featured_campaign = 'studio-preview';
	snapshot.card_tabs.secondary.title = 'Snapshot secondary card';
	snapshot.launch_accordion.cta.button_label = 'Import launch';
	snapshot.brand_typography['font-size'] = '3.1';

	await importBackupSnapshot( page, JSON.stringify( snapshot, null, 2 ) );
	await page.reload();

	await openSettingsSection( page, /Extension API/i );
	await expect( page.locator( 'input[name="acme_demo_settings[release_slug]"]' ) ).toHaveValue( 'snapshot-imported' );
	await expect( page.locator( 'input[name="acme_demo_settings[featured_campaign]"]' ) ).toHaveValue( 'studio-preview' );

	await openSettingsSection( page, /Advanced Fields/i );
	await expect( page.locator( 'input[name="acme_demo_settings[brand_typography][font-size]"]' ) ).toHaveValue( '3.1' );
	await page.locator( '[data-lerm-tabbed-target="secondary"]' ).first().click();
	await expect( page.locator( 'input[name="acme_demo_settings[card_tabs][secondary][title]"]' ) ).toHaveValue( 'Snapshot secondary card' );
	await page.locator( 'button[data-lerm-accordion-trigger]', { hasText: /CTA Panel/i } ).first().click();
	await expect( page.locator( 'input[name="acme_demo_settings[launch_accordion][cta][button_label]"]' ) ).toHaveValue( 'Import launch' );
} );

test( 'plugin mode uses REST transport for async options page actions', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/options-general.php?page=acme-demo-settings' );

	await openSettingsSection( page, /Extension API/i );
	const dataSourceResponse = await selectAjaxOption( page, 'featured_campaign', 'studio', 'Studio Preview', { transport: 'rest' } );
	expect( dataSourceResponse.ok() ).toBe( true );
	expect( decodeURIComponent( dataSourceResponse.url() ) ).toContain( 'lerm-admin-config/v1/schemas/acme-demo-settings/data-source' );

	await page.locator( 'input[name="acme_demo_settings[release_slug]"]' ).fill( 'rest-transport-check' );
	const saveResponse = await saveOptionsPage( page, { transport: 'rest' } );
	expect( saveResponse.ok() ).toBe( true );
	expect( decodeURIComponent( saveResponse.url() ) ).toContain( 'lerm-admin-config/v1/schemas/acme-demo-settings/values' );

	await openSettingsSection( page, /Tools/i );
	const exportResult = await exportBackupSnapshot( page, { transport: 'rest' } );
	expect( exportResult.response.ok() ).toBe( true );
	expect( decodeURIComponent( exportResult.response.url() ) ).toContain( 'lerm-admin-config/v1/schemas/acme-demo-settings/export' );
	expect( JSON.parse( exportResult.json ).release_slug ).toBe( 'rest-transport-check' );
} );
