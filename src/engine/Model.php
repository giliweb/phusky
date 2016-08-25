<?php namespace giliweb\phusky;
class Model implements iModel {

	//private $phusky;

	/**
	 * Model constructor.
	 * @param array $data
	 */
	public function __construct(array $data = NULL){
		$class = get_called_class();
		if(!is_null($data)){
			foreach($data as $k => $e){
				$this->$k = $e;
			}
		}
		//$this->phusky = Phusky::getInstance();
	}

	/**
	 * @return String
	 */
	protected static function getTableName(){
		$class = get_called_class();
		return $class::$tableName;
	}

	/**
	 * @param int $id
	 * @param array|NULL $fields
	 * @return bool|Model
	 */
	public static function getById(int $id, array $fields = NULL){
		$fields = is_array($fields) ? implode($fields) : '*';
		$data = \DB::queryFirstRow("select $fields from " . self::getTableName() . " where id = '$id'");
		if(is_null($data)){
			return false;
		}
		return self::modelize($data);
	}

	/**
	 * @throws PhuskyException
	 * @return bool;
	 */
	public function create(){
		if(isset($this->id)){
			return $this->update();
		}

		// check if parents exists
		$this->checkIfAllParentsExists();

		// write $this on DB
		$r = \DB::insert($this->getTableName(), $this->getWritableData());
		$this->id = \DB::insertId();

		// create children
		$this->handleChildren();
		print_r($this->output());
		return true;
	}

	/**
	 *
	 */
	private function handleChildren(){
		foreach($this->children as &$child){
			$table_name = $child['table_name'];

			if(isset($this->$table_name)){
				$children_array = $this->$table_name;
				foreach($children_array as &$c){
					$model = $child['class_name'];
					$c = $c instanceof $model ? $c : new $model($c);

					if(isset($child['join_table'])){ // there is an m:n intermediate table
						$c->create();
						\DB::insert($child['join_table'], [
							$child['join_index'] => $this->id,
							$child['index'] => $c->id
						]);
					} else { // children are on their own table, with just a foreign key to the parent object
						$c->$child['index'] = $this->id;
						$c->create();
					}
				}
			}
		}
	}

	/**
	 * @throws PhuskyException
	 */
	private function checkIfAllParentsExists(){
		foreach($this->parents as $p){
			$model = $p['class_name'];
			$index = $p['index'];
			$parent_id = $this->$index;
			$property_name = strtolower($model);

			if($parent_id && !$parent = $model::getById($parent_id)){ // parent doesn't exist, try to create from extended info
				throw new PhuskyException(get_called_class() . " index for $model ($index = $parent_id) doesn't have a correspondent record on table " . $p['table_name']);
			} elseif(!$parent_id && !isset($this->$property_name)){
				if($p['default'] !== false){
					$this->$index = $p['default'];
				} else {
					throw new PhuskyException(get_called_class() . " needs the $model property");
				}
			} elseif(!$parent_id && isset($this->$property_name)) {
				$parent = ($this->$property_name instanceof $model) ? $this->$property_name : new $model($this->$property_name);
				$parent->create();
				$this->$index = $parent->id;
			} else {
				debug_print_backtrace();
				throw new PhuskyException("Something went wrong, you shouldn't be here!");
			}
		}
	}

	/**
	 * @param \Closure $fn
	 * @return array
	 */
	public static function read(\Closure $fn){
		//$where = self::parseQuery($data);
		return self::modelizeArray(\DB::query("select * from " . self::getTableName() ." where  %l", $fn()));
	}

	/**
	 * @return bool
	 */
	public function update(){
		return true;
	}

	/**
	 * @return bool
	 */
	public function delete(){
		return true;
	}

	/**
	 * @param String $what
	 * @return Model
	 */
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
				$data = $model::read(function()use($r){
					$where = new \WhereClause('and');
					$where->add("{$r['index']}=%d", $this->id);
					return $where;
				});
			}

			$this->$what = $data;
			return $data;
		}
	}

	/**
	 * @param array $r
	 * @return Model[]
	 */
	protected static function getChildrenData(array $r){
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

	/**
	 * @param String $what
	 * @return bool|array
	 */
	private function hasParent(String $what){
		foreach($this->parents as $p){
			if(strtolower($p['class_name']) == strtolower($what)){
				return $p;
			}
		}
		return false;
	}

	/**
	 * @param String $what
	 * @return bool|array
	 */
	private function hasChild(String $what){
		foreach($this->children as $p){
			if(strtolower($p['class_name']).'s' == strtolower($what)){
				return $p;
			}
		}
		return false;
	}


	/**
	 * @param array $data
	 * @return Model
	 */
	private function modelize(array $data){
		$className = get_called_class();
		$model = new $className();
		foreach($data as $k => $e){
			$model->$k = $e;
		}
		return $model;
	}

	/**
	 * @param array $data
	 * @return Model[]
	 */
	private function modelizeArray(array $data){
		foreach($data as &$e){
			$e = self::modelize($e);
		}
		return $data;
	}

	/**
	 * @return Model
	 */
	public function output(){
		$that = clone $this;
		unset($that->parents);
		unset($that->children);
		foreach($that as $k => &$e){
			if(is_array($e)){
				foreach($e as &$w){
					$w = $w->output();
				}
			} elseif($e instanceof Model){
				$e = $e->output();
			}
		}
		return $that;
	}

	/**
	 * @return array
	 */
	public function getWritableData(){
		$columns = \DB::columnList($this->getTableName());
		$temp = [];
		foreach($columns as $column){
			isset($this->$column) && $temp[$column] = $this->$column;
		}
		return $temp;
	}
}