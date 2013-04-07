<?php
namespace Moca\Database;

use PDO;
use Exception;
use Moca\Database\Statement,
	Moca\Database\Logger\QueryLogger;

/**
 * Database connection
 * @author nikis
 *
 */
class Provider extends PDO {
	
	const DEFAULT_INSTANCE_NAME = '__default__';
	
	const STATEMENT_CLASS = 'Moca\Database\Statement';
	
	/**
	 * @var array
	 */
	protected static $_aInstances = array();
	
	protected $_statementClass = self::STATEMENT_CLASS;
	
	public function __construct($dsn, $username, $passwd, $options=null, $instanceName=null) {
		if(null === $instanceName) {
			$instanceName = self::DEFAULT_INSTANCE_NAME;
		}
		
		if(isset(self::$_aInstances[$instanceName])) {
			if($instanceName === self::DEFAULT_INSTANCE_NAME) {
				throw new Exception('Default Provider instance is already defined. Use getInstance()');
			} else {
				throw new Exception(sprintf('Provider instance with name "%s" is already defined. Use getInstance("%s")', $instanceName, $instanceName));
			}
		}
		
		if(null !== $options) {
			if(isset($options[self::ATTR_STATEMENT_CLASS])) {
				$this->_statementClass = $options[self::ATTR_STATEMENT_CLASS];
				unset($options[self::ATTR_STATEMENT_CLASS]);
			}
		}
		
		parent::__construct($dsn, $username, $passwd, $options);
		
		$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
		$this->setAttribute(self::ATTR_DEFAULT_FETCH_MODE, self::FETCH_OBJ);
		$this->setAttribute(self::ATTR_STATEMENT_CLASS, array(
			$this->_statementClass, array()
		));
		
		self::$_aInstances[$instanceName] = $this;
	}
	
	/**
	 * Create new instance or get already defined instance
	 * @param $name
	 * @param array $options
	 * @example
	 * 		Provider::getInstance("read", array(
	 * 				'mysql:dbname=escort;host=127.0.0.1', 
	 * 				'user',
	 * 				'pass', 
	 * 				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"')
	 * 		));
	 * 		Provider::getInstance("read")->first("users", array("id" => 1));
	 */
	public static function getInstance($name=null, array $options=null) {
		if(is_array($name)) {
			$options = $name;
			$name = null;
		}
		if(null === $name) {
			$name = self::DEFAULT_INSTANCE_NAME;	
		}
		if(null === $options) {
			if(!isset(self::$_aInstances[$name])) {
				if($name === self::DEFAULT_INSTANCE_NAME) {
					if(empty(self::$_aInstances)) {
						throw new Exception('Create Provider instance');
					}
					return reset(self::$_aInstances);
				} else {
					throw new Exception(sprintf('Provider instance with name "%s" is not defined', $name));
				}
			}
			return self::$_aInstances[$name];
		}
		if(!isset(self::$_aInstances[$name])) {
			$className = get_called_class();
			new $className($options[0], $options[1], $options[2], isset($options[3]) ? $options[3] : null, $name);
		}
		return self::$_aInstances[$name];
	}
	
	/**
	 * Set query logger
	 * @param QueryLogger $logger
	 */
	public function addLogger(QueryLogger $logger) {
		$this->setAttribute(self::ATTR_STATEMENT_CLASS, array(
			$this->_statementClass, array($logger)
		));
	}
	
	/**
	 * Fetch singel result
	 * @param string $query
	 * @param string or array $params
	 * @return object or false
	 */
	public function fetch($query, $params=null) {
		$stmt = $this->createStatement($query, $params);
		$stmt->execute();
		return $stmt->fetch();
	}
	
	/**
	 * Fetch all
	 * @param string $query
	 * @param string or array $params
	 * @return object or false
	 */
	public function fetchAll($query, $params=null) {
		$data = array();
		$stmt = $this->createStatement($query, $params);
		$stmt->execute();
		while($row = $stmt->fetch()) {
			$data[] = $row;
		}
		return $data;
	}
	
	/**
	 * Fetch column
	 * @param string $query
	 * @param string or array $params
	 * @return mixed
	 */
	public function fetchColumn($query, $params=null) {
		$stmt = $this->createStatement($query, $params);
		$stmt->execute();
		return $stmt->fetchColumn();	
	}
	
	/**
	 * Insert data
	 * @param string $tableName
	 * @param array $data
	 * @return integer
	 */
	public function insert($tableName, array $data, $onDuplicateKeyUpdate=null) {
		$query = 'INSERT INTO `'.$tableName.'` (`'.implode('`, `', array_keys($data)).'`) VALUES (:'.implode(', :', array_keys($data)).')';
		if(null !== $onDuplicateKeyUpdate) {
			$query .= ' ON DUPLICATE KEY UPDATE ';
			if(is_string($onDuplicateKeyUpdate)) {
				$query .= $onDuplicateKeyUpdate;
			} else {
				$set = array();
				foreach($onDuplicateKeyUpdate as $key=>$val) {
					if(is_numeric($key)) {
						$set[] = '`'.$val.'` = :'.$val;
					} else {
						$set[] = $val;
					}
				}
				$query .= implode(', ', $set);
			}
		}
		$stmt = $this->createStatement($query, $data);
		$stmt->execute();
		return $stmt->rowCount();
	}
	
	/**
	 * Update table
	 * @param string $tableName
	 * @param array $what
	 * @param array $where
	 * @return integer
	 */
	public function update($tableName, array $what, array $where=array(), $limit=null) {
		$params = array_merge($what, $where);
		
		$set = array();
		foreach($what as $key=>$val) {
			$set[] = '`'.$key.'`=:'.$key;
		}
		
		$whr = array();
		foreach($where as $key=>$val) {
			$whr[] = '`'.$key.'` '.(is_array($val) ? 'IN (' : '= ').':'.$key.(is_array($val) ? ')' : '');
		}

		$query = 'UPDATE `'.$tableName.'` SET '.implode(', ', $set);
		if(!empty($whr)) {
			$query .= ' WHERE '.implode(' AND ', $whr);
		}
		if(null !== $limit) {
			$query .= ' LIMIT '.$limit;
		}
		$stmt = $this->createStatement($query, $params);
		$stmt->execute();
		return $stmt->rowCount();			
	}
	
	/**
	 * Delete data
	 * @param string $tableName
	 * @param array $where
	 * @return integer
	 */
	public function delete($tableName, array $where, $limit=null) {
		$whr = array();
		foreach($where as $key=>$val) {
			$whr[] = '`'.$key.'` '.(is_array($val) ? 'IN (' : '= ').':'.$key.(is_array($val) ? ')' : '');
		}
		$query = 'DELETE FROM `'.$tableName.'`';
		if(!empty($whr)) {
			$query .= ' WHERE '.implode(' AND ', $whr);
		}
		if(null !== $limit) {
			$query .= ' LIMIT '.$limit;
		}
		$stmt = $this->createStatement($query, $where);
		$stmt->execute();
		return $stmt->rowCount();
	}
	
	/**
	 * Get first matched result
	 * @param string $table
	 * @param array $where
	 * @return object
	 */
	public function first($table, array $where, $restQuery=null) {
		$query = 'SELECT * FROM `'.$table.'` WHERE ';
		$query .= $this->array2where($where);
		if(null !== $restQuery) {
			$query .= ' '.$restQuery;
		}
		return $this->fetch($query, $where);	
	}
	
	/**
	 * Get found rows from SQL_CALC_FOUND_ROWS
	 * @return integer
	 */
	public function getFoundRows() {
		return $this->fetchColumn('SELECT FOUND_ROWS()');	
	}
	
	/**
	 * Create pagination result
	 * @param string $query
	 * @param mixed $params
	 * @param integer $current
	 * @param integer $max
	 * @return stdClass
	 */
	public function getPagingResult($query, $params=null, $current, $max) {
		$current = max(1, intval($current));
		$offset = max(0, ($current - 1) * $max);
		$query = preg_replace('/SELECT/i', 'SELECT SQL_CALC_FOUND_ROWS', $query);
		$query .= ' LIMIT '.$offset.', '.$max;
		$rows = $this->fetchAll($query, $params);
		$total = $this->getFoundRows();
		$pages = ceil($total / $max);
		$ret = array(
			'total' => $total,
			'offset' => $offset,
			'limit' => $max,
			'pages' => $pages,
			'current' => $current,
			'previous' => min($pages, max(1, $current - 1)),
			'next' => min($pages, $current + 1),
			'has' => array(),
			'rows' => $rows
		);
		$ret['has'] = (object)array(
			'previous' => $current > 1 && $ret['previous'] < $pages,
			'next' => $ret['next'] < $pages
		);
		return (object)$ret;
	}
	
	/**
	 * Create statement and execute
	 * @param unknown_type $query
	 * @param unknown_type $params
	 * @return PDOStatement
	 */
	public function query($query, $params=null) {
		$stmt = $this->createStatement($query, $params);
		$stmt->execute();
		return $stmt;
	}
	
	/**
	 * Create statement
	 * @param string $query
	 * @param string or array $params
	 * @return PDOStatement
	 */
	public function createStatement($query, $params=null) {
		if(null !== $params) {
			$params = (array)$params;
		}
		
		if($params) {
			$this->bindQueryParams($query, $params);
		}
		
		$stmt = $this->prepare($query);
		if($params) {
			$this->bindParams($stmt, $params);
		}
		
		return $stmt;
	}
	
	/**
	 * Bind statement params
	 * @param PDOStatement $stmt
	 * @param array $params
	 * @return PDOStatement
	 */
	public function bindParams(Statement $stmt, array $params) {
		$index = 0;
		foreach($params as $paramName=>$paramValue) {
			if(!is_string($paramName)) {
				$index++;
				$this->bindParam($index, $paramValue);
			} else {
				if(substr($paramName, 0, 1) != ':') {
					$paramName = ':'.$paramName;
				}
				$stmt->bindValue($paramName, $paramValue);
			}
		}
		return $stmt;
	}
	
	/**
	 * Modify query and params
	 * @param string $query
	 * @param array $params
	 */
	public function bindQueryParams(&$query, array &$params) {
		$index = 0;
		$newParams = array();
		foreach($params as $paramName=>$paramValue) {
			if(!is_string($paramName)) {
				$paramName++;
				$paramName = ':p'.$paramName;
				$query = substr_replace($query, $paramName, strpos($query, '?'), 1);
			}
			
			if(!is_array($paramValue)) {
				$newParams[$paramName] = $paramValue;
				continue;
			}
			
			if(substr($paramName, 0, 1) != ':') {
				$paramName = ':'.$paramName;
			}
			$pos = 0;
			$list = array();
			foreach($paramValue as $k=>$v) {
				$pos++;
				$k = $paramName.$pos;
				$list[] = $k;
				$newParams[$k] = $v;
			}				

			$query = str_replace($paramName, implode(',', $list), $query);
		}	
		$params = $newParams;
	}
	
	/**
	 * Create relation between data sets
	 * @param array|object $result
	 * @param array $relation
	 * @param array|string $query
	 * @example
	 * 		$relation = array(
	 * 			'primaryKey' => 'id', 		// What to read
	 * 			'foreignKey' => 'user_id',	// How to map
	 * 			'name' => 'details', 		// Where to map
	 * 			'many' => true,		 		// If we have many or singel result
	 * 			'bind' => array() 			// What else to bind in query
	 * 		);
	 * 
	 * 		$user = $db->find('SELECT * FROM users WHERE id = ?', 1);
	 * 		$db->map($user, array(
	 * 			'foreignKey' => 'user_id',
	 * 			'name' => 'details',
	 * 			'many' => false
	 * 		), 'SELECT * FROM users_details WHERE user_id = :user_id');
	 * 		echo $user->details->eye_color;
	 * 
	 *	or you can pass multiple queries
	 *		
	 *		$db->map($user, array(
	 *			'foreignKey' => 'user_id'
	 *		), array(
	 *			'details' => 'SELECT * FROM users_details WHERE user_id = :user_id',
	 *			'gallery' => array(
	 *				'SELECT * FROM users_gallery WHERE user_id = :user_id AND approved = :approved',
	 *				array(
	 *					'many' => true,
	 *					'bind' => array(
	 *						'approved' => 1
	 *					)
	 *				)
	 *			)
	 *		));
	 *
	 *	If you want to use different primary key you can pass primaryKey key in relation var
	 *		
	 *		$db->map($user, array(
	 *			'primaryKey' => 'last_login_id',
	 *			'foreignKey' => 'id'
	 *		), 'SELECT * FROM users_logins WHERE id = :id');		
	 *		
	 */
	public function map($result, array $relation, $query) {
		if(!isset($relation['primaryKey'])) {
			$relation['primaryKey'] = 'id';
		}
		if(!isset($relation['foreignKey'])) {
			throw new Exception('Foreign key is not defined');
		}
		if(!isset($relation['name'])) {
			$relation['name'] = $relation['primaryKey'];
		}
		if(!isset($relation['many'])) {
			$relation['many'] = false;
		}
		if(!isset($relation['bind'])) {
			$bind = array();
		} else {
			$bind = $relation['bind'];
			unset($relation['bind']);
		}
		
		if(is_array($query)) {
			foreach($query as $name=>$q) {
				$rel = $relation;
				$rel['name'] = $name;
				if(is_array($q)) {
					$rel = array_replace($rel, $q[1]);
					$q = $q[0];
				}
				$this->map($result, $rel, $q);
			}
			return true;
		}
		
		$pk = $relation['primaryKey'];
		$fk = $relation['foreignKey'];
		$name = $relation['name'];
		$many = (bool)$relation['many'];
		
		if(!is_array($result)) {
			$result = array($result);
		}
		$values = array();
		foreach($result as $row) {
			$values[] = $row->{$pk};
		}
		
		if(!empty($values)) {
			$bind[$fk] = $values;
			$all = $this->fetchAll($query, $bind);
		} else {
			$all = array();
		}
		
		foreach($result as $pRow) {
			$vals = array();
			$v1 = $pRow->{$pk};
			foreach($all as $fRow) {
				$v2 = $fRow->{$fk};
				if($v1 == $v2) {
					$vals[] = $fRow;		
				}
			}
			if($many) {
				$pRow->{$name} = $vals;
			} else {
				$first = reset($vals);
				if(false === $first) {
					$first = new \stdClass;
				}
				$pRow->{$name} = (object)$first;
			}
		}
	}
	
	/**
	 * Get meta data
	 * @param string $tableName
	 * @return array
	 */
	public function getTableMetaData($tableName) {
		$all = array();
		$fields = $this->fetchAll('DESCRIBE `'.$tableName.'`');
		foreach($fields as $field) {
			preg_match('/(.*?)\((.*?)\)/i', $field->Type, $match);
			$attrs = array(
				'type' => $match[1]
			);
			if($match[1] == 'enum' || $match[1] == 'set') {
				$match[2] = explode(',', $match[2]);
				foreach($match[2] as $key=>$val) {
					$match[2][$key] = str_replace("'", '', $val);
				}
				$attrs['values'] = $match[2];
			} else {
				$attrs['length'] = $match[2];
			}
			
			if($field->Default) {
				$attrs['default'] = $field->Default;
			}
			if($field->Key == 'PRI') {
				$attrs['primary'] = true;
			} else if($field->Key == 'UNI') {
				$attrs['unique'] = true;
			}
			if(strpos($field->Extra, 'auto_increment') !== false) {
				$attrs['auto_increment'] = true;
			}
			$all[$field->Field] = $attrs;
		}	
		return $all;
	}
	
	/**
	 * Create simple where clause
	 * @param array $where
	 */
	protected function array2where(array $where) {
		$whr = array();
		foreach($where as $key=>$val) {
			$whr[] = '`'.$key.'` '.(is_array($val) ? 'IN (' : '= ').':'.$key.(is_array($val) ? ')' : '');
		}
		return implode(' AND ', $whr);
	}
}