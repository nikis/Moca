<?php
use Moca\Orm;

if(!isset($database) || isset($database) && false === $database instanceof Orm\DatabaseProviderInterface ) {
	throw new Exception('Database variable is not defined or is not instanceof of Moca\Orm\DatabaseProviderInterface');
}
$GLOBALS['database'] = $database;

if(!isset($defaults)) {
	$defaults = array(
		'model.path' => 'model/',
		'model.namespace' => 'App\Model',
		'model.database' => 'Database'
	);
} 

class Main extends Orm\Table {
	public function connection(IConnector $i=null) {
		return $GLOBALS['database'];
	}
}

function cli_in() {
	return trim(fgets(STDIN));
}

function cli_out() {
	foreach(func_get_args() as $a) {
		echo $a."\r\n";
	}
}

function table($dest, $db, $extend, $ns, $name=null) {
	$table = Main::init();
	
	$table->db($db);
	
	if(null === $name) {
		cli_out('Table name');
		$in = cli_in();
	} else {
		$in = $name;
	}
	
	$table->name($in);
	
	$tableTool = new Orm\TableTool();
	$tableTool->fromSQL($table);
	
	$name = explode('_', $in);
	foreach($name as $k=>$v) {
		$name[$k] = ucfirst($v);
	}
	$fullName = implode('.', $name);
	$modelName = end($name);
	$fn = $modelName.'.php';
	array_pop($name);

	$dest .= implode('/', $name).'/';
	if(!is_dir($dest)) {
		mkdir($dest, 0777, true);
	}
	$dest .= $fn;
	if(is_file($dest)) {
		$cName = $ns.'\\'.$fullName;
		$oldModel = forward_static_call_array(array($cName, 'init'), array());
		$tableTool->extendTables($oldModel, $table);
		$php = $tableTool->toPHP($table, $ns, $extend);
		
		$old = file_get_contents($dest);
		$php = $tableTool->updateTableContent($old, $php);
	} else {
		$php = $tableTool->toPHP($table, $ns, $extend);
	}
	file_put_contents($dest, $php);
}

$dest = $defaults['model.path'];
if(!is_dir($dest)) {
	cli_out($dest.' is not a directory');
	exit;
}

if(empty($defaults['database'])) {
	cli_out('Database name');
	$db = cli_in();
} else {
	$db = $defaults['database'];
}

cli_out('Namespace ['.$defaults['model.namespace'].']');
$ns = trim(cli_in());
if(empty($ns)) {
	$ns = $defaults['model.namespace'];
}

cli_out('Extend base db ['.$defaults['model.database.class'].']');
$extend = trim(cli_in());
if(empty($extend)) {
	$extend = $defaults['model.database.class'];
}
if($extend == 'no' || $extend == 'n') {
	$extend = null;
}

if(!empty($extend)) {
	cli_out('Generate base class [no]');
	$in = trim(cli_in());
	if($in == 'y' || $in == 'yes') {
		$tableTool = new Orm\TableTool();
		$code = $tableTool->baseClassToPHP($extend, $db, $ns, get_class($database));
		file_put_contents($dest.strtolower($extend).'.php', $code);
	}
}

cli_out('Export all tables? [no]');
$all = trim(cli_in());
if($all == 'y' || $all == 'yes') {
	cli_out('All tables starts with: (leave blank)');
	$in = trim(cli_in());
	$search = null;
	if(!empty($in)) {
		$search = $in;
	}
	
	$tables = array();
	if($search) {
		$sql = $database->query('SHOW TABLES LIKE "%'.addslashes($search).'%"');
	} else {
		$sql = $database->query('SHOW TABLES');
	}
	while($row = $database->fetch_arr($sql)) {
		$tables[] = reset($row);
	}
	foreach($tables as $table) {
		if(function_exists('format_table')) {
			$table = format_table($table);
		}
		table($dest, $db, $extend, $ns, $table);
	}
	cli_out('Genered '.count($tables).' tables');
	exit;
}

load_table:
	table($dest, $db, $extend, $ns);
	cli_out('Other table?');
	$in = cli_in();
	if($in == 'y' || $in == 'yes') {
		goto load_table;
	}