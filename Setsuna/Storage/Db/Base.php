<?php


namespace Setsuna\Storage\Db;


/**
 * Base * 数据库操作类底层抽象接口
 */


abstract class BaseDao
{

	function __construct($db) {
		$this->db = $db;
	}

}
