<?php


namespace Setsuna\Db;


/**
 * Base * 数据库操作类底层抽象接口
 */


interface  Base
{
	 function insert( $arr = NULL );

	 function delete();

	 function total();

	 function find( $param='' );

	 function select();

	 //function update( $arr = NULL );

	 function query( $sql , $executeArray = array() );

	 function dbSize() ;

	 function dbVersion() ;

	 function __call($methodName , $args);

	 //function field($args  , $Method = array(), $relateArray = array() );

}
