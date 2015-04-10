<?php

if (!function_exists('h')) {
	function h($s) {
		return is_array($s) ? array_map("h", $s) : htmlspecialchars($s, ENT_QUOTES);
	}
}
function p($s) {
	echo nl2br(str_replace(' ', "&nbsp;", h(var_export($s, true))));
	// echo nl2br(h(var_export($s, true)));
}

function ifempty($v, $s) {
	if (empty($v)) echo $s;
}

// session_start();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<title><?php echo $_SERVER["REQUEST_URI"]; ?></title>
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap.min.css"></link>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap-responsive.min.css"></link>
	<!--[if lt IE 9]>
	<script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.6.2/html5shiv.js"></script>
	<![endif]-->
	<style type="text/css">
		.contents { padding : 60px; }
		td { word-break: break-all; word-wrap: break-word; }
	</style>
</head>
<body>
	<div class="navbar navbar-fixed-top navbar-inverse">
		<div class="navbar-inner">
			<div class="container">
<!--
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
-->
				<a class="brand" href="<?php echo $_SERVER['SCRIPT_NAME'] ?>"><?php echo $_SERVER["SCRIPT_NAME"]; ?></a>
				<div class="_nav-collapse">
					<ul class="nav nav-list">
						<li class=""><a href="index.php"><i class="icon-home icon-white"></i> Home</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="container contents">
		<h4 class="var-dump">Test Form <button class="btn btn-mini btn-success">Show/Hide</button></h4>
		<div style="display:none;">
			<button class="btn btn-info" id="js-method-toggle">Change Method "GET"</button>
			<form action="" method="POST" enctype="multipart/form-data">
				<fieldset>
					<label>text</label><input type="text" name="data[text]" >
					<label>textarea</label><textarea name="data[textarea]" ></textarea>
					<label class="radio">
						<input type="radio" name="data[radio]" value="1"> value = 1
					</label>
					<label class="radio">
						<input type="radio" name="data[radio]" value="2"> value = 2
					</label>
					<label class="checkbox">
						<input type="checkbox" name="data[checkbox][]" value="1"> value = 1
					</label>
					<label class="checkbox">
						<input type="checkbox" name="data[checkbox][]" value="2"> value = 2
					</label>
					<label>select</label>
					<select name="data[select]">
						<option value="">-</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
					</select>
					<label>select multiple</label>
					<select name="data[select_multi][]" multiple="multiple">
						<option value="">-</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
					</select>

					<div class="control-group">
						<label>Upload <input type="file" name="data[file]" class="file"></label>
					</div>
					<input type="submit" value="submit" class="btn btn-primary">
				</fieldset>
			</form>

		</div>
<?php
$raw_post_input = file_get_contents('php://input');
$raw_post_var = array();
if ($raw_post_input) $raw_post_var['php://input'] = $raw_post_input;
foreach(array(
	'$_GET' => $_GET,
	'$_POST' => $_POST,
	'RAW_POST_INPUT' => $raw_post_var,
	'$_FILES' => $_FILES,
	// '$_SESSION' => $_SESSION,
	'$_COOKIE' => $_COOKIE,
	'$_SERVER' => $_SERVER,
) as $name => $vars): ?>
		<h4 class="var-dump"><?php echo h($name); ?> <button class="btn btn-mini btn-success">Show/Hide</button></h4>
		<table class="table table-striped" <?php ifempty($vars, 'style="display:none;"'); ?>>
			<tr>
				<th>Parameter</th>
				<th>Value</th>
			</tr>
<?php if(is_array($vars)): ?>
<?php		foreach ($vars as $key => $val): ?>
			<tr>
				<td><?php echo h($key); ?></td>
				<td><?php p($val); ?></td>
			</tr>
<?php		endforeach; ?>
<?php endif; ?>
		</table>
<?php endforeach; ?>
	</div> <!-- /container -->

<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
<script>
jQuery(function($) {
	$('.var-dump').on('click', function() {
		// console.log($(this).next())
		$(this).next().fadeToggle();
		return false;
	});
	$('#js-method-toggle').on('click', function() {
		var $form = $('form')
			, cur_method = $form.attr('method')
			, is_get = cur_method.toLowerCase() == "get"
			, s = $(this).text()
			, method = is_get ? "POST" : "GET";

		$form.attr('method', method);
		$(this).text(s.replace(/"(get|post)"$/i, '"' + cur_method + '"'));

	})
})
</script>
</body>
