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

const setInputValue = async ( locator, value ) => {
	await locator.evaluate( ( input, nextValue ) => {
		input.value = nextValue;
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}, value );
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
	const entrySlug = panel.getByRole( 'textbox', { name: /Entry slug/i } );
	const entryLayout = panel.getByRole( 'combobox', { name: /Entry layout/i } );
	const entryFormat = panel.locator( '[data-field-id="entry_format"]' );
	const entryEmphasis = panel.locator( '[data-field-id="entry_emphasis"]' );
	const entryAccent = panel.locator( '[data-field-id="entry_accent"] input[type="color"]' );
	const entryReviewDate = panel.locator( '[data-field-id="entry_review_date"] input[type="date"]' );
	const entryPriority = panel.locator( '[data-field-id="entry_priority"] input[type="range"]' );
	const entryScore = panel.locator( '[data-field-id="entry_score"] input[type="number"]' );
	const entryIconReadOnly = panel.locator( '[data-field-id="entry_icon"][data-read-only-control="true"]' );
	const entryBadgeReadOnly = panel.locator( '[data-field-id="entry_badge"][data-read-only-control="true"]' );
	const newsletterChannel = panel.getByRole( 'checkbox', { name: /^Newsletter$/i } );

	await expect( featuredToggle ).toBeVisible();
	await expect( entrySlug ).toBeVisible();
	await expect( entryLayout ).toBeVisible();
	await expect( entryFormat.getByRole( 'radio', { name: /^Standard$/i } ) ).toBeVisible();
	await expect( entryEmphasis.getByRole( 'button', { name: /^Normal$/i } ) ).toBeVisible();
	await expect( entryAccent ).toBeVisible();
	await expect( entryReviewDate ).toBeVisible();
	await expect( entryPriority ).toBeVisible();
	await expect( entryScore ).toBeVisible();
	await expect( newsletterChannel ).toBeVisible();
	await expect( entryIconReadOnly ).toContainText( /Field type "icon" is read-only/i );
	await expect( entryBadgeReadOnly ).toContainText( /Field type "fieldset" is read-only/i );
	const initialChecked = await featuredToggle.isChecked();
	const initialSlug = await entrySlug.inputValue();
	const initialLayout = await entryLayout.inputValue();
	const initialFormat = await entryFormat.locator( 'input[type="radio"]:checked' ).inputValue();
	const initialEmphasis = await entryEmphasis.locator( 'button[aria-pressed="true"]' ).getAttribute( 'data-value' );
	const initialAccent = ( await entryAccent.inputValue() ).toLowerCase();
	const initialReviewDate = await entryReviewDate.inputValue();
	const initialPriority = await entryPriority.inputValue();
	const initialScore = await entryScore.inputValue();
	const initialNewsletter = await newsletterChannel.isChecked();
	const discardSlug = initialSlug === 'discard-check' ? 'discard-check-next' : 'discard-check';
	const discardLayout = initialLayout === 'wide' ? 'compact' : 'wide';
	const discardFormat = initialFormat === 'editorial' ? 'alert' : 'editorial';
	const discardEmphasis = initialEmphasis === 'spotlight' ? 'quiet' : 'spotlight';
	const discardAccent = initialAccent === '#445566' ? '#665544' : '#445566';
	const discardReviewDate = initialReviewDate === '2026-05-01' ? '2026-05-02' : '2026-05-01';
	const discardPriority = initialPriority === '5' ? '4' : '5';
	const discardScore = initialScore === '10' ? '9' : '10';
	const savedSlug = initialSlug === 'block-panel-valid' ? 'block-panel-valid-next' : 'block-panel-valid';
	const savedLayout = initialLayout === 'feature' ? 'compact' : 'feature';
	const savedFormat = initialFormat === 'alert' ? 'editorial' : 'alert';
	const savedEmphasis = initialEmphasis === 'quiet' ? 'spotlight' : 'quiet';
	const savedAccent = initialAccent === '#13579b' ? '#2468ac' : '#13579b';
	const savedReviewDate = initialReviewDate === '2026-05-03' ? '2026-05-04' : '2026-05-03';
	const savedPriority = initialPriority === '4' ? '3' : '4';
	const savedScore = initialScore === '7' ? '6' : '7';

	await entrySlug.fill( discardSlug );
	await entryLayout.selectOption( discardLayout );
	await entryFormat.getByRole( 'radio', { name: new RegExp( `^${ discardFormat }$`, 'i' ) } ).check();
	await entryEmphasis.locator( `button[data-value="${ discardEmphasis }"]` ).click();
	await setInputValue( entryAccent, discardAccent );
	await setInputValue( entryReviewDate, discardReviewDate );
	await setInputValue( entryPriority, discardPriority );
	await entryScore.fill( discardScore );
	await newsletterChannel.setChecked( ! initialNewsletter );
	await expect( panel ).toHaveAttribute( 'data-dirty', 'true' );

	const discardDialogPromise = page.waitForEvent( 'dialog' );
	const discardClickPromise = panel.getByRole( 'button', { name: /^Discard$/ } ).click();
	const discardDialog = await discardDialogPromise;

	expect( discardDialog.type() ).toBe( 'confirm' );
	expect( discardDialog.message() ).toContain( 'Discard unsaved AdminConfig changes?' );
	await discardDialog.accept();
	await discardClickPromise;
	await expect( panel ).toHaveAttribute( 'data-dirty', 'false' );
	await expect( entrySlug ).toHaveValue( initialSlug );
	await expect( entryLayout ).toHaveValue( initialLayout );
	await expect( entryFormat.locator( 'input[type="radio"]:checked' ) ).toHaveValue( initialFormat );
	await expect( entryEmphasis.locator( 'button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', initialEmphasis || '' );
	await expect( entryAccent ).toHaveValue( initialAccent );
	await expect( entryReviewDate ).toHaveValue( initialReviewDate );
	await expect( entryPriority ).toHaveValue( initialPriority );
	await expect( entryScore ).toHaveValue( initialScore );
	await expect( newsletterChannel ).toBeChecked( { checked: initialNewsletter } );

	await entrySlug.fill( 'x' );
	await expect( panel ).toHaveAttribute( 'data-dirty', 'true' );

	const invalidSaveRequest = page.waitForResponse( ( saveResponse ) => isMetaboxSaveResponse( saveResponse, 'acme-demo-post-metabox' ), { timeout: 20_000 } );

	await panel.getByRole( 'button', { name: /^Save$/ } ).click();

	const invalidSaveResponse = await invalidSaveRequest;

	expect( invalidSaveResponse.status() ).toBe( 422 );
	await expect( panel ).toHaveAttribute( 'data-status', 'error' );
	await expect( panel ).toHaveAttribute( 'data-error-count', '1' );
	await expect( panel.locator( '[data-field-error="entry_slug"]' ) ).toContainText( /between 3 and 32/i );

	await entrySlug.fill( savedSlug );
	await expect( panel ).toHaveAttribute( 'data-status', 'ready' );
	await expect( panel ).toHaveAttribute( 'data-error-count', '0' );
	await featuredToggle.setChecked( ! initialChecked );
	await entryLayout.selectOption( savedLayout );
	await entryFormat.locator( `input[type="radio"][value="${ savedFormat }"]` ).check();
	await entryEmphasis.locator( `button[data-value="${ savedEmphasis }"]` ).click();
	await setInputValue( entryAccent, savedAccent );
	await setInputValue( entryReviewDate, savedReviewDate );
	await setInputValue( entryPriority, savedPriority );
	await entryScore.fill( savedScore );
	await newsletterChannel.setChecked( ! initialNewsletter );
	await expect( panel ).toHaveAttribute( 'data-dirty', 'true' );

	const saveRequest = page.waitForResponse( ( saveResponse ) => isMetaboxSaveResponse( saveResponse, 'acme-demo-post-metabox' ), { timeout: 20_000 } );

	await panel.getByRole( 'button', { name: /^Save$/ } ).click();

	const saveResponse = await saveRequest;

	expect( saveResponse.ok() ).toBe( true );
	await expect( panel ).toHaveAttribute( 'data-dirty', 'false' );
	await expect( featuredToggle ).toBeChecked( { checked: ! initialChecked } );
	await expect( entrySlug ).toHaveValue( savedSlug );
	await expect( entryLayout ).toHaveValue( savedLayout );
	await expect( entryFormat.locator( 'input[type="radio"]:checked' ) ).toHaveValue( savedFormat );
	await expect( entryEmphasis.locator( 'button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', savedEmphasis );
	await expect( entryAccent ).toHaveValue( savedAccent );
	await expect( entryReviewDate ).toHaveValue( savedReviewDate );
	await expect( entryPriority ).toHaveValue( savedPriority );
	await expect( entryScore ).toHaveValue( savedScore );
	await expect( newsletterChannel ).toBeChecked( { checked: ! initialNewsletter } );

	const reloadSchemaRequest = page.waitForResponse( ( reloadResponse ) => isMetaboxSchemaResponse( reloadResponse ), { timeout: 30_000 } );

	await page.reload( { waitUntil: 'domcontentloaded' } );
	await reloadSchemaRequest;

	const reloadedPanel = await expandBlockPanel( page, 'acme-demo-post-metabox', 'Entry Display Overrides' );

	await expect( reloadedPanel.getByRole( 'checkbox', { name: /Feature this entry/i } ) ).toBeChecked(
		{ checked: ! initialChecked, timeout: 30_000 }
	);
	await expect( reloadedPanel.getByRole( 'textbox', { name: /Entry slug/i } ) ).toHaveValue( savedSlug );
	await expect( reloadedPanel.getByRole( 'combobox', { name: /Entry layout/i } ) ).toHaveValue( savedLayout );
	await expect( reloadedPanel.locator( '[data-field-id="entry_format"] input[type="radio"]:checked' ) ).toHaveValue( savedFormat );
	await expect( reloadedPanel.locator( '[data-field-id="entry_emphasis"] button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', savedEmphasis );
	await expect( reloadedPanel.locator( '[data-field-id="entry_accent"] input[type="color"]' ) ).toHaveValue( savedAccent );
	await expect( reloadedPanel.locator( '[data-field-id="entry_review_date"] input[type="date"]' ) ).toHaveValue( savedReviewDate );
	await expect( reloadedPanel.locator( '[data-field-id="entry_priority"] input[type="range"]' ) ).toHaveValue( savedPriority );
	await expect( reloadedPanel.locator( '[data-field-id="entry_score"] input[type="number"]' ) ).toHaveValue( savedScore );
	await expect( reloadedPanel.getByRole( 'checkbox', { name: /^Newsletter$/i } ) ).toBeChecked(
		{ checked: ! initialNewsletter }
	);
	expect( ajaxRequests ).toEqual( [] );
} );
