<?php
namespace Moca\Cache\Logger;

/**
 * Query chain logger
 * @author nikis
 *
 */
class CacheLoggerChain implements CacheLogger {
	
	/**
	 * @var array
	 */
	protected $_aLoggers = array();

	/**
	 * @var array
	 */
	protected $_aLog = array();
	
	/**
	 * Add query logger
	 * @param QueryLogger $logger
	 * @return QueryLoggerChain
	 */
	public function add(CacheLogger $logger) {
		$this->_aLoggers[] = $logger;
		return $this;
	}
	
	/**
	 * Get all executed queries
	 * @return array
	 */
	public function getLog() {
		return $this->_aLog;
	}
	
	public function start($query, $params=null) {
		$this->_aLog[] = $query;
		foreach($this->_aLoggers as $logger) {
			$logger->start($query, $params);
		}	
	}
	
	public function stop() {
		foreach($this->_aLoggers as $logger) {
			$logger->stop();
		}
	}
}