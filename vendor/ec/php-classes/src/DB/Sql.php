<?php 

namespace ec\DB;

class Sql {

	const HOSTNAME      = "127.0.0.1";
	const USERNAME      = "root";
	const PASSWORD      = "";
	const DBNAME        = "db_ecommerce";
	const DB_CONNECTION = "mysql";
	const DB_PORT       = "3306"; 

	private $conn;

	public function __construct()
	{

		$this->conn = new \PDO(
			Sql::DBDRIVER.":dbname=".Sql::DBNAME.";charset=utf8;host=".Sql::HOSTNAME.';'.Sql::DB_PORT, 
			Sql::USERNAME,
			Sql::PASSWORD,
			[\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
		);

	}

	private function setParams($statement, $parameters = array())
	{

		foreach ($parameters as $key => $value) {
			
			$this->bindParam($statement, $key, $value);

		}

	}

	private function bindParam($statement, $key, $value)
	{

		$statement->bindParam($key, $value);

	}

	public function query($rawQuery, $params = array())
	{

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

	}

	public function select($rawQuery, $params = array()):array
	{

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);

	}

}

 ?>