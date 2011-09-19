<?php
class KurogoMemcache extends KurogoCache {
	private $mem;
	private $compress = true;

	public function __construct() {
		$config = Kurogo::getSiteSection("memcache");
		$this->mem = new Memcache;
		if(count($config['HOST']) == 1) {
			$this->mem->connect($config['HOST'][0], $config['PORT'][0], $config['TIMEOUT'][0]);
		}else {
			$num = count($config['HOST']);
			for($i = 0;$i < $num;$i ++) {
				$persistent = true;
				if(isset($config['PERSISTENT'][$i])) {
					$persistent = $config['PERSISTENT'][$i];
				}
				$weight = 10;
				if(isset($config['WEIGHT'][$i])) {
					$weight = $config['WEIGHT'][$i];
				}
				$timeout = 1;
				if(isset($config['TIMEOUT'][$i])) {
					$timeout = $config['TIMEOUT'][$i];
				}
				$this->mem->addServer($config['HOST'][$i], $config['PORT'][$i], $persistent, $weight, $timeout);
			}
		}
	}

	public function isCompress($compress) {
		$this->compress = (boolean) $compress;
	}

	public function get($key) {
		return $this->mem->get($key);
	}

	public function set($key, $value, $expire = 0) {
		if($this->compress) {
			if($expire > 0) {
				return $this->mem->set($key, $value, MEMCACHE_COMPRESSED, $expire);
			}else {
				return $this->mem->set($key, $value, MEMCACHE_COMPRESSED);
			}
		}else {
			if($expire > 0) {
				return $this->mem->set($key, $value, false, $expire);
			}else {
				return $this->mem->set($key, $value);
			}
		}
	}

	public function delete($key) {
		return $this->men->delete($key);
	}

	public function add($key, $value, $expire = 0) {
		if($this->compress) {
			if($expire > 0) {
				return $this->mem->add($key, $value, MEMCACHE_COMPRESSED, $expire);
			}else {
				return $this->mem->add($key, $value, MEMCACHE_COMPRESSED);
			}
		}else {
			if($expire > 0) {
				return $this->mem->add($key, $value, false, $expire);
			}else {
				return $this->mem->add($key, $value);
			}
		}
	}
}
