<?php

// based http://techblog.ecstudio.jp/tech-tips/phptail.html
header('Content-type:text/html; charset=UTF-8');

$options = array(
	"logfile" => "logfile.log",
);
//対象のファイルパス
$logpath = get_logpath($options, params("logfile"));
//表示する末尾の行数
$lines = params("lines", 20);
//更新インターバル (ミリ秒)
$interval = intval(params("interval", 200));

if (isset($_GET['load'])){
	echo 'file:'.basename($logpath).' reload:'.date('H:i:s').'<hr size="1"/>';

	if (!file_exists($logpath)){
		die($logpath.'は存在しません');
	}

	foreach (read_tail($logpath,$lines) as $i => $line){
		$line = rtrim($line,"\r\n");
		echo strtr(htmlspecialchars($line,ENT_QUOTES),array("\t" => '    '));
		if ($i <($lines - 1)){
			echo '<br />';
		}
	}

	exit;
}
function h($s) {
	return htmlspecialchars($s, ENT_QUOTES);
}
function params($name, $default = null) {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}
function get_logpath($options, $name) {
	if (isset($options[$name])) {
		return $options[$name];
	}
	return array_shift(array_values($options));
}

/**
 * ログをファイルから指定行数読み出す
 *
 * tail function by flash tekkie
 * http://tekkie.flashbit.net/php/tail-functionality-in-php
 *
 * @param string $file ファイルパス
 * @param int $lines 行数
 * @return array 行ごとの配列
 */
function read_tail($file, $lines) {
	//global $fsize;
	$handle = fopen($file, "r");
	$linecounter = $lines;
	$pos = -2;
	$beginning = false;
	$text = array();
	while ($linecounter> 0) {
		$t = " ";
		while ($t != "\n") {
			if(fseek($handle, $pos, SEEK_END) == -1) {
				$beginning = true;
				break;
			}
			$t = fgetc($handle);
			$pos --;
		}
		$linecounter --;
		if ($beginning) {
			rewind($handle);
		}
		$text[$lines-$linecounter-1] = fgets($handle);
		if ($beginning) break;
	}
	fclose ($handle);
	return array_reverse($text);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<title>tail</title>
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap.min.css"></link>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap-responsive.min.css"></link>
	<!--[if lt IE 9]>
		<script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.6.2/html5shiv.js"></script>
	<![endif]-->
	<style type="text/css">
	#js-console {
/*
		width:800px;
*/
		height:500px;
		overflow:scroll;
		font-size:12px;
		line-height: 0.9;
	}
	</style>
</head>
<body>
	<div class="navbar navbar-inverse">
		<div class="navbar-inner">
			<div class="container">
				<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="brand" href="#">Tail</a>
				<div class="nav-collapse collapse">
					<ul class="nav">
<!--
						<li class="active"><a href="#">Home</a></li>
						<li><a href="#about">About</a></li>
						<li><a href="#contact">Contact</a></li>
-->
					</ul>
				</div><!--/.nav-collapse -->
			</div>
		</div>
	</div>

	<div class="container">
		<select id="js-logfile" class="select">
		<?php foreach ($options as $k => $v): ?>
		<option value="<?php echo h($k) ?>"><?php echo h($k) ?></option>
		<?php endforeach; ?>
		</select>
		Interval:<input type="text" id="js-interval" class="input-mini" value="<?php echo h($interval); ?>">
		Lines:<input type="text" id="js-lines" class="input-mini" value="<?php echo h($lines); ?>">
		<button id="js-tail" class="btn btn-primary">Tail</button> <button id="js-tail-stop" class="btn btn-danger">STOP</button>
		<span id="js-status" class="text-info"></span><br />
		<pre id="js-console" class="span12"></pre>
	</div> <!-- /container -->

	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
	<script type="text/javascript">
	jQuery(function($) {

		var timer_id = null;
		$('#js-tail').on('click', function() {
			if (timer_id){
				clearInterval(timer_id);
			}
			$('#js-status').html('running...');
			timer_id = setInterval(run, $('#js-interval').val());
			return false;
		});
		$('#js-tail-stop').on('click', function() {
			clearInterval(timer_id);
			$('#js-status').empty();
		})

		function run(){
			var q = $.param({
				load : 1
				, m : new Date().getTime()
				, logfile : $('#js-logfile').val()
				, lines : $('#js-lines').val()
			})
			$('#js-console').load('?' + q);
		}
	});
	</script>
</body>
</html>
