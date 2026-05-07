const { test, expect } = require( '@playwright/test' );
const {
	collectAdminConfigAjaxRequests,
	expectRestContractConfig,
	exportBackupSnapshot,
	importBackupSnapshot,
	login,
	openSettingsSection,
	resetOptionsPage,
	saveOptionsPage,
	selectAjaxOption,
} = require( './helpers/wp-admin' );

const isRestContract = process.env.LERM_ADMIN_CONFIG_REST_CONTRACT === '1';

test.skip( ! isRestContract, 'REST contract smoke only runs in the dedicated REST contract suite.' );

test( 'plugin mode exercises AdminConfig REST save, import, export, reset, and data-source contracts', async ( { page } ) => {
	const legacyAjaxRequests = collectAdminConfigAjaxRequests( page );

	await login( page );
	await page.goto( '/wp-admin/options-general.php?page=acme-demo-settings' );
	await expectRestContractConfig( page );

	await openSettingsSection( page, /Extension API/i );

	const dataSourceResponse = await selectAjaxOption(
		page,
		'featured_campaign',
		'studio',
		'Studio Preview',
		{ transport: 'rest' }
	);

	expect( dataSourceResponse.ok() ).toBe( true );
	expect( decodeURIComponent( dataSourceResponse.url() ) ).toContain(
		'lerm-admin-config/v1/schemas/acme-demo-settings/data-source'
	);

	await page.locator( 'input[name="acme_demo_settings[release_slug]"]' ).fill( 'rest-contract-save' );

	const saveResponse = await saveOptionsPage( page, { transport: 'rest' } );

	expect( saveResponse.ok() ).toBe( true );
	expect( decodeURIComponent( saveResponse.url() ) ).toContain(
		'lerm-admin-config/v1/schemas/acme-demo-settings/values'
	);

	await openSettingsSection( page, /Tools/i );

	const exportResult = await exportBackupSnapshot( page, { transport: 'rest' } );
	const snapshot = JSON.parse( exportResult.json );

	expect( exportResult.response.ok() ).toBe( true );
	expect( decodeURIComponent( exportResult.response.url() ) ).toContain(
		'lerm-admin-config/v1/schemas/acme-demo-settings/export'
	);
	expect( snapshot.release_slug ).toBe( 'rest-contract-save' );

	snapshot.release_slug = 'rest-contract-import';
	snapshot.featured_campaign = 'studio-preview';

	const importResponse = await importBackupSnapshot( page, JSON.stringify( snapshot, null, 2 ), { transport: 'rest' } );

	expect( importResponse.ok() ).toBe( true );
	expect( decodeURIComponent( importResponse.url() ) ).toContain(
		'lerm-admin-config/v1/schemas/acme-demo-settings/import'
	);

	await page.reload();
	await expectRestContractConfig( page );
	await openSettingsSection( page, /Extension API/i );
	await expect( page.locator( 'input[name="acme_demo_settings[release_slug]"]' ) ).toHaveValue( 'rest-contract-import' );
	await expect( page.locator( 'input[name="acme_demo_settings[featured_campaign]"]' ) ).toHaveValue( 'studio-preview' );

	await openSettingsSection( page, /General/i );

	const resetResponse = await resetOptionsPage( page, 'all', { transport: 'rest' } );

	expect( resetResponse.ok() ).toBe( true );
	expect( decodeURIComponent( resetResponse.url() ) ).toContain(
		'lerm-admin-config/v1/schemas/acme-demo-settings/reset'
	);
	expect( legacyAjaxRequests, JSON.stringify( legacyAjaxRequests, null, 2 ) ).toHaveLength( 0 );
} );
