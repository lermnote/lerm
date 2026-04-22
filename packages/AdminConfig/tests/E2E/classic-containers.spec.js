const { test, expect } = require( '@playwright/test' );
const {
	login,
	openCategoryEditor,
	openCommentEditor,
	openPostEditor,
	submitClassicForm,
} = require( './helpers/wp-admin' );

test( 'metabox container replays nested validation errors and persists valid values', async ( { page } ) => {
	await login( page );
	await openPostEditor( page, 'Admin Config Smoke Post', 'post' );

	const badgeSlug = page.locator( 'input[name="_acme_demo_entry_settings[entry_badge][slug]"]' );

	await badgeSlug.fill( 'x' );
	await submitClassicForm( page, '#publish' );

	await expect( page.locator( '.notice.notice-error.inline', { hasText: /highlighted metabox fields/i } ).first() ).toBeVisible();
	await expect( page.locator( '.lerm-settings-row.is-invalid input[name="_acme_demo_entry_settings[entry_badge][slug]"]' ) ).toHaveValue( 'x' );

	await page.locator( 'input[name="_acme_demo_entry_settings[entry_badge][label]"]' ).fill( 'Launch entry' );
	await badgeSlug.fill( 'launch-entry' );
	await submitClassicForm( page, '#publish' );

	await expect( page.locator( '#message' ) ).toContainText( /updated/i );
	await expect( page.locator( 'input[name="_acme_demo_entry_settings[entry_badge][label]"]' ) ).toHaveValue( 'Launch entry' );
	await expect( badgeSlug ).toHaveValue( 'launch-entry' );
} );

test( 'profile container replays nested validation errors and persists valid values', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/profile.php' );

	const badgeSlug = page.locator( 'input[name="acme_demo_profile_settings[profile_badge][slug]"]' );

	await badgeSlug.fill( 'x' );
	await submitClassicForm( page );

	await expect( page.locator( '.notice.notice-error.inline', { hasText: /highlighted profile fields/i } ).first() ).toBeVisible();
	await expect( page.locator( '.lerm-settings-row.is-invalid input[name="acme_demo_profile_settings[profile_badge][slug]"]' ) ).toHaveValue( 'x' );

	await page.locator( 'select[name="acme_demo_profile_settings[profile_tone]"]' ).selectOption( 'bold' );
	await badgeSlug.fill( 'profile-badge' );
	await submitClassicForm( page );

	await expect( page.locator( '#message.updated, #message.notice-success' ).first() ).toBeVisible();
	await expect( page.locator( 'select[name="acme_demo_profile_settings[profile_tone]"]' ) ).toHaveValue( 'bold' );
	await expect( badgeSlug ).toHaveValue( 'profile-badge' );
} );

test( 'taxonomy container replays nested validation errors and persists valid values', async ( { page } ) => {
	await login( page );
	await openCategoryEditor( page, 'Admin Config Smoke' );

	const badgeSlug = page.locator( 'input[name="acme_demo_category_settings[category_badge][slug]"]' );

	await badgeSlug.fill( 'x' );
	await submitClassicForm( page );

	await expect( page.locator( '.notice.notice-error.inline', { hasText: /highlighted term fields/i } ).first() ).toBeVisible();
	await expect( page.locator( '.lerm-settings-row.is-invalid input[name="acme_demo_category_settings[category_badge][slug]"]' ) ).toHaveValue( 'x' );

	await page.locator( 'input[name="acme_demo_category_settings[category_badge][label]"]' ).fill( 'Editorial' );
	await badgeSlug.fill( 'editorial-category' );
	await submitClassicForm( page );

	await expect( page.locator( '#message.updated, #message.notice-success' ).first() ).toBeVisible();
	await expect( page.locator( 'input[name="acme_demo_category_settings[category_badge][label]"]' ) ).toHaveValue( 'Editorial' );
	await expect( badgeSlug ).toHaveValue( 'editorial-category' );
} );

test( 'comment container replays nested validation errors and persists valid values', async ( { page } ) => {
	await login( page );
	await openCommentEditor( page, 'Admin Config Smoke Comment' );

	const badgeSlug = page.locator( 'input[name="acme_demo_comment_settings[review_badge][slug]"]' );

	await badgeSlug.fill( 'x' );
	await submitClassicForm( page );

	await expect( page.locator( '.notice.notice-error.inline', { hasText: /highlighted comment fields/i } ).first() ).toBeVisible();
	await expect( page.locator( '.lerm-settings-row.is-invalid input[name="acme_demo_comment_settings[review_badge][slug]"]' ) ).toHaveValue( 'x' );

	await page.locator( 'textarea[name="acme_demo_comment_settings[staff_note]"]' ).fill( 'Escalate for editorial review.' );
	await badgeSlug.fill( 'staff-review' );
	await submitClassicForm( page );

	await expect( page.locator( '#message.updated, #message.notice-success' ).first() ).toBeVisible();
	await expect( page.locator( 'textarea[name="acme_demo_comment_settings[staff_note]"]' ) ).toHaveValue( 'Escalate for editorial review.' );
	await expect( badgeSlug ).toHaveValue( 'staff-review' );
} );
