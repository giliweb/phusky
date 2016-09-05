<?php namespace giliweb\phusky;
class PhuskyException extends \Exception {

	const UNKOWN_ERROR = "Unkown error: %s";
	const RECORD_NOT_FOUND = "Cannot find %s with id %s";
	const RECORD_CREATION_ERROR = "Cannot create %s on DB\rERROR:%s";
	const DELETE_CHILDREN_ERROR = "Error on %s while delete of child %s:\rERROR:%s";
	const M_N_RELATION_CREATE_ERROR = "Error while creating m:n relation on %s with child %s:\rERROR:%s";
	const PARENT_DOESNT_EXIST_ERROR = "Error on %s: index for %s (%s = %s) doesn't have a correspondent record on table %s";
	const MISSING_PARENT_PROPERTY = "%s needs the %s property";
	const UPDATE_ERROR = "Error while update %s.\rERROR: %s";

	const OMEGA_ERROR = "You shouldn't be here, something went wrong. ERROR DATA: %s";

	public function __construct($code, array $data = []){
		$message = substr_count($code, '%') != count($data) ? $code : vprintf($code, $data);
		parent::__construct($message);
	}
}