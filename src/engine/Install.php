<?php namespace giliweb\phusky;
class Install {
	public static function init(){
		global $argv;
		Phusky::getInstance()->setup(
			[
				"base_path" => getcwd().'/',
				"absolute_class_path" => getcwd() . '/' . $argv[2] . '/',
				"db_host" => $argv[3],
				"db_name" => $argv[4],
				"db_user" => $argv[5],
				"db_password" => $argv[6]
			]
		);
		echo 'Done!';
	}
}