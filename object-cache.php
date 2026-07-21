<?php
/**
 * Plugin Name: WP Memcached
 * Plugin URI: https://github.com/achttienvijftien/wp-memcached
 * Description: The real Memcached (not Memcache) backend for the WP Object Cache.
 * Version: 1.1.0
 * Requires PHP: 8.0
 * Tested up to: 6.7.1
 * Author: 1815
 * Author URI: https://www.1815.nl
 *
 * @package WP_Memcached
 */

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- WordPress drop-ins must define the API functions and the backing class in one file.

if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
	define( 'WP_CACHE_KEY_SALT', '' );
}

if ( class_exists( 'Memcached' ) ) {

	/**
	 * Adds a value to the cache if its key is not already set.
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $data   Value to store.
	 * @param string $group  Cache group.
	 * @param int    $expire Expiration in seconds, 0 for the default.
	 *
	 * @return bool
	 */
	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;

		return $wp_object_cache->add( $key, $data, $group, $expire );
	}

	/**
	 * Increments a numeric cache value.
	 *
	 * @param string $key   Cache key.
	 * @param int    $n     Amount to increment by.
	 * @param string $group Cache group.
	 *
	 * @return int|false
	 */
	function wp_cache_incr( $key, $n = 1, $group = '' ) {
		global $wp_object_cache;

		return $wp_object_cache->incr( $key, $n, $group );
	}

	/**
	 * Decrements a numeric cache value.
	 *
	 * @param string $key   Cache key.
	 * @param int    $n     Amount to decrement by.
	 * @param string $group Cache group.
	 *
	 * @return int|false
	 */
	function wp_cache_decr( $key, $n = 1, $group = '' ) {
		global $wp_object_cache;

		return $wp_object_cache->decr( $key, $n, $group );
	}

	/**
	 * Closes the cache connections.
	 *
	 * @return void
	 */
	function wp_cache_close() {
		global $wp_object_cache;

		$wp_object_cache->close();
	}

	/**
	 * Deletes a key from the cache.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 *
	 * @return bool
	 */
	function wp_cache_delete( $key, $group = '' ) {
		global $wp_object_cache;

		return $wp_object_cache->delete( $key, $group );
	}

	/**
	 * Flushes all cache buckets.
	 *
	 * @return bool
	 */
	function wp_cache_flush() {
		global $wp_object_cache;

		return $wp_object_cache->flush();
	}

	/**
	 * Retrieves a value from the cache.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @param bool   $force Whether to bypass the runtime cache.
	 * @param bool   $found Set to whether the key was found, passed by reference.
	 *
	 * @return mixed Cached value, false on miss.
	 */
	function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
		global $wp_object_cache;

		return $wp_object_cache->get( $key, $group, $force, $found );
	}

	/**
	 * Retrieves multiple values using key and group pairs, false for misses.
	 *
	 * Example: array( array( 'key', 'group' ), array( 'key' ) ).
	 *
	 * @param array<int, array<int, string>|string> $key_and_groups Array of key and group pairs to fetch.
	 * @param string                                $bucket         Server bucket to read from.
	 *
	 * @return mixed[] Values, runtime-cache hits first, then fetched keys in the given order.
	 */
	function wp_cache_get_multi( $key_and_groups, $bucket = 'default' ) {
		global $wp_object_cache;

		return $wp_object_cache->get_multi( $key_and_groups, $bucket );
	}

	/**
	 * Stores multiple key, data and group triplets.
	 *
	 * Example: array( array( 'key', 'data', 'group' ), array( 'key', 'data' ) ).
	 *
	 * @param array  $items  Array of key, data and group triplets to store.
	 * @param int    $expire Expiration in seconds, 0 for the default.
	 * @param string $group  Fallback cache group.
	 *
	 * @return void
	 */
	function wp_cache_set_multi( $items, $expire = 0, $group = 'default' ) {
		global $wp_object_cache;

		$wp_object_cache->set_multi( $items, $expire = 0, $group = 'default' );
	}

	/**
	 * Initializes the global object cache instance.
	 *
	 * @return void
	 */
	function wp_cache_init() {
		global $wp_object_cache;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The drop-in owns the $wp_object_cache global.
		$wp_object_cache = new WP_Object_Cache();
	}

	/**
	 * Replaces a value in the cache if its key already exists.
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $data   Value to store.
	 * @param string $group  Cache group.
	 * @param int    $expire Expiration in seconds, 0 for the default.
	 *
	 * @return bool
	 */
	function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;

		return $wp_object_cache->replace( $key, $data, $group, $expire );
	}

	/**
	 * Stores a value in the cache, deleting it instead while WordPress is installing.
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $data   Value to store.
	 * @param string $group  Cache group.
	 * @param int    $expire Expiration in seconds, 0 for the default.
	 *
	 * @return bool
	 */
	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;

		if ( ! defined( 'WP_INSTALLING' ) ) {
			return $wp_object_cache->set( $key, $data, $group, $expire );
		}

		return $wp_object_cache->delete( $key, $group );
	}

	/**
	 * Registers groups that share their cache across sites.
	 *
	 * @param string|array $groups Group name or list of group names.
	 *
	 * @return void
	 */
	function wp_cache_add_global_groups( $groups ) {
		global $wp_object_cache;

		$wp_object_cache->add_global_groups( $groups );
	}

	/**
	 * Registers groups that must not be persisted to memcached.
	 *
	 * @param string|array $groups Group name or list of group names.
	 *
	 * @return void
	 */
	function wp_cache_add_non_persistent_groups( $groups ) {
		global $wp_object_cache;

		$wp_object_cache->add_non_persistent_groups( $groups );
	}

	/**
	 * Memcached-backed implementation of the WordPress object cache.
	 */
	class WP_Object_Cache {
		/**
		 * Groups that share their cache across sites.
		 *
		 * @var array
		 */
		public array $global_groups = [];

		/**
		 * Groups that are never persisted to memcached.
		 *
		 * @var array
		 */
		private array $no_mc_groups = [];

		/**
		 * In-process runtime cache keyed by full cache key.
		 *
		 * @var array
		 */
		public array $cache = [];

		/**
		 * Memcached connections keyed by server bucket.
		 *
		 * @var array
		 */
		private array $mc = [];

		/**
		 * Operation counters.
		 *
		 * @var array
		 */
		public array $stats = [];

		/**
		 * Log of performed operations per group.
		 *
		 * @var array
		 */
		public array $group_ops = [];

		/**
		 * Expiration in seconds applied when none is given.
		 *
		 * @var int
		 */
		private int $default_expiration = 0;

		/**
		 * Whether the per-operation debug log is recorded, controlled by WP_MEMCACHED_DEBUG or WP_DEBUG.
		 *
		 * @var bool
		 */
		public bool $debug = false;

		/**
		 * Collected debug information.
		 *
		 * @var array
		 */
		public array $memcache_debug = [];

		/**
		 * Prefix to use for cache keys when group from global groups is used.
		 *
		 * @var string
		 */
		private string $global_prefix;

		/**
		 * Extra prefix for cache keys if multisite.
		 *
		 * @var string
		 */
		private string $blog_prefix;

		/**
		 * Hit counter.
		 *
		 * @var int
		 */
		private $cache_hits;

		/**
		 * Miss counter.
		 *
		 * @var int
		 */
		private $cache_misses;

		/**
		 * Adds a value to the cache if its key is not already set.
		 *
		 * @param string $id     Cache key.
		 * @param mixed  $data   Value to store.
		 * @param string $group  Cache group.
		 * @param int    $expire Expiration in seconds, 0 for the default.
		 *
		 * @return bool
		 */
		public function add( $id, $data, $group = 'default', $expire = 0 ): bool {
			$key = $this->key( $id, $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			if ( in_array( $group, $this->no_mc_groups, true ) ) {
				$this->cache[ $key ] = $data;

				return true;
			} elseif ( isset( $this->cache[ $key ] ) && false !== $this->cache[ $key ] ) {
				return false;
			}

			$mc     =& $this->get_mc( $group );
			$expire = ( 0 === (int) $expire ) ? $this->default_expiration : (int) $expire;
			$result = $mc->add( $key, $data, $expire );

			if ( false !== $result ) {
				++$this->stats['add'];
				$this->cache[ $key ] = $data;

				if ( $this->debug ) {
					$this->group_ops[ $group ][] = "add $id";
				}
			}

			return $result;
		}

		/**
		 * Registers groups that share their cache across sites.
		 *
		 * @param string|array $groups Group name or list of group names.
		 *
		 * @return void
		 */
		public function add_global_groups( $groups ): void {
			if ( ! is_array( $groups ) ) {
				$groups = (array) $groups;
			}

			$this->global_groups = array_merge( $this->global_groups, $groups );
			$this->global_groups = array_unique( $this->global_groups );
		}

		/**
		 * Registers groups that must not be persisted to memcached.
		 *
		 * @param string|array $groups Group name or list of group names.
		 *
		 * @return void
		 */
		public function add_non_persistent_groups( $groups ): void {
			if ( ! is_array( $groups ) ) {
				$groups = (array) $groups;
			}

			$this->no_mc_groups = array_merge( $this->no_mc_groups, $groups );
			$this->no_mc_groups = array_unique( $this->no_mc_groups );
		}

		/**
		 * Increments a numeric cache value.
		 *
		 * @param string $id    Cache key.
		 * @param int    $n     Amount to increment by.
		 * @param string $group Cache group.
		 *
		 * @return int|false
		 */
		public function incr( $id, $n = 1, $group = 'default' ): int|false {
			$key                 = $this->key( $id, $group );
			$mc                  =& $this->get_mc( $group );
			$this->cache[ $key ] = $mc->increment( $key, $n );

			return $this->cache[ $key ];
		}

		/**
		 * Decrements a numeric cache value.
		 *
		 * @param string $id    Cache key.
		 * @param int    $n     Amount to decrement by.
		 * @param string $group Cache group.
		 *
		 * @return int|false
		 */
		public function decr( $id, $n = 1, $group = 'default' ): int|false {
			$key                 = $this->key( $id, $group );
			$mc                  =& $this->get_mc( $group );
			$this->cache[ $key ] = $mc->decrement( $key, $n );

			return $this->cache[ $key ];
		}

		/**
		 * Closes the cache connections.
		 *
		 * @return void
		 */
		public function close(): void {
			// Silence is Golden.
		}

		/**
		 * Deletes a key from the cache.
		 *
		 * @param string $id    Cache key.
		 * @param string $group Cache group.
		 *
		 * @return bool
		 */
		public function delete( $id, $group = 'default' ): bool {
			$key = $this->key( $id, $group );

			if ( in_array( $group, $this->no_mc_groups, true ) ) {
				unset( $this->cache[ $key ] );

				return true;
			}

			$mc =& $this->get_mc( $group );

			$result = $mc->delete( $key );

			if ( false !== $result ) {
				++$this->stats['delete'];
				unset( $this->cache[ $key ] );

				if ( $this->debug ) {
					$this->group_ops[ $group ][] = "delete $id";
				}
			}

			return $result;
		}

		/**
		 * Flushes all cache buckets unless running multi-blog.
		 *
		 * @return bool
		 */
		public function flush(): bool {
			$has_custom_user_tables = defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' );

			// Don't flush if multi-blog.
			if ( function_exists( 'is_site_admin' ) || $has_custom_user_tables ) {
				return true;
			}

			$ret = true;
			foreach ( array_keys( $this->mc ) as $group ) {
				$ret = $this->mc[ $group ]->flush() && $ret;
			}

			return $ret;
		}

		/**
		 * Retrieves a value, serving from the runtime cache before querying memcached.
		 *
		 * @param string $id    Cache key.
		 * @param string $group Cache group.
		 * @param bool   $force Whether to bypass the runtime cache.
		 * @param bool   $found Set to whether the key was found, passed by reference.
		 *
		 * @return mixed Cached value, false on miss.
		 */
		public function get( $id, $group = 'default', $force = false, &$found = null ): mixed {
			$key   = $this->key( $id, $group );
			$mc    =& $this->get_mc( $group );
			$found = false;

			if ( isset( $this->cache[ $key ] ) && ( ! $force || in_array( $group, $this->no_mc_groups, true ) ) ) {
				$found = true;
				if ( is_object( $this->cache[ $key ] ) ) {
					$value = clone $this->cache[ $key ];
				} else {
					$value = $this->cache[ $key ];
				}
			} elseif ( in_array( $group, $this->no_mc_groups, true ) ) {
				$value               = false;
				$this->cache[ $key ] = $value;
			} else {
				$value = $mc->get( $key );
				if ( empty( $value ) || ( is_int( $value ) && -1 === $value ) ) {
					$value = false;
					$found = Memcached::RES_NOTFOUND !== $mc->getResultCode();
				} else {
					$found = true;
				}
				$this->cache[ $key ] = $value;
			}

			if ( $found ) {
				++$this->stats['get'];

				if ( $this->debug ) {
					$this->group_ops[ $group ][] = "get $id";
				}
			} else {
				++$this->stats['miss'];
			}

			if ( 'checkthedatabaseplease' === $value ) {
				unset( $this->cache[ $key ] );
				$value = false;
			}

			return $value;
		}

		/**
		 * Retrieves multiple values using key and group pairs, false for misses.
		 *
		 * @param array<int, array<int, string>|string> $keys  Array of key and group pairs to fetch.
		 * @param string                                $group Server bucket group.
		 *
		 * @return mixed[] Values, runtime-cache hits first, then fetched keys in the given order.
		 */
		public function get_multi( $keys, $group = 'default' ): array {
			$return = [];
			$gets   = [];

			foreach ( $keys as $values ) {
				$values = (array) $values;
				if ( empty( $values[1] ) ) {
					$values[1] = 'default';
				}

				[ $id, $item_group ] = $values;
				$key                 = $this->key( $id, $item_group );

				if ( isset( $this->cache[ $key ] ) ) {
					if ( is_object( $this->cache[ $key ] ) ) {
						$return[ $key ] = clone $this->cache[ $key ];
					} else {
						$return[ $key ] = $this->cache[ $key ];
					}
				} elseif ( in_array( $item_group, $this->no_mc_groups, true ) ) {
					$return[ $key ] = false;
				} else {
					$gets[ $key ] = $key;
				}
			}

			if ( ! empty( $gets ) ) {
				$mc      =& $this->get_mc( $group );
				$results = $mc->getMulti( array_values( $gets ) );

				if ( ! is_array( $results ) ) {
					$results = [];
				}

				foreach ( $gets as $key ) {
					$return[ $key ] = array_key_exists( $key, $results ) ? $results[ $key ] : false;
				}
			}

			++$this->stats['get_multi'];
			$this->cache = array_merge( $this->cache, $return );

			if ( $this->debug ) {
				$this->group_ops[ $group ][] = 'get_multi ' . implode( ' ', array_keys( $gets ) );
			}

			return array_values( $return );
		}

		/**
		 * Builds the full memcached key for a key and group pair.
		 *
		 * @param string $key   Cache key.
		 * @param string $group Cache group.
		 *
		 * @return string
		 */
		public function key( $key, $group ): string {
			if ( empty( $group ) ) {
				$group = 'default';
			}

			if ( in_array( $group, $this->global_groups, true ) ) {
				$prefix = $this->global_prefix;
			} else {
				$prefix = $this->blog_prefix;
			}

			return preg_replace( '/\s+/', '', WP_CACHE_KEY_SALT . "$prefix$group:$key" );
		}

		/**
		 * Replaces a value in the cache if its key already exists.
		 *
		 * @param string $id     Cache key.
		 * @param mixed  $data   Value to store.
		 * @param string $group  Cache group.
		 * @param int    $expire Expiration in seconds, 0 for the default.
		 *
		 * @return bool
		 */
		public function replace( $id, $data, $group = 'default', $expire = 0 ): bool {
			$key    = $this->key( $id, $group );
			$expire = ( 0 === (int) $expire ) ? $this->default_expiration : (int) $expire;
			$mc     =& $this->get_mc( $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$result = $mc->replace( $key, $data, $expire );
			if ( false !== $result ) {
				$this->cache[ $key ] = $data;
			}

			return $result;
		}

		/**
		 * Stores a value in the runtime cache and memcached.
		 *
		 * @param string $id     Cache key.
		 * @param mixed  $data   Value to store.
		 * @param string $group  Cache group.
		 * @param int    $expire Expiration in seconds, 0 for the default.
		 *
		 * @return bool
		 */
		public function set( $id, $data, $group = 'default', $expire = 0 ): bool {
			$key = $this->key( $id, $group );
			if ( isset( $this->cache[ $key ] ) && ( 'checkthedatabaseplease' === $this->cache[ $key ] ) ) {
				return false;
			}

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$this->cache[ $key ] = $data;

			if ( in_array( $group, $this->no_mc_groups, true ) ) {
				return true;
			}

			$expire = ( 0 === (int) $expire ) ? $this->default_expiration : (int) $expire;
			$mc     =& $this->get_mc( $group );
			$result = $mc->set( $key, $data, $expire );

			return $result;
		}

		/**
		 * Stores multiple key, data and group triplets through one setMulti call.
		 *
		 * @param array  $items  Array of key, data and group triplets to store.
		 * @param int    $expire Expiration in seconds, 0 for the default.
		 * @param string $group  Fallback cache group.
		 *
		 * @return void
		 */
		public function set_multi( $items, $expire = 0, $group = 'default' ): void {
			$sets   = [];
			$mc     =& $this->get_mc( $group );
			$expire = ( 0 === (int) $expire ) ? $this->default_expiration : (int) $expire;

			foreach ( $items as $i => $item ) {
				if ( empty( $item[2] ) ) {
					$item[2] = 'default';
				}

				[ $id, $data, $group ] = $item;

				$key = $this->key( $id, $group );
				if ( isset( $this->cache[ $key ] ) && ( 'checkthedatabaseplease' === $this->cache[ $key ] ) ) {
					continue;
				}

				if ( is_object( $data ) ) {
					$data = clone $data;
				}

				$this->cache[ $key ] = $data;

				if ( in_array( $group, $this->no_mc_groups, true ) ) {
					continue;
				}

				$sets[ $key ] = $data;
			}

			if ( ! empty( $sets ) ) {
				$mc->setMulti( $sets, $expire );
			}
		}

		/**
		 * Wraps a logged operation line in a colored span for debug output.
		 *
		 * @param string $line Logged operation line.
		 *
		 * @return string
		 */
		public function colorize_debug_line( $line ): string {
			$colors = [
				'get'    => 'green',
				'set'    => 'purple',
				'add'    => 'blue',
				'delete' => 'red',
			];

			$cmd = substr( $line, 0, strpos( $line, ' ' ) );

			$cmd2 = "<span style='color:" . esc_attr( $colors[ $cmd ] ) . "'>" . esc_html( $cmd ) . '</span>';

			return $cmd2 . esc_html( substr( $line, strlen( $cmd ) ) ) . "\n";
		}

		/**
		 * Prints operation counters and the per-group operation log.
		 *
		 * @return void
		 */
		public function stats(): void {
			echo "<p>\n";
			foreach ( $this->stats as $stat => $n ) {
				echo '<strong>' . esc_html( $stat ) . '</strong> ' . esc_html( $n );
				echo "<br/>\n";
			}
			echo "</p>\n";
			echo '<h3>Memcached:</h3>';
			foreach ( $this->group_ops as $group => $ops ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only debug output toggle.
				if ( ! isset( $_GET['debug_queries'] ) && 500 < count( $ops ) ) {
					$ops = array_slice( $ops, 0, 500 );
					echo "<big>Too many to show! <a href='" . esc_url( add_query_arg( 'debug_queries', 'true' ) ) . "'>"
						. "Show them anyway</a>.</big>\n";
				}
				echo '<h4>' . esc_html( $group ) . ' commands</h4>';
				echo "<pre>\n";
				$lines = [];
				foreach ( $ops as $op ) {
					$lines[] = $this->colorize_debug_line( $op );
				}
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug output method by design.
				print_r( $lines );
				echo "</pre>\n";
			}

			if ( ! empty( $this->debug ) && $this->debug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump -- Debug output method by design.
				var_dump( $this->memcache_debug );
			}
		}

		/**
		 * Returns the memcached connection for a group, falling back to the default bucket.
		 *
		 * @param string $group Cache group.
		 *
		 * @return Memcached
		 */
		public function &get_mc( $group ): Memcached {
			if ( isset( $this->mc[ $group ] ) ) {
				return $this->mc[ $group ];
			}

			return $this->mc['default'];
		}

		/**
		 * Connects the configured server buckets and initializes key prefixes.
		 */
		public function __construct() {
			$this->debug = defined( 'WP_MEMCACHED_DEBUG' )
				? (bool) WP_MEMCACHED_DEBUG
				: ( defined( 'WP_DEBUG' ) && WP_DEBUG );

			$this->stats = [
				'get'       => 0,
				'get_multi' => 0,
				'add'       => 0,
				'set'       => 0,
				'delete'    => 0,
				'miss'      => 0,
			];

			global $memcached_servers;

			if ( isset( $memcached_servers ) ) {
				$buckets = $memcached_servers;
			} else {
				$buckets = [ '127.0.0.1:11211' ];
			}

			reset( $buckets );
			if ( is_int( key( $buckets ) ) ) {
				$buckets = [ 'default' => $buckets ];
			}

			foreach ( $buckets as $bucket => $servers ) {
				$this->mc[ $bucket ] = new Memcached();

				$instances = [];
				foreach ( $servers as $server ) {
					$parts = explode( ':', $server );
					$node  = $parts[0];
					$port  = $parts[1] ?? '';
					if ( empty( $port ) ) {
						$port = ini_get( 'memcache.default_port' );
					}
					$port = intval( $port );
					if ( ! $port ) {
						$port = 11211;
					}

					$instances[] = [ $node, $port, 1 ];
				}
				$this->mc[ $bucket ]->addServers( $instances );
			}

			global $blog_id, $table_prefix;
			$this->global_prefix = '';
			$this->blog_prefix   = '';
			if ( function_exists( 'is_multisite' ) ) {
				$has_custom_user_tables = defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' );
				$this->global_prefix    = ( is_multisite() || $has_custom_user_tables ) ? '' : $table_prefix;
				$this->blog_prefix      = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
			}

			$this->cache_hits   =& $this->stats['get'];
			$this->cache_misses =& $this->stats['miss'];
		}
	}
} elseif ( function_exists( 'wp_using_ext_object_cache' ) ) {
	wp_using_ext_object_cache( false );

} else {
	// In earlier versions, there isn't a clean bail-out method.
	wp_die( 'Memcached class not available.' );
}
