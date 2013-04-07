<?php
namespace Moca\Orm;

/**
 * Database connector interface
 * @author nikis
 *
 */
interface DatabaseProviderInterface {
	
	/**
	 * Execute query
	 * @param $query
	 * @return unknown_type
	 */
	public function exec_query($query);
	
	/**
	 * Fetch array
	 * @return array|false
	 */
	public function fetch_arr($resource);
	
	/**
	 * Get last insert id
	 * @return integer
	 */
	public function last_insert_id();
}