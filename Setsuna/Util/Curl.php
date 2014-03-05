<?php

namespace Setsuna\Util;

class Curl {
	public $url;
	public $cookieFile;
	public $curlHander;

	public function __construct($url) {
		$this->url = $url;
		$this->cookieFile =  dirname(__FILE__)."/cookie_".md5(basename(__FILE__)).".txt"; 
	}

	public function curlInit() {
		$this->curlHander = curl_init();
		curl_setopt($this->curlHander, CURLOPT_URL, $this->url);                              // 要访问的地址
		curl_setopt($this->curlHander, CURLOPT_SSL_VERIFYPEER, 0);                      // 对认证证书来源的检查
		curl_setopt($this->curlHander, CURLOPT_SSL_VERIFYHOST, 1);                      // 从证书中检查SSL加密算法是否存在
		curl_setopt($this->curlHander, CURLOPT_FOLLOWLOCATION, 1);                      // 使用自动跳转
		curl_setopt($this->curlHander, CURLOPT_AUTOREFERER, 1);                         // 自动设置Referer
		curl_setopt($this->curlHander, CURLOPT_HTTPGET, 1);                             // 发送一个常规的Post请求
		curl_setopt($this->curlHander, CURLOPT_COOKIEFILE, $this->cookieFile);         // 读取上面所储存的Cookie信息
		curl_setopt($this->curlHander, CURLOPT_TIMEOUT, 30);                            // 设置超时限制防止死循环
		curl_setopt($this->curlHander, CURLOPT_HEADER, 0);                              // 显示返回的Header区域内容
		curl_setopt($this->curlHander, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt($this->curlHander, CURLOPT_RETURNTRANSFER, 1);                      // 获取的信息以文件流的形式返回
		//$this->curlHander = $curl;
		return $this->curlHander;
	}

	public function curlGet($curlHander){
		$response = curl_exec($curlHander);                                        // 执行操作
		if (curl_errno($curlHander)) {      
			echo 'Errno'.curl_error($curlHander); 
		}      
		curl_close($curlHander); // 关闭CURL会话      
		return $response; // 返回数据      
	}

}


