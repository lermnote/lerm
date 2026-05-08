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
			url.includes( `/wp-json/lerm-admin-config/v1/schemas/${ schemaId }` ) ||
			url.includes( `rest_route=/lerm-admin-config/v1/schemas/${ schemaId }` )
		)
	);
};

const isMetaboxSaveResponse = ( response, schemaId ) => {
	const url = decodedUrl( response );

	return (
		response.request().method() === 'POST' &&
			(
			url.includes( `/wp-json/lerm-admin-config/v1/schemas/${ schemaId }/values` ) ||
			url.includes( `rest_route=/lerm-admin-config/v1/schemas/${ schemaId }/values` )
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

const currentBlockPanelMediaValues = async ( page ) => page.evaluate( () => {
	const instances = window.lermAdminConfigBlockPanel?.getInstances?.() || [];
	const instance = instances.find( ( candidate ) => candidate.schemaId === 'acme-demo-post-metabox' ) || {};
	const values = instance.state?.values || {};
	const media = values.entry_media || {};
	const gallery = Array.isArray( values.entry_gallery ) ? values.entry_gallery : [];

	return {
		galleryIds: gallery.map( ( item ) => Number( item?.id || item ) ).filter( ( id ) => id > 0 ),
		mediaId: Number( media?.id || media || 0 ),
		upload: String( values.entry_upload || '' ),
	};
} );

const selectMediaAttachments = async ( page, trigger, titles ) => {
	await trigger.click();

	const modal = page.locator( '.media-modal' ).last();

	await expect( modal ).toBeVisible( { timeout: 20_000 } );
	await modal.getByRole( 'tab', { name: /Media Library/i } ).click().catch( () => {} );

	for ( const title of titles ) {
		const checkbox = modal.getByRole( 'checkbox', { name: new RegExp( `^${ escapeRegExp( title ) }$` ) } ).first();

		await expect( checkbox, `Media attachment "${ title }" should be selectable` ).toBeVisible( { timeout: 20_000 } );
		await checkbox.click();
	}

	for ( let attempt = 0; attempt < 3; attempt += 1 ) {
		const button = modal.getByRole( 'button', {
			name: /Select|Choose|Create a new gallery|Insert gallery|Add to gallery|Update gallery/i,
		} ).last();

		await expect( button ).toBeEnabled( { timeout: 10_000 } );
		await button.click();
		await page.waitForTimeout( 500 );

		if ( ! await modal.isVisible().catch( () => false ) ) {
			break;
		}
	}

	await expect( modal ).toBeHidden( { timeout: 20_000 } );
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
	const entryUpload = panel.locator( '[data-field-id="entry_upload"]' );
	const entryMedia = panel.locator( '[data-field-id="entry_media"]' );
	const entryGallery = panel.locator( '[data-field-id="entry_gallery"]' );
	const entryDimensions = panel.locator( '[data-field-id="entry_dimensions"]' );
	const entrySpacing = panel.locator( '[data-field-id="entry_spacing"]' );
	const entryLinks = panel.locator( '[data-field-id="entry_links"]' );
	const entryIconReadOnly = panel.locator( '[data-field-id="entry_icon"][data-read-only-control="true"]' );
	const entryBadge = panel.locator( '[data-field-id="entry_badge"]' );
	const entryBadgeLabel = entryBadge.getByRole( 'textbox', { name: /^Label$/i } );
	const entryBadgeSlug = entryBadge.getByRole( 'textbox', { name: /^Badge slug$/i } );
	const entryDimensionsWidth = entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Width/i } );
	const entryDimensionsHeight = entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Height/i } );
	const entryDimensionsUnit = entryDimensions.getByRole( 'combobox', { name: /Entry card size unit/i } );
	const entrySpacingTop = entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Top/i } );
	const entrySpacingRight = entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Right/i } );
	const entrySpacingUnit = entrySpacing.getByRole( 'combobox', { name: /Entry card spacing unit/i } );
	const entryLinkLabel = entryLinks.getByRole( 'textbox', { name: /^Link label$/i } ).first();
	const entryLinkUrl = entryLinks.getByRole( 'textbox', { name: /^Link URL$/i } ).first();
	const newsletterChannel = panel.getByRole( 'checkbox', { name: /^Newsletter$/i } );

	await expect( page.locator( '#lerm-admin-config-metabox-acme-demo-post-metabox' ) ).toHaveCount( 0 );
	await expect( featuredToggle ).toBeVisible();
	await expect( entrySlug ).toBeVisible();
	await expect( entryLayout ).toBeVisible();
	await expect( entryFormat.getByRole( 'radio', { name: /^Standard$/i } ) ).toBeVisible();
	await expect( entryEmphasis.getByRole( 'button', { name: /^Normal$/i } ) ).toBeVisible();
	await expect( entryAccent ).toBeVisible();
	await expect( entryReviewDate ).toBeVisible();
	await expect( entryPriority ).toBeVisible();
	await expect( entryScore ).toBeVisible();
	await expect( entryUpload.getByRole( 'button', { name: /^Choose uploaded file$/i } ) ).toBeVisible();
	await expect( entryMedia.getByRole( 'button', { name: /^Choose image$/i } ) ).toBeVisible();
	await expect( entryGallery.getByRole( 'button', { name: /^Choose gallery images$/i } ) ).toBeVisible();
	await expect( entryBadgeLabel ).toBeVisible();
	await expect( entryBadgeSlug ).toBeVisible();
	await expect( entryDimensionsWidth ).toBeVisible();
	await expect( entryDimensionsHeight ).toBeVisible();
	await expect( entrySpacingTop ).toBeVisible();
	await expect( entrySpacingRight ).toBeVisible();
	await expect( entryLinkLabel ).toBeVisible();
	await expect( entryLinkUrl ).toBeVisible();
	await expect( newsletterChannel ).toBeVisible();
	await expect( entryIconReadOnly ).toContainText( /Field type "icon" is read-only/i );
	const initialChecked = await featuredToggle.isChecked();
	const initialSlug = await entrySlug.inputValue();
	const initialLayout = await entryLayout.inputValue();
	const initialFormat = await entryFormat.locator( 'input[type="radio"]:checked' ).inputValue();
	const initialEmphasis = await entryEmphasis.locator( 'button[aria-pressed="true"]' ).getAttribute( 'data-value' );
	const initialAccent = ( await entryAccent.inputValue() ).toLowerCase();
	const initialReviewDate = await entryReviewDate.inputValue();
	const initialPriority = await entryPriority.inputValue();
	const initialScore = await entryScore.inputValue();
	const initialBadgeLabel = await entryBadgeLabel.inputValue();
	const initialBadgeSlug = await entryBadgeSlug.inputValue();
	const initialDimensionsWidth = await entryDimensionsWidth.inputValue();
	const initialDimensionsHeight = await entryDimensionsHeight.inputValue();
	const initialDimensionsUnit = await entryDimensionsUnit.inputValue();
	const initialSpacingTop = await entrySpacingTop.inputValue();
	const initialSpacingRight = await entrySpacingRight.inputValue();
	const initialSpacingUnit = await entrySpacingUnit.inputValue();
	const initialLinkLabel = await entryLinkLabel.inputValue();
	const initialLinkUrl = await entryLinkUrl.inputValue();
	const initialNewsletter = await newsletterChannel.isChecked();
	const discardSlug = initialSlug === 'discard-check' ? 'discard-check-next' : 'discard-check';
	const discardLayout = initialLayout === 'wide' ? 'compact' : 'wide';
	const discardFormat = initialFormat === 'editorial' ? 'alert' : 'editorial';
	const discardEmphasis = initialEmphasis === 'spotlight' ? 'quiet' : 'spotlight';
	const discardAccent = initialAccent === '#445566' ? '#665544' : '#445566';
	const discardReviewDate = initialReviewDate === '2026-05-01' ? '2026-05-02' : '2026-05-01';
	const discardPriority = initialPriority === '5' ? '4' : '5';
	const discardScore = initialScore === '10' ? '9' : '10';
	const discardBadgeLabel = initialBadgeLabel === 'Draft Badge' ? 'Draft Badge Next' : 'Draft Badge';
	const discardBadgeSlug = initialBadgeSlug === 'draft-badge' ? 'draft-badge-next' : 'draft-badge';
	const discardDimensionsWidth = initialDimensionsWidth === '480' ? '420' : '480';
	const discardDimensionsHeight = initialDimensionsHeight === '260' ? '240' : '260';
	const discardDimensionsUnit = initialDimensionsUnit === '%' ? 'px' : '%';
	const discardSpacingTop = initialSpacingTop === '20' ? '18' : '20';
	const discardSpacingRight = initialSpacingRight === '24' ? '22' : '24';
	const discardSpacingUnit = initialSpacingUnit === 'rem' ? 'px' : 'rem';
	const discardLinkLabel = initialLinkLabel === 'Discard Link' ? 'Discard Link Next' : 'Discard Link';
	const discardLinkUrl = initialLinkUrl === 'https://example.test/discard' ? 'https://example.test/discard-next' : 'https://example.test/discard';
	const savedSlug = initialSlug === 'block-panel-valid' ? 'block-panel-valid-next' : 'block-panel-valid';
	const savedLayout = initialLayout === 'feature' ? 'compact' : 'feature';
	const savedFormat = initialFormat === 'alert' ? 'editorial' : 'alert';
	const savedEmphasis = initialEmphasis === 'quiet' ? 'spotlight' : 'quiet';
	const savedAccent = initialAccent === '#13579b' ? '#2468ac' : '#13579b';
	const savedReviewDate = initialReviewDate === '2026-05-03' ? '2026-05-04' : '2026-05-03';
	const savedPriority = initialPriority === '4' ? '3' : '4';
	const savedScore = initialScore === '7' ? '6' : '7';
	const savedBadgeLabel = initialBadgeLabel === 'Published Badge' ? 'Published Badge Next' : 'Published Badge';
	const savedBadgeSlug = initialBadgeSlug === 'published-badge' ? 'published-badge-next' : 'published-badge';
	const savedDimensionsWidth = initialDimensionsWidth === '640' ? '560' : '640';
	const savedDimensionsHeight = initialDimensionsHeight === '360' ? '320' : '360';
	const savedDimensionsUnit = initialDimensionsUnit === 'rem' ? 'px' : 'rem';
	const savedSpacingTop = initialSpacingTop === '16' ? '14' : '16';
	const savedSpacingRight = initialSpacingRight === '18' ? '14' : '18';
	const savedSpacingUnit = initialSpacingUnit === 'rem' ? 'px' : 'rem';
	const savedLinkLabel = initialLinkLabel === 'Continue reading' ? 'Read the update' : 'Continue reading';
	const savedLinkUrl = initialLinkUrl === 'https://example.test/update' ? 'https://example.test/story' : 'https://example.test/update';

	await entrySlug.fill( discardSlug );
	await entryLayout.selectOption( discardLayout );
	await entryFormat.getByRole( 'radio', { name: new RegExp( `^${ discardFormat }$`, 'i' ) } ).check();
	await entryEmphasis.locator( `button[data-value="${ discardEmphasis }"]` ).click();
	await setInputValue( entryAccent, discardAccent );
	await setInputValue( entryReviewDate, discardReviewDate );
	await setInputValue( entryPriority, discardPriority );
	await entryScore.fill( discardScore );
	await entryBadgeLabel.fill( discardBadgeLabel );
	await entryBadgeSlug.fill( discardBadgeSlug );
	await entryDimensionsWidth.fill( discardDimensionsWidth );
	await entryDimensionsHeight.fill( discardDimensionsHeight );
	await entryDimensionsUnit.selectOption( discardDimensionsUnit );
	await entrySpacingTop.fill( discardSpacingTop );
	await entrySpacingRight.fill( discardSpacingRight );
	await entrySpacingUnit.selectOption( discardSpacingUnit );
	await entryLinkLabel.fill( discardLinkLabel );
	await entryLinkUrl.fill( discardLinkUrl );
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
	await expect( entryBadgeLabel ).toHaveValue( initialBadgeLabel );
	await expect( entryBadgeSlug ).toHaveValue( initialBadgeSlug );
	await expect( entryDimensionsWidth ).toHaveValue( initialDimensionsWidth );
	await expect( entryDimensionsHeight ).toHaveValue( initialDimensionsHeight );
	await expect( entryDimensionsUnit ).toHaveValue( initialDimensionsUnit );
	await expect( entrySpacingTop ).toHaveValue( initialSpacingTop );
	await expect( entrySpacingRight ).toHaveValue( initialSpacingRight );
	await expect( entrySpacingUnit ).toHaveValue( initialSpacingUnit );
	await expect( entryLinkLabel ).toHaveValue( initialLinkLabel );
	await expect( entryLinkUrl ).toHaveValue( initialLinkUrl );
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

	await entryBadgeSlug.fill( 'x' );

	const invalidNestedSaveRequest = page.waitForResponse( ( saveResponse ) => isMetaboxSaveResponse( saveResponse, 'acme-demo-post-metabox' ), { timeout: 20_000 } );

	await panel.getByRole( 'button', { name: /^Save$/ } ).click();

	const invalidNestedSaveResponse = await invalidNestedSaveRequest;

	expect( invalidNestedSaveResponse.status() ).toBe( 422 );
	await expect( panel ).toHaveAttribute( 'data-status', 'error' );
	await expect( panel ).toHaveAttribute( 'data-error-count', '1' );
	await expect( panel.locator( '[data-field-error="entry_badge.slug"]' ) ).toContainText( /between 3 and 32/i );

	await entryBadgeSlug.fill( savedBadgeSlug );
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
	await entryBadgeLabel.fill( savedBadgeLabel );
	await entryDimensionsWidth.fill( savedDimensionsWidth );
	await entryDimensionsHeight.fill( savedDimensionsHeight );
	await entryDimensionsUnit.selectOption( savedDimensionsUnit );
	await entrySpacingTop.fill( savedSpacingTop );
	await entrySpacingRight.fill( savedSpacingRight );
	await entrySpacingUnit.selectOption( savedSpacingUnit );
	await entryLinkLabel.fill( savedLinkLabel );
	await entryLinkUrl.fill( savedLinkUrl );
	await newsletterChannel.setChecked( ! initialNewsletter );
	await selectMediaAttachments( page, entryUpload.getByRole( 'button', { name: /^Choose uploaded file$/i } ), [ 'Admin Config Media One' ] );
	await selectMediaAttachments( page, entryMedia.getByRole( 'button', { name: /^Choose image$/i } ), [ 'Admin Config Media Two' ] );
	await selectMediaAttachments( page, entryGallery.getByRole( 'button', { name: /^Choose gallery images$/i } ), [
		'Admin Config Media One',
		'Admin Config Media Three',
	] );

	const selectedMedia = await currentBlockPanelMediaValues( page );

	expect( selectedMedia.upload ).toContain( 'admin-config-media-one' );
	expect( selectedMedia.mediaId ).toBeGreaterThan( 0 );
	expect( selectedMedia.galleryIds ).toHaveLength( 2 );
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
	await expect( entryBadgeLabel ).toHaveValue( savedBadgeLabel );
	await expect( entryBadgeSlug ).toHaveValue( savedBadgeSlug );
	await expect( entryDimensionsWidth ).toHaveValue( savedDimensionsWidth );
	await expect( entryDimensionsHeight ).toHaveValue( savedDimensionsHeight );
	await expect( entryDimensionsUnit ).toHaveValue( savedDimensionsUnit );
	await expect( entrySpacingTop ).toHaveValue( savedSpacingTop );
	await expect( entrySpacingRight ).toHaveValue( savedSpacingRight );
	await expect( entrySpacingUnit ).toHaveValue( savedSpacingUnit );
	await expect( entryLinkLabel ).toHaveValue( savedLinkLabel );
	await expect( entryLinkUrl ).toHaveValue( savedLinkUrl );
	await expect( newsletterChannel ).toBeChecked( { checked: ! initialNewsletter } );
	await expect( entryUpload.locator( '.lerm-admin-config-block-panel__media-url-preview img' ) ).toHaveAttribute( 'src', /admin-config-media-one/i );
	await expect( entryMedia.locator( '.lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 1 );
	await expect( entryGallery.locator( '.lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 2 );

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
	await expect( reloadedPanel.locator( '[data-field-id="entry_badge"]' ).getByRole( 'textbox', { name: /^Label$/i } ) ).toHaveValue( savedBadgeLabel );
	await expect( reloadedPanel.locator( '[data-field-id="entry_badge"]' ).getByRole( 'textbox', { name: /^Badge slug$/i } ) ).toHaveValue( savedBadgeSlug );
	await expect( reloadedPanel.locator( '[data-field-id="entry_dimensions"]' ).getByRole( 'spinbutton', { name: /Entry card size Width/i } ) ).toHaveValue( savedDimensionsWidth );
	await expect( reloadedPanel.locator( '[data-field-id="entry_dimensions"]' ).getByRole( 'spinbutton', { name: /Entry card size Height/i } ) ).toHaveValue( savedDimensionsHeight );
	await expect( reloadedPanel.locator( '[data-field-id="entry_dimensions"]' ).getByRole( 'combobox', { name: /Entry card size unit/i } ) ).toHaveValue( savedDimensionsUnit );
	await expect( reloadedPanel.locator( '[data-field-id="entry_spacing"]' ).getByRole( 'spinbutton', { name: /Entry card spacing Top/i } ) ).toHaveValue( savedSpacingTop );
	await expect( reloadedPanel.locator( '[data-field-id="entry_spacing"]' ).getByRole( 'spinbutton', { name: /Entry card spacing Right/i } ) ).toHaveValue( savedSpacingRight );
	await expect( reloadedPanel.locator( '[data-field-id="entry_spacing"]' ).getByRole( 'combobox', { name: /Entry card spacing unit/i } ) ).toHaveValue( savedSpacingUnit );
	await expect( reloadedPanel.locator( '[data-field-id="entry_links"]' ).getByRole( 'textbox', { name: /^Link label$/i } ).first() ).toHaveValue( savedLinkLabel );
	await expect( reloadedPanel.locator( '[data-field-id="entry_links"]' ).getByRole( 'textbox', { name: /^Link URL$/i } ).first() ).toHaveValue( savedLinkUrl );
	await expect( reloadedPanel.locator( '[data-field-id="entry_upload"] .lerm-admin-config-block-panel__media-url-preview img' ) ).toHaveAttribute( 'src', /admin-config-media-one/i );
	await expect( reloadedPanel.locator( '[data-field-id="entry_media"] .lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 1 );
	await expect( reloadedPanel.locator( '[data-field-id="entry_gallery"] .lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 2 );
	await expect
		.poll( () => currentBlockPanelMediaValues( page ), { timeout: 30_000 } )
		.toMatchObject( selectedMedia );
	await expect( reloadedPanel.getByRole( 'checkbox', { name: /^Newsletter$/i } ) ).toBeChecked(
		{ checked: ! initialNewsletter }
	);
	expect( ajaxRequests ).toEqual( [] );
} );
