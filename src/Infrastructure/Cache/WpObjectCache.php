<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Cache;

use MintLMS\Application\Contract\CacheInterface;

final class WpObjectCache implements CacheInterface {

	private const GROUP = 'mintlms';

	public function get( string $key ): mixed {
		$value = wp_cache_get( $key, self::GROUP );

		return false === $value ? null : $value;
	}

	public function set( string $key, mixed $value, ?int $ttl = null ): bool {
		return wp_cache_set( $key, $value, self::GROUP, $ttl ?? 0 );
	}

	public function delete( string $key ): bool {
		return wp_cache_delete( $key, self::GROUP );
	}
}
