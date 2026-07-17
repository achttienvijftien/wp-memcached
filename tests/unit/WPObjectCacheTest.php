<?php
/**
 * Behavior tests for WP_Object_Cache against the real memcached server.
 *
 * @package WP_Memcached
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests the drop-in cache class directly, using a raw client for server-side assertions.
 */
class WPObjectCacheTest extends TestCase {

	/**
	 * Cache instance under test.
	 *
	 * @var WP_Object_Cache
	 */
	private $cache;

	/**
	 * Raw ext-memcached client for assertions behind the drop-in's back.
	 *
	 * @var Memcached
	 */
	private $raw;

	/**
	 * Flushes the server and creates fresh instances before each test.
	 */
	protected function setUp(): void {
		$this->raw = new Memcached();
		$this->raw->addServer( '127.0.0.1', 11211 );
		$this->raw->flush();

		$this->cache = new WP_Object_Cache();
	}

	/**
	 * Computes the expected blog-prefixed key.
	 *
	 * @param string $id    Cache key.
	 * @param string $group Cache group.
	 *
	 * @return string
	 */
	private function expected_key( $id, $group ) {
		return WP_CACHE_KEY_SALT . $GLOBALS['table_prefix'] . ":$group:$id";
	}

	public function test_key_applies_salt_and_blog_prefix() {
		$this->assertSame( $this->expected_key( 'key', 'group' ), $this->cache->key( 'key', 'group' ) );
	}

	public function test_key_defaults_to_default_group() {
		$this->assertSame( $this->expected_key( 'key', 'default' ), $this->cache->key( 'key', '' ) );
	}

	public function test_key_strips_whitespace() {
		$this->assertSame( $this->expected_key( 'akey', 'group' ), $this->cache->key( 'a key', 'group' ) );
	}

	public function test_key_uses_global_prefix_for_global_groups() {
		$this->cache->add_global_groups( [ 'testglobal' ] );

		$this->assertSame(
			WP_CACHE_KEY_SALT . $GLOBALS['table_prefix'] . 'testglobal:key',
			$this->cache->key( 'key', 'testglobal' )
		);
	}

	public function test_set_and_get_roundtrip_persists_to_server() {
		$this->cache->set( 'id', [ 'a' => 1 ], 'grp' );

		$this->assertSame( [ 'a' => 1 ], $this->raw->get( $this->cache->key( 'id', 'grp' ) ), 'Value must reach the server.' );

		$fresh = new WP_Object_Cache();
		$this->assertSame( [ 'a' => 1 ], $fresh->get( 'id', 'grp', false, $found ) );
		$this->assertTrue( $found );
	}

	public function test_get_miss_returns_false_and_found_false() {
		$this->assertFalse( $this->cache->get( 'nope', 'grp', false, $found ) );
		$this->assertFalse( $found );
	}

	public function test_stored_false_reports_found() {
		$this->cache->set( 'lit', false, 'grp' );

		$fresh = new WP_Object_Cache();
		$this->assertFalse( $fresh->get( 'lit', 'grp', false, $found ) );
		$this->assertTrue( $found, 'A stored false is found, not a miss.' );
	}

	public function test_runtime_cache_serves_without_server_round_trip() {
		$this->cache->set( 'rt', 'value', 'grp' );
		$this->raw->delete( $this->cache->key( 'rt', 'grp' ) );

		$this->assertSame( 'value', $this->cache->get( 'rt', 'grp' ), 'Runtime cache must serve after a server-side delete.' );
		$this->assertFalse( $this->cache->get( 'rt', 'grp', true, $found ), 'Force must bypass the runtime cache.' );
		$this->assertFalse( $found );
	}

	public function test_non_persistent_group_never_touches_server() {
		$this->cache->add_non_persistent_groups( [ 'np' ] );

		$this->cache->set( 'id', 'local', 'np' );
		$this->assertFalse( $this->raw->get( $this->cache->key( 'id', 'np' ) ), 'Non-persistent values must not reach the server.' );
		$this->assertSame( 'local', $this->cache->get( 'id', 'np' ) );

		$this->assertTrue( $this->cache->delete( 'id', 'np' ) );
		$this->assertFalse( $this->cache->get( 'id', 'np' ) );
	}

	public function test_add_does_not_overwrite_existing_value() {
		$this->assertNotFalse( $this->cache->add( 'k', 'first', 'grp' ) );
		$this->assertFalse( $this->cache->add( 'k', 'second', 'grp' ) );
		$this->assertSame( 'first', $this->cache->get( 'k', 'grp' ) );
	}

	public function test_add_is_blocked_by_existing_server_value() {
		$this->raw->set( $this->cache->key( 'k', 'grp' ), 'server' );

		$this->assertFalse( $this->cache->add( 'k', 'other', 'grp' ) );
	}

	public function test_delete_removes_from_server_and_runtime() {
		$this->cache->set( 'gone', 'v', 'grp' );
		$this->assertTrue( $this->cache->delete( 'gone', 'grp' ) );

		$this->assertFalse( $this->raw->get( $this->cache->key( 'gone', 'grp' ) ) );
		$this->assertFalse( $this->cache->get( 'gone', 'grp', false, $found ) );
		$this->assertFalse( $found );
	}

	public function test_incr_and_decr() {
		$this->cache->set( 'count', 5, 'grp' );

		$this->assertSame( 7, $this->cache->incr( 'count', 2, 'grp' ) );
		$this->assertSame( 6, $this->cache->decr( 'count', 1, 'grp' ) );
	}

	public function test_replace_only_replaces_existing_keys() {
		$this->assertFalse( $this->cache->replace( 'missing', 'v', 'grp' ) );

		$this->cache->set( 'present', 'old', 'grp' );
		$this->assertNotFalse( $this->cache->replace( 'present', 'new', 'grp' ) );

		$fresh = new WP_Object_Cache();
		$this->assertSame( 'new', $fresh->get( 'present', 'grp' ) );
	}

	public function test_expiration_is_respected_by_the_server() {
		$this->cache->set( 'shortlived', 'v', 'grp', 1 );

		sleep( 2 );

		$this->assertFalse( $this->cache->get( 'shortlived', 'grp', true, $found ) );
		$this->assertFalse( $found );
	}

	public function test_zero_expiration_stores_without_expiry() {
		$this->cache->set( 'forever', 'v', 'grp', 0 );

		sleep( 2 );

		$this->assertSame( 'v', $this->cache->get( 'forever', 'grp', true ) );
	}

	public function test_sentinel_value_is_not_cached_and_blocks_set() {
		$key = $this->cache->key( 'sent', 'grp' );
		$this->raw->set( $key, 'checkthedatabaseplease' );

		$this->assertFalse( $this->cache->get( 'sent', 'grp' ) );

		// The sentinel must not poison the runtime cache: after a server-side delete the next read is a true miss.
		$this->raw->delete( $key );
		$this->assertFalse( $this->cache->get( 'sent', 'grp', false, $found ) );
		$this->assertFalse( $found, 'Second read must consult the server again.' );

		// A sentinel sitting in the runtime cache blocks set().
		$this->cache->cache[ $key ] = 'checkthedatabaseplease';
		$this->assertFalse( $this->cache->set( 'sent', 'new', 'grp' ) );
	}

	public function test_set_multi_stores_triplets_with_group_fallback() {
		$this->cache->add_non_persistent_groups( [ 'np' ] );

		$this->cache->set_multi(
			[
				[ 'a', 'value-a', 'grp' ],
				[ 'b', 'value-b' ],
				[ 'c', 'value-c', 'np' ],
			]
		);

		$this->assertSame( 'value-a', $this->raw->get( $this->cache->key( 'a', 'grp' ) ) );
		$this->assertSame( 'value-b', $this->raw->get( $this->cache->key( 'b', 'default' ) ) );
		$this->assertFalse( $this->raw->get( $this->cache->key( 'c', 'np' ) ), 'Non-persistent items must stay local.' );
		$this->assertSame( 'value-c', $this->cache->get( 'c', 'np' ) );
	}

	public function test_flush_clears_the_server() {
		$this->cache->set( 'wipe', 'v', 'grp' );

		$this->cache->flush();

		$this->assertFalse( $this->raw->get( $this->cache->key( 'wipe', 'grp' ) ) );
	}

	public function test_global_group_roundtrip() {
		$this->cache->add_global_groups( [ 'site-options-test' ] );
		$this->cache->set( 'k', 'v', 'site-options-test' );

		$fresh = new WP_Object_Cache();
		$fresh->add_global_groups( [ 'site-options-test' ] );

		$this->assertSame( 'v', $fresh->get( 'k', 'site-options-test' ) );
	}
}
