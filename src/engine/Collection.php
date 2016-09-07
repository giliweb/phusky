<?php namespace giliweb\phusky;
class Collection {
	private $data;

	/**
	 * Collection constructor.
	 * @param array $data
	 */
	public function __construct(array $data = []){
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function output(){
		$temp = $this->data;
		foreach($temp as &$e){
			$e = $e->output();
		}
		return $temp;
	}

	/**
	 * @param String $what
	 * @return array
	 */
	public function __get(String $what){
		foreach($this->data as &$e){
			$e->$what;
		}
		return $this->data;
	}

	/**
	 * @return array
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 *
	 */
	public function create(){
		foreach($this->data as $e){
			$e->create();
		}
	}

	/**
	 *
	 */
	public function update(){
		foreach($this->data as $e){
			$e->update();
		}
	}

	/**
	 *
	 */
	public function delete(){
		foreach($this->data as $e){
			$e->delete();
		}
	}

	/**
	 * @param Model $o
	 */
	public function add(Model $o){
		$this->data []= $o;
	}
}