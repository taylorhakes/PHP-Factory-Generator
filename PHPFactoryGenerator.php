<?php
/*
PHP Factory Generator is a code generating tool. It will make objects out
of all the tables in a specified in a MYSQL database and makes a single file to
include to use them. It creates interfaces to the MYSQL as well. 
It makes the functions: Create, CreateUpdate, Update, Retrieve and Delete functions
for every table in a database and stores them in a single file.
This allows a developer to use database tables like other PHP object
and they do not have to worry about connecting to thethe MYSQL database  
and closing connections. The code is all generated for you.

*/


// the initial variables

// the username that has access to the database
$username = '';
// password to the username
$password = '';
// the url of the database
$host = '';
// The database to create functions for
$seldatabase = '';
// The location to store the created files
$file_path = '';

//connect to the database
$databases = array();
$connection = new mysqli($host, $username, $password, "information_schema");
if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}
$query = "SELECT DISTINCT TABLE_SCHEMA FROM TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND ENGINE = 'MyISAM' AND TABLE_SCHEMA <> 'mysql'";
$result = $connection->query($query);
while ($obj = $result->fetch_object()) {
	$databases[] = $obj->TABLE_SCHEMA;
}

$query = "SELECT DISTINCT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA = '$seldatabase'";
$result = $connection->query($query);
while ($obj = $result->fetch_object()) {
	$tables[] = $obj->TABLE_NAME;
}
$tablestring = '<?php
	//This is PHP Generated Code From AutomatePHPMySQL. Be Careful editing this code, functions may become unstable. If you made a change to your database, recreate this file.';
foreach ($tables as $tab) {
	$tablestring .= '
	class '.$tab.'Object{';
	$query = "SELECT * FROM COLUMNS WHERE TABLE_NAME = '$tab' AND TABLE_SCHEMA = '$seldatabase'";
	$result = $connection->query($query);
	$all_columns = array();
	while ($obj = $result->fetch_object()) {
		$tablestring .= 'public $'.$obj->COLUMN_NAME.';';
	}
	$tablestring .= '}
	';
}
$tablestring .= '
	class '.$seldatabase.'
	{
	private static $username = "'.$username.'";
	private static $password = "'.$password.'";
	private static $database = "'.$seldatabase.'";
	private static $host = "'.$host.'";
';


foreach ($tables as $tab) {
	$query = "SELECT * FROM COLUMNS WHERE TABLE_NAME = '$tab' AND TABLE_SCHEMA = '$seldatabase'";
	$result = $connection->query($query);
	$main_columns = array();
	$all_columns = array();
	$key = null;
	while ($obj = $result->fetch_object()) {
		$column = new stdClass();
		$column->name = $obj->COLUMN_NAME;
		if (strpos($obj->COLUMN_TYPE, 'int') !== false) {
			$column->type = 'i';
		} else if (strpos($obj->COLUMN_TYPE, 'dec') !== false || strpos($obj->COLUMN_TYPE, 'flo') !== false) {
			$column->type = 'd';
		} else {
			$column->type = 's';
		}
		if (strpos($obj->COLUMN_KEY, 'PRI') !== false) {
			$key = $column;
			if (strpos($obj->EXTRA, 'auto_increment') !== false) {
				$key->auto = true;
			} else
				$key->auto = false;
		} else {
			$main_columns[] = $column;
		}
		if (strpos($obj->COLUMN_KEY, 'PRI') === false && strpos($obj->EXTRA, 'auto_increment') !== false) {
			throw new Exception('Invalid column, Factory does not support auto increment columns that are not the primary key');
		}
		$all_columns[] = $column;
	}
	$main_column_names = array();
	$main_column_updates = array();
	$main_column_objects = array();
	$main_column_types = array();
	foreach ($main_columns as $col) {
		$main_column_names[] = $col->name;
		$main_column_updates[] = $col->name.'=?';
		$main_column_objects[] = '$object->'.$col->name;
		$main_column_types[] = $col->type;
	}
	$all_column_names = array();
	$all_column_updates = array();
	$all_column_objects = array();
	$all_column_types = array();
	foreach ($all_columns as $col) {
		$all_column_names[] = $col->name;
		$all_column_updates[] = $col->name.'=?';
		$all_column_objects[] = '$object->'.$col->name;
		$all_column_types[] = $col->type;
	}
	// Setup the column names for use in all the functions
	$tablestring .= '
	private static $'.$tab.'_col_names = array(\''.implode('\',\'', $all_column_names).'\');';

	//Setup the Create function
	$tablestring .= '
	public static function Create'.$tab.'Object($object = null)
	{
		if(!empty($object)) {
			$params = get_object_vars($object);
			foreach(array_keys($params) as $par) {
				if(!in_array($par,'.$seldatabase.'::$'.$tab.'_col_names)) return \'Invalid object, , $par column does not exist\';
			}
		}
		$connection = new mysqli('.$seldatabase.'::$host, '.$seldatabase.'::$username, '.$seldatabase.'::$password, '.$seldatabase.'::$database);
		if (mysqli_connect_errno()) {
			return \'Connection Error: \'.mysqli_connect_error();
		}';
	if (!empty($key) && $key->auto) {
		$tablestring .= '
			$stmt = $connection->prepare("INSERT INTO '.$tab.' ('.implode(',', $main_column_names).') VALUES (';
		$ques = array();
		for ($i = 0; $i < count($main_column_names); $i++) {
			$ques[] = '?';
		}
		$tablestring .= implode(',', $ques).')");
		$stmt->bind_param("'.implode('', $main_column_types).'",'.implode(',', $main_column_objects).');';
	} else {
		$tablestring .= '
		$stmt = $connection->prepare("INSERT INTO '.$tab.' ('.implode(',', $all_column_names).') VALUES (';
		$ques = array();
		for ($i = 0; $i < count($all_column_names); $i++) {
			$ques[] = '?';
		}
		$tablestring .= implode(',', $ques).')");
		$stmt->bind_param("'.implode('', $all_column_types).'",'.implode(',', $all_column_objects).');';
	}

	$tablestring .= '
		$stmt->execute();
		$error = $stmt->error;
		$connection->close();
		if(!empty($error)) return \'Error: \'.$error;';
	if (!empty($key) && $key->auto) {
		$tablestring .= '
		$object->'.$key->name.'=$stmt->insert_id;';
	}
	$tablestring .= '
		return $object;
	}
	';

	//Setup the CreateUpdate Function
	$tablestring .= '
	public static function CreateUpdate'.$tab.'Object($object = null)
	{';
	if (empty($key)) {
		$tablestring .= '
		return \'Function not supported. No table primary key\';';
	} else {
		$tablestring .= '
		if(!empty($object)) {
			$params = get_object_vars($object);
			foreach(array_keys($params) as $par) {
				if(!in_array($par,'.$seldatabase.'::$'.$tab.'_col_names)) return \'Invalid object, , $par column does not exist\';
			}
		}
		$connection = new mysqli('.$seldatabase.'::$host, '.$seldatabase.'::$username, '.$seldatabase.'::$password, '.$seldatabase.'::$database);
		if (mysqli_connect_errno()) {
			return \'Connection Error: \'.mysqli_connect_error();
		}
		';
		if (!empty($key) && $key->auto) {
			$tablestring .= '
			$stmt = $connection->prepare("INSERT INTO '.$tab.' ('.implode(',', $main_column_names).') VALUES (';
			$ques = array();
			for ($i = 0; $i < count($main_column_names); $i++) {
				$ques[] = '?';
			}
			$tablestring .= implode(',', $ques).') ON DUPLICATE KEY UPDATE '.$key->name.'=LAST_INSERT_ID('.$key->name.'),'.implode(',', $main_column_updates).'");
				$stmt->bind_param("'.implode('', $main_column_types).implode('', $main_column_types).'",'.implode(',', $main_column_objects).','.implode(',', $main_column_objects).');';
		} else {
			$tablestring .= '
			$stmt = $connection->prepare("INSERT INTO '.$tab.' ('.implode(',', $all_column_names).') VALUES (';
			$ques = array();
			for ($i = 0; $i < count($all_column_names); $i++) {
				$ques[] = '?';
			}
			$tablestring .= implode(',', $ques).') ON DUPLICATE KEY UPDATE '.implode(',', $main_column_updates).'");
			$stmt->bind_param("'.implode('', $all_column_types).implode('', $main_column_types).'",'.implode(',', $all_column_objects).','.implode(',', $main_column_objects).');';
		}
		$tablestring .= '
		$stmt->execute();
		$error = $stmt->error;
		$connection->close();
		if(!empty($error)) return \'Error: \'.$error;';
		if ($key->auto) {
			$tablestring .= '
		$object->'.$key->name.'=$stmt->insert_id;';
		}
		$tablestring .= '
		return $object;';
	}
	$tablestring .= '}
	';

	//Setup the Update Function
	$tablestring .= '
	public static function Update'.$tab.'Object($object)
	{';
	if (empty($key))
		$tablestring .= 'return \'Function not supported. No table primary key\';';
	else {
		$tablestring .= '
		if(empty($object) || empty($object->'.$key->name.')) return \'Missing primary key value\';
		$params = get_object_vars($object);
		foreach(array_keys($params) as $par) {
			if(!in_array($par,'.$seldatabase.'::$'.$tab.'_col_names)) return \'Invalid object, $par column does not exist\';
		}
		$connection = new mysqli('.$seldatabase.'::$host, '.$seldatabase.'::$username, '.$seldatabase.'::$password, '.$seldatabase.'::$database);
		if (mysqli_connect_errno()) {
			return \'Connection Error: \'.mysqli_connect_error();
		}
		$stmt = $connection->prepare("UPDATE '.$tab.' SET '.implode(',', $main_column_updates).' WHERE '.$key->name.'=?");
		$stmt->bind_param("'.implode('', $main_column_types).$key->type.'",'.implode(',', $main_column_objects).',$object->'.$key->name.');
		$stmt->execute();
		$error = $stmt->error;
		$connection->close();
		if(!empty($error)) return \'Error: \'.$error;
		return $object;';
	}
	$tablestring .= '}
	';

	// Setup th Retrieve Function
	$tablestring .= '
	public static function Retrieve'.$tab.'Object($'.(!empty($key) ? $key->name : 'unsupported').')
	{';
	if (empty($key))
		$tablestring .= 'return \'Function not supported. No table primary key\';';
	else {
		$tablestring .= '
		$connection = new mysqli('.$seldatabase.'::$host, '.$seldatabase.'::$username, '.$seldatabase.'::$password, '.$seldatabase.'::$database);
		if (mysqli_connect_errno()) {
			return \'Connection Error: \'.mysqli_connect_error();
		}
		$stmt = $connection->prepare("SELECT '.implode(',', $main_column_names).' FROM '.$tab.' WHERE '.$key->name.'=?");
		$stmt->bind_param("'.$key->type.'",$'.$key->name.');
		$object = new'.$tab.'Object();
		$stmt->bind_result('.implode(',', $main_column_objects).');
		$stmt->execute();
		$stmt->fetch();
		$error = $stmt->error;
		$connection->close();
		if(!empty($error)) return \'Error: \'.$error;
		return $object;';
	}
	$tablestring .= '}
	';
	
	//Setup the Delete Function
	$tablestring .= '
	public static function Delete'.$tab.'Object($'.(!empty($key) ? $key->name : 'unsupported').')
	{';
	if (empty($key))
		$tablestring .= 'return \'Function not supported. No table primary key\';';
	else {
		$tablestring .= '
		$connection = new mysqli('.$seldatabase.'::$host, '.$seldatabase.'::$username, '.$seldatabase.'::$password, '.$seldatabase.'::$database);
		if (mysqli_connect_errno()) {
			return \'Connection Error: \'.mysqli_connect_error();
		}
		$stmt = $connection->prepare("DELETE FROM '.$tab.' WHERE '.$key->name.'=?");
		$stmt->bind_param("'.$key->type.'",$'.$key->name.');
		$inserted = $stmt->execute();
		$error = $stmt->error;
		$connection->close();
		if(!empty($error)) return \'Error: \'.$error;
		return $inserted;';
	}
	$tablestring .= '}
	';

}
$tablestring .= '
}
?>';

$fh = fopen($file_path, 'w+');
fwrite($fh, $tablestring);
fclose($fh);
?>