<?php
abstract class KurogoCache {

	public function __construct() {
	}

	abstract public function get($key);

	abstract public function set($key, $value, $ttl = 0);

	abstract public function delete($key);
}
