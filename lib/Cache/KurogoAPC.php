<?php

if (!function_exists('apc_fetch')) {
    die('KurogoAPC requires the APC Extension');
}

class KurogoAPC extends KurogoCache {
	private $apc;
	private $compressed;
	private $ttl;

	protected function init($args) {
		if(isset($args['TTL'])) {
			$this->setTTL($args['TTL']);
		}else {
			$this->setTTL(0);
		}
	}

	public function setTTL($ttl) {
		$this->ttl = (int) $ttl;
	}

	public function get($key) {
		return apc_fetch($key);
	}

	public function set($key, $value, $ttl = false) {
		if($ttl === false) {
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
