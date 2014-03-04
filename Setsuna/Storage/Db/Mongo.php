<?php

use Setsuna\Db\Base;
use Setsuna\Core\MyExpection;

/**
 * BaseMongodb * MongoDB基础类
 * 如果此类方法不能满足需求,有三个Mongo接口可完全实现所有操作方法,只要你会用:
 * 	$this->getMongo($isMaster),$this->getMongoDB($isMaster);$this->getCollection($isMaster);	//$isMaster默认是true(主库)
 */


class Mongo 
{
	static private $masterConf 	= array();
	static private $slaveConf 	= array();
	static private $dbConf 		= array();
	static private $masterConn;
	static private $slaveConn;
	static private $tabName;
	static private $dbName 		= NULL;
	static private $cursorObj;
	static private $profile 	= 1;			//慢查询开关 0 , 1 , 2
	static protected $MethodName = array(
					'field'  => '',		//OK
					//'skip'  => '',	//OK	//已经整合到limit中了
					'where'  => '',		//OK
					'order'  => '',		//OK
					'limit'  => '',		//OK
					'group'  => '',
					'sort' => '',		//OK
				);
	static private $Comparison = array(
					'>'=>'gt',
					'>='=>'gte',
					'<'=>'lt',
					'<='=>'lte',
					'!='=>'ne',
					'neq'=>'ne',
					'ne'=>'ne',
					'gt'=>'gt',
					'egt'=>'gte',
					'gte'=>'gte',
					'lt'=>'lt',
					'elt'=>'lte',
					'lte'=>'lte',
					'in'=>'in',
					'nin'=>'nin'
				);


	static private $ConditionBedeck = array(
					'$mod',
					'$ne',
					'$nne',
					'$or',
					'$nor',
					'$size',
					'$where',
					'$inc',
					'$set',
					'$unset',
					'$push',
					'$pop',
					'$addToSet',
					'$pull',
					'$pullAll',
				);


	/**
	 * __construct 受保护的__construct用户单例模式
	 * 
	 * @Access protected
	 * @Return void
	 */
	protected function __construct() {}


	/**
	 * setDbConfig 设置Mongo数据库配置文件到本类静态变量中
	 * 
	 * @Access private
	 * @Return void
	 */
	static private function setDbConfig()
	{
		if (empty($GLOBALS['DB_OTHER'])){		//Action中切换数据库用
			global $DB_MASTER,$DB_SLAVE;
		}else {
			$DB_MASTER = $GLOBALS['DB_OTHER']['MASTER'];
			$DB_SLAVE  = $GLOBALS['DB_OTHER']['SLAVE'];
		}

		//没有设置从,只有一个主
		if (!isset($DB_SLAVE) || !is_array($DB_SLAVE))
			$DB_SLAVE = array( $DB_MASTER );
		self::$masterConf = $DB_MASTER;		//初始化主配置
		self::$slaveConf  = $DB_SLAVE;		//初始化从配置
		self::$dbConf[$DB_MASTER['db_host'].':'.$DB_MASTER['db_port']] = $DB_MASTER;

		foreach ($DB_SLAVE as $key => $value)
			self::$dbConf[$value['db_host'] . ':' . $value['db_port']] = $value;
	}



	/**
	 * construct 虚拟__construct(),实现单例模式
	 * 
	 * @Param $tabName 
	 * @Access protected
	 * @Return void
	 */
	static protected function construct($tabName)
	{
		if (!class_exists('mongo'))
			MyException::Exceptions('请先安装PHP下Mongo驱动模块');
		if (empty(self::$dbConf))
			self::setDbConfig();
		self::$tabName = self::$masterConf['db_prefix'] . $tabName ;
	}



	/** 					(多余的)
	static private function getTabName($tabName)
	{
		self::$prefix  = self::$prefix ? '' : self::$masterConf['db_prefix'];
		self::$tabName = self::$prefix . $tabName ;
	}
	 */


	/**
	 * getMasterConfKey 得到为主库的键值	(多余的)
	 * 
	 * @Param $HostsInfo 
	 * @Access private
	 * @Return void
	static private function getMasterConfKey($HostsInfo)
	{
		if ( is_array($HostsInfo) ){
			foreach ($HostsInfo as $key => $confValue)
			{
				if ($confValue['state'] == 1 && $confValue['health'] == 1)
					return $key;
			}
		}
		return FALSE;
	}
	 */


	/**
	 * resetConf 当遇到故障时,重置主从配置,(貌似这个是多余的)
	 * 
	 * @Access private
	 * @Return void
	static private function resetConf($masterKey)
	{
		self::$masterConf = self::$dbConf[$masterKey];
		unset(self::$dbConf[$masterKey]);
		self::$slaveConf = self::$dbConf;
	}
	 */


	/**
	 * getMasterConn 得到主连接对象,用于增,删,改 , 也可以查(少用)
	 * 
	 * @Access private
	 * @Return void
	 */
	static private function getMasterConn()
	{
		if (empty(self::$masterConn)){
			try{
				$_server = self::getDSN(self::$dbConf) ;
				$paramConnect = array( 
					'replicaSet' => 'myReplSet',		//副本集自动选举主节点,并返回主的链接资源
				);
				//连接Mongo,无论里面是从还是主,最终返回的资源都是主
				return self::$masterConn = new Mongo($_server ,$paramConnect);
			}catch(MongoConnectionException $e){
				MyException::Exceptions($e->getMessage() , $e->getTrace());
			}
		}

		return self::$masterConn;
	}

	/**
	 * getSlaveConn 得到从连接对象,用户查,
	 * 
	 * @Param $isSetSlaveOk 		//从是否开启find()功能即是否可以查,默认为真
	 * @Access private
	 * @Return void
	 */
	static private function getSlaveConn( $isSetSlaveOk = true)
	{
		if (empty(self::$slaveConn)){
			try{
				$mongo = self::getMasterConn();
				$slaveSocket = $mongo->switchSlave(true);
				$_server =  self::getDSN( array(self::$dbConf[$slaveSocket]) );
				self::$slaveConn = new Mongo($_server);
				$isSetSlaveOk ? self::$slaveConn->setSlaveOkay(true) : self::$slaveConn->setSlaveOkay(FALSE);
			}catch(MongoConnectionException $e){
				MyException::Exceptions($e->getMessage() , $e->getTrace());
			}
		}
		return self::$slaveConn;
	}


	static private function getDSN( $Conf )
	{
		if (is_array($Conf)){
			$_server = 'mongodb://';
			foreach ($Conf as $key=>$value)				//副本集故障自动修复实现
				$_server .= $value['db_user'] . ':' . $value['db_pass'] . '@' . $value['db_host']. ':'. $value['db_port'] . ',';
			return	rtrim($_server , ',');
		}
	}







	/**
	 * getId 获取_id值
	 * 
	 * @Param $arrMongoCell 一条Mongo数据的数组
	 * @Access private
	 * @Return void
	 */
	static private function getId($arrMongoCell)
	{
		return $arrMongoCell['_id'] -> {'$id'};
	}


	/**
	 * setId 设置_id,一般情况,系统默认添加_id,有时时候我们需要手动设置
	 * 
	 * @Param $id 
	 * @Access private
	 * @Return void
	 */
	static private function setId($id)
	{
		return new MongoId($id); 		//必须经过Mongo自带的类
	}






	/**		//多余了,可以直接使用$this->getMongo()->listDBs();
	 * listDBs 查看所有数据库以及数据库大小,兼容listDBs();
	 * 
	 * @Access public
	 * @Return void
	public function listDBs()
	{
		$mongo = $this->getMongo(true);
		return $mongo->listDBs();
	}
	 */

	/**		//多余了,可以直接使用$this->getMongo()->getHosts();
	 * getHosts 查看副本集主从状态信息和资源信息,兼容getHosts()
	 * 
	 * @Access public
	 * @Return void
	public function getHosts()
	{
		$mongo = self::getMongo(true);
		return $mongo->getHosts();
	}
	 */

	/**
	 * getMongo 外部调用获取Mongo对象接口
	 *
	 * @Access public
	 * @Return void
		public bool Mongo::close ( void )		//获取Mongo其可以使用Mongo下所有方法,详细请参阅手册
		public bool Mongo::connect ( void )
		protected bool Mongo::connectUtil ( void )
		Mongo::__construct ([ string $server = "mongodb://localhost:27017" [, array $options = array("connect" => true) ]] )
		public array Mongo::dropDB ( mixed $db )
		public MongoDB Mongo::__get ( string $dbname )
		public array Mongo::getHosts ( void )
		public static int Mongo::getPoolSize ( void )
		public string Mongo::getSlave ( void )
		public bool Mongo::getSlaveOkay ( void )
		public array Mongo::listDBs ( void )
		public array Mongo::poolDebug ( void )
		public MongoCollection Mongo::selectCollection ( string $db , string $collection )
		public MongoDB Mongo::selectDB ( string $name )
		public static bool Mongo::setPoolSize ( int $size )
		public bool Mongo::setSlaveOkay ([ bool $ok = true ] )
		public string Mongo::switchSlave ( void )
		public string Mongo::__toString ( void )
	 */
	public function getMongo( $isMaster = true )
	{
		return $isMaster ? self::getMasterConn() : self::getSlaveConn();
	}


	/**
	 * getMongoDB 			//获取DB对象,默认是数据库配置中的库
	 * 
	 * @Param 	string	 $dbName //是否切换数据库
	 * @Param $isMaster 		 //是否是主
	 * @Access public
	 * @Return void
	 *				//获取MongoDB对象,可以使用MongoDB下所有方法,详细请参阅手册
		public array MongoDB::authenticate ( string $username , string $password )
		public array MongoDB::command ( array $command )
		MongoDB::__construct ( Mongo $conn , string $name )
		public MongoCollection MongoDB::createCollection ( string $name [, bool $capped = FALSE [, int $size = 0 [, int $max = 0 ]]] )
		public array MongoDB::createDBRef ( string $collection , mixed $a )
		public array MongoDB::drop ( void )
		public array MongoDB::dropCollection ( mixed $coll )
		public array MongoDB::execute ( mixed $code [, array $args = array() ] )
		public bool MongoDB::forceError ( void )
		public MongoCollection MongoDB::__get ( string $name )
		public array MongoDB::getDBRef ( array $ref )
		public MongoGridFS MongoDB::getGridFS ([ string $prefix = "fs" ] )
		public int MongoDB::getProfilingLevel ( void )
		public bool MongoDB::getSlaveOkay ( void )
		public array MongoDB::lastError ( void )
		public array MongoDB::listCollections ( void )
		public array MongoDB::prevError ( void )
		public array MongoDB::repair ([ bool $preserve_cloned_files = FALSE [, bool $backup_original_files = FALSE ]] )
		public array MongoDB::resetError ( void )
		public MongoCollection MongoDB::selectCollection ( string $name )
		public int MongoDB::setProfilingLevel ( int $level )
		public bool MongoDB::setSlaveOkay ([ bool $ok = true ] )
		public string MongoDB::__toString ( void )
	 
	 */
	public function getMongoDB( $isMaster = true , $dbName = '' )
	{
		$mongo = $this->getMongo( $isMaster );
		$dbName = empty($dbName) ? (empty(self::$dbName) ? self::$masterConf['db_name'] : self::$dbName) : $dbName;
		return $mongo->$dbName;
	}



	/**
	 * switchDB 切换数据库
	 * 
	 * @Param $dbName 
	 * @Access public
	 * @Return void
	 */
	public function switchDB($dbName)
	{
		self::$dbName = $dbName;
		return $this;
	}


	/**
	 * getCollection 获取集合对象方法,默认是数据库配置中的库和实例化的表名,比如D('user'),就是 $prefix . "user"表
	 * 
	 * @Param $isMaster 
	 * @Param 	string	 $dbName 
	 * @Param 	string	 $tabName 
	 * @Access public
	 * @Return void
		public mixed MongoCollection::batchInsert ( array $a [, array $options = array() ] )
		public MongoCollection::__construct ( MongoDB $db , string $name )
		public int MongoCollection::count ([ array $query = array() [, int $limit = 0 [, int $skip = 0 ]]] )
		public array MongoCollection::createDBRef ( array $a )
		public array MongoCollection::deleteIndex ( string|array $keys )
		public array MongoCollection::deleteIndexes ( void )
		public array MongoCollection::drop ( void )
		public bool MongoCollection::ensureIndex ( array $keys [, array $options = array() ] )
		public MongoCursor MongoCollection::find ([ array $query = array() [, array $fields = array() ]] )
		public array MongoCollection::findOne ([ array $query = array() [, array $fields = array() ]] )
		public MongoCollection MongoCollection::__get ( string $name )
		public array MongoCollection::getDBRef ( array $ref )
		public array MongoCollection::getIndexInfo ( void )
		public string MongoCollection::getName ( void )
		public bool MongoCollection::getSlaveOkay ( void )
		public array MongoCollection::group ( mixed $keys , array $initial , MongoCode $reduce [, array $options = array() ] )
		public mixed MongoCollection::insert ( array $a [, array $options = array() ] )
		public mixed MongoCollection::remove ([ array $criteria = array() [, array $options = array() ]] )
		public mixed MongoCollection::save ( array $a [, array $options = array() ] )
		public bool MongoCollection::setSlaveOkay ([ bool $ok = true ] )
		public string MongoCollection::__toString ( void )
		public bool MongoCollection::update ( array $criteria , array $newobj [, array $options = array() ] )
		public array MongoCollection::validate ([ bool $scan_data = FALSE ] )
	 */
	public function getCollection ( $isMaster = true , $dbName = '' , $tabName = '')
	{
		$mongo   = $this->getMongo($isMaster);
		//$dbName  = empty($dbName)  ? self::$masterConf['db_name'] : $dbName;
		$dbName = empty($dbName) ? ( empty(self::$dbName) ? self::$masterConf['db_name'] : self::$dbName ) : $dbName;
		$tabName = empty($tabName) ? self::$tabName : $tabName ;
		return $mongo->$dbName->$tabName;
	}



	public function lastError()
	{
		$Mongo = $this->getMongoDB();
		return $Mongo->lastError();
	}



	function __get($param)
	{
		echo $param;
	}

	/**
	 * insert Insert方法
	 * 
	 * @Param $arrValue 		//要插入的的值,默认接收$_POST,一般情况下$_POST是直接从表单提交过来,而我们需要处理下
	 * @Param 	array	 $options=array(
						  'safe'=>1,
						  'fsync'=>1,
						  'timeout'=>'您指定的值(单位:milliseconds)'
					  ) 
	 * @Access public
	 * @Return void
	 */
	public function insert( $arrValue = NULL , $options = array())
	{
		try {
			$arrValue = empty($arrValue) ? $_POST : $arrValue;
			$collection = $this->getCollection(true);
			if (empty($arrValue)) throw new MongoCursorException('请传入正确的数组参数或者接收$_POST!');
			return empty($options) ? $collection->insert($arrValue) : $collection->insert($arrValue , $options);

		} catch(MongoCursorException $e) {
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}

		
	}

	/**
	 * save Save方法
	 * 
	 * @Param $arrValue 		//要插入的的值,默认接收$_POST
	 * @Param 	array	 $options=array('safe'=>1,'fsync'=>1,'timeout'=>'您指定的值(单位:milliseconds)') 
	 * @Access public
	 * @Return void
	 */
	public function save( $arrValue = NULL , $options = array())
	{
		try {
			$arrValue = empty($arrValue) ? $_POST : $arrValue;
			$collection = $this->getCollection(true);
			if (empty($arrValue)) throw new MongoCursorException('请传入正确的数组参数或者接收$_POST!');
			return empty($options) ? $collection->save($arrValue) : $collection->save($arrValue , $options);

		} catch(MongoCursorException $e) {
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}

		
	}



	/**
	 * checkIDExists 检测并设置_id
	 * 
	 * @Param $criteria 
	 * @Access private
	 * @Return void
	 */
	static private function checkIDExists( $criteria )
	{
		if (array_key_exists('_id' , $criteria)) $criteria['_id'] = self::setId($criteria['_id']);
		return $criteria;
	}

	/**
	 * remove 删除操作,
	 * 
	 * @Param 	array	 $criteria 
	 * @Param 	array	 $options 		//array(
	 * 							'justOne'=>true,		//只删除这一个
	  							'safe'=>'您的参数',
	  							'fsync'=>'您的参数',
	  							'timeout'=>'您的参数',
	 							)
	 * @Access public
	 * @Return void
	 */
	public function remove($criteria = array() , $options = array())
	{
		try{
			if ( empty($criteria) )
			{
				$criteria = self::parseWhere(self::$MethodName['where']);
				if  ( empty($criteria) ) throw new MongoCursorException('亲,您不会想删除整个表吧!太狠了,drop()更效率');
			}
			$criteria = self::checkIDExists($criteria);
			return $this->getCollection()->remove( $criteria , $options );
		} catch(MongoCursorException $e) {
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}

	/**
	 * MongoCode 
	 * 
	 * @Param $code 
	 * @Param 	array	 $scope 
	 * @Access public
	 * @Return void
	 */
	/**		//用法
		$func = 
		    "function(greeting, name) { ".
			"return greeting+', '+name+', says '+CodeValue;".
		    "}";
		$scope = array("CodeValue" => "gaoqilin");		//这个相当于Javascript全局变量

		$code = new MongoCode($func, $scope);

		$response = $db->execute($code, array("Goodbye", "Joe"));
		p( $response);		//结果为: Goodbye Joe gaoqilin

		
	 */
	public function MongoCode( $code  , $scope = array())
	{
		try{
			if (!is_array($scope)) throw new MongoException('请第二个参数类型为数组');
			return new MongoCode($code , $scope);
		}catch(MongoException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}

	/**
	 * execute 
	 * 
	 * @Param $code 
	 * @Param $scope 
	 * @Access public
	 * @Return void
	 * 用法一:可以使用下面所有DB的函数
		db.addUser(username, password[, readOnly=false])		//添加用户
		db.auth(username, password)		//对用户在这个库中授权
		db.cloneDatabase(fromhost)		//克隆数据库
		db.commandHelp(name) returns the help for the command		//命令帮助
		db.copyDatabase(fromdb, todb, fromhost)	//赋值数据库
		db.createCollection(name, { size : ..., capped : ..., max : ... } )		//创建新的集合
		db.currentOp() displays the current operation in the db
		db.dropDatabase()			//删除整个数据库
		db.eval(func, args) run code server-side
		db.getCollection(cname) same as db['cname'] or db.cname
		db.getCollectionNames()			//获取集合名
		db.getLastError() - just returns the err msg string		//获取最后的出错信息,很有用
		db.getLastErrorObj() - return full status object
		db.getMongo() get the server connection object		//获取数据库的链接对象
		db.getMongo().setSlaveOk() allow this connection to read from the nonmaster member of a replica pair
		db.getName()				//得到当前数据库库名
		db.getPrevError()			//获取上一个出错信息
		db.getProfilingLevel() - deprecated
		db.getProfilingStatus() - returns if profiling is on and slow threshold
		db.getReplicationInfo()
		db.getSiblingDB(name) get the db at the same server as this one
		db.isMaster() check replica primary status		//查看主库的信息
		db.killOp(opid) kills the current operation in the db
		db.listCommands() lists all the db commands		//列出所有命令
		db.logout()				//退出
		db.printCollectionStats()		//查看所有集合信息
		db.printReplicationInfo()		//查看响应信息
		db.printSlaveReplicationInfo()		//所有从库响应信息
		db.printShardingStatus()		//分片状态信息
		db.removeUser(username)			//删除Mongo用户
		db.repairDatabase()			//数据库修复
		db.resetError()				//重置错误
		db.runCommand(cmdObj) run a database command.  if cmdObj is a string, turns it into { cmdObj : 1 } //这个需要用一下,底层命令,可运行所有存在的命令,甚至自己编写
		db.serverStatus()			//服务器状态,包括主,从等信息
		db.setProfilingLevel(level,<slowms>) 0=off 1=slow 2=all		//设置慢查询
		db.shutdownServer()			//关闭Mongo
		db.stats()				//数据库状态,主要是占用空间大小,数据文件大小,索引大小等
		db.version() current version of the server		//数据库版本
		db.getMongo().setSlaveOk() allow queries on a replication slave server		//设置从可以读,既从具有查找功能
		db.fsyncLock() flush data to disk and lock server for backups		//大文件锁
		db.fsyncUnock() unlocks server following a db.fsyncLock()		//大文件解锁
	 
	用法二:
		可以自己编写Javascript函数
		比如:
		$response = $mongo->execute("function(greeting, name) { return greeting+', '+name+'!'; }", array("Good bye", "Joe"));
		P($response);		//结果:    $response[retval] => Good bye, Joe!
	 */
	public function execute($code , $scope = array())
	{
		try{
			if (!is_array($scope)) throw new MongoCursorException('请第二个参数类型为数组');
			return $this->getMongoDB()->execute($code , $scope);
		}catch(MongoCursorException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}




	/**
	 * delete 兼容delete , 详细使用见$this->remove();
	 * 
	 * @Param 	array	 $criteria 
	 * @Param 	array	 $options 
	 * @Access public
	 * @Return void
	 */
	function delete($criteria = array() , $options = array())
	{
		return $this->remove($criteria , $options);
	}


	/**
	 * find 
	 * 
	 * @Param 	array	 $conditionQuery 
	 * @Param 	array	 $conditionFields 
	 * @Access public
	 * @Return void
	 * 用法实例:
		一:$data = D('user')->field(array('province','newuser','pageinfo'=>array('$slice'=>array(3,5))))->find();
		二:可连贯操作以下方法:
			public MongoCursor MongoCursor::addOption ( string $key , mixed $value )
			public MongoCursor MongoCursor::batchSize ( int $num )
			MongoCursor::__construct ( Mongo $connection , string $ns [, array $query = array() [, array $fields = array() ]] )
			//public int MongoCursor::count ([ bool $foundOnly = FALSE ] )
			public bool MongoCursor::dead ( void )
			protected void MongoCursor::doQuery ( void )
			public array MongoCursor::explain ( void )
			//public MongoCursor MongoCursor::fields ( array $f )
			public array MongoCursor::getNext ( void )
			public bool MongoCursor::hasNext ( void )
			public MongoCursor MongoCursor::hint ( array $key_pattern )
			public MongoCursor MongoCursor::immortal ([ bool $liveForever = true ] )
			public array MongoCursor::info ( void )
			public string MongoCursor::key ( void )
			public MongoCursor MongoCursor::limit ( int $num )
			public void MongoCursor::next ( void )
			public MongoCursor MongoCursor::partial ([ bool $okay = true ] )
			public void MongoCursor::reset ( void )
			public void MongoCursor::rewind ( void )
			//public MongoCursor MongoCursor::skip ( int $num )		//跳跃数
			public MongoCursor MongoCursor::slaveOkay ([ bool $okay = true ] )
			public MongoCursor MongoCursor::snapshot ( void )
			//public MongoCursor MongoCursor::sort ( array $fields )	//对结果集排序
			public MongoCursor MongoCursor::tailable ([ bool $tail = true ] )
			public MongoCursor MongoCursor::timeout ( int $ms )		//设置游标存活时间
			public bool MongoCursor::valid ( void )
	 * 	
	 */

	function find( $conditionQuery = array() , $conditionFields = array() )
	{
		try{
			//p(self::$MethodName);
			$mongo  	 = $this->getCollection(FALSE);
			$conditionQuery  = empty($conditionQuery) ? self::parseWhere(self::$MethodName['where']) : $conditionQuery;
			$conditionFields = empty($conditionFields) ? self::parseFields(self::$MethodName['field']) : $conditionFields;
			self::$cursorObj = $mongo->find($conditionQuery , $conditionFields);

			if (self::$cursorObj->dead())		//判断是否宕机
				throw new MongoCursorException('数据库宕机了');

			self::$cursorObj = self::parseLimit(self::$cursorObj);

			if ( is_array(self::$MethodName['order']))
				self::$cursorObj = self::parseOrder(self::$cursorObj);
			return self::$cursorObj;


		}catch(MongoCursorException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}

	/**
	 * update 更新操作,[未实现全部内容..暂时先用着]
	 * 
	 * @Param $newData 
	 * @Param 	array	 $conditionCriteria 
	 * @Param 	array	 $options 		//array(
	 * 							'upsert'   => '您的参数',		//没有insert(),存在则更新
	  							'multiple' => '您的参数',		//全部文档都会更新
	  							'safe'     => '您的参数',		//不等待服务器响应就更新
	  							'fsync'    => '您的参数',
	  							'timeout'  => '您的参数',
	 							)
	 * @Access public
	 * @Return void
	 */
	public function update( $newData  , $conditionCriteria = array() , $options = array() )
	{
		try{

		$conditionCriteria  = empty($conditionCriteria) ? self::parseWhere(self::$MethodName['where']) : $conditionCriteria;

		if (empty($conditionCriteria)) throw new MongoException('请传入条件数组');
		if (empty($newData)) throw new MongoException('请传入更新的新数组值!');

		return $this->getCollection(true)->update($conditionCriteria , $newData , $options);

		}catch(MongoException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}
	/**
	 * parseOrder 解析Order 此方法同样间接整合Mongo中的sort()方法 ,详细见__call();
	 * 
	 * @Param $cursorObj 
	 * @Access private
	 * @Return void
	 */
	static private function parseOrder($cursorObj)
	{
		$order = self::$MethodName['order'];
		if (is_string($order[0]))
		{
			$order = explode(' ', $order[0]);
			if ($order[0] == 'by') array_shift($order);
		}

		if (is_array($order[0]))
		{
			//if ( $_desc = array_search('desc', $order[0]) ) $order[0][$_desc] = -1;
			//if ( $_asc  = array_search('asc' , $order[0]) ) $order[0][$_asc]  = 1 ;
			$order[0][1] = in_array('asc' , $order[0]) ? 1 : -1 ;
		}
		return $cursorObj->sort($order[0]);
	}

	/**
	 * parseWhere 解析连贯操作where 目前只支持数组格式,因为Mongo的Where太过复杂和强大,抛弃单纯字符串的的格式
	 * 
	 * @Param $where 
	 * @Access private
	 * @Return void
	 *           // 查询字段的安全过滤
                if(!preg_match('/^[A-Z_\|\&\-.a-z0-9]+$/',trim($key))){
			MyException:Excaptions('字段不安全':'.$key);
                }
	 */
	static private function parseWhere($where)
	{
		return is_array($where) ?  (is_array($where[0]) ? $where[0] : array()) : array();
	}


	/**
	 * createSystemProfile 解决Mongo慢查询日志中出现的错误
	 *	手动创建
	 *	Sat Apr 28 14:44:10 [initandlisten] profile: warning ns local.system.profile does not exist
	 * 
	 * 
	 * @Access public
	 * @Return void
	 */
	public  function createSystemProfile()
	{
		$tabNames = $this->execute('db.getCollectionNames()');
		if (!in_array('system.profile',$tabNames))
			return $this->getMongoDB()->createCollection('system.profile' , true ,  10240);
		return true;
	}

	/**
	 * parseLimit 解析连贯操作limit() 此方法,整合Mongo中Skip()过来,所以,实现分页和限定查询条数很简单
	 * 
	 * @Param $cursorObj 
	 * @Access private
	 * @Return void
	 */
	static private function parseLimit($cursorObj)
	{
		try{
			$limit = self::$MethodName['limit'];
			if (!empty($limit))
			{
				$limitArray = !is_array($limit[0]) ? explode(',',$limit[0]) : $limit[0] ;
				$limitCount = count($limitArray);
				switch ($limitCount)
				{
					case 1:
						return $cursorObj->limit($limitArray[0]);
						break;
					case 2:
						return $cursorObj->skip($limitArray[0])->limit($limitArray[1]);
						break;
					default:
						return $cursorObj;
						break;
				}
			}
			return $cursorObj;
		}catch(MongoCursorException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}

	/**
	 * total 		//查询总条数,视结果用哦
	 * 
	 * @Param $foundOnly 	//此参数用于返回是否标识limit(),为true的情况下士返回limit()限定后的条数,默认是false返回此表所有的条数
	 * @Access public
	 * @Return void
	 */
	function total($foundOnly = FALSE )
	{
		try{
			if (empty(self::$cursorObj))
				return $this->getCollection(FALSE)->find()->count($foundOnly);

			return self::$cursorObj->count( $foundOnly );

		}catch(MongoCursorException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}


	/**
	 * parseFields 解析查询字段
	 * 
	 * @Param $fields 可以是以逗号分割的字符串,也可以是数组
	 * @Access private
	 * @Return void
	 */
	static private function parseFields($fields)
	{
		return !empty($fields) ?  (is_array($fields[0]) ?  $fields[0] : explode(',',$fields[0])) : array();
	}


	/**
	 * findOne 查一条数据,使用Mongo中默认的findOne,因为只查找一条不需要limit(),order(),sort()等支持,只需要查询条件和需要查询字段支持
	 * 
	 * @Param 	array	 $conditionQuery 
	 * @Param 	array	 $conditionFields 
	 * @Access public
	 * @Return array()		//返回值,直接是数组
	 */
	public function findOne($conditionQuery = array() , $conditionFields = array())
	{
		try{

			$conditionQuery  = empty($conditionQuery) ? self::parseWhere(self::$MethodName['where']) : $conditionQuery;
			$conditionFields = empty($conditionFields) ? self::parseFields(self::$MethodName['field']) : $conditionFields;
			return $this->getCollection(FALSE)->findOne($conditionQuery , $conditionFields);

		}catch(MongoCursorException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}



	/**
	 * select 兼容select
	 * 
	 * @Param 	array	 $conditionQuery 
	 * @Param 	array	 $conditionFields 
	 * @Access public
	 * @Return void
	 */
	public function select($conditionQuery = array() , $conditionFields = array())
	{
		return $this->find( $conditionQuery  , $conditionFields  ) ;
	}


	public function group(   $keys ,  $initial ,  $reduce , $options = array()  )
	{
		try{
			return $this->getCollection()->group($keys, $initial,$reduce , $options);
		}catch(MongoCursorException $e){
			MyException::Exceptions($e->getMessage() , $e->getTrace());
		}
	}

	/**
	 * query 兼容 query
	 * 
	 * @Param $code 
	 * @Param 	array	 $scope 
	 * @Access public
	 * @Return void
	 */
	public function query( $code , $scope = array() )
	{
		return $this->execute($code , $scope );
	}

	/**
	 * dbstats 数据库状态信息,非常详细
	 * 
	 * @Access public
	 * @Return array()
	 */
	public function dbstats()
	{
		$stats = $this->execute('db.serverStatus()');
		return $stats['retval'];
	}

	/**
	 * dbSize 数据库,平均文件大小,占用空间大小,占用磁盘大小, 数据文件大小,索引大小等
	 * 
	 * @Access public
	 * @Return array()
	 */
	public function dbSize()
	{
		$stats            = $this->execute('db.stats()');
		$s                = $stats['retval'];
		$s['avgObjSize']  = byte_format($s['avgObjSize'],2);		//byte_format()在Public/Common/function.php文件中
		$s['dataSize']    = byte_format($s['dataSize'],2);
		$s['storageSize'] = byte_format($s['storageSize'],2);
		$s['indexSize']   = byte_format($s['indexSize'],2);
		$s['fileSize']    = byte_format($s['fileSize'],2);

		return $s;
	}
	
	/**
	 * dbVersion 获取Mongo的版本信息
	 * 
	 * @Access public
	 * @Return void
	 */
	public function dbVersion()
	{
		 $version = $this->execute('db.version()');
		 return $version['retval'];
	}

	/**
	 * __call 连贯操作 , where()->order()->sort()->field()->group()->limit()->skip()
	 * 
	 * @Param $methodName 
	 * @Param $args 
	 * @Access public
	 * @Return void
	 */
	public function __call($methodName , $args)
	{
		$methodName = strtolower($methodName);
		if( array_key_exists($methodName, self::$MethodName) )
		{
			if( empty($args[0]) || (is_string($args[0]) && trim($args[0]) === '' ))
				self::$MethodName[$methodName] = '';
			else
				self::$MethodName[$methodName] = $args;

			if($methodName=="limit")
			{
				if($args[0]=="0") self::$MethodName[$methodName] = $args;
			}else if ($methodName == 'sort'){
				self::$MethodName['order'] = $args;
			}
		} else 
			Debug::addmsg("<font color='red'>调用类" . get_class($this) . "中的方法{$methodName}()不存在!</font>");

		return $this;
	}



	/**
	 * command 执行Mongo指令
	 * 
	 * @Param	array() 	 $command 
	 * @Access public
	 * @Return void
	 */
	public function command($command)
	{
		return $this->getMongoDB(true)->command($command);
	}


}
