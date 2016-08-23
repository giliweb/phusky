<?php namespace giliweb\phusky;
interface iModel {
	public static function getById(int $id);
	public function create();
	public static function read(array $data);
	public function update();
	public function delete();
}