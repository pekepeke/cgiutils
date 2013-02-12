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
		} else {
			eval('?>' . section($view_path));
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

function section($name = null) {
	static $data = null;
	static $sections = array();

	if (is_null($data)) {
		$data = preg_replace('/\r\n|\r|\n/', "\n", file_get_contents(__FILE__, FALSE, NULL, __COMPILER_HALT_OFFSET__));
		$contents = explode('@@', $data);
		foreach (explode('@@', $data) as $section) {
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
		<script src="js/difflib/difflib.js"></script>
		<script src="js/difflib/diffview.js"></script>
		<script src="js/imagediff.min.js"></script>

		<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
		<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<script src="//css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
		<![endif]-->
		<script>//<![[CDATA[
!function($) {
	var pasteCatcher = document.createElement("div")
		, is_registered = false;
	// Firefox allows images to be pasted into contenteditable elements
	pasteCatcher.setAttribute("contenteditable", "");

	// We can hide the element and append it to the body,
	pasteCatcher.style.opacity = 0;
	pasteCatcher.style.height = 0;
	pasteCatcher.style.width = 0;
	pasteCatcher.style.position = 'absolute';
	$(function() {
		document.body.appendChild(pasteCatcher);
	});

	$.fn.pasteCatcher = function() {
		if (!window.Clipboard) {
			// pasteCatcher.focus();
			$(this).on('click', function() {
				// as long as we make sure it is always in focus
				$(pasteCatcher).css({
					top : $(document).scrollTop()
				}).focus();
			});
		}

		// Add the paste event listener
		if (!is_registered) {
			$(window).on("paste", pasteHandler);
			is_registered = true;
		}
	};

	/* Handle paste events */
	function pasteHandler(ev) {
		var e = ev.originalEvent;
		// pasteCatcher.focus();
		$(pasteCatcher).css({
			top : $(document).scrollTop()
		}).focus();
		// We need to check if event.clipboardData is supported (Chrome)
		if (e.clipboardData) {
			// Get the items from the clipboard
			var items = e.clipboardData.items;
			if (items) {
				// Loop through all items, looking for any kind of image
				for (var i = 0; i < items.length; i++) {
					if (items[i] && items[i].type.indexOf("image") !== -1) {
						// We need to represent the image as a file,
						var blob = items[i].getAsFile();
						// and use a URL or webkitURL (whichever is available to the browser)
						// to create a temporary URL to the object
						var URLObj = window.URL || window.webkitURL;
						var source = URLObj.createObjectURL(blob);

						// The URL can then be used as the source of an image
						createImage(source);
					}
				}
			}
		// If we can't handle clipboard data directly (Firefox),
		// we need to read what was pasted from the contenteditable element
		} else {
			// This is a cheap trick to make sure we read the data
			// AFTER it has been inserted.
			setTimeout(checkInput, 1);
		}
	}

	/* Parse the input in the paste catcher element */
	function checkInput() {
		// Store the pasted content in a variable
		var child = pasteCatcher.childNodes[0];

		// Clear the inner html to make sure we're always
		// getting the latest inserted content
		pasteCatcher.innerHTML = "";

		if (child) {
			// If the user pastes an image, the src attribute
			// will represent the image as a base64 encoded string.
			if (child.tagName === "IMG") {
					createImage(child.src);
			}
		}
	}

	function createImage(source) {
		// Creates a new image from a given source
		var pastedImage = new Image();
		pastedImage.onload = function() {
			// You now have the image!
		}
		pastedImage.src = source;
		$(window).trigger('pasteCatcher', [pastedImage, source]);
	}

}(jQuery);
			!function($, Global) {
				$(function() {
					// index
					$(document).on('keypress', '.sql-binder input,.sql-binder textarea', function(ev) {
						if (ev.which == 13) {
							$(this).parents('form').submit();
							return false;
						}
					});
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
							return ;
						} else if (!$b.data('src')) { // }if (!b.src) {
							b.src = src;//img.src;
							$b.data('src', src);//img.src);
							$b.show();
						}

						var difference = (function(a, b) {
							return function() {
								$(a).hide(); $(b).hide();
								if (!a.complete || !b.complete) {
									setTimeout(difference, 10);
								} else {
									// Once they are ready, create a diff. This returns an ImageData object.
									setTimeout(function() {
										$(a).show(); $(b).show();
										$(out).children().remove()
										diff = imagediff.diff(a, b);
										canvas = imagediff.createCanvas(diff.width, diff.height); // Now create a canvas,
										context = canvas.getContext('2d'); // get its context
										context.putImageData(diff, 0, 0); // and finally draw the ImageData diff.
										out.appendChild(canvas); // Add the canvas element to the container.
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
							return $target.attr('src', src).show().get(0);
						}
					});

					// pjax handlers
					$(document).pjax('a', '#pjax-content');
					$(document).on('submit', 'form[data-pjax]', function(ev) {
						$.pjax.submit(ev, '#pjax-content');
					});

					// change navbar state
					$('.nav a').on('click', function() {
						$('.nav li').removeClass('active');
						$(this).parent('li').addClass('active');
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
							<li<?php if ($__action == "index") echo ' class="active"'; ?>><a href="?action=index">SQL Binder</a></li>
							<li<?php if ($__action == "diff") echo ' class="active"'; ?>><a href="?action=diff">Diff</a></li>
							<li<?php if ($__action == "image_diff") echo ' class="active"'; ?>><a href="?action=image_diff">Image Diff</a></li>
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

			<div class="sql-binder">
				<form action="?action=index" method="GET" data-pjax="true">
					<p>
						<label>SQL</label>
						<textarea name="sql" rows="3" class="span6"><?php echo h($sql) ?></textarea>
						<label>Binds</label>
						<textarea name="binds" rows="3" class="span6"><?php echo h($binds) ?></textarea>
						<div class="control-group">
							<input type="submit" value="render" class="btn">
						</div>
						<textarea rows="4" class="span6" readonly onclick="this.select()"><?php if ($result){ echo h($result); } ?></textarea>
					</p>
				</form>
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

@@test
