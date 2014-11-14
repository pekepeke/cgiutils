<?php

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

require_once dirname(__FILE__)."/lib/GeoHash.php";

if (!function_exists('h')) {
	function h($s) {
		return is_array($s) ? array_map("h", $s) : htmlspecialchars($s, ENT_QUOTES);
	}
}
if (!function_exists('nf')) {
	function nf($v) {
		return number_format($v);
	}
}
if (!function_exists('d')) {
	function d() {
		echo '<pre style="background:#fff;color:#333;border:1px solid #ccc;margin:2px;padding:4px;font-family:monospace;font-size:12px">';
		foreach (func_get_args() as $v) var_dump($v);
		echo '</pre>';
	}
}

function view_path($name) {
	$fpath = dirname(__FILE__) . '/views/' . $name . ".php";
	return $fpath;
}
function render_view_file($_name, $vars) {
	extract($vars);

	$_fpath = view_path($_name);
	// include $view_path;
	if (file_exists($_fpath)) {
		include $_fpath;
	}
}

if (!function_exists('render')) {
	function render_file_or_contents($view_path, $vars) {
		extract($vars);
		ob_start();
		if (file_exists(view_path($view_path))) {
			render_view_file($view_path, $vars);
			// include $view_path;
		} else if (has_partial($view_path)) {
			partial($view_path, $vars);
		} else {
			$src = section($view_path);
			if ($src) {
				eval('?>' . section($view_path));
			} else {
				echo $vars["contents"];
			}
		}
		$content = ob_get_clean();
		return $content;
	}
	function render($view, $vars = array(), $layout = null) {
		$content = render_file_or_contents($view, $vars);
		is_null($layout) && !req_is("ajax") && $layout = "layout";

		echo is_null($layout) ? $content : render_file_or_contents($layout, array_merge($vars, compact('content')));
	}
}


function req_is($type) {
	static $_detectors = array(
		'get' => array('env' => 'REQUEST_METHOD', 'value' => 'GET'),
		'post' => array('env' => 'REQUEST_METHOD', 'value' => 'POST'),
		'put' => array('env' => 'REQUEST_METHOD', 'value' => 'PUT'),
		'delete' => array('env' => 'REQUEST_METHOD', 'value' => 'DELETE'),
		'head' => array('env' => 'REQUEST_METHOD', 'value' => 'HEAD'),
		'options' => array('env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'),
		'ssl' => array('env' => 'HTTPS', 'value' => 1),
		'ajax' => array('env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'),
		'flash' => array('env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'),
		'mobile' => array('env' => 'HTTP_USER_AGENT', 'options' => array(
			'Android', 'AvantGo', 'BlackBerry', 'DoCoMo', 'Fennec', 'iPod', 'iPhone', 'iPad',
			'J2ME', 'MIDP', 'NetFront', 'Nokia', 'Opera Mini', 'Opera Mobi', 'PalmOS', 'PalmSource',
			'portalmmm', 'Plucker', 'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\\.Browser',
			'webOS', 'Windows CE', 'Windows Phone OS', 'Xiino'
		))
	);
	$type = strtolower($type);
	if (!isset($_detectors[$type])) {
		return false;
	}

	$detect = $_detectors[$type];
	if (isset($detect['env'])) {
		$env = isset($_SERVER[$detect['env']]) ? $_SERVER[$detect['env']] : null;
		if (isset($detect['value'])) {
			return $env == $detect['value'];
		}
		if (isset($detect['pattern'])) {
			return (bool)preg_match($detect['pattern'], $env);
		}
		if (isset($detect['options'])) {
			$pattern = '/' . implode('|', $detect['options']) . '/i';
			return (bool)preg_match($pattern, $env);
		}
	}
	if (isset($detect['callback']) && is_callable($detect['callback'])) {
		// return call_user_func($detect['callback'], $this);
		return call_user_func($detect['callback'], $detect);
	}
	return false;
}

function partial_path($name) {
	$ext = ".html";
	if (strpos($name, ".") !== false) {
		$ext = "";
	}
	$partial_path = "partial" . DIRECTORY_SEPARATOR . $name . $ext;
	return $partial_path;
}

function has_partial($name) {
	return file_exists(partial_path($name));
}

function partial($__name, $vars = array()) {
	extract($vars);
	include partial_path($__name);
}

function section($name = null) {
	static $data = null;
	static $sections = array();

	if (is_null($data)) {
		$data = preg_replace('/\r\n|\r|\n/', "\n", file_get_contents(__FILE__, FALSE, NULL, __COMPILER_HALT_OFFSET__));
		// foreach (explode('@@', $data) as $section) {
		foreach (preg_split('/(^|\n)@@/', $data) as $section) {
			$lines = explode("\n", $section);
			$key = array_shift($lines);
			$sections[$key] = implode("\n", $lines);
		}
	}
	if (is_null($name)) {
		return $data;
	}
	return $sections[$name];
}

function params($s) {
	$args = is_array($s) ? $s : func_get_args();
	if (count($args) <= 1) {
		return isset($_REQUEST[$s]) ? $_REQUEST[$s] : null;
	}
	$params = array();
	foreach ($args as $s) {
		$params[] = isset($_REQUEST[$s]) ? $_REQUEST[$s] : null;
	}
	return $params;
}
function set($key = null, $val = null) {
	static $vars = array();
	if (is_null($key)) {
		return $vars;
	}
	if (!is_array($key)) {
		$key = array(
			$key => $val,
		);
	}
	$vars = array_merge($vars, $key);
	return $vars;
}

function action_index() {
	list ($sql, $binds) = params("sql", "binds");
	$result = "";

	if (!is_null($sql)) {
		$result = $sql;
		foreach (explode(",", preg_replace('/^\[|\][\r\n]*$/', '', $binds)) as $s) {
			if (!$s) continue;
			list ($key, $val) = explode("=", $s);
			if (is_null($val)) {
				$val = $s;
				$key = 0;
			}

			if (!is_numeric($val)) {
				$val = "'" . str_replace("'", "''", $val) . "'";
			}

			if (is_numeric($key)) {
				$result = preg_replace('/\?/', $val, $result, 1);
			} else {
				$result = preg_replace('/:' . $key . '/', $val, $result, 1);
			}
		}
	}
	set(compact('sql', 'binds', 'result'));
}

function action_ajax_xml_format() {
	$xml = simplexml_load_string(params("xml_data"));

	if ($xml === false) {
		header("HTTP/1.0 400 Bad Request");
		set('contents', "xml parse error");
		return;
	}
	$dom = dom_import_simplexml($xml)->ownerDocument;
	if (is_null($xml)) {
		header("HTTP/1.0 400 Bad Request");
		set('contents', "xml parse error");
		return;
	}
	$dom->preserveWhiteSpace = false;
	$dom->loadXML(params("xml_data"));
	$dom->formatOutput = true;
	set("contents", $dom->saveXml());
}

function action_ajax_xml_json() {
	$xml = simplexml_load_string(params("xml_data"));
	set('contents', json_encode($xml));
}

function action_geohash() {
	$latlon = params("latlon");

	$geohash = "";
	if (!empty($latlon)) {
		list($lat, $lon) = explode(",", $latlon);

		$geo = new GeoHash();
		$geohash = $geo->encode($lat, $lon);
	}

	set(compact('latlon', 'geohash'));
}


function run() {
	$action = params("action");
	is_null($action) && $action = "index";

	$method = "action_" . $action;
	if (function_exists($method)) {
		$method();
	}
	// auto render
	set('__action', $action);
	render($action, set());
}


if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	run();
}



__halt_compiler(); ?>
@@layout
@@index
@@diff
@@image_diff
@@jstools
@@geohash

