<?php namespace giliweb\phusky;
class Model implements iModel {

	private $phusky;

	/**
	 * Model constructor.
	 */
	public function __construct(){
		$class = get_called_class();
		$this->phusky = Phusky::getInstance();
	}

	protected static function getTableName(){
		$class = get_called_class();
		return $class::$tableName;
	}
	
	public static function getById(int $id, array $fields = NULL){
		$fields = is_array($fields) ? implode($fields) : '*';
		$data = \DB::queryFirstRow("select $fields from " . self::getTableName() . " where id = '$id'");
		if(is_null($data)){
			return false;
		}
		return self::modelize($data);
	}

	public function create(){
		// TODO: Implement create() method.
	}

	public static function read(array $data){
		$where = self::parseQuery($data);
		return self::modelizeArray(\DB::query("select * from " . self::getTableName() ." where " . $where));
	}

	public function update(){
		// TODO: Implement update() method.
	}

	public function delete(){
		// TODO: Implement delete() method.
	}

	public function __get(String $what){
		if($r = $this->hasParent($what)){
			$index = $r['index'];
			$model = $r['class_name'];
			$data = $model::getById($this->$index);
			$this->$what = $data;
			return $data;
		} elseif($r = $this->hasChild($what)){

			$index = $r['index'];
			$model = $r['class_name'];

			if(isset($r['join_table'])) {
				$r['id'] = $this->id;
				$data = $model::getChildrenData($r);
			} else {
				$data = $model::read(
					[
						$r['index'], "=", $this->id
					]
				);
			}

			$this->$what = $data;
			return $data;
		}
	}

	protected static function getChildrenData($r){
		$sql = "
			select
				*
			from
				{$r['table_name']} a
			JOIN 
				{$r['join_table']} b
			ON 
				a.id = b.{$r['index']}
			where
			  	b.{$r['join_index']} = '{$r['id']}'
		";
		return self::modelizeArray(\DB::query($sql));
	}

	private function hasParent($what){
		foreach($this->parents as $p){
			if(strtolower($p['class_name']) == strtolower($what)){
				return $p;
			}
		}
		return false;
	}

	private function hasChild($what){
		foreach($this->children as $p){
			if(strtolower($p['class_name']).'s' == strtolower($what)){
				return $p;
			}
		}
		return false;
	}


	private function modelize($data){
		$className = get_called_class();
		$model = new $className();
		foreach($data as $k => $e){
			$model->$k = $e;
		}
		return $model;
	}

	private function modelizeArray(array $data){
		foreach($data as &$e){
			$e = self::modelize($e);
		}
		return $data;
	}

	/**
	 * parseQuery v 1.3.1 27-05-16
	 * Trasforma array del tipo:
	 *
	 * $filters = array(
	 *      "or" => array(
	 *          array("id", "=", "69"),
	 *          "and" => array(
	 *              array("name", "=", "'tony'"),
	 *              array("id", "=", "2")
	 *          ),
	 *          array("admin", "=", "1")
	 *      )
	 *  );
	 *
	 * in stringhe sql tipo:
	 * "where id = 69 or ( name = 'tony' and id = 12) or admin = 1"
	 *
	 * utilizzo: parseQuery($array);
	 * parametro $operator non deve mai essere passato manualmente
	 *
	 * @param    array $ar da trasformare in stringa
	 * @param bool $operator
	 * @return string per query sql
	 *
	 */
	protected static function parseQuery($ar, $operator = false){
		if (!function_exists(__NAMESPACE__ . '\objectToArray')) {
			function objectToArray($object, $recursive = true){
				if (is_array($object)) return $object;
				if (gettype($object) == 'string') return $object;
				return is_null($object) ? array() : get_object_vars($object);
			}
		}
		if (!function_exists(__NAMESPACE__ . '\getDepth')) {
			function getDepth(array $arr){
				$exploded = explode(',', json_encode($arr, JSON_FORCE_OBJECT) . "\n\n");
				$longest = 0;
				foreach ($exploded as $row) {
					$longest = (substr_count($row, ':') > $longest) ?
						substr_count($row, ':') : $longest;
				}
				return $longest;
			}
		}
		$ignore = array("query", "page", "start", "limit", "sort", "sort_direction", "email", "password");
		if(isset($ar['start'])) unset($ar['start']);
		if(isset($ar['limit'])) unset($ar['limit']);
		if(isset($ar['sort'])) unset($ar['sort']);
		if(isset($ar['sort_direction'])) unset($ar['sort_direction']);

		if (!is_array($ar) && !is_string($ar)) {
			$ar = objectToArray($ar);
			foreach (array_keys($ar) as $k) {
				if (array_search($k, $ignore) !== false || count($ar[$k]) <= 0) {
					unset($ar[$k]);
				}
			}
		}
		if (is_null($ar) || !is_array($ar) || count($ar) <= 0) return '';
		$temp = array();
		if (getDepth($ar) == 1) {
			foreach($ar as $k => &$e){
				$e = preg_replace_callback('/(?!^.?).(?!.{0}$)/', function ($a) {
					return $a[0] == "'" ? "\\" . $a[0] : $a[0];
				}, $e);
			}
			return implode(' ', $ar);
		} else {

			foreach ($ar as $k => $v) {
				$temp[$k] = self::parseQuery($v, $k);
			}
		}
		return ($operator === false ? " where " : "")  . ' ( ' . implode(' ' . $operator . ' ', $temp) . ' ) ';
	}
}