<?php

namespace Setsuna\Core;

class MyException extends Exception{
	//所有错误的Trace信息
	static private $trace;
	//错误信息
	static private $Errmessage;
	//错误类型
	static private $type;
	//短信接口			请修改"####"为您的手机号码..
	static private $noteInterface = '';
	//存储错误信息用
	static private $logInfo = array();		

	/**
	 * Exceptions *异常信息
	 * 
	 * @Param $Errmessage 
	 * @Param $trace 
	 * @Access public
	 * @Return void
	 */
	static public function Exceptions( $Errmessage = NULL, $trace = NULL ){
		if(empty($trace) ){
			$e = new Exception();
			$trace = $e->getTrace();
			if(empty($Errmessage)){
				$Errmessage = $e->getMessage();
			}
		}
		self::$trace = $trace;
		self::$Errmessage = $Errmessage;
		self::$type  = __CLASS__;

		if(DEBUG && DEBUG === TRUE){
			//本地测试环境
			self::showDebug();
		}else{
			//线上环境,短信通知
			exit();		//直接退出不显示任何内容
		}
	}

	/**
	 * getErrorArray * 得到错误信息数组
	 * 
	 * @Param 	Array 	$trace 
	 * @Param 		$Errmessage 
	 * @Access 	private
	 * @Return 		void
	 */
	static private function getErrorArray($trace,$Errmessage){
		$class = $trace[0]['class'];
		$function = $trace[0]['function'];
		$filePath = $trace[0]['file'];
		$line = $trace[0]['line'];
		$file = file($filePath);
		$traceInfo = '';
		$time = date('Y-m-d H:i:s');
		foreach($trace as $key => $value)
		{
			$traceInfo .= '['.$time."]:".$value['file'].'(第'.$value['line'].'行)';
			$traceInfo .= $value['class'].$value['type'].$value['function']."(";
			@$traceInfo .= implode(', ', $value['args']);
			$traceInfo .=")\n";
		}
		self::$logInfo['line']    = $line; 
		self::$logInfo['file']    = $file; 
		self::$logInfo['errstr']  = $Errmessage; 
		self::$logInfo['type']    = self::$type;

		$error['message']   = $Errmessage;
		$error['type']      = self::$type;
		$error['detail']    =  "\n";
		$error['detail']   .=   ($line-2).': '.$file[$line-3];
		$error['detail']   .=   ($line-1).': '.$file[$line-2];
		$error['detail']   .=   '<font color="#FF6600" >'.($line).': <b>'.$file[$line-1].'</b></font>';
		$error['detail']   .=   ($line+1).': '.$file[$line];
		$error['detail']   .=   ($line+2).': '.$file[$line+1];
		$error['class']     =	$class;
		$error['function']  =	$function;
		$error['file']      =	$filePath;
		$error['line']      =	$line;
		$error['trace']     =	$traceInfo;
		$error['time']      =	$time;
		return $error;
	}

	/**
	 * showDebug * 本地环境下显示错误信息
	 * 
	 * @Access private
	 * @Return void
	 */
	static private function showDebug()
	{
		$errorArray = self::getErrorArray(self::$trace,self::$Errmessage);
		self::template($errorArray);
	}


	/**
	 * template * 异常错误模板
	 *
	 * @param 	Array		$e 	异常信息的数组格式
	 * @Access private
	 * @Return void
	 */
	static private function template($e)
	{
		$html=<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>系统发生错误</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
	<style>
		body{ font-family: 'Microsoft Yahei', Verdana, arial, sans-serif; font-size:14px; }
		a{text-decoration:none;color:#174B73;}
		a:hover{ text-decoration:none;color:#FF6600;}
		h2{ border-bottom:1px solid #DDD; padding:8px 0; font-size:25px; }
		.title{ margin:4px 0; color:#F60; font-weight:bold; }
		.message,#trace{ padding:1em; border:solid 1px #000; margin:10px 0; background:#FFD; line-height:150%; } 
		.message{ background:#E7F7FF; color:#2E2E2E; border:1px solid #E0E0E0; }
		#trace{ background:#E7F7FF; border:1px solid #E0E0E0; color:#535353; }
		.notice{ padding:10px; margin:5px; color:#666; background:#FCFCFC; border:1px solid #E0E0E0; }
		.red{ color:red; font-weight:bold; }
	</style>
</head>
<body>
	<div class="notice">
		<h2>系统发生错误 </h2>
	<div>
HTML;
		$html .='[ <a href="'.$_SERVER['PHP_SELF'].'">重试</a> ] ';
		$html .=' [ <A HREF="javascript:history.back()">返回</A> ] ]</div>';
		if (isset($e['file'])) {
			$html .='<p class="title">[ 错误快照 ]:</p>　文件: <span class="red">'. $e['file'] . '</span>　LINE: <span class="red">'.$e['line'].'</span></p>';
			$html .='<p class="message">'.nl2br($e['detail']).'</p>';
			$html .='<p class="title">[ 错误信息 ]</p>';
			$html .='<p class="message" style="color:red;">'.$e['message'].'</p>';
			if (isset($e['trace'])) {
				$html .='<p class="title">[ TRACE ]</p>';
				$html .='<p id="trace">'. nl2br($e['trace']).'</p>';
			}
			$html .= '</div> </body> </html>';

		}
		echo $html;
		exit();
	}

	/**
	 * writeLog * 错误日志写入数据库中操作
	 * 
	 * @Access private
	 * @Return void
	 */
	static private function writeLog()
	{

	}
}
