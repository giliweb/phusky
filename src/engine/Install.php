<?php namespace giliweb\phusky;
class Install {
	public static function init(){
		Phusky::getInstance()->setup(self::getArguments());
		echo 'Done!';
	}

	private static function getArguments(){
		global $argv;
		$args = [
			"base_path" => getcwd().'/',
			"absolute_class_path" => getcwd() . '/',
			"db_host" => 'localhost',
			"db_name" => 'test',
			"db_user" => 'root',
			"db_password" => ''
		];

		for($i = 3; $i < count($argv); $i++){
			$temp = explode('=', $argv[$i]);
			$key = substr($temp[0], 1);
			switch($key){
				case 'path':
					$args['absolute_class_path'] = getcwd() . '/' . $temp[1] . '/';
					break;
				case 'dbhost':
					$args['db_host'] = $temp[1];
					break;
				case 'dbname':
					$args['db_name'] = $temp[1];
					break;
				case 'dbuser':
					$args['db_user'] = $temp[1];
					break;
				case 'dbpassword':
					$args['db_password'] = $temp[1] ;
					break;
				case 'help':
				case 'h':
					die(
"Usage:
    composer phusky_setup -path=classes_folder -dbhost=db_host -dbname=db_name -dbuser=db_user -dbpassword=db_password
List of supported parameters: 
    -path             The folder where generated classes files will be saved 
    -dbhost           Database host, default to localhost
    -dbname           Database name, default to test
    -dbuser           Database user, default to root
    -dbpassword       Database password, default to empty string
    -help             Shows this list
"
					);
					break;
				default:
					die("Parameter {$temp[0]} it not supported");
					break;
			}
		}
		return $args;
	}
}