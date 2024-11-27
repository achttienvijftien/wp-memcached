# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Add `^2.0` to `composer/installers` constraint

## [1.0.0] - 2024-05-16

### Fixed

- Format code according to PHPCS results (still a lot of errors and warnings remaining)
- Fix PHP 8.2 dynamic properties deprecation warnings

### Added

- Add Composer file
- Add code styling tools

### Changed

- Rebrand plugin

## 0.1.7

- Improved escaping in debug output ported from [Memcached plugin](https://wordpress.org/plugins/memcached/) (props
  @batmoo).
- Fixed PHP notice when no Memcached server:port manuqally specified.

## 0.1.6

- Corrected documentation
- Corrected stats collection (props @johnbillion)

## 0.1.5

- Added support for PHP 7+ by changing to `__construct` and pre-initializing stats

## 0.1.3

- Added support for WP_CACHE_KEY_SALT allowing multiple sites to use the same Memcached server.

## 0.1.2

- Allows graceful fallback to database object cache in WordPress 3.7+ for users without PECL Memcached available.
- Fixes warning due to replace() call, as it does not take a compression argument in Memcached.

## 0.1.1

- Fixes a problem with the get_option() function and the return value of wp_cache_get() on Linux

## 0.1

- Initial release

[unreleased]: https://github.com/achttienvijftien/wp-memcached/compare/1.0.0...main

[1.0.0]: https://github.com/achttienvijftien/wp-memcached/compare/0.1.7...1.0.0
