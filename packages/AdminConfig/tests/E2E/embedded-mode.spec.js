const { test, expect } = require( '@playwright/test' );
const {
	login,
	openSettingsSection,
	saveOptionsPage,
	selectAjaxOption,
} = require( './helpers/wp-admin' );

test( 'embedded mode saves nested theme settings', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/themes.php?page=acme-theme-style-kit' );

	await openSettingsSection( page, /Brand/i );

	await expect( page.locator( '[data-lerm-save]:visible' ).first() ).toBeVisible();
	const surfaceTitle = page.locator( 'input[name="acme_theme_style_kit[surface_tabs][default][title]"]' );
	await surfaceTitle.fill( 'Editorial surface updated' );

	await saveOptionsPage( page );

	await page.reload();
	await expect( page.locator( 'input[name="acme_theme_style_kit[surface_tabs][default][title]"]' ) ).toHaveValue( 'Editorial surface updated' );
} );

test( 'embedded mode saves typography, ajax select, accordion, and tabbed fields together', async ( { page } ) => {
	await login( page );
	await page.goto( '/wp-admin/themes.php?page=acme-theme-style-kit' );

	await openSettingsSection( page, /Brand/i );

	await expect( page.locator( '[data-lerm-save]:visible' ).first() ).toBeVisible();
	await page.locator( 'input[name="acme_theme_style_kit[headline_typography][font-size]"]' ).fill( '3.4' );
	await page.locator( 'input[name="acme_theme_style_kit[headline_typography][color]"]' ).fill( '#112233' );
	await page.locator( '[data-lerm-tabbed-target="featured"]' ).first().click();
	await page.locator( 'input[name="acme_theme_style_kit[surface_tabs][featured][title]"]' ).fill( 'Promoted narrative card' );

	await openSettingsSection( page, /Hero Content/i );
	await selectAjaxOption( page, 'featured_story_pack', 'week', 'Editorial Weekender' );
	await page.locator( 'button[data-lerm-accordion-trigger]', { hasText: /Actions/i } ).first().click();
	await page.locator( 'input[name="acme_theme_style_kit[hero_accordion][actions][primary_label]"]' ).fill( 'Read the feature' );

	await saveOptionsPage( page );
	await page.reload();

	await expect( page.locator( 'input[name="acme_theme_style_kit[headline_typography][font-size]"]' ) ).toHaveValue( '3.4' );
	await expect( page.locator( 'input[name="acme_theme_style_kit[headline_typography][color]"]' ) ).toHaveValue( '#112233' );
	await page.locator( '[data-lerm-tabbed-target="featured"]' ).first().click();
	await expect( page.locator( 'input[name="acme_theme_style_kit[surface_tabs][featured][title]"]' ) ).toHaveValue( 'Promoted narrative card' );

	await openSettingsSection( page, /Hero Content/i );
	await expect( page.locator( 'input[name="acme_theme_style_kit[featured_story_pack]"]' ) ).toHaveValue( 'editorial-weekender' );
	await page.locator( 'button[data-lerm-accordion-trigger]', { hasText: /Actions/i } ).first().click();
	await expect( page.locator( 'input[name="acme_theme_style_kit[hero_accordion][actions][primary_label]"]' ) ).toHaveValue( 'Read the feature' );
} );
