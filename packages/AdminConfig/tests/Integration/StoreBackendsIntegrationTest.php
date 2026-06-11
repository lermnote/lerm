<?php
/**
 * Integration coverage for WordPress-backed stores.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Integration;

use Lerm\AdminConfig\WordPress\Runtime;

final class StoreBackendsIntegrationTest extends WpIntegrationTestCase {

	public function testOptionAndSiteOptionStoresPersistThroughWordPress(): void {
		delete_option( 'lerm_integration_option' );
		delete_site_option( 'lerm_integration_site_option' );

		$runtime = $this->runtime();
		$runtime->register( self::make_store_schema( 'integration-option', 'option', 'lerm_integration_option' ) );
		$runtime->register( self::make_store_schema( 'integration-site-option', 'site_option', 'lerm_integration_site_option' ) );

		$option_store = $runtime->store( 'integration-option' );
		$site_store   = $runtime->store( 'integration-site-option' );

		self::assertTrue( $option_store->import_all( array( 'note' => 'Site option test' ) ) );
		self::assertTrue( $site_store->import_all( array( 'note' => 'Network option test' ) ) );
		self::assertSame( 'Site option test', (string) get_option( 'lerm_integration_option', array() )['note'] );
		self::assertSame( 'Network option test', (string) get_site_option( 'lerm_integration_site_option', array() )['note'] );
	}

	public function testMetaStoresPersistThroughWordPress(): void {
		$suffix = (string) wp_rand( 1000, 999999 );

		$post_id    = wp_insert_post(
			array(
				'post_title'  => 'Admin Config Integration Post ' . $suffix,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);
		$term       = wp_insert_term( 'Admin Config Integration ' . $suffix, 'category' );
		$user_id    = wp_create_user(
			'admin-config-integration-' . $suffix,
			'password',
			'admin-config-integration-' . $suffix . '@example.com'
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => 'Integration Bot',
				'comment_author_email' => 'integration@example.com',
				'comment_content'      => 'Integration comment',
				'comment_approved'     => 1,
				'user_id'              => $user_id,
			)
		);

		self::assertIsInt( $post_id );
		self::assertIsArray( $term );
		self::assertIsInt( $user_id );
		self::assertIsInt( $comment_id );

		$term_id = (int) $term['term_id'];

		delete_post_meta( $post_id, 'lerm_integration_post_meta' );
		delete_term_meta( $term_id, 'lerm_integration_term_meta' );
		delete_user_meta( $user_id, 'lerm_integration_user_meta' );
		delete_comment_meta( $comment_id, 'lerm_integration_comment_meta' );

		$runtime = $this->runtime();
		$runtime->register( self::make_store_schema( 'integration-post-meta', 'post_meta', 'lerm_integration_post_meta' ) );
		$runtime->register( self::make_store_schema( 'integration-term-meta', 'term_meta', 'lerm_integration_term_meta' ) );
		$runtime->register( self::make_store_schema( 'integration-user-meta', 'user_meta', 'lerm_integration_user_meta' ) );
		$runtime->register( self::make_store_schema( 'integration-comment-meta', 'comment_meta', 'lerm_integration_comment_meta' ) );

		self::assertTrue( $runtime->store( 'integration-post-meta', array( 'post_id' => $post_id ) )->import_all( array( 'note' => 'Post meta test' ) ) );
		self::assertTrue( $runtime->store( 'integration-term-meta', array( 'term_id' => $term_id ) )->import_all( array( 'note' => 'Term meta test' ) ) );
		self::assertTrue( $runtime->store( 'integration-user-meta', array( 'user_id' => $user_id ) )->import_all( array( 'note' => 'User meta test' ) ) );
		self::assertTrue( $runtime->store( 'integration-comment-meta', array( 'comment_id' => $comment_id ) )->import_all( array( 'note' => 'Comment meta test' ) ) );

		self::assertSame( 'Post meta test', (string) get_post_meta( $post_id, 'lerm_integration_post_meta', true )['note'] );
		self::assertSame( 'Term meta test', (string) get_term_meta( $term_id, 'lerm_integration_term_meta', true )['note'] );
		self::assertSame( 'User meta test', (string) get_user_meta( $user_id, 'lerm_integration_user_meta', true )['note'] );
		self::assertSame( 'Comment meta test', (string) get_comment_meta( $comment_id, 'lerm_integration_comment_meta', true )['note'] );

		wp_delete_comment( $comment_id, true );
		wp_delete_user( $user_id );
		wp_delete_term( $term_id, 'category' );
		wp_delete_post( $post_id, true );
	}
}
