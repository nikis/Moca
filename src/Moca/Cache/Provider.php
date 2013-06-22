<?php
namespace Moca\Cache;

/**
 * Cache provider
 * @author nikis
 *
 */
abstract class Provider {
	
	/**
	 * @var string
	 */
	protected $namespace = null;
	
	/**
	 * @var boolean
	 */
	protected $enableBuffering = false;
	
	/**
	 * @var ArrayCache
	 */
	protected $buffer = null;
	
	/**
	 * @var Logger\CacheLogger
	 */
	protected $logger;
	
	/**
	 * Set namespace
	 * @param $ns
	 */
	public function setNamespace($ns) {
		$this->namespace = $ns;
	}
	
	/**
	 * Enable cache buffering
	 * @param boolean $bool
	 */
	public function enableBuffering($bool) {
		$this->enableBuffering = $bool;
		return $bool;		
	}

	/**
	 * Set cache logger
	 * @param Logger\CacheLogger $logger
	 */
	public function setLogger(Logger\CacheLogger $logger) {
		$this->logger = $logger;
	}
	
	/**
	 * Get cache logger
	 * @return Logger\CacheLogger|null
	 */
	public function getLogger() {
		return $this->logger;
	}
	
	/**
	 * Get data from cache
	 * @param $key
	 */
	public function get($key) {
		$key = $this->generateKey($key);
		if($this->enableBuffering) {
			$data = $this->getBuffer()->get($key);
			if($data !== false) {
				return $data;
			}
		}
		if($this->logger) {
			$this->logger->start(__FUNCTION__, $key);
		}
		$data = $this->doGet($key);
		if($this->logger) {
			$this->logger->stop();
		}
		if($this->enableBuffering) {
			$this->getBuffer()->set($key, $data);
		}
		return $data;
	}
	
	/**
	 * Set data in cache
	 * @param $key
	 * @param $val
	 * @param $lifetime
	 */
	public function set($key, $val, $lifetime=0) {
		$key = $this->generateKey($key);
		if($this->enableBuffering) {
			$this->getBuffer()->set($key, $val);
		}
		if($this->logger) {
			$this->logger->start(__FUNCTION__, $key);
		}
		$data = $this->doSet($key, $val, $lifetime);
		if($this->logger) {
			$this->logger->stop();
		}
		return $data;
	}
	
	/**
	 * Delete key from cache
	 * @param $key
	 */
	public function delete($key) {
		$key = $this->generateKey($key);
		if($this->enableBuffering) {
			$this->getBuffer()->delete($key);
		}
		if($this->logger) {
			$this->logger->start(__FUNCTION__, $key);
		}
		$data = $this->doDelete($key);
		if($this->logger) {
			$this->logger->stop();
		}
		return $data;
	}
	
	/**
	 * Check if key exist
	 * @param $key
	 * @return boolean
	 */
	public function has($key) {
		return $this->doHas($this->generateKey($key));
	}
	
	/**
	 * Modify data if not exist
	 * @param $key
	 * @param $cb
	 * @param $lifetime
	 */
	public function modify($key, $cb, $lifetime=0) {
		$data = $this->get($key);
		if(false === $data) {
			$data = $cb();
			if(false !== $data) {
				$this->set($key, $data, $lifetime);
			}
		}
		return $data;
	}
	
	/**
	 * @param string $key
	 */
	abstract protected function doGet($key);
	
	/**
	 * @param string $key
	 * @param mixed $val
	 * @param integer $lifetime
	 */
	abstract protected function doSet($key, $val, $lifetime=0);
	
	/**
	 * @param $key
	 */
	abstract protected function doDelete($key);
	
	/**
	 * @param $key
	 */
	abstract protected function doHas($key);
	
	/**
	 * Generate cache key with namespace
	 * @param $key
	 * @return string
	 */
	protected function generateKey($key) {
		return ($this->namespace ? $this->namespace.'.' : '').$key;
	}
	
	/**
	 * Get buffer
	 * @return ArrayCache
	 */
	protected function getBuffer() {
		if(null === $this->buffer) {
			$this->buffer = new ArrayCache();
		}
		return $this->buffer;
	}
}