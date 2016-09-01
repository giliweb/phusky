<?php namespace giliweb\phusky;
class PhuskyException extends \Exception {

	const UNKOWN_ERROR = "Unkown error: %s";


	public function __construct($code, array $data = []){
		$message = substr_count($code, '%') != count($data) ? $code : vprintf($code, $data);
		parent::__construct($message);
	}
}