<?php

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

	$dom = dom_import_simplexml($xml)->ownerDocument;
	$dom->preserveWhiteSpace = false;
	$dom->loadXML(params("xml_data"));
	$dom->formatOutput = true;
	set("contents", $dom->saveXml());
}

function action_ajax_xml_json() {
	$xml = simplexml_load_string(params("xml_data"));
	set('contents', json_encode($xml));
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
	$('#xml-format-exec,#xml-json-exec').on('click', function() {
		var $form = $('#xml-format-form');
		$.post($(this).data('action-uri'), $form.serialize()).done(function(res) {
			$('#xml-result').text(res);
		});
		return false;
	});

}(jQuery);
</script>
	</div>
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
