<?php
/**
 * Tests for the global wp_cache_* API functions.
 *
 * @package WP_Memcached
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests that the wp_cache_* functions delegate to the global cache instance.
 */
class CacheFunctionsTest extends TestCase {

	/**
	 * Remembers the WordPress-booted cache instance so it can be restored.
	 *
	 * @var WP_Object_Cache
	 */
	private $previous_instance;

	/**
	 * Swaps in a fresh global cache instance against a flushed server.
	 */
	protected function setUp(): void {
		$raw = new Memcached();
		$raw->addServer( '127.0.0.1', 11211 );
		$raw->flush();

		$this->previous_instance = $GLOBALS['wp_object_cache'];
		wp_cache_init();
	}

	/**
	 * Restores the WordPress-booted cache instance.
	 */
	protected function tearDown(): void {
		$GLOBALS['wp_object_cache'] = $this->previous_instance;
	}

	public function test_wp_cache_init_creates_global_instance() {
		$this->assertInstanceOf( WP_Object_Cache::class, $GLOBALS['wp_object_cache'] );
		$this->assertNotSame( $this->previous_instance, $GLOBALS['wp_object_cache'] );
	}

	public function test_set_get_delete_roundtrip() {
		$this->assertNotFalse( wp_cache_set( 'fn', 'value', 'grp' ) );
		$this->assertSame( 'value', wp_cache_get( 'fn', 'grp', false, $found ) );
		$this->assertTrue( $found );

		$this->assertTrue( wp_cache_delete( 'fn', 'grp' ) );
		$this->assertFalse( wp_cache_get( 'fn', 'grp', false, $found ) );
		$this->assertFalse( $found );
	}

	public function test_add_respects_existing_keys() {
		$this->assertNotFalse( wp_cache_add( 'once', 'a', 'grp' ) );
		$this->assertFalse( wp_cache_add( 'once', 'b', 'grp' ) );
		$this->assertSame( 'a', wp_cache_get( 'once', 'grp' ) );
	}

	public function test_incr_and_decr() {
		wp_cache_set( 'n', 10, 'grp' );

		$this->assertSame( 12, wp_cache_incr( 'n', 2, 'grp' ) );
		$this->assertSame( 11, wp_cache_decr( 'n', 1, 'grp' ) );
	}

	public function test_global_and_non_persistent_group_registration() {
		wp_cache_add_global_groups( 'gg' );
		wp_cache_add_non_persistent_groups( 'np' );

		$this->assertSame(
			WP_CACHE_KEY_SALT . $GLOBALS['table_prefix'] . 'gg:key',
			$GLOBALS['wp_object_cache']->key( 'key', 'gg' )
		);

		wp_cache_set( 'k', 'v', 'np' );
		$fresh = new WP_Object_Cache();
		$this->assertFalse( $fresh->get( 'k', 'np' ), 'Non-persistent values must not be readable server-side.' );
	}

	public function test_wp_cache_flush() {
		wp_cache_set( 'gone', 'v', 'grp' );

		$this->assertTrue( (bool) wp_cache_flush() );
		$this->assertFalse( wp_cache_get( 'gone', 'grp', true ) );
	}
}
