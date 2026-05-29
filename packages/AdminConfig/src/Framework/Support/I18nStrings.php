<?php
/**
 * Localized strings used by AdminConfig JavaScript runtimes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class I18nStrings {

	/**
	 * @param mixed $code_editor_settings Code editor settings returned by wp_enqueue_code_editor().
	 * @return array<string, mixed>
	 */
	public static function for_admin_page( $code_editor_settings ): array {
		return array(
			'restUrl'             => rest_url( 'lerm-admin-config/v1/' ),
			'restNonce'           => wp_create_nonce( 'wp_rest' ),
			'codeEditor'          => $code_editor_settings,
			'selectMedia'         => __( 'Choose image', 'lerm-admin-config' ),
			'useMedia'            => __( 'Use this image', 'lerm-admin-config' ),
			'selectFile'          => __( 'Choose file', 'lerm-admin-config' ),
			'useFile'             => __( 'Use this file', 'lerm-admin-config' ),
			'selectImages'        => __( 'Choose images', 'lerm-admin-config' ),
			'useImages'           => __( 'Use these images', 'lerm-admin-config' ),
			'removeMedia'         => __( 'Remove image', 'lerm-admin-config' ),
			'clearGallery'        => __( 'Clear gallery', 'lerm-admin-config' ),
			'noMedia'             => __( 'No image selected.', 'lerm-admin-config' ),
			'noGallery'           => __( 'No gallery images selected.', 'lerm-admin-config' ),
			'searchPrompt'        => __( 'Start typing to search.', 'lerm-admin-config' ),
			'searchMinPrompt'     => __( 'Type more characters to search.', 'lerm-admin-config' ),
			'loadingResults'      => __( 'Loading results...', 'lerm-admin-config' ),
			'noResults'           => __( 'No matching results found.', 'lerm-admin-config' ),
			'loadMoreResults'     => __( 'Load more', 'lerm-admin-config' ),
			'clearSelection'      => __( 'Clear selection', 'lerm-admin-config' ),
			'removeSelection'     => __( 'Remove selection', 'lerm-admin-config' ),
			'saving'              => __( 'Saving...', 'lerm-admin-config' ),
			'saveSuccess'         => __( 'Settings saved.', 'lerm-admin-config' ),
			'saveError'           => __( 'Unable to save the settings right now.', 'lerm-admin-config' ),
			'resetting'           => __( 'Resetting...', 'lerm-admin-config' ),
			'resetSectionSuccess' => __( 'The current page has been reset to defaults.', 'lerm-admin-config' ),
			'resetAllSuccess'     => __( 'All sections have been reset to defaults.', 'lerm-admin-config' ),
			'resetError'          => __( 'Unable to reset the settings right now.', 'lerm-admin-config' ),
			'confirmResetSection' => __( 'Reset the current page back to its default values?', 'lerm-admin-config' ),
			'confirmResetAll'     => __( 'Reset every section on this page back to default values?', 'lerm-admin-config' ),
			'confirmNavigate'     => __( 'You have unsaved changes in this tab. Leave without saving?', 'lerm-admin-config' ),
			'confirmLeave'        => __( 'You have unsaved changes that have not been saved yet.', 'lerm-admin-config' ),
			'statusReady'         => __( 'Synced', 'lerm-admin-config' ),
			'statusDirty'         => __( 'Unsaved changes', 'lerm-admin-config' ),
			'statusSaving'        => __( 'Saving...', 'lerm-admin-config' ),
			'statusResetting'     => __( 'Resetting...', 'lerm-admin-config' ),
			'statusSaved'         => __( 'Saved', 'lerm-admin-config' ),
			'statusError'         => __( 'Error', 'lerm-admin-config' ),
			'groupAdd'            => __( 'Add item', 'lerm-admin-config' ),
			'groupRemove'         => __( 'Remove', 'lerm-admin-config' ),
			'groupEmpty'          => __( 'No items added yet.', 'lerm-admin-config' ),
			'confirmRemoveItem'   => __( 'Remove this item?', 'lerm-admin-config' ),
			'exportSuccess'       => __( 'Current settings snapshot generated.', 'lerm-admin-config' ),
			'importSuccess'       => __( 'Settings imported successfully.', 'lerm-admin-config' ),
			'importError'         => __( 'Unable to import the provided settings JSON.', 'lerm-admin-config' ),
			'confirmImport'       => __( 'Importing will overwrite the current saved settings. Continue?', 'lerm-admin-config' ),
			'debugCopy'           => __( 'Copy JSON', 'lerm-admin-config' ),
			'debugCopied'         => __( 'Copied', 'lerm-admin-config' ),
		);
	}
}
