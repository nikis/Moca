<?php
namespace Moca\Database\Logger;

/**
 * Query chain logger
 * @author nikis
 *
 */
class QueryLoggerChain implements QueryLogger {
	
	/**
	 * @var array
	 */
	protected $_aLoggers = array();

	/**
	 * @var array
	 */
	protected $_aQueries = array();
	
	/**
	 * Add query logger
	 * @param QueryLogger $logger
	 * @return QueryLoggerChain
	 */
	public function add(QueryLogger $logger) {
		$this->_aLoggers[] = $logger;
		return $this;
	}
	
	/**
	 * Get all executed queries
	 * @return array
	 */
	public function getQueries() {
		return $this->_aQueries;
	}
	
	public function start($query, $params=null) {
		$this->_aQueries[] = $query;
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