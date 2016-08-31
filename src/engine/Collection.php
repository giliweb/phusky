<?php namespace giliweb\phusky;
class Collection {
	private $data;
	public function __construct(array $data = []){
		$this->data = $data;
	}

	public function output(){
		$temp = $this->data;
		foreach($temp as &$e){
			$e = $e->output();
		}
		return $temp;
	}

	public function __get(String $what){
		foreach($this->data as &$e){
			$e->$what;
		}
		return $this->data;
	}

	public function getData(){
		return $this->data;
	}
}