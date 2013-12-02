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
if (!function_exists('render')) {
	function render_file_or_contents($view_path, $vars) {
		extract($vars);
		ob_start();
		if (file_exists($view_path)) {
			include $view_path;
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
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<title>Utilities</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">
		<meta name="author" content="">
		<meta name="robots" content="noindex, nofollow"/>
		<link rel="shortcut icon" href="favicon.ico">
		<!--
		<link rel="stylesheet" media="screen" href="reset.css" />
		<meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1, minimum-scale=0.5 , maximum-scale=2">
		<link rel="stylesheet" media="screen and (max-width: 65025px)" href="style.css" />
		<link rel="stylesheet" media="screen and (max-width: 640px)" href="mobile.css" />
		-->

		<!-- Le styles -->
		<link href="css/bootstrap.min.css" rel="stylesheet">
		<style>
			body {
				padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
				padding-bottom: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
			}
		</style>
		<link href="css/bootstrap-responsive.min.css" rel="stylesheet">

		<!-- Le javascript
		================================================== -->

		<!--
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.0rc1/jquery.mobile-1.0rc1.min.css" />
		<script src="http://code.jquery.com/jquery-1.6.4.min.js"></script>
		<script src="http://code.jquery.com/mobile/1.0rc1/jquery.mobile-1.0rc1.min.js"></script>
		<script src="http://ajax.aspnetcdn.com/ajax/modernizr/modernizr-2.0.6-development-only.js"></script>
		-->
		<!-- Placed at the end of the document so the pages load faster -->
		<!-- <script src="http://twitter.github.com/bootstrap/assets/js/jquery.js"></script> -->
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<script src="js/jquery.pjax.js"></script>
		<script src="js/bootstrap.min.js"></script>

		<link rel="stylesheet" href="css/diff.css">
		<link rel="stylesheet" type="text/css" href="js/difflib/diffview.css">
		<script src="js/jquery.easing.1.3.js"></script>
		<script src="js/difflib/difflib.js"></script>
		<script src="js/difflib/diffview.js"></script>
		<script src="js/imagediff.min.js"></script>
		<script src="js/resemble.js"></script>
		<script src="js/vkbeautify.js"></script>
		<script type="text/javascript" src="//maps.google.com/maps/api/js?libraries=geometry&sensor=false"></script>
		<script src='http://jashkenas.github.com/coffee-script/extras/coffee-script.js'></script>

		<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
		<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<script src="//css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
		<![endif]-->
		<script>//<![[CDATA[
			<?php partial('pastecatcher.js') ?>

			!function($, Global) {
				$(function() {
					//####################################################################
					// index
					$(document).on('keypress', '.sql-binder-enter-submit', function(ev) {
						if (ev.which == 13) {
							$(this).parents('form').submit();
							return false;
						}
					});
					$(document).on('pjax:success', function(ev) {
						var $that = $('#sqlbinder-result');
						if ($that.length <= 0 || !$that.val()) return;
						var sql = vkbeautify.sql($that.val());
						$that.val(sql);
						$that.focus();
					}).trigger('pjax:success');

					//####################################################################
					// diff
					$(document).on('click', '#btn-exec-diff', function(ev) {
						var base = difflib.stringAsLines($("#param-base").val())
							, newtxt = difflib.stringAsLines($("#param-new").val())
							, sm = new difflib.SequenceMatcher(base, newtxt)
							, opcodes = sm.get_opcodes()
							, diffoutputdiv = $("#diff-output").get(0);
						while (diffoutputdiv.firstChild) {
							diffoutputdiv.removeChild(diffoutputdiv.firstChild);
						}
						var context_size = $("#param-context-size").val();
						context_size = context_size ? context_size : null;
						diffoutputdiv.appendChild(diffview.buildView({ baseTextLines:base,
							newTextLines:newtxt,
							opcodes:opcodes,
							baseTextName:"Base Text",
							newTextName:"New Text",
							contextSize:context_size,
							viewType: $("#param-inline").attr('checked') ? 1 : 0
						}));

						$('html,body').animate({
							scrollTop : $('#diff-output').offset().top
						})
						return false;
					});
					//####################################################################
					// image diff
					$(document).on('dblclick', '#img-base', function () {
						$(this)
							.hide()
							.data('src', '');
					}).on('dblclick', '#img-new', function () {
						$(this)
							.hide()
							.data('src', '');
					});
					$(window).on('pasteCatcher', function(ev, img, src) {

						var a = $('#img-base').get(0)
							, b = $('#img-new').get(0)
							, out = $('#img-canvas').get(0)
							, $a = $(a), $b = $(b)
						if (!a || !b || !out) {
							return ;
						}
						var diff, canvas, context;
						if (!$a.data('src')) {
							a.src = src; //img.src;
							$a.data('src', src);//img.src);
							$a.show();
							if (!$b.data('src')) {
								return ;
							}
						} else if (!$b.data('src')) { // }if (!b.src) {
							b.src = src;//img.src;
							$b.data('src', src);//img.src);
							$b.show();
						}

						var difference = (function(a, b) {
							return function() {
								if (!a.complete || !b.complete) {
									setTimeout(difference, 10);
								} else {
									// Once they are ready, create a diff. This returns an ImageData object.
									setTimeout(function() {
										// prepare
										$(a).show(); $(b).show();
										$(out).children().remove()

										// diff
										diff = imagediff.diff(a, b);
										canvas = imagediff.createCanvas(diff.width, diff.height); // Now create a canvas,
										context = canvas.getContext('2d'); // get its context
										context.putImageData(diff, 0, 0); // and finally draw the ImageData diff.
										out.appendChild(canvas); // Add the canvas element to the container.

										// for visibility
										canvas.setAttribute('class', 'span11');
										$(a).hide(); $(b).hide();
									}, 10);
								}

							};
						})(get_or_append_img('__img_base', a.src), get_or_append_img('__img_new', b.src));
						difference();

						function get_or_append_img(id, src) {
							var $target = $('#' + id);
							if ($target.length <= 0) {
								$target = $('<img>')
									.appendTo($(document.body))
									.attr('id', id).css({
										'max-width' : '9999px'
									});
							}
							return $target.attr('src', src).hide().get(0);
						}
					});
					//####################################################################
					// json format
					$(document).on('click', '#json-format-exec', function() {
						var //is_unescaped_unicode = $('#json-unescaped-unicode').is(':checked')
							$result = $('#json-result')
							, json;

						try {
							json = JSON.parse($('#json-data').val());
						} catch(e) {
							$result.val(e.message)
							return;
						}

						var formatted_json = JSON.stringify(json, null, "  ");
						$result.val(formatted_json);
					});
					//####################################################################
					// sql format
					$(document).on('click', '#sql-format-exec', function(){
						var sql = $('#sql-text').val()
							, $result = $('#sql-result')
							, formatted = vkbeautify.sql(sql, "    ");

						$result.val(formatted);
					});

					$(document).on('keypress', '#sql-text', function(ev) {
						if (ev.which == 13 && ev.ctrlKey) {
							$('#sql-format-exec').trigger('click');
							return false;
						}
					});

					//####################################################################
					// easing

					$.fn.fadeToggle = $.fn.fadeToggle || function(speed, easing, callback) {
						return this.animate({opacity: 'toggle'}, speed, easing, callback);
					};

					function resetTest() {
						var effectType = $('#easing-effectType').val();
						if (effectType == 'fadeIn()'){
							$('#easing-tester').fadeOut(0)
						}
						else if (effectType == 'fadeOut()') {
							$('#easing-tester').fadeIn(0)
						}
						else if (effectType == 'slideDown()') {
							$('#easing-tester').slideUp(0)
						}
						else if ( effectType == 'slideUp()' ) {
							$('#easing-tester').slideDown(0)
						}
						else if (effectType == 'slideToggle()') {
							$('#easing-tester').slideUp(0)
						}
						else if (effectType == 'fadeToggle()') {
							$('#easing-tester').fadeOut(0)
						}
					}

					$(document).on('change', '#easing-effectType, #easing-easeType', function() {
						$(this).blur()
						resetTest()
					})

					function runTest() {
						var easeType = $('#easing-easeType').val();
						var effectType = $('#easing-effectType').val();
						var testDuration = parseInt( $('#easing-testDuration').val() );

						$('#easing-testDuration, #easing-test, #easing-reset').blur()


						// alert(testDuration)

						if (effectType == 'fadeIn()'){
							$('#easing-tester').fadeIn(testDuration,easeType)
						} else if (effectType == 'fadeOut()') {
							$('#easing-tester').fadeOut(testDuration,easeType)
						} else if (effectType == 'slideDown()') {
							$('#easing-tester').slideDown(testDuration,easeType)
						} else if ( effectType == 'slideUp()' ) {
							$('#easing-tester').slideUp(testDuration,easeType)
						} else if (effectType == 'slideToggle()') {
							$('#easing-tester').slideToggle(testDuration,easeType)
						} else if (effectType == 'fadeToggle()') {
							$('#easing-tester').fadeToggle(testDuration,easeType)
						}
					}

					$(document).on('click', '#easing-test', function() {
						var effectType = $('#easing-effectType').val();

						if (effectType == 'fadeToggle()' || effectType == 'slideToggle()') {
							runTest()
						} else {
							resetTest()
							runTest()
						}
					});
					$(document).on('click', '#easing-reset', function() {
						resetTest()
					})
					$(document).on('keydown', function(event) {
						if ($('#easing-easeType').length <= 0) return;

						var effectType = $('#easing-effectType').val()
							, code = event.keyCode
							, $duration = $('#easing-testDuration');
						// alert(event.keyCode)
						console.log(event.keyCode);
						if (event.target === $duration.get(0)) {
							if (!event.altKey && (code == 39 || code == 37)) return;
							if (code == 38 || code == 40) {
								// 38 == up, 40 == down
								var sub = code == 38 ? 100 : -100
									, val = parseInt($duration.val(), 10) + sub;
								if (val < 0) {
									val = 0;
								}
								$duration.val(val);
							}
							setTimeout(function() {
								$duration.focus();
							}, 100);
						}
						if (code == 39 || code == 37) {
							// alert('right')
							var is_right = code == 39 ? true : false
								, q = '#easing-easeType option:' + (is_right ?  "last" : "first")
								, $options = $('#easing-easeType option')
								, $cur = $('#easing-easeType option:selected')
								, ease = $(q).val()
								, $next = is_right ? $cur.next('option') : $cur.prev('option');
							if ($cur.val()!=ease) {
								$cur.prop('selected', false);
								$next.prop('selected', true);
							}
						} else if (code == 13) {
							//alert('return')
							if (effectType == 'fadeToggle()' || effectType == 'slideToggle()') {
								runTest()
							} else {
								resetTest()
								runTest()
							}
						} else if (code == 32) {
							//alert('space?')
						}
					})

					// resetTest()

					//####################################################################
					// pjax handlers
					$(document).pjax('a', '#pjax-content');
					$(document).on('submit', 'form[data-pjax]', function(ev) {
						$.pjax.submit(ev, '#pjax-content');
					});
					$('a[data-no-pjax]').on('click', function(e) {
						location.href = $(this).attr('href');
						// e.stopPropagation();
						e.preventDefault();
						return false;
					});

					//####################################################################
					// change navbar state
					$('.nav a').on('click', function() {
						$('.nav li').removeClass('active');
						$(this).parent('li').addClass('active');
					});
					// navbar collapse
					$('.navbar-inner a').on('click', function() {
						 if (!$(this).data('target') && !$(this).data('toggle')) {
								var $link = $('a[data-toggle="collapse"]')
									 , $target = $($link.data('target'));
								if ($target.hasClass('in')) {
									 $link.trigger('click');
							}
						}
					});
				})
			}(jQuery, this);
		//]]></script>

		<!-- Le fav and touch icons -->
		<!--
		<link rel="shortcut icon" href="images/favicon.ico">
		<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
		<link rel="apple-touch-icon" sizes="72x72" href="images/apple-touch-icon-72x72.png">
		<link rel="apple-touch-icon" sizes="114x114" href="images/apple-touch-icon-114x114.png">
		-->
	</head>
	<body>
		<div class="navbar navbar-fixed-top navbar-inverse">
			<div class="navbar-inner">
				<div class="container">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>
					<a class="brand" href="?action=index">Utilities</a>
					<div class="nav-collapse">
						<ul class="nav">
							<li<?php if ($__action == "index") echo ' class="active"'; ?>><a href="?action=index">SQL Tools</a></li>
							<li<?php if ($__action == "diff") echo ' class="active"'; ?>><a href="?action=diff">Diff</a></li>
							<li<?php if ($__action == "image_diff") echo ' class="active"'; ?>><a href="?action=image_diff">Image Diff</a></li>
							<li<?php if ($__action == "jstools") echo ' class="active"'; ?>><a href="?action=jstools">JS Tools</a></li>
							<li<?php if ($__action == "easing") echo ' class="active"'; ?>><a href="?action=easing">Easing</a></li>
							<li<?php if ($__action == "geohash") echo ' class="active"'; ?>><a href="?action=geohash">GeoHash</a></li>

							<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">Other<b class="caret"></b></a>
									<ul class="dropdown-menu">
										<li><a href="inputtest.php" data-no-pjax="true">Input Test</a></li>
										<li><a href="status.php" data-no-pjax="true">Status</a></li>
<!--
										<li class="divider"></li>
										<li class="nav-header">Nav header</li>
										<li><a href="#">Separated link</a></li>
										<li><a href="#">One more separated link</a></li>
-->
									</ul>
								</li>
<!--
							<li<?php if ($__action == "test") echo ' class="active"'; ?>><a href="?action=test">Test</a></li>
							<li><a href="#about">About</a></li>
							<li><a href="#contact">Contact</a></li>
-->
						</ul>
					</div><!--/.nav-collapse -->
				</div>
			</div>
		</div>

		<div class="container" id="pjax-content">
			<?php echo $content ?>
		</div> <!-- /container -->

	</body>
</html>

@@index

<div class="accordion" id="js-accordion-index">
	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-sql-binder" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-index">
				<i class="icon-random icon-white"></i> SQL Binder
			</a>
		</div>
		<div class="sql-binder accordion-body collapse in" id="js-accordion-sql-binder">
			<div class="accordion-inner">
				<form action="?action=index" method="GET" data-pjax="true">
					<p>
						<label>SQL</label>
						<textarea name="sql" rows="3" class="span6 sql-binder-enter-submit"><?php echo h($sql) ?></textarea>
						<label>Binds</label>
						<textarea name="binds" rows="3" class="span6 sql-binder-enter-submit"><?php echo h($binds) ?></textarea>
						<div class="control-group">
							<input type="submit" value="Render" class="btn">
						</div>
						<textarea id="sqlbinder-result" rows="4" class="span6" readonly onclick="this.select()"><?php if ($result){ echo h($result); } ?></textarea>
					</p>
				</form>
			</div>
		</div>
	</div>
	<div class="accordion-group">
		<div class="accordion-heading">

			<a href="#js-accordion-sql-format" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-index">
				<i class="icon-align-justify icon-white"></i> SQL Format
			</a>
		</div>
		<div id="js-accordion-sql-format" class="accordion-body collapse">
			<div class="accordion-inner">
				<textarea id="sql-text" name="json" rows="3" class="span6"></textarea>
				<div class="control-group">
					<button id="sql-format-exec" class="btn">Format</button>
				</div>
				<textarea id="sql-result" rows="10" class="span6" readonly onclick="this.select()"></textarea>
			</div>
		</div>
	</div>
</div>


@@diff
<div class="form-horizontal">
	<div class="control-group">
		<label class="control-label">Context Size (Optional) : </label>
		<div class="controls">
			<input type="text" id="param-context-size" value="" class="input-mini">
		</div>
		<label class="control-label">
			Diff View Type:
		</label>
		<div class="controls">
			<label class="radio">
				<input type="radio" name="_viewtype" checked="checked" id="param-sidebyside">Side by Side
			</label>
			<label class="radio">
				<input type="radio" name="_viewtype" id="param-inline">Inline
			</label>
		</div>
	</div>
</div>
<div class="row">
	<div class="span6">
		<h3>Base Text</h3>
		<textarea id="param-base" style="width:100%;height:300px;"></textarea>
	</div>
	<div class="span6">
		<h3>New Text</h3>
		<textarea id="param-new" style="width:100%;height:300px;"></textarea>
	</div>
</div>
<button type="button" id="btn-exec-diff" value="Diff" class="btn btn-primary btn-large" style="width : 80%; margin : 0 auto; display : block;"> Diff </button><br><br>

<hr>
<div id="diff-output"> </div>

@@image_diff

<div id="capture">
	<h2>Image Diff</h2>
	<p class="lead">
		Press Ctrl-V/Cmd-v, clipboard's image will display here.<br >
		If you want to clear image, double-click the image.
	</p>
	<div class="row">
		<div class="span6">
			<h3>Image1</h3>
			<img src="#" id="img-base" style="display:none">
		</div>
		<div class="span6">
			<h3>Image2</h3>
			<img src="#" id="img-new" style="display:none">
		</div>
		<div class="span12">
			<h3>Diff Result</h3>
			<div id="img-canvas" class="span12"></div>
		</div>
	</div>
</div>
<script>
	$(function() {
		$('#capture').pasteCatcher();
	})
	$('#capture').focus();
</script>

@@jstools

<div class="accordion" id="js-accordion-jsutils">

	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-keycode" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-fire icon-white"></i> KeyCode
			</a>
		</div>
		<div id="js-accordion-keycode" class="accordion-body collapse">
			<div class="accordion-inner">
				<input type="text" placeholder="Input Key..." id="js-keycode-input">
				<table class="table">
					<tr>
						<th>keydown</th>
						<th>keypress</th>
						<th>keyup</th>
					</tr>
					<tr>
						<td id="js-keycode-keydown"></td>
						<td id="js-keycode-keypress"></td>
						<td id="js-keycode-keyup"></td>
					</tr>
					<tr>
						<td id="js-keycode-keydown-char">　</td>
						<td id="js-keycode-keypress-char">　</td>
						<td id="js-keycode-keyup-char">　</td>
					</tr>
					<tr>
						<td id="js-keycode-keydown-shift">　</td>
						<td id="js-keycode-keypress-shift">　</td>
						<td id="js-keycode-keyup-shift">　</td>
					</tr>
				</table>
				<?php echo partial('keycode.html') ?>

			</div>
		</div>
<script type="text/javascript">
!function($) {
	var fn = function(event_name) {
		var q = '#js-keycode-' + event_name
			, c_q = q + '-char'
			, s_q = q + '-shift'
			, attrs = "shiftKey,metaKey,altKey".split(",")
			, counter = 0;
		return function(e) {
			console.log(e);
			$(q).text(++counter % 2 ? '◇' : '◆');

			var code = e.keyCode || e.charCode
				, shifts = $(attrs).filter(function() {
				return e[this]
			}).toArray().join(",");
			$(c_q).text([
				String.fromCharCode(code)
				, ' [ ', e.which, ',keyCode=',e.keyCode , ',charCode=', e.charCode, ' ]'
			].join(""));
			$(s_q).text(shifts || '　');
			e.preventDefault();
		};
	}
	$('#js-keycode-input')
		.on('keydown', fn("keydown"))
		.on('keypress', fn("keypress"))
		.on('keyup', fn("keyup"));
}(jQuery);
</script>
	</div>

	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-escape-tool" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> Encode
			</a>
		</div>
		<div class="sql-binder accordion-body collapse" id="js-accordion-escape-tool">
			<div class="accordion-inner" >
				<span class="span2">Input:</span><textarea id="escape-in" name="json" rows="3" class="span6" onfocus="this.select()"></textarea>
				<div class="control-group" style="text-align:center;">
					<button id="escape-inout-escape" class="btn"><i class="icon-arrow-down"></i>Escape</button>
				</div>

				<div class="control-group">
					<span class="span2">escape:</span><textarea id="escape-out-escape" rows="3" class="span6" onfocus="this.select()"></textarea>
					<button id="escape-outin-unescape" class="btn"><i class="icon-arrow-up"></i>unescape</button>
				</div>
				<div class="control-group">
				<span class="span2">encodeURI:</span><textarea id="escape-out-encodeURI" rows="3" class="span6" onfocus="this.select()"></textarea>
				<button id="escape-outin-decodeURI" class="btn"><i class="icon-arrow-up"></i>unescape</button>
				</div>
				<div class="control-group">
				<span class="span2">encodeURI<br>Component:</span><textarea id="escape-out-encodeURIComponent" rows="3" class="span6" onfocus="this.select()"></textarea>
				<button id="escape-outin-decodeURIComponent" class="btn"><i class="icon-arrow-up"></i>unescape</button>
				</div>
				<!-- <div class="control-group">
					<button id="escape-inout-encodeURI" class="btn"><i class="icon-arrow-down"></i>encodeURI</button>
					<button id="escape-outin-decodeURI" class="btn"><i class="icon-arrow-up"></i>decodeURI</button>
				</div>
				<div class="control-group">
					<button id="escape-inout-encodeURIComponent" class="btn"><i class="icon-arrow-down"></i>encodeURIComponent</button>
					<button id="escape-outin-decodeURIComponent" class="btn"><i class="icon-arrow-up"></i>decodeURIComponent</button>
				</div> -->
			</div>
		</div>
	</div>

	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-json-tool" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> JSON Format
			</a>
		</div>
		<div class="sql-binder accordion-body collapse" id="js-accordion-json-tool">
			<div class="accordion-inner">
				<textarea id="json-data" name="json" rows="3" class="span6"></textarea>
				<div class="control-group">
					<button id="json-format-exec" class="btn">Format</button>
				</div>
				<textarea id="json-result" rows="10" class="span6" readonly onclick="this.select()"></textarea>
			</div>
		</div>
	</div>

	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-xml-format" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-file icon-white"></i> XML Util
			</a>
		</div>
		<div id="js-accordion-xml-format" class="accordion-body collapse">
			<div class="accordion-inner">
				<form id="xml-format-form" action="" method="POST">
				<textarea name="xml_data" id="xml-input" rows="10" class="span6" onfocus="this.select()"></textarea>
				<div class="control-group">
					<button id="xml-format-exec" class="btn" data-action-uri="?action=ajax_xml_format">XML Format</button>
					<button id="xml-json-exec" class="btn" data-action-uri="?action=ajax_xml_json">toJSON</button>
				</div>
				<textarea id="xml-result" rows="10" class="span6" readonly onfocus="this.select()"></textarea>
				</form>
			</div>
		</div>
<script>
!function($) {
	var Converter = {
		toHex : function(b) {
			var c = "";
			for (i = 0; i < b.length; i++) {
				if (b.charCodeAt(i).toString(16).toUpperCase().length < 2) {
					c += "%0" + b.charCodeAt(i).toString(16).toUpperCase()
				} else {
					c += "%" + b.charCodeAt(i).toString(16).toUpperCase()
				}
			}
			return c
		}
		, toHexHTML : function(b) {
			var c = "";
			for (i = 0; i < b.length; i++) {
				if (b.charCodeAt(i).toString(16).toUpperCase().length < 2) {
					c += "&#x0" + b.charCodeAt(i).toString(16).toUpperCase() + ";"
				} else {
					c += "&#x" + b.charCodeAt(i).toString(16).toUpperCase() + ";"
				}
			}
			return c
		}
		, toUnicode: function(b) {
			var result = "";
			for (i = 0; i < b.length; i++) {
				result += "&#" + b.charCodeAt(i)
			}
			return result
		}
		, dec2Hex : function(e) {
			var d = "0123456789ABCDEF";
			var c = 15;
			var b = "";
			while (e != 0) {
				b = d.charAt(e & c) + b;
				e >>>= 4
			}
			return b.length == 0 ? "0" : b
		}
		, toAscii : function(e) {
			if (e != "") {
				var b = e;
				var c = b.substring(2, b.length).split("&#");
				var d = "";
				for (i = 0; i < c.length; i++) {
					if (dec2hex(c[i]).length < 2) {
						d += "%0" + dec2hex(c[i])
					} else {
						d += "%" + dec2hex(c[i])
					}
				}
				return unescape(d)
			}
			return ""
		}
		, hexToAscii : function(f) {
			if (f != "") {
				var d = f;
				var c = d.substring(3, d.length).split("&#x");
				var e = "";
				var b = "";
				for (i = 0; i < c.length; i++) {
					b = c[i].substring(c[i].length - 3, c[i].length - 1);
					if (b.length < 2) {
						e += "%0" + b;
						alert(b, " - ", e)
					} else {
						e += "%" + b
					}
				}
				return unescape(e)
			}
			return ""
		}
		, escapeHTML : function(d, j, h, c) {
			j = j || "ENT_QUOTES";
			var f = 0, e = 0, g = false;
			if (typeof j === "undefined" || j === null) {
				j = 2
			}
			d = d.toString();
			if (c !== false) {
				d = d.replace(/&/g, "&amp;")
			}
			d = d.replace(/</g, "&lt;").replace(/>/g, "&gt;");
			var b = {"ENT_NOQUOTES": 0,"ENT_HTML_QUOTE_SINGLE": 1,"ENT_HTML_QUOTE_DOUBLE": 2,"ENT_COMPAT": 2,"ENT_QUOTES": 3,"ENT_IGNORE": 4};
			if (j === 0) {
				g = true
			}
			if (typeof j !== "number") {
				j = [].concat(j);
				for (e = 0; e < j.length; e++) {
					if (b[j[e]] === 0) {
						g = true
					} else {
						if (b[j[e]]) {
							f = f | b[j[e]]
						}
					}
				}
				j = f
			}
			if (j & b.ENT_HTML_QUOTE_SINGLE) {
				d = d.replace(/'/g, "&#039;") // '
			}
			if (!g) {
				d = d.replace(/"/g, "&quot;") // "
			}
			return d
		}
		, decodeHTML : function(c, g) {
			g = g || "ENT_QUOTES";
			var e = 0, d = 0, f = false;
			if (typeof g === "undefined") {
				g = 2
			}
			c = c.toString().replace(/&lt;/g, "<").replace(/&gt;/g, ">");
			var b = {"ENT_NOQUOTES": 0,"ENT_HTML_QUOTE_SINGLE": 1,"ENT_HTML_QUOTE_DOUBLE": 2,"ENT_COMPAT": 2,"ENT_QUOTES": 3,"ENT_IGNORE": 4};
			if (g === 0) {
				f = true
			}
			if (typeof g !== "number") {
				g = [].concat(g);
				for (d = 0; d < g.length; d++) {
					if (b[g[d]] === 0) {
						f = true
					} else {
						if (b[g[d]]) {
							e = e | b[g[d]]
						}
					}
				}
				g = e
			}
			if (g & b.ENT_HTML_QUOTE_SINGLE) {
				c = c.replace(/&#0*39;/g, "'")
			}
			if (!f) {
				c = c.replace(/&quot;/g, '"')
			}
			c = c.replace(/&amp;/g, "&");
			return c
		}
	};
	var Base64 = {
		_keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
		encode: function(d, l) {
		var b = "";
		var m, j, g, k, h, f, e;
		var c = 0;
		if (l) {
			d = Base64._utf8_encode(d)
		}
		while (c < d.length) {
			m = d.charCodeAt(c++);
			j = d.charCodeAt(c++);
			g = d.charCodeAt(c++);
			k = m >> 2;
			h = ((m & 3) << 4) | (j >> 4);
			f = ((j & 15) << 2) | (g >> 6);
			e = g & 63;
			if (isNaN(j)) {
				f = e = 64
			} else {
				if (isNaN(g)) {
					e = 64
				}
			}
			b = b + this._keyStr.charAt(k) + this._keyStr.charAt(h) + this._keyStr.charAt(f) + this._keyStr.charAt(e)
		}
		return b
	},decode: function(d, l) {
		var b = "";
		var m, j, g;
		var k, h, f, e;
		var c = 0;
		d = d.replace(/[^A-Za-z0-9\+\/\=]/g, "");
		while (c < d.length) {
			k = this._keyStr.indexOf(d.charAt(c++));
			h = this._keyStr.indexOf(d.charAt(c++));
			f = this._keyStr.indexOf(d.charAt(c++));
			e = this._keyStr.indexOf(d.charAt(c++));
			m = (k << 2) | (h >> 4);
			j = ((h & 15) << 4) | (f >> 2);
			g = ((f & 3) << 6) | e;
			b = b + String.fromCharCode(m);
			if (f != 64) {
				b = b + String.fromCharCode(j)
			}
			if (e != 64) {
				b = b + String.fromCharCode(g)
			}
		}
		if (l) {
			b = Base64._utf8_decode(b)
		}
		return b
	},_utf8_encode: function(d) {
		d = d.replace(/\r\n/g, "\n");
		var b = "";
		for (var f = 0; f < d.length; f++) {
			var e = d.charCodeAt(f);
			if (e < 128) {
				b += String.fromCharCode(e)
			} else {
				if ((e > 127) && (e < 2048)) {
					b += String.fromCharCode((e >> 6) | 192);
					b += String.fromCharCode((e & 63) | 128)
				} else {
					b += String.fromCharCode((e >> 12) | 224);
					b += String.fromCharCode(((e >> 6) & 63) | 128);
					b += String.fromCharCode((e & 63) | 128)
				}
			}
		}
		return b
	},_utf8_decode: function(b) {
		var d = "";
		var e = 0;
		var f = c1 = c2 = 0;
		while (e < b.length) {
			f = b.charCodeAt(e);
			if (f < 128) {
				d += String.fromCharCode(f);
				e++
			} else {
					if ((f > 191) && (f < 224)) {
						c2 = b.charCodeAt(e + 1);
						d += String.fromCharCode(((f & 31) << 6) | (c2 & 63));
						e += 2
					} else {
						c2 = b.charCodeAt(e + 1);
						c3 = b.charCodeAt(e + 2);
						d += String.fromCharCode(((f & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
						e += 3
					}
				}
		}
		return d
	}};
	var $in = $('#escape-in')
		, $out_escape = $('#escape-out-escape')
		, $out_enuri = $('#escape-out-encodeURI')
		, $out_enuricom = $('#escape-out-encodeURIComponent');
	$('#escape-inout-escape').on('click', function() {
		var v = $in.val();
		$.each([{
			out : $out_escape
			, fn : escape
		}, {
			out : $out_enuri
			, fn : encodeURI
		}, {
			out : $out_enuricom
			, fn : encodeURIComponent
		}], function(i, item) {
			$(item.out).val(item.fn.call(window, v));
		})
	});
	$('#escape-outin-unescape').on('click', function() {
		$in.val(unescape($out_escape.val()));
	});
	$('#escape-outin-decodeURI').on('click', function() {
		$in.val(decodeURI($out_enuri.val()));
	});
	$('#escape-outin-decodeURIComponent').on('click', function() {
		$in.val(decodeURIComponent($out_enuricom.val()));
	});

	$('#xml-format-exec,#xml-json-exec').on('click', function() {
		var $form = $('#xml-format-form');
		$.post($(this).data('action-uri'), $form.serialize()).done(function(res) {
			$('#xml-result').text(res);
		}).fail(function(xhr, err, cause) {
			$('#xml-result').text(cause + " : " + xhr.responseText);
		});
		return false;
	});

}(jQuery);
</script>
	</div>

	<link href='https://jumly.herokuapp.com/release/jumly.min.css' rel="stylesheet"/>
	<!-- <script src='http://code.jquery.com/jquery-2.0.0.min.js'></script> -->
	<script src='https://jumly.herokuapp.com/release/jumly.min.js'></script>
	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-jumly" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> Jumly
			</a>
		</div>
		<div id="js-accordion-jumly" class="accordion-body collapse">
			<div class="accordion-inner">
				<div class="row">
					<div class="span5">
						<p>Try changing below. Available directives are @found,
@message, @create, @reply, @alt, @loop, @ref and @note.
In more detail, see <a href='http://jumly.herokuapp.com/reference.html'>the reference</a>.</p>
			<textarea id="code" class="span5" rows="10">@found "You", ->
	@message "Think", ->
		@message "Write your idea", "JUMLY", ->
			@create "Diagram"
jumly.css "background-color":"#8CC84B"</textarea>
					</div>
					<div class="span6 tab-content" style="height: 400px;">
						<div id="diagram_container"></div>
						<div id="notification"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-webkit2scss" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> Webkit2Scss
			</a>
		</div>
		<div id="js-accordion-webkit2scss" class="accordion-body collapse">
			<div class="accordion-inner">
			<textarea id="css_code" class="span5" rows="10"></textarea>
			<div class="control-group">
				<button id="webkit2scss-exec" class="btn">Convert</button>
			</div>
			<textarea id="scss_code" class="span5" rows="10" readonly></textarea>
			<textarea id="scss_mixin_code" class="span5" rows="10" readonly>@import "compass/css3/transition"

@mixin animation ($animations...) {
  @include experimental(animation, $animations);
}
// Individual Animation Properties
@mixin animation-name ($names...) {
  @include experimental(animation-name, $names);
}
@mixin animation-duration ($times...) {
  @include experimental(animation-duration, $times);
}
@mixin animation-timing-function ($motions...) {
// ease | linear | ease-in | ease-out | ease-in-out
  @include experimental(animation-timing-function, $motions);
}
@mixin animation-iteration-count ($values...) {
// infinite | <number>
  @include experimental(animation-iteration-count, $values);
}
@mixin animation-direction ($directions...) {
// normal | alternate
  @include experimental(animation-direction, $directions);
}
@mixin animation-play-state ($states...) {
// running | paused
  @include experimental(animation-play-state, $states);
}
@mixin animation-delay ($times...) {
  @include experimental(animation-delay, $times);
}
@mixin animation-fill-mode ($modes...) {
// none | forwards | backwards | both
  @include prefixer(animation-fill-mode, $modes);
}
// keyframes mixin
@mixin keyframes($name) {
  @-webkit-keyframes #{$name} {
    @content;
  }
  @-moz-keyframes #{$name} {
    @content;
  }
  @-ms-keyframes #{$name} {
    @content;
  }
  @-o-keyframes #{$name} {
    @content;
  }
  @keyframes #{$name} {
    @content;
  }
}</textarea>
			</div>
		</div>
	</div>
<script>
	$('#webkit2scss-exec').click(function() {
		var s = $('#css_code').val();
		s = s.replace(/-webkit-([^\s:]+)\s*:([^;]+)\s*;/gm, "@include $1($2);")
			.replace(/@-webkit-keyframes\s*([^{]+)\s*/gm, "@include keyframes($1) ");
		$('#scss_code').val(s);
		return false;
	});
</script>
<script>
jQuery(function($) {
	var compile, id;
	function compile() {
		var ex;
		$('#code').removeClass("failed");
		$("#notification").text("");
		try {
			window.JUMLY.eval($('#code'), {
				into: $('#diagram_container')
			});
			return;
		} catch (_error) {
			ex = _error;
			$('#code').addClass("failed");
			return $("#notification").text(ex);
		}
	};
	var load_fn = function() {
		if (typeof JUMLY === 'undefined' || typeof CoffeeScript === 'undefined') {
			return setTimeout(load_fn, 100);
		}
		compile();
	};
	setTimeout(load_fn, 100);
	id = -1;
	return $('#code').on("keyup", function(a, b, c) {
		clearTimeout(id);
		return id = setTimeout(compile, 500);
	});
});
</script>
<!--
	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-xxx" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> Dummy
			</a>
		</div>
		<div id="js-accordion-xxx" class="accordion-body collapse">
			<div class="accordion-inner">
			</div>
		</div>
	</div>
 -->
</div>

@@geohash

<div>
	<form action="?action=geohash" method="POST" data-pjax="true">
		<p>
			<label><span class="span2">Latitude, Longitude</span>
				<input id="js-latlon" name="latlon" type="text" class="span6" onclick="this.select()" value="<?php echo h($latlon) ?>">
			</label>
			<div class="control-group" style="padding-left: 360px;">
				<button id="js-calc-geohash" class="btn"><i class="icon-arrow-down"></i>Hash</button>
				<button id="js-calc-latlon" class="btn"><i class="icon-arrow-up"></i>LatLon</button>
			</div>
			<label><span class="span2">GeoHash</span>
				<input id="js-geohash" type="text" class="span6" onclick="this.select()" value="<?php echo h($geohash); ?>">
			</label>

			<div class="control-group" style="padding-left: 360px;">
				<button id="js-apply-latlon" class="btn"><i class="icon-arrow-up"></i>LatLon</button>
				<button id="js-apply-map" class="btn"><i class="icon-arrow-down"></i>Apply map</button>
			</div>
		</p>
	</form>

	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-map" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> Map
			</a>
		</div>
		<div id="js-accordion-map" class="accordion-body collapse">
			<div class="accordion-inner">
				<div id="js-map" style="width:450px; height:450px; float:left;"></div>
				<div class="control-group">
					<label>
						<span class="span2">Latitute, Longitude</span>
						<input id="js-latlon-info" type="text" class="span4" onclick="this.select()" readonly>
					</label>
					<div class="control-group">
						<label>
							<span class="span2">Geohash Length</span>
							<input id="js-geohash-length" type="text" class="span2" value="5">
							<button id="js-draw-geohash"class="btn">Draw</button>
						</label>
						<div class="span4" id="js-draw-result">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
<script type="text/javascript" src="js/geohash.js"></script>
<script type="text/javascript">
(function($){
	function bootstrap() {
		var $latlon = $('#js-latlon')
			, $geohash = $('#js-geohash');
		$('#js-calc-geohash').on('click', function() {
			var values = $latlon.val().split(",")
				, lat = values[0], lon = values[1];
			var hash = geohash.encode(lat, lon);

			$geohash.val(hash);

			var hash = geohash.encode(lat, lon, 6);
			var neighbors = geohash.neighbors(hash);
			return false;
		});
		$('#js-calc-latlon').on('click', function() {
			var hash = $geohash.val();
			var latlon = geohash.decode(hash).join(",");
			$latlon.val(latlon);
			return false;
		});
		var opt = {
			zoom: 10,
			center: new google.maps.LatLng(35.65855154020919, 139.70120429992676),
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		var map = null
			, latestLatLng = null
			, latestPin = null;
		var MapUtil = {
			setPosition : function(latLng) {
				if (map) {
					latestLatLng = latLng;
					map.panTo(latLng);
					if (latestPin) {
						latestPin.setMap(null);
						latestPin = null;
					}
					latestPin = new google.maps.Marker({
						position : latLng,
						map : map
					});
				}
			}
		};
		var $cur_latlon = $('#js-latlon-info');
		$('#js-accordion-map').on('shown', function() {
			if (!map) {
				map = new google.maps.Map($("#js-map").get(0), opt);
				google.maps.event.addListener(map, 'center_changed', function(ev) {
					MapUtil.setPosition(map.getCenter());

					var latlng = [latestLatLng.lat(), latestLatLng.lng()];
					$cur_latlon.val(latlng);
				});
			}
		});
		$('#js-apply-latlon').on('click', function() {
			if (!latestLatLng) { return false; }
			var latlng = [latestLatLng.lat(), latestLatLng.lng()];
			$latlon.val(latlng);
			$geohash.val('');
			return false;
		});
		$('#js-apply-map').on('click', function() {
			var latlng = $latlon.val().split(",");
			if (latlng.length >= 2 && map) {
				var point = new google.maps.LatLng(latlng[0], latlng[1]);
				MapUtil.setPosition(point);
			}
			return false;
		});


		var rects = [];

		$('#js-draw-geohash').on('click', function() {
			var hash_len = parseInt($('#js-geohash-length').val(), 10);
			var lat = latestLatLng.lat(), lng = latestLatLng.lng();

			var hash = geohash.encode(lat, lng, hash_len);
			var neighbors = geohash.neighbors(hash);

			$.each(rects, function(i, r) { r.setMap(null); });

			var boxes = $.map(neighbors.concat([hash]), function(hash, i) {
				var box = geohash.bbox(hash);
				// console.log(box);
				var rect = new google.maps.Rectangle({
					strokeColor: "#FF0000",
					strokeOpacity: 0.8,
					strokeWeight: 2,
					fillColor: "#FF0000",
					fillOpacity: 0.35,
					map: map,
					bounds: new google.maps.LatLngBounds(
						new google.maps.LatLng(box.n, box.w),
						new google.maps.LatLng(box.s, box.e))
				});
				rects.push(rect);
				return box;
			});

			// TODO : use boxes
			var box = geohash.bbox(hash);
			var calcDistance = google.maps.geometry.spherical.computeDistanceBetween
			, LatLng = google.maps.LatLng;

			var xdistance = calcDistance(new LatLng(box.n, box.w), new LatLng(box.n, box.e))//geoHashBox.neighbors.topleft.corners.topleft.distanceFrom(geoHashBox.neighbors.topright.corners.topright);
			var ydistance = calcDistance(new LatLng(box.n, box.w), new LatLng(box.s, box.e))//geoHashBox.neighbors.topleft.corners.topleft.distanceFrom(geoHashBox.neighbors.bottomleft.corners.bottomleft);
			var searcharea = parseInt((xdistance/1000) * (ydistance/1000)*100)/100
			, units = "m";
			if (xdistance>2000) {
				xdistance = parseInt(xdistance/10)/100;
				ydistance = parseInt(ydistance/10)/100;
				units = "km";
			} else {
				xdistance = parseInt(xdistance+0.5);
				ydistance = parseInt(ydistance+0.5);
				units = "m";
			}
			var s = ["LEFT(geohash, " + hash + ") IN ("
					+ neighbors.concat([hash]).join(', ') + ')'
				, (lat * 1000/1000) + ", " + (lng * 1000/1000)
					+ " [w:" + xdistance + units + ", h:" + ydistance + units + "] (" + searcharea + "km2)"
			].join("\n")

			$('#js-draw-result').html(s.replace(/\n/, '<br>'));
		});
	}

	var id = setInterval(function() {
		if (typeof google !== 'undefined'
			 && typeof google.maps.LatLng !== 'undefined') {
			bootstrap();
			clearInterval(id);
		}
	}, 100);
}(jQuery));
  </script>

