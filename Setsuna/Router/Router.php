<?php

namespace Setsuna\Router;


class Router{

	private $rules = array();
	private $segment;

	public function __construct() { 

	}

	public function setDefaultSegmentRouter($num = 2) {
		$this->segment = $num;
	}

	/**
	 * 分发函数
	 * 调用此函数时执行 action 方法
	 * default indexController::indexAction()
	 */

	public function dispatch($url) {
		if ($this->segment !== null) {
			return $this->dispatchBySegment($url);
		} else {
			return $this->dispatchByRule($url);
		}
	}


	public function dispatchBySegment($url) {
		if ($this->segment == 1) {
			$controller = 'index';
			$action = isset($url) && $url ? $url : 'index';
			$result = array(array($controller, $action), array());
			return $result;
		} elseif ($this->segment == 2) {
			return $this->dispatchDefault($url);
		}
	}

	public function dispatchDefault($url) {
		// 默认的路由规则 /controller/action
		// 默认 404 page404Controller::indexAction()

		$arr = explode('/', $url);
		unset($arr[0]);
		$controller = isset($arr[1]) && $arr[1] ? $arr[1] : 'index';
		$action = isset($arr[2]) && $arr[2] ? $arr[2] : 'index';
		$result = array(array($controller, $action), array());
		return $result;
	}

	public function dispatchByRule($url) {
		$params = array();
		if ($this->rules) {
			// 解析规则（阻断性）
			foreach ($this->rules as $rule) {
				$is_method_match = $this->macthMethod($rule['methods']);
				if ($is_method_match && preg_match($rule['regex'], $url, $matches)) {

					// 提取参量
					foreach ($matches as $key => $value) {
						if ($key) {
							$name = $rule['names'][$key-1];
							$params[$name] = $value;
						}
					}

					$controller = $rule['controller'];
					$action = $rule['action'];

					break;
				}
			}
		} else {
			return $this->dispatchDefault($url);
		}
		$result = array(array($controller, $action), $params);
		return $result;
	}

	public function macthMethod($methods) {
		if ($methods == NULL) {
			return TRUE;
		}
		if (in_array($_SERVER['REQUEST_METHOD'], $methods)) {
			return TRUE;
		}
		return false;
	}

	/**
	 * 新增一条路由规则
	 * $router->rule('GET', '/user/[:id]', array('user', 'view'))
	 * $router->rule('POST', '/user/[:id]', array('user', 'edit'))
	 * $router->rule('/user/', array('user', 'list'))
	 * $router->rule('*', array('page404', 'index'))
	 * 第一个参数可以不填
	 * @param $method HTTP方法 'GET'|'POST'|'PUT'|'DELETE'
	 * @param $rule URL规则，如 /user/[:id]，其中方括号冒号开头代指一个参数，放到 $_GET 数组中
	 * @param $ca 数组 array('控制器', 'Action')
	 * 
	 * @author wangxiaochi <cumt.xiaochi@gmail.com>
	 */
	public function rule() {
		$args_num = func_num_args();
		if ($args_num == 2) {
			return $this->_rule(null, func_get_arg(0), func_get_arg(1));
		}
		if ($args_num == 3) {
			return $this->_rule(func_get_arg(0), func_get_arg(1), func_get_arg(2));
		}
	}

	public function rules($rules) {
		if(!empty($rules)){
			foreach ($rules as $rule) {
				$this->_rule($rule[0], $rule[1], $rule[2]);
			}
		}
	}

	private function _rule($method, $rule, $ca) {
		if ($rule === '*') {
			$regex = '.*';
		} else {
			$regex = preg_replace('/\[:[a-zA-Z][a-zA-Z\d_]*\]/', '([^/]+)', $rule);
		}
		preg_match_all('/\[:([a-zA-Z][a-zA-Z\d_]*)\]/', $rule, $matches);
		$this->rules[] = array(
			'methods' => is_string($method) ? array($method) : $method,
			'regex' => '/^'.str_replace('/', '\/', $regex).'$/',
			'names' => $matches[1],
			'controller' => $ca[0],
			'action' => $ca[1],
		);
		return $this;
	}
}

