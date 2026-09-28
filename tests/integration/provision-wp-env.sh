#!/usr/bin/env bash
# Installs ext-memcached and a local memcached daemon into the wp-env tests containers.
set -euo pipefail

install_path=$(./node_modules/.bin/wp-env install-path)
project=$(basename "$install_path")

for svc in tests-cli tests-wordpress; do
	cid=$(docker ps -q --filter "name=${project}-${svc}")
	if [ -z "$cid" ]; then
		echo "provision: no running container for ${svc}, skipping"
		continue
	fi

	echo "provision: ${svc} (${cid})"
	docker exec -u root "$cid" sh -c '
		set -e
		if php -m | grep -qi "^memcached$" && pgrep memcached > /dev/null 2>&1; then
			echo "already provisioned"
			exit 0
		fi

		if command -v apk > /dev/null 2>&1; then
			apk add --no-cache memcached libmemcached-libs zlib zstd-libs > /dev/null
			if ! php -m | grep -qi "^memcached$"; then
				apk add --no-cache --virtual .wpmc-build $PHPIZE_DEPS libmemcached-dev zlib-dev zstd-dev > /dev/null
				yes "" | pecl install memcached > /dev/null
				docker-php-ext-enable memcached
				apk del .wpmc-build > /dev/null
			fi
			pgrep memcached > /dev/null 2>&1 || memcached -d -u memcached
		else
			export DEBIAN_FRONTEND=noninteractive
			apt-get update -q > /dev/null
			apt-get install -yq memcached libmemcached-dev zlib1g-dev libzstd-dev > /dev/null
			if ! php -m | grep -qi "^memcached$"; then
				yes "" | pecl install memcached > /dev/null
				docker-php-ext-enable memcached
			fi
			service memcached start 2> /dev/null || pgrep memcached > /dev/null 2>&1 || memcached -d -u memcache
		fi
		php -m | grep -qi "^memcached$" && echo "ext-memcached active"
	'
done

echo "provision: done"
