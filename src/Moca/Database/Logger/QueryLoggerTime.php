<?php
namespace Moca\Database\Logger;

/**
 * Query logger that detect query execution time
 * @author nikis
 *
 */
class QueryLoggerTime implements QueryLogger {
	
	/**
	 * @var integer
	 */
	protected $_iCurrent = 0;
	
	/**
	 * @var array
	 */
	protected $_aQueries = array();
	
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
	public function getQueries() {
		return $this->_aQueries;
	}
	
	public function start($query, $params=null) {
		$this->_iCurrent++;
		$this->_aQueries[$this->_iCurrent] = array(
			'query' => $query,
			'params' => $params,
			'start' => microtime(true)
		);
	}
	
	public function stop() {
		$this->_aQueries[$this->_iCurrent]['time'] = round(microtime(true) - $this->_aQueries[$this->_iCurrent]['start'], 5);
		unset($this->_aQueries[$this->_iCurrent]['start']);
		
		if($this->_fMinTime !== null) {
			if($this->_aQueries[$this->_iCurrent]['time'] < $this->_fMinTime) {
				unset($this->_aQueries[$this->_iCurrent]);
			}
		}
	}
}