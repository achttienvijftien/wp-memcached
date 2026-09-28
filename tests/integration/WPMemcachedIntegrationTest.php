<?php
/**
 * Integration tests: the drop-in running as the live object cache inside a real WordPress.
 *
 * @package WP_Memcached
 */

/**
 * Exercises the drop-in through WordPress core APIs.
 */
class WPMemcachedIntegrationTest extends WP_UnitTestCase {

	public function test_dropin_is_the_active_object_cache() {
		$this->assertTrue( wp_using_ext_object_cache(), 'WordPress must be using the external object cache.' );

		$rc = new ReflectionClass( WP_Object_Cache::class );
		$this->assertStringEndsWith(
			'wp-content/object-cache.php',
			wp_normalize_path( $rc->getFileName() ),
			'The active WP_Object_Cache must come from the drop-in.'
		);
	}

	public function test_option_reads_are_served_from_cache() {
		add_option( 'wpmc_it_option', 'v1', '', 'no' );
		get_option( 'wpmc_it_option' );

		$queries_before = $GLOBALS['wpdb']->num_queries;
		$this->assertSame( 'v1', get_option( 'wpmc_it_option' ) );
		$this->assertSame( $queries_before, $GLOBALS['wpdb']->num_queries, 'A repeated option read must not query the database.' );
	}

	public function test_transients_bypass_the_database() {
		set_transient( 'wpmc_it_transient', 'tv', 300 );

		$this->assertSame( 'tv', get_transient( 'wpmc_it_transient' ) );

		$wpdb = $GLOBALS['wpdb'];
		$row  = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s", '_transient_wpmc_it_transient' )
		);
		$this->assertNull( $row, 'With an external object cache, transients must not be written to wp_options.' );
	}

	public function test_post_meta_reads_are_served_from_cache() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'wpmc_it_meta', 'mv' );
		get_post_meta( $post_id, 'wpmc_it_meta', true );

		$queries_before = $GLOBALS['wpdb']->num_queries;
		$this->assertSame( 'mv', get_post_meta( $post_id, 'wpmc_it_meta', true ) );
		$this->assertSame( $queries_before, $GLOBALS['wpdb']->num_queries, 'A repeated meta read must not query the database.' );
	}

	public function test_cached_values_survive_into_a_fresh_cache_instance() {
		wp_cache_set( 'wpmc_persist', 'pv', 'wpmc_grp' );

		$fresh = new WP_Object_Cache();
		$this->assertSame( 'pv', $fresh->get( 'wpmc_persist', 'wpmc_grp' ), 'Values must persist server-side across instances, as across requests.' );
	}

	public function test_user_cache_group_is_global() {
		$blog_key   = $GLOBALS['wp_object_cache']->key( 'x', 'some-blog-group' );
		$global_key = $GLOBALS['wp_object_cache']->key( 'x', 'users' );

		$this->assertNotSame( $blog_key, $global_key );
		$this->assertStringNotContainsString( ':users:', $global_key, 'Core-registered global groups must use the global prefix.' );
	}
}
