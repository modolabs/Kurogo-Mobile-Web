<?php

class KurogoNoCache extends KurogoCache {

	protected function init($args) {
		return false;
	}

	public function get($key) {
	    return null;
	}

	public function set($key, $value, $ttl = false) {
	    return false;
	}

	public function delete($key) {
	    return false;
	}

	public function clear() {
	    return false;
	}
}
