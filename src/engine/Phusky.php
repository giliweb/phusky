<?php namespace giliweb\phusky;
class Phusky {

	private static $i;
	public static $structure = [];
	private static $tables = [];


	public function __construct(){

	}



	public function setup(array $data){
		$this->writeConfig($data);
		require_once($data['base_path'].'/config.php');
		self::$tables = $this->getTables();
		foreach(self::$tables as $table){
			self::$structure[$table] = [
				"children" => $this->getChildren($table),
				"parents" => $this->getParents($table)
			];
		}

		// complete children structures...

		foreach(self::$structure as $tableName => &$e){
			$className = $this->tableName2ClassName($tableName);
			$children = $e['children'];
			foreach($children as $child){
				$extends = $this->getStructure($child['table_name'])['parents'];
				foreach($extends as $k => &$extend){
					if($extend['class_name'] == $className){
						unset($extends[$k]);
						continue;
					}
					$extend['join_table'] = $child['table_name'];
					$extend['join_index'] = $child['index'];
				}
				$e['children'] = array_merge($e['children'], $extends);
			}
		}

		$path = $data['absolute_class_path'];
		if(!file_exists($path)){
			mkdir($path, 0777);
		}
		foreach(self::$structure as $tableName => $e){
			$filename = $this->tableName2ClassName($tableName).'.php';
			$classData = $this->prepareClass([
				"className" => $this->tableName2ClassName($tableName),
				"tableName" => $tableName
			]);

			if(file_exists($path.$filename)){
				$fileData = file_get_contents($path.$filename);
				$pattern = '/<\?php([a-zA-Z0-9\\s\\\{}$=\[\]"_>,;\/]*)/';
				$fileData = preg_replace($pattern, $classData, $fileData);
				file_put_contents($path.$filename, $fileData);
			} else {
				file_put_contents($path.$filename, $classData."§\r\r\r}");
			}
		}
	}
	
	private function writeConfig(array $data){
		$configData = 
"<?php
// AUTO GENERATED FILE, ANY MOD WILL BE OVERWRITTEN WITH \"composer phusky_setup ... \" COMMAND. 
// YOU CAN LAUNCH COMMAND \"composer phusky_setup {classes folder} {db host} {db name} {db_user} {db_password} \"
require_once 'vendor/autoload.php';
\\DB::\$host = '{$data['db_host']}';
\\DB::\$dbName = '{$data['db_name']}';
\\DB::\$user = '{$data['db_user']}';
\\DB::\$password = '{$data['db_password']}';
\\DB::\$encoding = 'UTF8';
spl_autoload_register(function(\$className){
    \$path = '{$data['absolute_class_path']}/';
    include \$path.\$className.'.php';
});
";
		file_put_contents($data['base_path']. 'config.php', $configData);
	}

	private function prepareClass(array $data){
		$children = self::getStructure($data['tableName'])['children'];
		$parents = self::getStructure($data['tableName'])['parents'];
		$classData =
"<?php
class {$data['className']} extends \\giliweb\\phusky\\Model {
	public \$children = ".$this->array2String($children).";
	public \$parents = ".$this->array2String($parents).";
	public static \$tableName = \"".$data['tableName']."\";
	//any manual edit above this line will be overwritten with setup, you can write below this line"
		;
		return $classData;
	}

	public static function getInstance(){
		if(!self::$i instanceof Phusky){
			self::$i = new Phusky();
		}
		return self::$i;
	}

	private function getTables(){
		return \DB::queryFirstColumn("show tables from " . \DB::$dbName);
	}

	private function getChildren($table){
		$children = [];
		$temp = \DB::query(
			"
					SELECT
					    *
					FROM
					    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
					WHERE
					    REFERENCED_TABLE_SCHEMA = '".\DB::$dbName."' AND
					    REFERENCED_TABLE_NAME = '$table'
				"
		);
		foreach($temp as $k => $e){
			$children []= [
				"table_name" => $e['TABLE_NAME'],
				"index" => $e['COLUMN_NAME'],
				"class_name" => $this->tableName2ClassName($e['TABLE_NAME'])
			];
		}
		return $children;
	}

	private function getParents($table){
		$temp = \DB::query(
			"
					SELECT
					    *
					FROM
					    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
					WHERE
					    REFERENCED_TABLE_SCHEMA = '".\DB::$dbName."' AND
					    TABLE_NAME = '$table'
				"
		);
		$parents = [];
		foreach($temp as $k => $e){
			$field_properties = \DB::queryOneRow("SHOW FULL COLUMNS FROM $table where Field = '{$e['COLUMN_NAME']}'");
			if($field_properties['Default'] === NULL){
				if($field_properties['Null'] == 'YES'){
					$default = NULL;
				} else {
					$default = false;
				}
			} else {
				$default = $field_properties['Default'];
			}
			$parents []= [
				"table_name" => $e['REFERENCED_TABLE_NAME'],
				"index" => $e['COLUMN_NAME'],
				"class_name" => $this->tableName2ClassName($e['REFERENCED_TABLE_NAME']),
				"default" => $default
			];
		}
		return $parents;
	}

	public function getStructure(String $tableName){
		return self::$structure[$tableName];
	}

	private function tableName2ClassName($tableName){
		$tableName = explode('_', $tableName);
		foreach($tableName as &$t){
			$t = ucfirst($t);
			if(substr($t, -3) == 'ies') {
				$t = substr($t, 0, -3) . 'y';
			} elseif(substr($t, -3) == 'ves'){
				$t = substr($t, 0, -3) . 'fe';
			} elseif($t{strlen($t)-1} == 's'){
				$t = substr($t, 0, -1);
			}
		}
		$tableName = implode('', $tableName);
		return $tableName;
	}

	private function array2String(array $array){
		$temp = "[";
		foreach($array as $k => $e){
			if(is_array($e)){
				$e = $this->array2String($e);
			} else {
				if(is_bool($e)){
					$e = $e === false ? 'false' : 'true';
				} elseif($e === NULL){
					$e = 'NULL';
				} else {
					$e = "\"$e\"";
				}
			}
			$temp .= is_numeric($k) ? "$e," : "\"$k\" => $e,";

		}
		$temp .= "]";
		return $temp;
	}
}