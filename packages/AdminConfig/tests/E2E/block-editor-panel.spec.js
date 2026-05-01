const { test, expect } = require( '@playwright/test' );
const {
	collectAdminConfigAjaxRequests,
	login,
	openPostEditor,
} = require( './helpers/wp-admin' );

const decodedUrl = ( response ) => {
	let url = response.url();

	try {
		url = decodeURIComponent( url );
	} catch ( error ) {
		// Keep the original URL if the browser emits an invalid escape sequence.
	}

	return url;
};

const isMetaboxSchemaResponse = ( response, schemaId = 'acme-demo-post-metabox' ) => {
	const url = decodedUrl( response );

	return (
		response.request().method() === 'GET' &&
		(
			url.includes( `/wp-json/lerm-admin-config/v1/schema/${ schemaId }` ) ||
			url.includes( `rest_route=/lerm-admin-config/v1/schema/${ schemaId }` )
		)
	);
};

const isMetaboxSaveResponse = ( response, schemaId ) => {
	const url = decodedUrl( response );

	return (
		response.request().method() === 'POST' &&
		(
			url.includes( `/wp-json/lerm-admin-config/v1/schema/${ schemaId }/save` ) ||
			url.includes( `rest_route=/lerm-admin-config/v1/schema/${ schemaId }/save` )
		)
	);
};

const escapeRegExp = ( value ) => value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );

const expandBlockPanel = async ( page, schemaId, title ) => {
	const panel = page.locator( `[data-lerm-admin-config-block-panel][data-schema-id="${ schemaId }"]` ).first();

	if ( await panel.count() > 0 && await panel.isVisible() ) {
		return panel;
	}

	const titlePattern = new RegExp( `^${ escapeRegExp( title ) }$` );
	const shell = page.locator( '.lerm-admin-config-block-panel', {
		has: page.getByRole( 'button', { name: titlePattern } ),
	} ).first();

	await expect( shell ).toBeVisible( { timeout: 30_000 } );
	await shell.getByRole( 'button', { name: titlePattern } ).first().click();
	await expect( panel ).toBeVisible( { timeout: 30_000 } );

	return panel;
};

test.skip(
	process.env.LERM_ADMIN_CONFIG_BLOCK_EDITOR !== '1',
	'Block editor smoke runs through npm run test:e2e:block-editor so the fixture can temporarily enable the editor.'
);

test( 'block editor edits and saves AdminConfig panel values through REST', async ( { page } ) => {
	await login( page );

	const ajaxRequests = collectAdminConfigAjaxRequests( page );
	const schemaRequest = page.waitForResponse( ( response ) => isMetaboxSchemaResponse( response ), { timeout: 30_000 } );

	await openPostEditor( page, 'Admin Config Smoke Post', 'post' );

	const response = await schemaRequest;
	const url = decodedUrl( response );

	expect( response.ok() ).toBe( true );
	expect( url ).toContain( 'post_id=' );

	await expect
		.poll(
			() => page.evaluate( () => {
				const instances = window.lermAdminConfigBlockPanel?.getInstances?.() || [];

				return instances.map( ( instance ) => ( {
					postId: instance.context?.post_id || 0,
					schemaId: instance.schemaId || '',
					status: instance.state?.status || '',
				} ) );
			} ),
			{ timeout: 30_000 }
		)
		.toContainEqual(
			expect.objectContaining( {
				postId: expect.any( Number ),
				schemaId: 'acme-demo-post-metabox',
				status: 'ready',
			} )
		);

	const readyInstance = await page.evaluate( () => {
		const instances = window.lermAdminConfigBlockPanel?.getInstances?.() || [];

		return instances.find( ( instance ) => instance.schemaId === 'acme-demo-post-metabox' ) || null;
	} );

	expect( readyInstance.context.post_id ).toBeGreaterThan( 0 );

	await page.getByRole( 'dialog', { name: /Welcome to the editor/i } )
		.getByRole( 'button', { name: /Close/i } )
		.click( { timeout: 3_000 } )
		.catch( () => {} );

	const panel = await expandBlockPanel( page, 'acme-demo-post-metabox', 'Entry Display Overrides' );
	const featuredToggle = panel.getByRole( 'checkbox', { name: /Feature this entry/i } );

	await expect( featuredToggle ).toBeVisible();
	const initialChecked = await featuredToggle.isChecked();

	await featuredToggle.setChecked( ! initialChecked );
	await expect( panel ).toHaveAttribute( 'data-dirty', 'true' );

	const saveRequest = page.waitForResponse( ( saveResponse ) => isMetaboxSaveResponse( saveResponse, 'acme-demo-post-metabox' ), { timeout: 20_000 } );

	await panel.getByRole( 'button', { name: /^Save$/ } ).click();

	const saveResponse = await saveRequest;

	expect( saveResponse.ok() ).toBe( true );
	await expect( panel ).toHaveAttribute( 'data-dirty', 'false' );
	await expect( featuredToggle ).toBeChecked( { checked: ! initialChecked } );

	const reloadSchemaRequest = page.waitForResponse( ( reloadResponse ) => isMetaboxSchemaResponse( reloadResponse ), { timeout: 30_000 } );

	await page.reload( { waitUntil: 'domcontentloaded' } );
	await reloadSchemaRequest;

	const reloadedPanel = await expandBlockPanel( page, 'acme-demo-post-metabox', 'Entry Display Overrides' );

	await expect( reloadedPanel.getByRole( 'checkbox', { name: /Feature this entry/i } ) ).toBeChecked(
		{ checked: ! initialChecked, timeout: 30_000 }
	);
	expect( ajaxRequests ).toEqual( [] );
} );
