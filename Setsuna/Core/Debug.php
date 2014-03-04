<?php

class Debug  {

	 //	收集脚本运行中错误信息
	static $info   = array();
	 //	收集Sql语句运行信息 
	static $sqls   = array();
	 //	收集类自动加载信息
	static $includefile =array();
	 //	脚本运行开始时间
	static $startTime;
	 //	脚本运行结束时间 
	static $stopTime; 
	 //	是否DEBUG信息已经输出
	static $printErr = true;
	 //	定义不同错误等级别名
	static $msg    = array(
		 E_WARNING	=> '运行时警告',
		 E_NOTICE	=> '运行时提醒',
		 E_STRICT	=> '编码标准化警告',
		 E_USER_ERROR	=> '自定义错误',
		 E_USER_WARNING	=> '自定义警告',
		 E_USER_NOTICE	=> '自定义提醒',
		 'UNkOWN_ERROR'	=> '未知错误'
	);


	 // 脚本运行开始处获取脚本运行时间微秒值
	static public function scriptStart() {
		self::$startTime = microtime(true);
	}
	 // 脚本运行结束处获取脚本运行时间微秒值
	static public function scriptEnd(){
		self::$stopTime = microtime(true);
	}
	 // 得到脚本运行时间
	static PRIVATE function resultTime() {
		return round(abs(self::$stopTime - self::$startTime) , 4);
	}


	/**
	 * CatchDebug 
	 * 
	 * @Param $errNo 
	 * @Param $errStr 
	 * @Param $errFile 
	 * @Param $errLine 
	 * @Access public
	 * @Return void
	 */
	static public function CatchDebug($errNo, $errStr, $errFile, $errLine) {
		if( ! isset(self::$msg[$errNo]))
			$errNo = 'UNKOWN_ERROR';

		if($errNo == E_NOTICE || $errNo == E_USER_NOTICE)
			$color = '#000088';
		else
			$color = 'red';

		$mess  = '<font color=' . $color . '>';
		$mess .= '<b>' . self::$msg[$errNo] . "</b>[在文件{$errFile} 中,第{$errLine}行]:";
		$mess .= $errStr;
		$mess .= '</font>';
		self::addmsg($mess);
	}


	/**
	 * addmsg 
	 * 
	 * @Param 		$mess 
	 * @Param 	int	$type 
	 * @Access 		public
	 * @Return 		void
	 */
	static public function addmsg($mess, $type=0) {
		if( DEBUG && DEBUG === true ){
			switch($type){
				case 0:		//正常信息
					self::$info[] = $mess;
					break;
				case 1:
					self::$includefile[] = $mess;		//包含关系
					break;
				case 2:
					self::$sqls[] = $mess;
					break;
			}
		}else{
			return true;
		}
	}



	/**
	 * compareArray 
	 * 
	 * @Param $type 
	 * @Param $setArray 
	 * @Param $comPareArray 
	 * @Access public
	 * @Return void
	 */
	static public function compareArray($type , $setArray , $comPareArray  ) {
		$arr = array_keys($setArray);
		$array = array_diff($arr , $comPareArray);
		if(!empty($array))
		{
			$count = count($arr);
			for($i =0; $i < $count; $i++){
				if(in_array($arr[$i] , $comPareArray))
					$arr[$i] = $arr[$i] .'<font color="blue">设置成功</font>';
				else  
					$arr[$i] = $arr[$i] . '未设置';
			}
			$string ='
		<div style="width:100%;">
			<dl style="float:left;">
				<dt style="background:pink;">请确认' .$type. '设置的属性正确:</dt>
';
			foreach($arr as $key => $value){
				$string .='<dd style="color:red;"><span style="width:120px;text-align:left;">' .$value. '</span><span style="width:200px;text-align:right;">------------------&gt;</span></dd>';
			}

			$string .=<<<OPTION
			</dl>
			<dl style="float:left;margin-left:20px;">
				<dt style="background:pink;">参考属性:</dt>
OPTION;
			foreach($comPareArray as $key => $value) {
				$string .='<dd style="color:blue;">'.$value.'</dd>';
			}

			$string .=<<<OPTION

			</dl>
		</div>
OPTION;
			self::addmsg($string);
			return false;
		}
		return true;
	}



	/**
	 * debugPrint 
	 * 
	 * @Access public
	 * @Return void
	 */
	static public function debugPrint() {
			self::viewInfo();
			$debugContent  = '';
			$debugContent .= '<div style="float:left;clear:both;text-align:left;font-size:13px;color:#333;width:95%;margin:10px;padding:10px;background:#E7F7FF;border:1px dotted #778855;z-index:100">';
			$debugContent .= '<div style="float:left;width:100%;"><span style="float:left;width:200px;"><b>运行信息</b>( <font color="red">'.self::resultTime().' </font>秒):</span>
				<span onclick="this.parentNode.parentNode.style.display=\'none\'" style="cursor:pointer;float:right;width:50px;background:#8E113D;border:1px solid #555acf;color:white;text-align:center;"> 关闭X </span>
				</div><br>';
			$debugContent .= '<ul style="margin:0px;padding:0 10px 0 10px;list-style:none">';
			if(count(self::$includefile) > 0){
				$debugContent .= '［自动加载类］';
				foreach(self::$includefile as $ClassName)
					$debugContent .= '<li>&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$ClassName.'</b>&nbsp;类</li>';
			}
			if(count(self::$info) > 0 ){
				$debugContent .= '<br>［系统信息］';
				foreach(self::$info as $info)
					$debugContent .= '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$info.'</li>';
			}

			if(count(self::$sqls) > 0) {
				$debugContent .= '<br>［SQL语句］';
				foreach(self::$sqls as $sql)
					$debugContent .= '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$sql.'</li>';
			}
			$debugContent .= '</ul>';
			$debugContent .= '</div>';	

			if(DEBUG && DEBUG===true){ 
				if(self::$printErr){
					self::$printErr = false;
					echo $debugContent;
				}
			} else {
				return true;
			}
	}



	/**
	 * viewInfo 
	 * 
	 * @Access public
	 * @Return void
	 */
	static public function viewInfo() {
		$viewFile = './' . rtrim(ltrim(APP_PATH ,'./') , '/') . '/View/' . trim(TEMPLATE_STYLE , '/') . '/' . strtolower($_GET['m']) . '/' . strtolower($_GET['a']) . '.' . TPL_PREFIX;
		$ViewInfo='
				<style>
					.bbb{ width:500px;}
					.ccc{ width:70px; background:; margin-left:50px; float:left; }
					.ddd{ margin-left:30px;  }
				</style>
				<div>
					&nbsp;&nbsp;&nbsp;&nbsp;View信息:
					<div class="bbb">
						<div class="ccc">控制器:</div>
						<span class="ddd">[ ' . ucfirst(strtolower($_GET['m'])) . 'Action ]</span>
					</div>
					<div class="bbb">
						<div class="ccc">方&nbsp;&nbsp;&nbsp;&nbsp;法:</div>
						<span class="ddd">[ ' . strtolower($_GET['a']) . ' ]</span>
					</div>
					';
		if(file_exists($viewFile))
		{
		$ViewInfo .= '
					<div class="bbb">
						<div class="ccc">模板信息:</div>
						<span class="ddd">[ '. $viewFile.' ]</span>
					</div>
				';
		} else  {
		$ViewInfo .= '
					<div class="bbb">
						<div class="ccc" style="color:red;">未用模板:</div>
						<span class="ddd">[ '. $viewFile.' ]</span>
					</div>
				';
		}
		$ViewInfo .= ' </div>';
		self::addmsg($ViewInfo);

	}

}
