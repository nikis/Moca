<?php
namespace Moca\Orm;

use Moca\Database;

class MocaDatabase extends Database\Provider implements DatabaseProviderInterface {
	
	public function exec_query($query) {
		return $this->query($query);
	}
	
	public function fetch_arr($resource) {
		if($resource) {
			return $resource->fetch(\PDO::FETCH_ASSOC);
		} else {
			return false;
		}
	}
	
	public function last_insert_id() {
		return $this->lastInsrtId();
	}
}