<?php

class Database
{
	const name = 'testDb';

	private $db;

	public function init()
	{
		$this->db = new PDO('sqlite:'.self::name.'.db', '', '', [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		]);
		$this->createTables();
		$stmt = $this->db->query('SELECT 1 FROM construction_stages LIMIT 1');
		if (!$stmt->fetchColumn()) {
			$this->loadData();
		}
		return $this->db;
	}

	private function createTables()
	{
		$sql = file_get_contents('database/structure.sql');
		$this->db->exec($sql);
	}

	private function loadData()
	{
		$sql = file_get_contents('database/data.sql');
		$this->db->exec($sql);
	}
}