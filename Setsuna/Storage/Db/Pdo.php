<?php

/**
 * BasePdo * PDO驱动基础操作类
 */

class Pdo implements Base
{
	//主数据库配置
	STATIC protected $master;

	//从数据库配置
	STATIC protected $slave;

	//初始化的时候是否要链接到数据库
	STATIC protected $isInitConn = FALSE;

	//写操作PDO对象
	STATIC protected $masterConn = NULL;

	//读操作PDO对象
	STATIC protected $slaveConn = NULL;

	//字符集
	STATIC protected $charset = DB_CHARSET;

	STATIC public $InsertId = NULL;

	//STATIC protected $CacheFieldPos='./RUNTIME/DbFieldCache/';

	//主挂掉了,临时主配置路径,如果修复了主库后,自动删掉此目录下文件!
	//STATIC protected $tempMasterPath ='./RUNTIME/DbFieldCache/tempMaster.php';

	//主挂掉了,临时主配置数组
	STATIC protected $tempMasterArr = array();

	STATIC protected $tabName;


	STATIC protected $Fields;

	STATIC protected $masterDown = FALSE;


	STATIC protected $MethodName = array(
						'where'  => '',
						'order'  => '',
						'limit'  => '',
						'group'  => '',
						'having' => ''
					);

	STATIC protected $YouField;



	 STATIC protected function construct($tabName)
	{
		if(is_null(self::$master))		//只调用一次
			self::setDbConfig();
		self::$tabName = self::$master['db_prefix'] . $tabName;
		self::$Fields  = self::getFields( self::$tabName );
	}

	//设置数据库配置参数
	STATIC protected function setDbConfig()
	{
		
		if (empty($GLOBALS['DB_OTHER']))		//切换数据库配置
			global $DB_MASTER,$DB_SLAVE;
		else {
			$DB_MASTER = $GLOBALS['DB_OTHER']['MASTER'];
			$DB_SLAVE  = $GLOBALS['DB_OTHER']['SLAVE'];
		}


		$comPareArray = array(
			'db_host',
			'db_user',
			'db_pass',
			'db_name',
			'db_port',
			'db_prefix'
		);


		$tempMasterPath = $_SERVER['SINASRV_CACHE_DIR'] . '/DbFieldCache/tempMaster.php';

		if(!file_exists($tempMasterPath)){
			//得到主配置信息
			if(is_array($DB_MASTER) && Debug::compareArray('DB_MASTER' , $DB_MASTER , $comPareArray) && !empty($DB_MASTER))
			{
				self::$master = $DB_MASTER;
			}

		} else  {
			self::$master = include $tempMasterPath ;
		}


		//如果没有从配置信息,那么主变成从
		if( !isset($DB_SLAVE) || !is_array($DB_SLAVE) ){
			self::$slave = array( $DB_MASTER );		//转换为二维数组
		} else  {


			if(!file_exists($tempMasterPath)){
				//从配置信息存在
				foreach($DB_SLAVE as $value){
					if(!Debug::compareArray('DB_SLAVE' , $value , $comPareArray) && !empty($value))
						return FALSE;
					$array[] = $value;
				}
					self::$slave = $array;
			} else  {
				//说明主挂掉了,直接包含
				$masterArr = include $tempMasterPath;
				foreach($DB_SLAVE as $value){
					if(!Debug::compareArray('DB_SLAVE' , $value , $comPareArray) && !empty($value))
						return FALSE;
					if($value != $masterArr)
						$array[] = $value;
				}
					self::$slave = $array;

			}
		}

		return TRUE;
	}


	//获取Master的写数据PDO
	STATIC protected function getWriteConn()
	{
		$tempMasterPath = $_SERVER['SINASRV_CACHE_DIR'] . '/DbFieldCache/tempMaster.php';
		if( self::IsResource( self::$masterConn ) )
			return self::$masterConn;
		self::$masterConn = ($conn = self::getMasterConnect(self::$master)) ? $conn : NULL;
		if( !is_object(self::$masterConn) || is_null(self::$masterConn) )
		{ 		//说明主库挂了!随机切一从库为主库
			self::$masterConn = self::getReadConn(TRUE);		//增加标志位,说明主库挂了
			if( self::IsResource( self::$masterConn ) ){ 	//	echo '主库挂了,尝试连接从库成功!';
				if( !file_exists($tempMasterPath) ){
					$String="<?php \n return " . var_export(self::$tempMasterArr,true) . "; \n ?" . '>';
					file_put_contents( $tempMasterPath , $String );
				}
				return self::$masterConn;
			}
		}
		if(file_exists($tempMasterPath))
			unlink($tempMasterPath);
		return self::$masterConn;
	}



	STATIC protected function IsResource($Obj)
	{
		if( !empty($Obj) && is_object($Obj) )
			return $Obj;
		else  
			return FALSE;
	}


	STATIC protected function getMasterConnect( $array )
	{

		if( self::IsResource( self::$masterConn ) )
			return self::$masterConn;

		$DSN = 'mysql:host=' . $array['db_host'] . ';' . 'port=' . $array['db_port'] . ';' . 'dbname=' . $array['db_name'];
		try{
			$pdo=new PDO( 
				$DSN , 
				$array['db_user'] ,
				$array['db_pass'], 
				array( 

					PDO::ATTR_ERRMODE =>   PDO::ERRMODE_EXCEPTION ,
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . self::$charset,
				)
			);
			if(is_object($pdo))
				return $pdo;
			else  
				return FALSE;
		}catch(PDOException $e){
			//主库挂掉了!这里线上环境是写入日志 //线下环境在抛出异常
			//MyException::Exceptions($e->getMessage() , $e->getTrace());
		}

		
	}

	//这个方法不能喝上面getMasterConnect共用,否则主挂了后,从库也变主库了
	STATIC protected function getSlaveConnect( $array )
	{
		if( self::IsResource( self::$slaveConn ) )
			return self::$slaveConn;
		try{	
				$DSN = 'mysql:host=' . $array['db_host'] . ';' . 'port=' . $array['db_port'] . ';' . 'dbname=' . $array['db_name'];
				$pdo=new PDO( 
					$DSN , 
					$array['db_user'] ,
				       	$array['db_pass'], 
					array( 
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . self::$charset,
					)
				);
			if(is_object($pdo))
				return $pdo;
			else  
				return FALSE;
		}catch(PDOException $e){
			//echo '从连接失败!!';
			//MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}

	//获取Slave读资源
	//$flag 主挂掉的标志位
	STATIC protected function getReadConn( $flag = FALSE )
	{
		//如果已经纯在直接返回
		if( self::IsResource(self::$slaveConn) ){
			return self::$slaveConn;
		} else  {

		//说明没有从配置,那么直接返回主的( 这个逻辑请参阅上面setDbConfig()方法中从库配置! )
		if(self::$slave[0]['db_host'] == self::$master['db_host'] && !is_null(self::$masterConn)){
	//		echo '没有设置从配置,从配置就是主配置!';
				//先执行赋值运算,然后判断是否是对象哦!
			return is_object(self::$slaveConn = self::$masterConn) ? self::$slaveConn : self::getWriteConn();
		}

		//到此,说明设置从配置,尝试从从库中随机取一个连接
		shuffle(self::$slave);
		self::$slaveConn = self::getSlaveConnect( self::$slave[0] );		//取其中一个,如果从配置只有一个那么也刚刚好

		if( self::IsResource(self::$slaveConn) )
		{
		//	echo '设置了从配置,随机取一个连接';
			if($flag)		//说明主挂掉了!随机选一个作为主
				self::$tempMasterArr = array_shift(self::$slave);
			return self::$slaveConn; 				//判断有没有链接成功
		} else {		
		//没有成功
			//echo '随机设置从连接没有成功!';
			//移除从配置中第一个
			array_shift(self::$slave);
			if( count(self::$slave) >  1){
			
				//echo '从不只一个,这里foreach遍历尝试连接!';
				//数组的顺序已经是随机的
				foreach(self::$slave as $slaveEs){
					self::$slaveConn = self::getSlaveConnect($slaveEs);
					if( self::IsResource(self::$slaveConn) )  
						return self::$slaveConn;			//所以直接返回,终止循环!
					else  
						MyException::Exceptions('请确认数据库配置正确!');

				}
			} else  {
				//p(self::$slave);
				//可能没有
				if( empty(self::$slave) ){
					//echo '从配置就一个已经连接失败了!这里return FALSE';
					return FALSE;
				}
				//从配置中就两个元素(之前移除一个了,现在还剩一个!)
				self::$slaveConn = self::getSlaveConnect(self::$slave[0]);
				if( self::IsResource(self::$slaveConn) )  {
					//echo '从配置就两个元素,现在这个已经连接成功!这里返回';
					return self::$slaveConn;
				}else  
					MyException::Exceptions('请确认从数据库配置正确!');
			}
		}
		}
	}

	//根据读写来判断
	STATIC protected function achieveConn( $isMaster = FALSE )
	{
		$conn =  $isMaster  ? self::$masterConn : self::$slaveConn ;

		if( !is_object($conn)  )
			$conn =  $isMaster  ? self::getWriteConn() : self::getReadConn() ;
		return $conn;

	}




	STATIC protected function dealArray( $array )
	{
		if(empty($array))
			return array();
		 $value=array();
		 foreach($array as $val){
			 $value[]=str_replace(array('"', "'"), '', $val);
		 }
		 return $value;
	 }




	STATIC protected function checkField($field )
	{
		if(Debug::compareArray( 'Field' , array_flip($field) , array_unique(self::$Fields) ))
			return $field;
	}


	STATIC private function checkValue($POST , $flag)
	{
		$array = array();
		foreach($POST as $key => $value){
			$key = strtolower( $key );
			if( in_array($key , self::$Fields) ){
				$array[$key] =  $flag ? stripcslashes(htmlspecialchars($value)) : $value;
			}
		}
		if(empty($array)) 
			MyException::Exceptions( '请确认 '.implode(',',array_keys($POST)).' 存在于表 '.self::$tabName.' 中<br />' );
		return $array;
	}



	public function insert( $arr = NULL , $isSpecialChar = TRUE )
	{
		//insert into bbs_user(username,password,jifen) values(?,?,? );
		$conn = self::achieveConn(TRUE);
		$array = empty($arr) ?  self::checkValue( $_POST , $isSpecialChar) : self::checkValue( $arr , $isSpecialChar) ;

		$field = implode(',', array_keys($array));
		$value = implode( ',' , array_fill(0, count($array), '?') );

		$sql = "insert into " . self::$tabName . "({$field}) values({$value})";
		return self::Eexecute( $conn, $sql , array_values($array) );

	}



	public function delete()
	{
		$conn = self::achieveConn(TRUE);
		$data = array();
		$args = func_get_args();
		if(count($args)>0){
			//没有用连贯操作,手动传了ID条件之
			$where = self::comWhere($args);
			$data  = $where["data"];
			$where = $where["where"];
		}else if(self::$MethodName["where"] != ""){
			//使用了连贯操作
			$where = self::comWhere(self::$MethodName["where"]);
			$data  = $where["data"];
			$where = $where["where"];
			
		}

		$order = self::$MethodName['order']   != '' ? ' ORDER BY ' . self::$MethodName['order'][0]  : ' ORDER BY ' . self::$Fields['_pk'] . ' ASC';
		$limit = self::$MethodName['limit']   != '' ?  self::comLimit(self::$MethodName["limit"])   : '';
		
		if( empty($where) && $limit==""){
			$where=' where ' . self::$Fields['_pk'] ."=''";
		}
		

		$sql='DELETE FROM ' . self::$tabName . "{$where} {$order} {$limit}";
	
		return self::Eexecute( $conn, $sql , $data );
	}


	function total()
	{
		$conn  = self::achieveConn();

		$data  = array();
		$args  = func_get_args();
		$where = '';
		if(count($args)>0){
			//没有用连贯操作,手动传了ID条件之
			$where = self::comWhere($args);
			$data  = $where['data'];
			$where = $where['where'];
		}else if( self::$MethodName['where'] != ""){
			//使用了连贯操作
			$where = self::comWhere(self::$MethodName["where"]);
			$data  = $where['data'];
			$where = $where['where'];
			
		}

		$field = self::$Fields['_pk'];
		$sql='SELECT count(' .$field. ') FROM ' . self::$tabName . $where;
		$result = self::Eexecute( $conn, $sql , $data , TRUE);
		return $result[0]['count('.$field.')'];
	}



	function find( $param='' )
	{

		$conn  = self::achieveConn();
		$field = empty(self::$YouField) ?  implode( ',', array_unique(self::$Fields) ) : self::$YouField;
		if($param == '')
		{
			$where = self::comWhere(self::$MethodName['where']) ;
			$data  = $where['data'];
			$where = self::$MethodName['where'] != '' ? $where['where'] : '';		

		}else{
			$where = ' where ' . self::$Fields['_pk'] . '=?'; 
			$data[]=$param;
		}

		//$order = self::$MethodName['order']   != '' ? ' ORDER BY ' . self::$MethodName['order'][0]  : ' ORDER BY ' . self::$Fields['_pk'] . ' ASC';
		$order  = self::$MethodName['order']  != '' ? ' ORDER BY ' . self::$MethodName['order'][0]  : '';

		$sql="SELECT {$field} FROM " . self::$tabName ."{$where}{$order} LIMIT 1";


		$result = self::Eexecute( $conn, $sql , $data , TRUE);
		return $result[0];


	}




	public function select()
	{
		$conn  = self::achieveConn();
		$field = empty(self::$YouField) ?  implode( ',', array_unique(self::$Fields) ) : self::$YouField;

		$data  = array();
		$args  = func_get_args();
		$where = '';
		if(count($args)>0){
			//没有用连贯操作,手动传了ID条件之
			$where = self::comWhere($args);
			$data  = $where['data'];
			$where = $where['where'];
		}else if( self::$MethodName['where'] != ""){
			//使用了连贯操作
			$where = self::comWhere(self::$MethodName["where"]);
			$data  = $where['data'];
			$where = $where['where'];
			
		}

		$order  = self::$MethodName['order']  != '' ? ' ORDER BY ' . self::$MethodName['order'][0]  : '';
		$limit  = self::$MethodName['limit']  != '' ?  self::comLimit(self::$MethodName["limit"])   : '';
		$group  = self::$MethodName['group']  != '' ? ' GROUP BY ' . self::$MethodName['group'][0]  : '';
		$having = self::$MethodName['having'] != '' ? ' HAVING ' . self::$MethodName['having'][0]   : '';

		if( strrpos($field , ' join ') )
			$sql="SELECT {$field}  {$where} {$group} {$having} {$order} {$limit}";
		else if ( strrpos($field , ' from ') )
			$sql="SELECT {$field}  {$group} {$having} {$order} {$limit}";
		else
			$sql="SELECT {$field} FROM " . self::$tabName . "{$where} {$group} {$having} {$order} {$limit}";

		return self::Eexecute( $conn, $sql , $data , TRUE);

	}



	public function update( $arr = NULL , $isSpecialChar = TRUE )
	{
		$conn = self::achieveConn(TRUE);
		$array = empty($arr) ?  self::checkValue( $_POST , $isSpecialChar) : self::checkValue( $arr , $isSpecialChar) ;

		if( is_array($array) ){
			if(array_key_exists(self::$Fields['_pk'] , $array)){
				$pkValue = $array[self::$Fields['_pk']];
				unset($array[self::$Fields['_pk']]);
			}

			$setField = '';
			foreach($array as $key => $value){
				$setField .= $key . '=?,';
				$data[]  = $value; 
			}
			$setField = rtrim($setField , ',');

		} else  {
			$setField = $array;
			$pkValue = '';
		}

		$order = self::$MethodName['order']   != '' ? ' ORDER BY ' . self::$MethodName['order'][0]  : ' ORDER BY ' . self::$Fields['_pk'] . ' ASC';
		$limit = self::$MethodName['limit']   != '' ?  self::comLimit(self::$MethodName["limit"])   : '';

		if(self::$MethodName['where'] != '')
		{
			$where = self::comWhere(self::$MethodName['where']);
			$sql = "update " . self::$tabName . ' set '. $setField .$where['where'];
			if(!empty($where['data']))
				$data = array_merge($data , $where['data']);
			$sql .= $order . $limit;
		} else  {
			$sql = "update " . self::$tabName . ' set '. $setField . ' where ' . self::$Fields['_pk'] . '=?';
			$data[] = empty($pkValue) ? '' : $pkValue;
		}


		return self::Eexecute( $conn, $sql , $data );

	}

	/**
	 *
	 * 这个query方法需要继续改写,优化
	 */
	public function query( $sql , $executeArray = array() )
	{
		if(defined('MEMCACHE_START')){
			$data = MyMemcache::getCache( $sql );
			if(!empty($data))	 return $data;
		} 
		$conn = strstr($sql , 'select') ?  self::achieveConn() : self::achieveConn(TRUE);
		try{
			$startTime = microtime(TRUE);
			$STMT = $conn->prepare( $sql );
			$result = empty($executeArray) ? $STMT->execute() : $STMT->execute( $executeArray );
			if($result){

				if(DEBUG && !empty($executeArray)){
					if( !empty($executeArray) ){
						$v  = array_fill( 0, count($executeArray) , '?' );
						$count = count($v);
						for($i=0 ; $i<$count ; $i++){
							//组织sql语句到DEBUG系统中去
							$sql = preg_replace('/\?/' , "'".$executeArray[$i]."'" , $sql ,1);
						}
					}
				}

				if( strstr($sql , 'select')  )
				{
					while( $row = $STMT->fetch(PDO::FETCH_ASSOC) ){
						$data[] = $row;
					}
					self::StopTime( $sql , $startTime );
					if(defined('MEMCACHE_START'))		//缓存结果集到Memcache中
						MyMemcache::addCache(self::$tabName , $sql , $data);
					return empty($data) ? NULL : $data;
				} else  {
					if( strstr( $sql , 'insert' ) ){
						self::StopTime( $sql , $startTime );
						return self::$InsertId = $conn->lastinsertid();
					} 
					self::StopTime( $sql , $startTime );
					return $STMT->rowCount();	//受影响行,这个只针对update,delete
				}
			}
		}catch(PDOException $e){
			if(DEBUG)
				MyException::Exceptions( '请确认您的SQL语句:' .$sql .'<br />'.$e->getMessage() , $e->getTrace() );
		}
	}

	//这个必须是抽象方法
	STATIC protected function Eexecute($conn , $sql , $executeArray = array() , $isSelect = FALSE)
	{
		try{
			if(defined('MEMCACHE_START')){
				$data = MyMemcache::getCache( $sql );
				if(!empty($data))	 return $data;
			} 
			$startTime = microtime(TRUE);
			$STMT = $conn->prepare($sql);
			$result = empty($executeArray) ? $STMT->execute() : $STMT->execute( $executeArray );
			if ( $result )
			{
				if(DEBUG){
					if( !empty($executeArray) )
					$SQL = self::comSQL( $sql , $executeArray);		//好方法
					$SQL = empty($SQL) ? $sql : $SQL ;
				}

				if( $isSelect  )
				{
					while( $row = $STMT->fetch(PDO::FETCH_ASSOC) ){
						$data[] = $row;
					}
					self::StopTime( $SQL , $startTime );
					if(defined('MEMCACHE_START'))		//缓存结果集到Memcache中
						MyMemcache::addCache(self::$tabName , $sql , $data);
					return empty($data) ? NULL : $data;
				} else  {
					if( strstr( $sql , 'insert' ) ){
						self::StopTime( $SQL , $startTime );
						return self::$InsertId = $conn->lastinsertid();
					} 
					self::StopTime( $SQL , $startTime );
					return $STMT->rowCount();	//受影响行,这个只针对update,delete
				}

			} else  {
				if(DEBUG)
					Debug::addmsg( '<font color="red">请检查您的SQL: ' . $sql .'</font>' , 2); 
			}
		}catch(PDOException $e){
			if(DEBUG)
				MyException::Exceptions( '请确认您的SQL语句:<b>' .$sql .'</b><br />'.$e->getMessage() , $e->getTrace() );
		}
	}



	STATIC protected function StopTime( $sql = NULL, $startTime )
	{
		if(DEBUG)
		{
			$stopTime = microtime(TRUE);
			$result   =  round( ($stopTime - $startTime ) , 4);
			Debug::addmsg('[用时<font color="red">'.$result.'</font>秒] - ' . $sql,2); 
		}
	}






	STATIC protected function getFields($tabName)
	{	
		$CacheFieldPos = $_SERVER['SINASRV_CACHE_DIR'] . '/DbFieldCache/';
		$conn = self::achieveConn(TRUE);

		$filedPath = $CacheFieldPos . md5('Reluctance' . $tabName ) . '.php' ;

		try{
			$stmt   = $conn->query('DESC '.$tabName);
			$result = $stmt->fetchAll( PDO::FETCH_ASSOC );

		}catch(PDOException $e){
			MyException::Exceptions('您传入的表名不正确,请确认!<br />'.$e->getMessage(),$e->getTrace());
		}

		if(DEBUG)
		{		//本地环境
			$fields = self::getFieldArray($result);
			if ( file_exists($filedPath) ) {		//当修改数据库中字段重新生成新的文件
				$fieldArr =  include $filedPath;
				if($fieldArr == $fields){
					return $fieldArr;
				}else  {
					unlink($filedPath);
				}
			}
		} else  {		//线上环境,可以少一个foreach循环
			if( file_exists($filedPath) )
				return include $filedPath;
			$fields = self::getFieldArray($result);
		}

		$tabName = md5( 'Reluctance' . $tabName );
		$FieldString="<?php \n return " . var_export($fields,true) . ";\n?".">" ;
		file_put_contents( $CacheFieldPos  . $tabName.'.php' , $FieldString );
		return $fields;
	}


	STATIC protected function getFieldArray($result)
	{
		foreach($result as $key=>$value){
			$fields[]=$value['Field'];
			if( $value['Key']   == 'PRI' )
				$fields['_pk']   = $value['Field'];
			if( $value['Extra'] == 'auto_increment' )
				$fields['_auto'] = $value['Field'];
		}
		return $fields;
	}


	/**
	 * 事务开始
	 */
	public function beginTransaction() 
	{
		$conn = self::achieveConn(TRUE);
		$conn->setAttribute(PDO::ATTR_AUTOCOMMIT, 0); 
		$conn->beginTransaction();
	}
	
	/**
	 * 事务提交
	 */
	public function commit() 
	{
		$conn = self::achieveConn(TRUE);
		$conn->commit();
		$conn->setAttribute(PDO::ATTR_AUTOCOMMIT, 1); 
	}
	
	/***
	 * 事务回滚
	 */
	public function rollBack() 
	{
		$conn = self::achieveConn(TRUE);
		$conn->rollBack();
		$conn->setAttribute(PDO::ATTR_AUTOCOMMIT, 1); 
	}

	/**
	 * 获取数据库使用大小
	 * @return	string		返回转换后单位的尺寸
	 */
	public function dbSize() 
	{
		$sql  = "SHOW TABLE STATUS FROM " . self::$master['db_name'];
		$sql .= " LIKE '".self::$master['db_prefix']."%'";
		$pdo  = self::achieveConn(TRUE);
		$stmt =$pdo->prepare($sql);  //准备好一个语句
		$stmt->execute();   //执行一个准备好的语句
		$size = 0;
		while($row=$stmt->fetch(PDO::FETCH_ASSOC))
			$size += $row["Data_length"] + $row["Index_length"];
		return tosize($size);
	}

	/**
	 * 数据库的版本
	 * @return	string		返回数据库系统的版本
	 */
	function dbVersion() 
	{
		$pdo  = self::achieveConn(TRUE);
		return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
	}



	STATIC protected function setMethodNull()
	{
		self::$MethodName = array( 'field' => '', 'where'  => '', 'order'  => '', 'limit'  => '', 'group'  => '', 'having' => '');
	}

	function __call($methodName , $args)
	{
		$methodName=strtolower($methodName);
		if( array_key_exists($methodName, self::$MethodName) )
		{
			if( empty($args[0]) || (is_string($args[0]) && trim($args[0]) === '' ))
				self::$MethodName[$methodName]="";
			else
				self::$MethodName[$methodName]=$args;

			if($methodName=="limit")
			{
				if($args[0]=="0")
					self::$MethodName[$methodName]=$args;
			}	
		} else {
			Debug::addmsg("<font color='red'>调用类".get_class($this)."中的方法{$methodName}()不存在!</font>");
		}

		return $this;
	}


	/**
	 *$Method = array(
		 'left'=>'puser',
		 array('id'=>'cid')
	 );
	 */

	function field($args  , $Method = array(), $relateArray = array() )
	{
		if(is_array($Method) && !empty($args) && !empty($Method) && !empty($relateArray))
		{
			$aTab = is_array($args) ? explode(',' , $args[0]) : explode(',',$args);
			$bTab = is_array($relateArray) ? explode(',',$relateArray[0]) : explode(',', $relateArray);
			$aTab = self::checkField($aTab);
			$count = count($aTab);
			foreach($bTab as $value){
				if(in_array($value , $aTab))
					$aTab[] = 'b.'. $value . ' as b_' . $value;
				else  
					$aTab[] = 'b.' . $value;
			}
			for($i=0; $i<$count; $i++)
				$aTab[$i] = 'a.' . $aTab[$i];
			$condition = '';
			$field = implode( ',', $aTab );

			if( array_key_exists('left', $Method) ){			//左关联
				if( is_array( $Method[0] ) )
				{
					foreach($Method[0] as $key => $value)
						$condition .= ' on a.' . $key .'='. 'b.' . $value;
				} else  {
					$condition .= $Method[0];
				}
				$field .=' from ' . self::$tabName . ' as a left join ' . self::$master['db_prefix'] . array_shift($Method) . ' as b ' . $condition;
				self::$YouField =  $field ;
			} else  if (array_key_exists('right', $Method)){		//右关联
				if( is_array( $Method[0] ) )
				{
					foreach($Method[0] as $key => $value)
						$condition .= ' on a.' . $key .'='. 'b.' . $value;
				} else  {
					$condition .= $Method[0];
				}
				$field .=' from ' . self::$tabName . ' as a right join ' . self::$master['db_prefix'] . array_shift($Method) . ' as b ' . $condition;
				self::$YouField =  $field ;
			} else  { 							//内联
				if( is_array( $Method[1] ) )
				{
					$condition = ' where ';
					foreach($Method[1] as $key => $value){
						$condition .= ' a.' . $key .'='. 'b.' . $value ;
					}

				} else  {
					$condition .= ' where '.$Method[1];
				}

				$field .=  ' from ' . self::$master['db_prefix'] . array_shift($Method) . ' as b,'.self::$tabName.' as a ' .$condition;
				self::$YouField =  $field ;

			}
		} else  {
			if(!is_array($args) && !empty($args))
				self::$YouField = $args;
			else
				self::$YouField = count($args) > 1  ?  implode(',',$args) : $args[0];
		}
		return $this;
	}


	STATIC protected function comWhere($args)
	{
		$where=" WHERE ";
		$data=array();
		
		if(empty($args))
			return array("where"=>"", "data"=>$data);

		foreach($args as $option) {
			if(empty($option)){
				$where = ''; //条件为空，返回空字符串；如'',0,false 返回： '' //5
				continue;
			}else if( is_string($option) ){
				if (is_numeric($option[0])) {
					$option = explode(',', $option); //3
					$where .= self::$Fields['_pk'] . " IN(" . implode(',', array_fill(0, count($option), '?')) . ")";
					$data=$option;
					continue;
				} else {
					$where .= $option; //2
					continue;
				}	
			}else if( is_numeric($option) ){
				$where  .= self::$Fields['_pk'] . '=?';   //1
				$data[0] = $option;
				continue;
			}else if( is_array($option) ){
				if (isset($option[0])) {
					//如果是1维数组，array(1,2,3,4);  //4
					$where .= self::$Fields['_pk'] .' IN(' . implode(',', array_fill(0, count($option), '?')) . ")";
					$data=$option;
					continue;
				}
				
				
				foreach($option as $k => $v ){
					if (is_array($v)) {
						// 5、如果是2维数组，array('uid'=>array(1,2,3,4))
						$where .= "{$k} IN(" . implode(',', array_fill(0, count($v), '?')) . ")";					
						foreach($v as $val){
							$data[] = $val;
						}
					 } else if (strpos($k, ' ')) {
						 // 6、array('add_time >'=>'2010-10-1')，条件key中带 > < 符号
						 $where .= "{$k}?";
						 $data[] = $v;
					 } else if (isset($v[0]) && $v[0] == '%' && substr($v, -1) == '%') {
						 // 7、array('name'=>'%中%')，LIKE操作
						 $where .= "{$k} LIKE ?";
						 $data[] = $v;
					} else {
						// 8、array('res_type'=>1)
						$where  .= "{$k}=?";
						$data[]  = $v;
					}
					$where.=" AND ";
				}
			
				$where =rtrim($where, "AND ");
				$where.=" OR ";
				continue;
			}
		}
		$where=rtrim($where, "OR ");
		return array("where"=>$where, "data"=>$data);
	}




	STATIC protected function comSQL($sql , $paramsArr)
	{
		if (false === strpos($sql, '?') || count($paramsArr) == 0) 
			return $sql;
		if (false === strpos($sql, '%')) {
			 $sql = str_replace('?', "'%s'", $sql); 		// 不存在%，替换问号为s%，进行字符串格式化
			 array_unshift($paramsArr, $sql);
			 $data =  call_user_func_array('sprintf', $paramsArr); 	//调用函数和所用参数
			 return rtrim($data);
		}
	}

		 	     
	STATIC protected function comLimit($args)
	{
		if(count($args)==2){
			return " LIMIT {$args[0]},{$args[1]}";
		}else if(count($args)==1){
			if(strstr($args[0] , 'limit') || strstr( $args[0] , 'LIMIT'))
				return  $args[0];
			else  
				return " LIMIT {$args[0]}";
		}else{
			return '';
		}	
	}

}
