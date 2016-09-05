<?php namespace giliweb\phusky;
class Model implements iModel {

	/**
	 * Model constructor.
	 * @param array $data
	 */
	public function __construct(array $data = NULL){
		if(!is_null($data)){
			foreach($data as $k => $e){
				$this->$k = $e;
			}
		}
		return $this;
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
	 * @throws PhuskyException
	 */
	public static function getById(int $id, array $fields = NULL){
		$fields = is_array($fields) ? implode($fields) : '*';
		$data = \DB::queryFirstRow("select $fields from " . self::getTableName() . " where id = '$id'");
		if(is_null($data)){
			throw new PhuskyException(PhuskyException::RECORD_NOT_FOUND, [get_called_class(), $id]);
		}
		$o = self::modelize($data);
		return $o;
	}

	/**
	 * @throws PhuskyException
	 * @return bool;
	 */
	public function create(){
		if(isset($this->id)){
			return $this->update();
		}
		\DB::startTransaction();
		// check if parents exists
		$this->checkIfAllParentsExist();

		// write $this on DB
		try {
			$r = \DB::insertUpdate($this->getTableName(), $this->getWritableData());
			$this->id = \DB::insertId();
		} catch (\Exception $e){
			\DB::rollback();
			throw new PhuskyException(PhuskyException::RECORD_CREATION_ERROR, [get_called_class(), print_r($e, true)]);
		}

		// create children
		$this->handleChildren();
		\DB::commit();
		return $this;
	}

	/**
	 *
	 */
	private function handleChildren(){
		foreach($this->children as &$child){
			$table_name = $child['table_name'];
			if(isset($this->$table_name)){
				$children_array = $this->$table_name; // new children array

				if(count($children_array) <= 0){ // delete all children
					try {
						if(isset($child['join_table'])) { // there is an m:n intermediate table
							\DB::delete($child['join_table'], "{$child['join_index']}=%d", $this->id);
						} else { // children are on their own table, with just a foreign key to the parent object
							\DB::delete($child['table_name'], "{$child['index']}=%d", $this->id);
						}
					} catch (\Exception $e){
						throw new PhuskyException(PhuskyException::DELETE_CHILDREN_ERROR, [get_called_class(), print_r($child, true), print_r($e, true)]);
					}					
				} else { // match new children against new children, maybe delete or add some of them

					$children_table_name = $child['table_name'];
					// query the DB to get the old children
					if(isset($this->old_instance->$children_table_name)){
						$old_children_array = $this->old_instance->$children_table_name;
						// remove old child if not is present in $children_array
						foreach($old_children_array as $old_child){
							if($this->findInstanceInArray($old_child, $children_array) === false){
								if(isset($child['join_table'])) { // there is an m:n intermediate table
									$child_index = $child['index'];
									try {
										\DB::query("delete from {$child['join_table']} where {$child['index']}=%d and {$child['join_index']}=%d", $old_child->$child_index, $this->id);
									} catch (\Exception $e){
										throw new PhuskyException(PhuskyException::DELETE_CHILDREN_ERROR, [get_called_class(), print_r($child, true), print_r($e, true)]);
									}

								} else { // children are on their own table, with just a foreign key to the parent object
									$old_child->delete();
								}
							} else {
								//echo "found and confirmed\r";
							}
						}
					}

					// add or update new children
					foreach($children_array as &$c){
						$model = $child['class_name'];
						$c = $c instanceof $model ? $c : new $model($c);
						if(isset($child['join_table'])){ // there is an m:n intermediate table
							$child_index = $child['index'];
							$c->create();
							try {
								\DB::insertUpdate($child['join_table'], [
									$child['join_index'] => $this->id,
									$child['index'] => $c->id
								]);
							} catch(Exception $e){
								throw new PhuskyException(PhuskyException::M_N_RELATION_CREATE_ERROR, [get_called_class(), print_r($child, true), print_r($e, true)]);
							}
							$c = $model::getById($c->id);
						} else { // children are on their own table, with just a foreign key to the parent object
							$child_index = $child['index'];
							$c->$child_index = $this->id;
							$c->create();
						}
					}
				}
				$this->$table_name = array_values($children_array);
			}
		}
	}

	/**
	 * @throws PhuskyException
	 */
	private function checkIfAllParentsExist(){
		foreach($this->parents as $p){
			$model = $p['class_name'];
			$index = $p['index'];
			$parent_id = $this->$index;
			$property_name = strtolower($model);

			if($parent_id && !$parent = $model::getById($parent_id)){ // parent doesn't exist, try to create from extended info
				throw new PhuskyException(PhuskyException::PARENT_DOESNT_EXIST_ERROR, [get_called_class(), $model, $index, $parent_id, $p['table_name']]);
			} elseif(!$parent_id && !isset($this->$property_name)){ //
				if($p['default'] !== false){
					$this->$index = $p['default'];
				} else {
					throw new PhuskyException(PhuskyException::MISSING_PARENT_PROPERTY, [get_called_class(), $model]);
				}
			} elseif(!$parent_id && isset($this->$property_name)) {
				$parent = ($this->$property_name instanceof $model) ? $this->$property_name : new $model($this->$property_name);
				$parent->create();
				$this->$index = $parent->id;
				$this->$property_name = $parent;
			} elseif($parent_id && $parent){
				$this->$property_name = $parent;
			} else {
				//debug_print_backtrace();

				throw new PhuskyException(PhuskyException::OMEGA_ERROR, [print_r($p, true)]);
			}
		}
	}

	/**
	 * @param \Closure $fn
	 * @return array
	 */
	public static function read(\Closure $fn = NULL){
		$r = \DB::query("select * from " . self::getTableName() ." where  %l", !is_null($fn) ? $fn() : NULL);
		$r = new Collection(self::modelizeArray($r));
		return $r;
	}

	/**
	 * @return bool
	 * @throws PhuskyException
	 */
	public function update(){

		// handle parents
		$this->checkIfAllParentsExist();

		// handle $this
		try {
			$r = \DB::update($this->getTableName(), $this->getWritableData(), "id=%d", $this->id);
		} catch(\Exception $e){
			throw new PhuskyException(PhuskyException::UPDATE_ERROR, [get_called_class(), print_r($this, true)]);
		}


		// handle children
		$this->handleChildren();
		return $this;
	}

	/**
	 * @return bool
	 */
	public function delete(){
		// delete children too
		$this->deleteChildren();
		// finally, delete this
		return \DB::delete(self::getTableName(), "id=%d", $this->id);
	}

	/**
	 * @return bool
	 */
	private function deleteChildren(){
		foreach($this->children as $child) {
			$table_name = $child['table_name'];
			if(isset($child['join_table'])) { // m:n relationship
				// this should be blank
			} else { // simple relationship
				\DB::query("delete from $table_name where {$child['index']}=%d", $this->id);
			}
		}
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
			$this->old_instance->$what = $data;
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
		$model->old_instance = new $className();
		foreach($data as $k => $e){
			$model->$k = $e;
			$model->old_instance->$k = $e;
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
		unset($that->old_instance);
		foreach($that as $k => &$e){
			if(is_array($e)){
				foreach($e as &$w){
					$w = $w !== false ? $w->output() : false;
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

	/**
	 * @param Model $instance
	 * @param array $array
	 * @return bool|mixed
	 */
	private function findInstanceInArray(Model $instance, array $array){
		foreach($array as $k => $e){
			if($e->id == $instance->id){
				return $e;
			}
		}
		return false;
	}


}