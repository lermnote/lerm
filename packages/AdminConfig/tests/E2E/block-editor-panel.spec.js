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

const isMetaboxDataSourceResponse = ( response, schemaId = 'acme-demo-post-metabox' ) => {
	const url = decodedUrl( response );

	return (
		response.request().method() === 'POST' &&
		(
			url.includes( `/wp-json/lerm-admin-config/v1/schemas/${ schemaId }/data-source` ) ||
			url.includes( `rest_route=/lerm-admin-config/v1/schemas/${ schemaId }/data-source` )
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

const setColorValue = async ( locator, value ) => {
	const input = locator.locator( 'input' ).first();
	await input.fill( value );
};

const currentBlockPanelMediaValues = async ( page ) => page.evaluate( () => {
	const instances = window.lermAdminConfigBlockPanel?.getInstances?.() || [];
	const instance = instances.find( ( candidate ) => candidate.schemaId === 'acme-demo-post-metabox' ) || {};
	const values = instance.state?.values || {};
	const media = values.entry_media || {};
	const background = values.entry_background || {};
	const backgroundImage = background[ 'background-image' ] || {};
	const gallery = Array.isArray( values.entry_gallery ) ? values.entry_gallery : [];

	return {
		backgroundMediaId: Number( backgroundImage?.id || backgroundImage || 0 ),
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
		if ( ! await checkbox.isChecked().catch( () => false ) ) {
			await checkbox.click();
		}
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

const selectAjaxOption = async ( page, field, search, value, label ) => {
	const input = field.getByRole( 'searchbox', { name: /Search entry campaign/i } );
	const responsePromise = page.waitForResponse(
		( response ) => isMetaboxDataSourceResponse( response ),
		{ timeout: 20_000 }
	);

	await input.fill( search );

	const response = await responsePromise;

	expect( response.ok() ).toBe( true );
	await expect( field.locator( `button[data-value="${ value }"]` ) ).toContainText( label, { timeout: 20_000 } );
	await field.locator( `button[data-value="${ value }"]` ).click();
};

/**
 * Gather all field locators from the expanded panel.
 *
 * @param {import('@playwright/test').Locator} panel
 * @returns {Record<string, import('@playwright/test').Locator>}
 */
const gatherLocators = ( panel ) => ( {
	featuredToggle: panel.getByRole( 'checkbox', { name: /Feature this entry/i } ),
	entrySlug: panel.getByRole( 'textbox', { name: /Entry slug/i } ),
	entryLayout: panel.getByRole( 'combobox', { name: /Entry layout/i } ),
	entryFormat: panel.locator( '[data-field-id="entry_format"]' ),
	entryEmphasis: panel.locator( '[data-field-id="entry_emphasis"]' ),
	entryAccent: panel.locator( '[data-field-id="entry_accent"] [data-color-field="entry_accent"]' ),
	entryReviewDate: panel.locator( '[data-field-id="entry_review_date"] input[type="date"]' ),
	entryPriority: panel.locator( '[data-field-id="entry_priority"] input[type="range"]' ),
	entryScore: panel.locator( '[data-field-id="entry_score"] input[type="number"]' ),
	entryUpload: panel.locator( '[data-field-id="entry_upload"]' ),
	entryMedia: panel.locator( '[data-field-id="entry_media"]' ),
	entryGallery: panel.locator( '[data-field-id="entry_gallery"]' ),
	entryDimensions: panel.locator( '[data-field-id="entry_dimensions"]' ),
	entrySpacing: panel.locator( '[data-field-id="entry_spacing"]' ),
	entryBorder: panel.locator( '[data-field-id="entry_border"]' ),
	entryLinkColors: panel.locator( '[data-field-id="entry_link_colors"]' ),
	entryTypography: panel.locator( '[data-field-id="entry_typography"]' ),
	entryBackground: panel.locator( '[data-field-id="entry_background"]' ),
	entryPalette: panel.locator( '[data-field-id="entry_palette"]' ),
	entryImageStyle: panel.locator( '[data-field-id="entry_image_style"]' ),
	entryCampaign: panel.locator( '[data-field-id="entry_campaign"]' ),
	entryLinks: panel.locator( '[data-field-id="entry_links"]' ),
	entryIcon: panel.locator( '[data-field-id="entry_icon"]' ),
	entryBadge: panel.locator( '[data-field-id="entry_badge"]' ),
} );

/**
 * Read all initial field values from the panel.
 *
 * @param {Record<string, import('@playwright/test').Locator>} l
 * @returns {Promise<Record<string, string>>}
 */
const readInitialValues = async ( l ) => ( {
	checked: await l.featuredToggle.isChecked(),
	slug: await l.entrySlug.inputValue(),
	layout: await l.entryLayout.inputValue(),
	format: await l.entryFormat.locator( 'input[type="radio"]:checked' ).inputValue(),
	emphasis: await l.entryEmphasis.locator( 'button[aria-pressed="true"]' ).getAttribute( 'data-value' ),
	accent: ( await l.entryAccent.getAttribute( 'data-color-value' ) ).toLowerCase(),
	reviewDate: await l.entryReviewDate.inputValue(),
	priority: await l.entryPriority.inputValue(),
	score: await l.entryScore.inputValue(),
	badgeLabel: await l.entryBadge.getByRole( 'textbox', { name: /^Label$/i } ).inputValue(),
	badgeSlug: await l.entryBadge.getByRole( 'textbox', { name: /^Badge slug$/i } ).inputValue(),
	dimensionsWidth: await l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Width/i } ).inputValue(),
	dimensionsHeight: await l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Height/i } ).inputValue(),
	dimensionsUnit: await l.entryDimensions.getByRole( 'combobox', { name: /Entry card size unit/i } ).inputValue(),
	spacingTop: await l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Top/i } ).inputValue(),
	spacingRight: await l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Right/i } ).inputValue(),
	spacingUnit: await l.entrySpacing.getByRole( 'combobox', { name: /Entry card spacing unit/i } ).inputValue(),
	borderTop: await l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Top/i } ).inputValue(),
	borderRight: await l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Right/i } ).inputValue(),
	borderStyle: await l.entryBorder.getByRole( 'combobox', { name: /Entry card border Style/i } ).inputValue(),
	borderColor: ( await l.entryBorder.locator( '[data-color-field="entry_border.color"]' ).getAttribute( 'data-color-value' ) ).toLowerCase(),
	linkNormal: ( await l.entryLinkColors.locator( '[data-color-field="entry_link_colors.color"]' ).getAttribute( 'data-color-value' ) ).toLowerCase(),
	linkHover: ( await l.entryLinkColors.locator( '[data-color-field="entry_link_colors.hover"]' ).getAttribute( 'data-color-value' ) ).toLowerCase(),
	typographyFamily: await l.entryTypography.getByRole( 'textbox', { name: /^Family$/i } ).inputValue(),
	typographyWeight: await l.entryTypography.getByRole( 'combobox', { name: /^Weight$/i } ).inputValue(),
	typographyStyle: await l.entryTypography.locator( '[data-field-path="entry_typography.font-style"] button[aria-pressed="true"]' ).getAttribute( 'data-value' ),
	typographySize: await l.entryTypography.getByRole( 'textbox', { name: /^Size$/i } ).inputValue(),
	typographyUnit: await l.entryTypography.getByRole( 'combobox', { name: /^Unit$/i } ).inputValue(),
	typographyLineHeight: await l.entryTypography.getByRole( 'textbox', { name: /^Line height$/i } ).inputValue(),
	typographyLetterSpacing: await l.entryTypography.getByRole( 'textbox', { name: /^Letter spacing$/i } ).inputValue(),
	typographyAlign: await l.entryTypography.locator( '[data-field-path="entry_typography.text-align"] button[aria-pressed="true"]' ).getAttribute( 'data-value' ),
	typographyColor: ( await l.entryTypography.locator( '[data-field-path="entry_typography.color"] [data-color-field="entry_typography.color"]' ).getAttribute( 'data-color-value' ) ).toLowerCase(),
	backgroundColor: ( await l.entryBackground.locator( '[data-field-path="entry_background.background-color"] [data-color-field="entry_background.background-color"]' ).getAttribute( 'data-color-value' ) ).toLowerCase(),
	backgroundGradientColor: ( await l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-color"] [data-color-field="entry_background.background-gradient-color"]' ).getAttribute( 'data-color-value' ) ).toLowerCase(),
	backgroundGradientDirection: await l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-direction"] select' ).inputValue(),
	backgroundPosition: await l.entryBackground.locator( '[data-field-path="entry_background.background-position"] select' ).inputValue(),
	backgroundRepeat: await l.entryBackground.locator( '[data-field-path="entry_background.background-repeat"] select' ).inputValue(),
	backgroundAttachment: await l.entryBackground.locator( '[data-field-path="entry_background.background-attachment"] select' ).inputValue(),
	backgroundSize: await l.entryBackground.locator( '[data-field-path="entry_background.background-size"] select' ).inputValue(),
	backgroundOrigin: await l.entryBackground.locator( '[data-field-path="entry_background.background-origin"] select' ).inputValue(),
	backgroundClip: await l.entryBackground.locator( '[data-field-path="entry_background.background-clip"] select' ).inputValue(),
	backgroundBlendMode: await l.entryBackground.locator( '[data-field-path="entry_background.background-blend-mode"] select' ).inputValue(),
	palette: await l.entryPalette.locator( 'button[aria-pressed="true"]' ).getAttribute( 'data-value' ),
	imageStyle: await l.entryImageStyle.locator( 'button[aria-pressed="true"]' ).getAttribute( 'data-value' ),
	campaign: await l.entryCampaign.locator( '[data-selected-value]' ).first().getAttribute( 'data-selected-value' ),
	icon: await l.entryIcon.locator( 'button[aria-pressed="true"]' ).getAttribute( 'data-value' ),
	linkLabel: await l.entryLinks.getByRole( 'textbox', { name: /^Link label$/i } ).first().inputValue(),
	linkUrl: await l.entryLinks.getByRole( 'textbox', { name: /^Link URL$/i } ).first().inputValue(),
	newsletter: await panel.getByRole( 'checkbox', { name: /^Newsletter$/i } ).isChecked(),
} );

/**
 * Fill all fields with a computed set of values.
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} panel
 * @param {Record<string, import('@playwright/test').Locator>} l
 * @param {Record<string, string>} v
 * @param {boolean} initialNewsletter
 */
const fillAllFields = async ( page, panel, l, v, initialNewsletter ) => {
	const typographyStyleItalic = l.entryTypography.locator( '[data-field-path="entry_typography.font-style"] button[data-value="italic"]' );
	const typographyStyleNormal = l.entryTypography.locator( '[data-field-path="entry_typography.font-style"] button[data-value="normal"]' );
	const typographyAlignCenter = l.entryTypography.locator( '[data-field-path="entry_typography.text-align"] button[data-value="center"]' );
	const typographyAlignLeft = l.entryTypography.locator( '[data-field-path="entry_typography.text-align"] button[data-value="left"]' );

	await l.featuredToggle.setChecked( ! v.checked );
	await l.entrySlug.fill( v.slug );
	await l.entryLayout.selectOption( v.layout );
	await l.entryFormat.getByRole( 'radio', { name: new RegExp( `^${ v.format }$`, 'i' ) } ).check();
	await l.entryEmphasis.locator( `button[data-value="${ v.emphasis }"]` ).click();
	await setColorValue( l.entryAccent, v.accent );
	await setInputValue( l.entryReviewDate, v.reviewDate );
	await setInputValue( l.entryPriority, v.priority );
	await l.entryScore.fill( v.score );
	await l.entryBadge.getByRole( 'textbox', { name: /^Label$/i } ).fill( v.badgeLabel );
	await l.entryBadge.getByRole( 'textbox', { name: /^Badge slug$/i } ).fill( v.badgeSlug );
	await l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Width/i } ).fill( v.dimensionsWidth );
	await l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Height/i } ).fill( v.dimensionsHeight );
	await l.entryDimensions.getByRole( 'combobox', { name: /Entry card size unit/i } ).selectOption( v.dimensionsUnit );
	await l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Top/i } ).fill( v.spacingTop );
	await l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Right/i } ).fill( v.spacingRight );
	await l.entrySpacing.getByRole( 'combobox', { name: /Entry card spacing unit/i } ).selectOption( v.spacingUnit );
	await l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Top/i } ).fill( v.borderTop );
	await l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Right/i } ).fill( v.borderRight );
	await l.entryBorder.getByRole( 'combobox', { name: /Entry card border Style/i } ).selectOption( v.borderStyle );
	await setColorValue( l.entryBorder.locator( '[data-color-field="entry_border.color"]' ), v.borderColor );
	await setColorValue( l.entryLinkColors.locator( '[data-color-field="entry_link_colors.color"]' ), v.linkNormal );
	await setColorValue( l.entryLinkColors.locator( '[data-color-field="entry_link_colors.hover"]' ), v.linkHover );
	await l.entryTypography.getByRole( 'textbox', { name: /^Family$/i } ).fill( v.typographyFamily );
	await l.entryTypography.getByRole( 'combobox', { name: /^Weight$/i } ).selectOption( v.typographyWeight );
	await ( v.typographyStyle === 'italic' ? typographyStyleItalic : typographyStyleNormal ).click();
	await l.entryTypography.getByRole( 'textbox', { name: /^Size$/i } ).fill( v.typographySize );
	await l.entryTypography.getByRole( 'combobox', { name: /^Unit$/i } ).selectOption( v.typographyUnit );
	await l.entryTypography.getByRole( 'textbox', { name: /^Line height$/i } ).fill( v.typographyLineHeight );
	await l.entryTypography.getByRole( 'textbox', { name: /^Letter spacing$/i } ).fill( v.typographyLetterSpacing );
	await ( v.typographyAlign === 'center' ? typographyAlignCenter : typographyAlignLeft ).click();
	await setColorValue( l.entryTypography.locator( '[data-field-path="entry_typography.color"] [data-color-field="entry_typography.color"]' ), v.typographyColor );
	await setColorValue( l.entryBackground.locator( '[data-field-path="entry_background.background-color"] [data-color-field="entry_background.background-color"]' ), v.backgroundColor );
	await setColorValue( l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-color"] [data-color-field="entry_background.background-gradient-color"]' ), v.backgroundGradientColor );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-direction"] select' ).selectOption( v.backgroundGradientDirection );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-position"] select' ).selectOption( v.backgroundPosition );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-repeat"] select' ).selectOption( v.backgroundRepeat );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-attachment"] select' ).selectOption( v.backgroundAttachment );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-size"] select' ).selectOption( v.backgroundSize );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-origin"] select' ).selectOption( v.backgroundOrigin );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-clip"] select' ).selectOption( v.backgroundClip );
	await l.entryBackground.locator( '[data-field-path="entry_background.background-blend-mode"] select' ).selectOption( v.backgroundBlendMode );
	await l.entryPalette.locator( `button[data-value="${ v.palette }"]` ).click();
	await l.entryImageStyle.locator( `button[data-value="${ v.imageStyle }"]` ).click();
	await selectAjaxOption( page, l.entryCampaign, v.campaignSearch, v.campaign, v.campaignLabel );
	await l.entryIcon.locator( `button[data-value="${ v.icon }"]` ).click();
	await l.entryLinks.getByRole( 'textbox', { name: /^Link label$/i } ).first().fill( v.linkLabel );
	await l.entryLinks.getByRole( 'textbox', { name: /^Link URL$/i } ).first().fill( v.linkUrl );
	await panel.getByRole( 'checkbox', { name: /^Newsletter$/i } ).setChecked( ! initialNewsletter );
};

/**
 * Compute a set of "discard" values that differ from the initial values.
 *
 * @param {Record<string, string>} init
 * @returns {Record<string, string>}
 */
const computeDiscardValues = ( init ) => ( {
	slug: init.slug === 'discard-check' ? 'discard-check-next' : 'discard-check',
	layout: init.layout === 'wide' ? 'compact' : 'wide',
	format: init.format === 'editorial' ? 'alert' : 'editorial',
	emphasis: init.emphasis === 'spotlight' ? 'quiet' : 'spotlight',
	accent: init.accent === '#445566' ? '#665544' : '#445566',
	reviewDate: init.reviewDate === '2026-05-01' ? '2026-05-02' : '2026-05-01',
	priority: init.priority === '5' ? '4' : '5',
	score: init.score === '10' ? '9' : '10',
	badgeLabel: init.badgeLabel === 'Draft Badge' ? 'Draft Badge Next' : 'Draft Badge',
	badgeSlug: init.badgeSlug === 'draft-badge' ? 'draft-badge-next' : 'draft-badge',
	dimensionsWidth: init.dimensionsWidth === '480' ? '420' : '480',
	dimensionsHeight: init.dimensionsHeight === '260' ? '240' : '260',
	dimensionsUnit: init.dimensionsUnit === '%' ? 'px' : '%',
	spacingTop: init.spacingTop === '20' ? '18' : '20',
	spacingRight: init.spacingRight === '24' ? '22' : '24',
	spacingUnit: init.spacingUnit === 'rem' ? 'px' : 'rem',
	borderTop: init.borderTop === '4' ? '3' : '4',
	borderRight: init.borderRight === '5' ? '4' : '5',
	borderStyle: init.borderStyle === 'dashed' ? 'solid' : 'dashed',
	borderColor: init.borderColor === '#aa5500' ? '#0055aa' : '#aa5500',
	linkNormal: init.linkNormal === '#aa0000' ? '#0044aa' : '#aa0000',
	linkHover: init.linkHover === '#00aa44' ? '#aa4400' : '#00aa44',
	typographyFamily: init.typographyFamily === 'Georgia, serif' ? 'Inter, system-ui, sans-serif' : 'Georgia, serif',
	typographyWeight: init.typographyWeight === '600' ? '500' : '600',
	typographyStyle: init.typographyStyle === 'italic' ? 'normal' : 'italic',
	typographySize: init.typographySize === '2.4' ? '2.2' : '2.4',
	typographyUnit: init.typographyUnit === 'px' ? 'rem' : 'px',
	typographyLineHeight: init.typographyLineHeight === '1.4' ? '1.3' : '1.4',
	typographyLetterSpacing: init.typographyLetterSpacing === '0.02' ? '0.01' : '0.02',
	typographyAlign: init.typographyAlign === 'center' ? 'left' : 'center',
	typographyColor: init.typographyColor === '#665544' ? '#445566' : '#665544',
	backgroundColor: init.backgroundColor === '#eeffee' ? '#ffeeee' : '#eeffee',
	backgroundGradientColor: init.backgroundGradientColor === '#ddeeff' ? '#ffeedd' : '#ddeeff',
	backgroundGradientDirection: init.backgroundGradientDirection === '135deg' ? 'to bottom' : '135deg',
	backgroundPosition: init.backgroundPosition === 'right bottom' ? 'left top' : 'right bottom',
	backgroundRepeat: init.backgroundRepeat === 'repeat-x' ? 'repeat-y' : 'repeat-x',
	backgroundAttachment: init.backgroundAttachment === 'fixed' ? 'scroll' : 'fixed',
	backgroundSize: init.backgroundSize === 'contain' ? 'auto' : 'contain',
	backgroundOrigin: init.backgroundOrigin === 'border-box' ? 'content-box' : 'border-box',
	backgroundClip: init.backgroundClip === 'padding-box' ? 'content-box' : 'padding-box',
	backgroundBlendMode: init.backgroundBlendMode === 'screen' ? 'overlay' : 'screen',
	palette: init.palette === 'warm' ? 'cool' : 'warm',
	imageStyle: init.imageStyle === 'split' ? 'cover' : 'split',
	campaign: init.campaign === 'studio-preview' ? 'design-sprint' : 'studio-preview',
	campaignSearch: init.campaign === 'studio-preview' ? 'design' : 'studio',
	campaignLabel: init.campaign === 'studio-preview' ? 'Design Sprint' : 'Studio Preview',
	icon: init.icon === 'dashicons-star-filled' ? 'dashicons-format-aside' : 'dashicons-star-filled',
	linkLabel: init.linkLabel === 'Discard Link' ? 'Discard Link Next' : 'Discard Link',
	linkUrl: init.linkUrl === 'https://example.test/discard' ? 'https://example.test/discard-next' : 'https://example.test/discard',
} );

/**
 * Compute a set of "saved" values that differ from both initial and discard.
 *
 * @param {Record<string, string>} init
 * @returns {Record<string, string>}
 */
const computeSavedValues = ( init ) => ( {
	slug: init.slug === 'block-panel-valid' ? 'block-panel-valid-next' : 'block-panel-valid',
	layout: init.layout === 'feature' ? 'compact' : 'feature',
	format: init.format === 'alert' ? 'editorial' : 'alert',
	emphasis: init.emphasis === 'quiet' ? 'spotlight' : 'quiet',
	accent: init.accent === '#13579b' ? '#2468ac' : '#13579b',
	reviewDate: init.reviewDate === '2026-05-03' ? '2026-05-04' : '2026-05-03',
	priority: init.priority === '4' ? '3' : '4',
	score: init.score === '7' ? '6' : '7',
	badgeLabel: init.badgeLabel === 'Published Badge' ? 'Published Badge Next' : 'Published Badge',
	badgeSlug: init.badgeSlug === 'published-badge' ? 'published-badge-next' : 'published-badge',
	dimensionsWidth: init.dimensionsWidth === '640' ? '560' : '640',
	dimensionsHeight: init.dimensionsHeight === '360' ? '320' : '360',
	dimensionsUnit: init.dimensionsUnit === 'rem' ? 'px' : 'rem',
	spacingTop: init.spacingTop === '16' ? '14' : '16',
	spacingRight: init.spacingRight === '18' ? '14' : '18',
	spacingUnit: init.spacingUnit === 'rem' ? 'px' : 'rem',
	borderTop: init.borderTop === '2' ? '3' : '2',
	borderRight: init.borderRight === '6' ? '5' : '6',
	borderStyle: init.borderStyle === 'double' ? 'dotted' : 'double',
	borderColor: init.borderColor === '#2468ac' ? '#13579b' : '#2468ac',
	linkNormal: init.linkNormal === '#123abc' ? '#345abc' : '#123abc',
	linkHover: init.linkHover === '#bc123a' ? '#ac2468' : '#bc123a',
	typographyFamily: init.typographyFamily === 'Aptos, sans-serif' ? 'Inter, system-ui, sans-serif' : 'Aptos, sans-serif',
	typographyWeight: init.typographyWeight === '800' ? '700' : '800',
	typographyStyle: init.typographyStyle === 'italic' ? 'normal' : 'italic',
	typographySize: init.typographySize === '2.75' ? '2.5' : '2.75',
	typographyUnit: init.typographyUnit === 'px' ? 'rem' : 'px',
	typographyLineHeight: init.typographyLineHeight === '1.05' ? '1.1' : '1.05',
	typographyLetterSpacing: init.typographyLetterSpacing === '0.03' ? '0.02' : '0.03',
	typographyAlign: init.typographyAlign === 'center' ? 'left' : 'center',
	typographyColor: init.typographyColor === '#8844aa' ? '#aa4488' : '#8844aa',
	backgroundColor: init.backgroundColor === '#fafafa' ? '#f0f9ff' : '#fafafa',
	backgroundGradientColor: init.backgroundGradientColor === '#dbeafe' ? '#fef3c7' : '#dbeafe',
	backgroundGradientDirection: init.backgroundGradientDirection === '-135deg' ? 'to right' : '-135deg',
	backgroundPosition: init.backgroundPosition === 'center top' ? 'center bottom' : 'center top',
	backgroundRepeat: init.backgroundRepeat === 'no-repeat' ? 'repeat' : 'no-repeat',
	backgroundAttachment: init.backgroundAttachment === 'fixed' ? 'scroll' : 'fixed',
	backgroundSize: init.backgroundSize === 'cover' ? 'contain' : 'cover',
	backgroundOrigin: init.backgroundOrigin === 'padding-box' ? 'border-box' : 'padding-box',
	backgroundClip: init.backgroundClip === 'border-box' ? 'padding-box' : 'border-box',
	backgroundBlendMode: init.backgroundBlendMode === 'multiply' ? 'normal' : 'multiply',
	palette: init.palette === 'mono' ? 'cool' : 'mono',
	imageStyle: init.imageStyle === 'poster' ? 'cover' : 'poster',
	campaign: init.campaign === 'pro-tools' ? 'community-notes' : 'pro-tools',
	campaignSearch: init.campaign === 'pro-tools' ? 'community' : 'pro',
	campaignLabel: init.campaign === 'pro-tools' ? 'Community Notes' : 'Pro Tools',
	icon: init.icon === 'dashicons-megaphone' ? 'dashicons-format-aside' : 'dashicons-megaphone',
	linkLabel: init.linkLabel === 'Continue reading' ? 'Read the update' : 'Continue reading',
	linkUrl: init.linkUrl === 'https://example.test/update' ? 'https://example.test/story' : 'https://example.test/update',
} );

test.skip(
	process.env.LERM_ADMIN_CONFIG_BLOCK_EDITOR !== '1',
	'Block editor smoke runs through npm run test:e2e:block-editor so the fixture can temporarily enable the editor.'
);

// Shared state across serial tests.
const shared = /** @type {any} */ ( {} );

test.describe.serial( 'block editor AdminConfig panel', () => {

	test( 'schema loads and panel renders all fields', async ( { page } ) => {
		await login( page );

		shared.ajaxRequests = collectAdminConfigAjaxRequests( page );
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

		shared.panel = await expandBlockPanel( page, 'acme-demo-post-metabox', 'Entry Display Overrides' );
		shared.page = page;

		const l = gatherLocators( shared.panel );

		await expect( page.locator( '#lerm-admin-config-metabox-acme-demo-post-metabox' ) ).toHaveCount( 0 );
		await expect( l.featuredToggle ).toBeVisible();
		await expect( l.entrySlug ).toBeVisible();
		await expect( l.entryLayout ).toBeVisible();
		await expect( l.entryFormat.getByRole( 'radio', { name: /^Standard$/i } ) ).toBeVisible();
		await expect( l.entryEmphasis.getByRole( 'button', { name: /^Normal$/i } ) ).toBeVisible();
		await expect( l.entryAccent ).toBeVisible();
		await expect( l.entryReviewDate ).toBeVisible();
		await expect( l.entryPriority ).toBeVisible();
		await expect( l.entryScore ).toBeVisible();
		await expect( l.entryUpload.getByRole( 'button', { name: /^Choose uploaded file$/i } ) ).toBeVisible();
		await expect( l.entryMedia.getByRole( 'button', { name: /^Choose image$/i } ) ).toBeVisible();
		await expect( l.entryGallery.getByRole( 'button', { name: /^Choose gallery images$/i } ) ).toBeVisible();
		await expect( l.entryBadge.getByRole( 'textbox', { name: /^Label$/i } ) ).toBeVisible();
		await expect( l.entryBadge.getByRole( 'textbox', { name: /^Badge slug$/i } ) ).toBeVisible();
		await expect( l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Width/i } ) ).toBeVisible();
		await expect( l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Height/i } ) ).toBeVisible();
		await expect( l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Top/i } ) ).toBeVisible();
		await expect( l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Right/i } ) ).toBeVisible();
		await expect( l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Top/i } ) ).toBeVisible();
		await expect( l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Right/i } ) ).toBeVisible();
		await expect( l.entryBorder.getByRole( 'combobox', { name: /Entry card border Style/i } ) ).toBeVisible();
		await expect( l.entryBorder.locator( '[data-color-field="entry_border.color"]' ) ).toBeVisible();
		await expect( l.entryLinkColors.locator( '[data-color-field="entry_link_colors.color"]' ) ).toBeVisible();
		await expect( l.entryLinkColors.locator( '[data-color-field="entry_link_colors.hover"]' ) ).toBeVisible();
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Family$/i } ) ).toBeVisible();
		await expect( l.entryTypography.getByRole( 'combobox', { name: /^Weight$/i } ) ).toBeVisible();
		await expect( l.entryTypography.locator( '[data-field-path="entry_typography.font-style"] button[data-value="normal"]' ) ).toBeVisible();
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Size$/i } ) ).toBeVisible();
		await expect( l.entryTypography.getByRole( 'combobox', { name: /^Unit$/i } ) ).toBeVisible();
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Line height$/i } ) ).toBeVisible();
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Letter spacing$/i } ) ).toBeVisible();
		await expect( l.entryTypography.locator( '[data-field-path="entry_typography.text-align"] button[data-value="left"]' ) ).toBeVisible();
		await expect( l.entryTypography.locator( '[data-field-path="entry_typography.color"] [data-color-field="entry_typography.color"]' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-color"] [data-color-field="entry_background.background-color"]' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-color"] [data-color-field="entry_background.background-gradient-color"]' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-direction"] select' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-image"]' ).getByRole( 'button', { name: /^Choose background image$/i } ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-position"] select' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-repeat"] select' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-attachment"] select' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-size"] select' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-origin"] select' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-clip"] select' ) ).toBeVisible();
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-blend-mode"] select' ) ).toBeVisible();
		await expect( l.entryPalette.locator( 'button[data-value="cool"]' ) ).toBeVisible();
		await expect( l.entryPalette.locator( 'button[data-value="warm"]' ) ).toBeVisible();
		await expect( l.entryImageStyle.locator( 'button[data-value="cover"]' ) ).toBeVisible();
		await expect( l.entryImageStyle.locator( 'button[data-value="split"]' ) ).toBeVisible();
		await expect( l.entryCampaign.getByRole( 'searchbox', { name: /Search entry campaign/i } ) ).toBeVisible();
		await expect( l.entryCampaign.locator( '[data-selected-value="spring-launch"]' ) ).toContainText( /Spring Launch|spring-launch/i );
		await expect( l.entryIcon.locator( 'button[data-value="dashicons-format-aside"]' ) ).toBeVisible();
		await expect( l.entryIcon.locator( 'button[data-value="dashicons-megaphone"]' ) ).toBeVisible();
		await expect( l.entryLinks.getByRole( 'textbox', { name: /^Link label$/i } ).first() ).toBeVisible();
		await expect( l.entryLinks.getByRole( 'textbox', { name: /^Link URL$/i } ).first() ).toBeVisible();
		await expect( shared.panel.getByRole( 'checkbox', { name: /^Newsletter$/i } ) ).toBeVisible();

		shared.initial = await readInitialValues( l );
	} );

	test( 'discard reverts all unsaved changes', async () => {
		const page = shared.page;
		const panel = shared.panel;
		const init = shared.initial;
		const l = gatherLocators( panel );

		const discard = computeDiscardValues( init );

		await fillAllFields( page, panel, l, discard, init.newsletter );
		await expect( panel ).toHaveAttribute( 'data-dirty', 'true' );

		const discardClickPromise = panel.getByRole( 'button', { name: /^Discard$/ } ).click();
		const discardModal = page.locator( '.components-modal__frame' ).filter( { hasText: /Discard unsaved AdminConfig changes?/ } ).first();

		await expect( discardModal ).toBeVisible( { timeout: 5_000 } );
		await expect( discardModal.getByRole( 'button', { name: /^Discard$/ } ).first() ).toBeVisible();
		await discardModal.getByRole( 'button', { name: /^Discard$/ } ).first().click();
		await discardClickPromise;

		await expect( panel ).toHaveAttribute( 'data-dirty', 'false' );
		await expect( l.entrySlug ).toHaveValue( init.slug );
		await expect( l.entryLayout ).toHaveValue( init.layout );
		await expect( l.entryFormat.locator( 'input[type="radio"]:checked' ) ).toHaveValue( init.format );
		await expect( l.entryEmphasis.locator( 'button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', init.emphasis || '' );
		await expect( l.entryAccent ).toHaveAttribute( 'data-color-value', init.accent );
		await expect( l.entryReviewDate ).toHaveValue( init.reviewDate );
		await expect( l.entryPriority ).toHaveValue( init.priority );
		await expect( l.entryScore ).toHaveValue( init.score );
		await expect( l.entryBadge.getByRole( 'textbox', { name: /^Label$/i } ) ).toHaveValue( init.badgeLabel );
		await expect( l.entryBadge.getByRole( 'textbox', { name: /^Badge slug$/i } ) ).toHaveValue( init.badgeSlug );
		await expect( l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Width/i } ) ).toHaveValue( init.dimensionsWidth );
		await expect( l.entryDimensions.getByRole( 'spinbutton', { name: /Entry card size Height/i } ) ).toHaveValue( init.dimensionsHeight );
		await expect( l.entryDimensions.getByRole( 'combobox', { name: /Entry card size unit/i } ) ).toHaveValue( init.dimensionsUnit );
		await expect( l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Top/i } ) ).toHaveValue( init.spacingTop );
		await expect( l.entrySpacing.getByRole( 'spinbutton', { name: /Entry card spacing Right/i } ) ).toHaveValue( init.spacingRight );
		await expect( l.entrySpacing.getByRole( 'combobox', { name: /Entry card spacing unit/i } ) ).toHaveValue( init.spacingUnit );
		await expect( l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Top/i } ) ).toHaveValue( init.borderTop );
		await expect( l.entryBorder.getByRole( 'spinbutton', { name: /Entry card border Right/i } ) ).toHaveValue( init.borderRight );
		await expect( l.entryBorder.getByRole( 'combobox', { name: /Entry card border Style/i } ) ).toHaveValue( init.borderStyle );
		await expect( l.entryBorder.locator( '[data-color-field="entry_border.color"]' ) ).toHaveAttribute( 'data-color-value', init.borderColor );
		await expect( l.entryLinkColors.locator( '[data-color-field="entry_link_colors.color"]' ) ).toHaveAttribute( 'data-color-value', init.linkNormal );
		await expect( l.entryLinkColors.locator( '[data-color-field="entry_link_colors.hover"]' ) ).toHaveAttribute( 'data-color-value', init.linkHover );
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Family$/i } ) ).toHaveValue( init.typographyFamily );
		await expect( l.entryTypography.getByRole( 'combobox', { name: /^Weight$/i } ) ).toHaveValue( init.typographyWeight );
		await expect( l.entryTypography.locator( '[data-field-path="entry_typography.font-style"] button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', init.typographyStyle || '' );
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Size$/i } ) ).toHaveValue( init.typographySize );
		await expect( l.entryTypography.getByRole( 'combobox', { name: /^Unit$/i } ) ).toHaveValue( init.typographyUnit );
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Line height$/i } ) ).toHaveValue( init.typographyLineHeight );
		await expect( l.entryTypography.getByRole( 'textbox', { name: /^Letter spacing$/i } ) ).toHaveValue( init.typographyLetterSpacing );
		await expect( l.entryTypography.locator( '[data-field-path="entry_typography.text-align"] button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', init.typographyAlign || '' );
		await expect( l.entryTypography.locator( '[data-field-path="entry_typography.color"] [data-color-field="entry_typography.color"]' ) ).toHaveAttribute( 'data-color-value', init.typographyColor );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-color"] [data-color-field="entry_background.background-color"]' ) ).toHaveAttribute( 'data-color-value', init.backgroundColor );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-color"] [data-color-field="entry_background.background-gradient-color"]' ) ).toHaveAttribute( 'data-color-value', init.backgroundGradientColor );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-gradient-direction"] select' ) ).toHaveValue( init.backgroundGradientDirection );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-position"] select' ) ).toHaveValue( init.backgroundPosition );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-repeat"] select' ) ).toHaveValue( init.backgroundRepeat );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-attachment"] select' ) ).toHaveValue( init.backgroundAttachment );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-size"] select' ) ).toHaveValue( init.backgroundSize );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-origin"] select' ) ).toHaveValue( init.backgroundOrigin );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-clip"] select' ) ).toHaveValue( init.backgroundClip );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-blend-mode"] select' ) ).toHaveValue( init.backgroundBlendMode );
		await expect( l.entryPalette.locator( 'button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', init.palette || '' );
		await expect( l.entryImageStyle.locator( 'button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', init.imageStyle || '' );
		await expect( l.entryCampaign.locator( '[data-selected-value]' ).first() ).toHaveAttribute( 'data-selected-value', init.campaign || '' );
		await expect( l.entryIcon.locator( 'button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', init.icon || '' );
		await expect( l.entryLinks.getByRole( 'textbox', { name: /^Link label$/i } ).first() ).toHaveValue( init.linkLabel );
		await expect( l.entryLinks.getByRole( 'textbox', { name: /^Link URL$/i } ).first() ).toHaveValue( init.linkUrl );
		await expect( shared.panel.getByRole( 'checkbox', { name: /^Newsletter$/i } ) ).toBeChecked( { checked: init.newsletter } );
	} );

	test( 'validation errors prevent save', async () => {
		const page = shared.page;
		const panel = shared.panel;
		const l = gatherLocators( panel );

		// Top-level validation error: slug too short.
		await l.entrySlug.fill( 'x' );
		await expect( panel ).toHaveAttribute( 'data-dirty', 'true' );

		const invalidSaveRequest = page.waitForResponse( ( saveResponse ) => isMetaboxSaveResponse( saveResponse, 'acme-demo-post-metabox' ), { timeout: 20_000 } );

		await panel.getByRole( 'button', { name: /^Save$/ } ).click();

		const invalidSaveResponse = await invalidSaveRequest;

		expect( invalidSaveResponse.status() ).toBe( 422 );
		await expect( panel ).toHaveAttribute( 'data-status', 'error' );
		await expect( panel ).toHaveAttribute( 'data-error-count', '1' );
		await expect( panel.locator( '[data-field-error="entry_slug"]' ) ).toContainText( /between 3 and 32/i );

		// Fix slug, then trigger a nested field validation error.
		await l.entrySlug.fill( 'block-panel-valid' );
		await expect( panel ).toHaveAttribute( 'data-status', 'ready' );
		await expect( panel ).toHaveAttribute( 'data-error-count', '0' );

		await l.entryBadge.getByRole( 'textbox', { name: /^Badge slug$/i } ).fill( 'x' );

		const invalidNestedSaveRequest = page.waitForResponse( ( saveResponse ) => isMetaboxSaveResponse( saveResponse, 'acme-demo-post-metabox' ), { timeout: 20_000 } );

		await panel.getByRole( 'button', { name: /^Save$/ } ).click();

		const invalidNestedSaveResponse = await invalidNestedSaveRequest;

		expect( invalidNestedSaveResponse.status() ).toBe( 422 );
		await expect( panel ).toHaveAttribute( 'data-status', 'error' );
		await expect( panel ).toHaveAttribute( 'data-error-count', '1' );
		await expect( panel.locator( '[data-field-error="entry_badge.slug"]' ) ).toContainText( /between 3 and 32/i );

		// Fix the nested error so the panel is clean for the next test.
		await l.entryBadge.getByRole( 'textbox', { name: /^Badge slug$/i } ).fill( shared.initial.badgeSlug );
		await expect( panel ).toHaveAttribute( 'data-status', 'ready' );
		await expect( panel ).toHaveAttribute( 'data-error-count', '0' );
	} );

	test( 'saves values through REST and persists on reload', async () => {
		const page = shared.page;
		const panel = shared.panel;
		const init = shared.initial;
		const l = gatherLocators( panel );

		const saved = computeSavedValues( init );

		await fillAllFields( page, panel, l, saved, init.newsletter );

		await selectMediaAttachments( page, l.entryUpload.getByRole( 'button', { name: /^Choose uploaded file$/i } ), [ 'Admin Config Media One' ] );
		await selectMediaAttachments( page, l.entryMedia.getByRole( 'button', { name: /^Choose image$/i } ), [ 'Admin Config Media Two' ] );
		await selectMediaAttachments( page, l.entryBackground.locator( '[data-field-path="entry_background.background-image"]' ).getByRole( 'button', { name: /^Choose background image$/i } ), [ 'Admin Config Media Three' ] );
		await selectMediaAttachments( page, l.entryGallery.getByRole( 'button', { name: /^Choose gallery images$/i } ), [
			'Admin Config Media One',
			'Admin Config Media Three',
		] );

		const selectedMedia = await currentBlockPanelMediaValues( page );

		expect( selectedMedia.upload ).toContain( 'admin-config-media-one' );
		expect( selectedMedia.mediaId ).toBeGreaterThan( 0 );
		expect( selectedMedia.backgroundMediaId ).toBeGreaterThan( 0 );
		expect( selectedMedia.galleryIds ).toHaveLength( 2 );
		await expect( panel ).toHaveAttribute( 'data-dirty', 'true' );

		const saveRequest = page.waitForResponse( ( saveResponse ) => isMetaboxSaveResponse( saveResponse, 'acme-demo-post-metabox' ), { timeout: 20_000 } );

		await panel.getByRole( 'button', { name: /^Save$/ } ).click();

		const saveResponse = await saveRequest;

		expect( saveResponse.ok() ).toBe( true );
		await expect( panel ).toHaveAttribute( 'data-dirty', 'false' );

		// Verify saved values are reflected in the UI.
		await expect( l.featuredToggle ).toBeChecked( { checked: ! init.newsletter } );
		await expect( l.entrySlug ).toHaveValue( saved.slug );
		await expect( l.entryLayout ).toHaveValue( saved.layout );
		await expect( l.entryFormat.locator( 'input[type="radio"]:checked' ) ).toHaveValue( saved.format );
		await expect( l.entryEmphasis.locator( 'button[aria-pressed="true"]' ) ).toHaveAttribute( 'data-value', saved.emphasis );
		await expect( l.entryAccent ).toHaveAttribute( 'data-color-value', saved.accent );
		await expect( l.entryUpload.locator( '.lerm-admin-config-block-panel__media-url-preview img' ) ).toHaveAttribute( 'src', /admin-config-media-one/i );
		await expect( l.entryMedia.locator( '.lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 1 );
		await expect( l.entryBackground.locator( '[data-field-path="entry_background.background-image"] .lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 1 );
		await expect( l.entryGallery.locator( '.lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 2 );

		// Reload and verify persistence.
		const reloadSchemaRequest = page.waitForResponse( ( reloadResponse ) => isMetaboxSchemaResponse( reloadResponse ), { timeout: 30_000 } );

		await page.reload( { waitUntil: 'domcontentloaded' } );
		await reloadSchemaRequest;

		const reloadedPanel = await expandBlockPanel( page, 'acme-demo-post-metabox', 'Entry Display Overrides' );

		await expect( reloadedPanel.getByRole( 'checkbox', { name: /Feature this entry/i } ) ).toBeChecked(
			{ checked: ! init.newsletter, timeout: 30_000 }
		);
		await expect( reloadedPanel.getByRole( 'textbox', { name: /Entry slug/i } ) ).toHaveValue( saved.slug );
		await expect( reloadedPanel.getByRole( 'combobox', { name: /Entry layout/i } ) ).toHaveValue( saved.layout );
		await expect( reloadedPanel.locator( '[data-field-id="entry_format"] input[type="radio"]:checked' ) ).toHaveValue( saved.format );
		await expect( reloadedPanel.locator( '[data-field-id="entry_upload"] .lerm-admin-config-block-panel__media-url-preview img' ) ).toHaveAttribute( 'src', /admin-config-media-one/i );
		await expect( reloadedPanel.locator( '[data-field-id="entry_media"] .lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 1 );
		await expect( reloadedPanel.locator( '[data-field-id="entry_background"] [data-field-path="entry_background.background-image"] .lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 1 );
		await expect( reloadedPanel.locator( '[data-field-id="entry_gallery"] .lerm-admin-config-block-panel__media-preview-item' ) ).toHaveCount( 2 );

		await expect
			.poll( () => currentBlockPanelMediaValues( page ), { timeout: 30_000 } )
			.toMatchObject( selectedMedia );

		expect( shared.ajaxRequests ).toEqual( [] );
	} );
} );
