<?php namespace giliweb\phusky;
interface iModel {
	public static function getById(int $id);
	public function create();
	public static function read(\Closure $fn);
	public function update();
	public function delete();
}