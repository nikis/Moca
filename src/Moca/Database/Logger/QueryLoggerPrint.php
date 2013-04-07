<?php
namespace Moca\Database\Logger;

/**
 * Query logger printer
 * @author nikis
 *
 */
class QueryLoggerPrint implements QueryLogger {
	
	public function start($query, $params=null) {
		echo $query.PHP_EOL;
	}
	
	public function stop() {}
}