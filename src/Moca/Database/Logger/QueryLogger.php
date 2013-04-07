<?php
namespace Moca\Database\Logger;

/**
 * Query logger
 * @author nikis
 *
 */
interface QueryLogger {
	
	public function start($query, $params=null);
	
	public function stop();
}