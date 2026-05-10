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
			'selectMedia'         => __( 'Choose image', 'lerm' ),
			'useMedia'            => __( 'Use this image', 'lerm' ),
			'selectFile'          => __( 'Choose file', 'lerm' ),
			'useFile'             => __( 'Use this file', 'lerm' ),
			'selectImages'        => __( 'Choose images', 'lerm' ),
			'useImages'           => __( 'Use these images', 'lerm' ),
			'removeMedia'         => __( 'Remove image', 'lerm' ),
			'clearGallery'        => __( 'Clear gallery', 'lerm' ),
			'noMedia'             => __( 'No image selected.', 'lerm' ),
			'noGallery'           => __( 'No gallery images selected.', 'lerm' ),
			'searchPrompt'        => __( 'Start typing to search.', 'lerm' ),
			'searchMinPrompt'     => __( 'Type more characters to search.', 'lerm' ),
			'loadingResults'      => __( 'Loading results...', 'lerm' ),
			'noResults'           => __( 'No matching results found.', 'lerm' ),
			'loadMoreResults'     => __( 'Load more', 'lerm' ),
			'clearSelection'      => __( 'Clear selection', 'lerm' ),
			'removeSelection'     => __( 'Remove selection', 'lerm' ),
			'saving'              => __( 'Saving...', 'lerm' ),
			'saveSuccess'         => __( 'Settings saved.', 'lerm' ),
			'saveError'           => __( 'Unable to save the settings right now.', 'lerm' ),
			'resetting'           => __( 'Resetting...', 'lerm' ),
			'resetSectionSuccess' => __( 'The current page has been reset to defaults.', 'lerm' ),
			'resetAllSuccess'     => __( 'All sections have been reset to defaults.', 'lerm' ),
			'resetError'          => __( 'Unable to reset the settings right now.', 'lerm' ),
			'confirmResetSection' => __( 'Reset the current page back to its default values?', 'lerm' ),
			'confirmResetAll'     => __( 'Reset every section on this page back to default values?', 'lerm' ),
			'confirmNavigate'     => __( 'You have unsaved changes in this tab. Leave without saving?', 'lerm' ),
			'confirmLeave'        => __( 'You have unsaved changes that have not been saved yet.', 'lerm' ),
			'statusReady'         => __( 'Synced', 'lerm' ),
			'statusDirty'         => __( 'Unsaved changes', 'lerm' ),
			'statusSaving'        => __( 'Saving...', 'lerm' ),
			'statusResetting'     => __( 'Resetting...', 'lerm' ),
			'statusSaved'         => __( 'Saved', 'lerm' ),
			'statusError'         => __( 'Error', 'lerm' ),
			'groupAdd'            => __( 'Add item', 'lerm' ),
			'groupRemove'         => __( 'Remove', 'lerm' ),
			'groupEmpty'          => __( 'No items added yet.', 'lerm' ),
			'confirmRemoveItem'   => __( 'Remove this item?', 'lerm' ),
			'exportSuccess'       => __( 'Current settings snapshot generated.', 'lerm' ),
			'importSuccess'       => __( 'Settings imported successfully.', 'lerm' ),
			'importError'         => __( 'Unable to import the provided settings JSON.', 'lerm' ),
			'confirmImport'       => __( 'Importing will overwrite the current saved settings. Continue?', 'lerm' ),
			'debugCopy'           => __( 'Copy JSON', 'lerm' ),
			'debugCopied'         => __( 'Copied', 'lerm' ),
		);
	}
}
