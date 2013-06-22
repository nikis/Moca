<?php
namespace Moca\Cache\Logger;

class CacheLoggerTime implements CacheLogger {
	
/**
	 * @var integer
	 */
	protected $_iCurrent = 0;
	
	/**
	 * @var array
	 */
	protected $_aLog = array();
	
	/**
	 * @var float
	 */
	protected $_fMinTime = null;
	
	/**
	 * Set min time for logging
	 * @param $time
	 */
	public function setMinTime($fTime) {
		$this->_fMinTime = $fTime;
		return $this;
	}
	
	/**
	 * Get all queries
	 * @return array
	 */
	public function getLog() {
		return $this->_aLog;
	}
	
	public function start($method, $key) {
		$this->_iCurrent++;
		$this->_aLog[$this->_iCurrent] = array(
			'method' => $method,
			'key' => $key,
			'start' => microtime(true)
		);
	}
	
	public function stop() {
		$this->_aLog[$this->_iCurrent]['time'] = round(microtime(true) - $this->_aLog[$this->_iCurrent]['start'], 5);
		unset($this->_aLog[$this->_iCurrent]['start']);
		
		if($this->_fMinTime !== null) {
			if($this->_aLog[$this->_iCurrent]['time'] < $this->_fMinTime) {
				unset($this->_aLog[$this->_iCurrent]);
			}
		}
	}
}