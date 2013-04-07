<?php
namespace Moca\Database;

use PDOStatement;
use Moca\Database\Logger\QueryLogger;

/**
 * Statement class
 * @author nikis
 *
 */
class Statement extends PDOStatement {
	
	/**
	 * @var QueryLogger
	 */
	protected $_logger = null;
	
	/**
	 * @var array
	 */
	protected $_aParams = array();
	
	protected function __construct(QueryLogger $logger=null) {
		$this->_logger = $logger;
	}
	
	public function execute ($bound_input_params=null) {
		if(null !== $this->_logger) {
			$this->_logger->start($this->queryString, $bound_input_params !== null ? $bound_input_params : $this->_aParams);
		}
		$result = parent::execute($bound_input_params);
		if(null !== $this->_logger) {
			$this->_logger->stop();
		}
		return $result;
	}

	public function bindParam ($paramno, &$param, $type=null, $maxlen=null, $driverdata=null) {
		$this->_aParams[$paramno] = array($param, $type);
		return parent::bindParam($paramno, $param, $type, $maxlen, $driverdata);
	}

	public function bindColumn ($column, &$param, $type=null, $maxlen=null, $driverdata=null) {
		$this->_aParams[$column] = array($param, $type);
		return parent::bindColumn($column, $param, $type, $maxlen, $driverdata);
	}

	public function bindValue ($paramno, $param, $type=null) {
		$this->_aParams[$paramno] = array($param, $type);
		return parent::bindValue($paramno, $param, $type);
	}
}