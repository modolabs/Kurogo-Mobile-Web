<?php

if (!function_exists('apc_fetch')) {
    throw new KurogoException('APCCache requires the APC Extension');
}

class APCCache extends KurogoCache {
    protected $description = 'APC';
	public function get($key) {
		return apc_fetch($key);
	}

	public function set($key, $value, $ttl = null) {
		if(is_null($ttl)) {
			$ttl = $this->ttl;
		}
		return apc_store($key, $value, $ttl);
	}

	public function delete($key) {
		return apc_delete($key);
	}

	public function add($key, $value, $ttl = false) {
		if($ttl === false) {
			$ttl = $this->ttl;
		}
		return apc_add($key, $value, $ttl);
	}

	public function clear() {
		return apc_clear_cache();
	}
}
