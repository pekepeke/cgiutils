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
				<a class="brand" href="<?php echo $_SERVER['SCRIPT_NAME'] ?>"><?php echo h(basename($_SERVER["SCRIPT_NAME"], ".php")); ?></a>
				<div class="_nav-collapse">
					<ul class="nav nav-list">
						<li class=""><a href="index.php"><i class="icon-home icon-white"></i> Home</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="container contents">

		<p>
			<i class="icon-check"></i> STATUS = <?php echo h($code)?><br>
			<i class="icon-time"></i> SLEEP = <?php echo h($sleep) ?>
		</p>

		<ul class="nav nav-list">
			<li><a href="index.php"><i class="icon-home"></i> Back to index</a></li>
			<li><a href="?sleep=5"><i class="icon-pause"></i> Example sleep 5</a></li>
			<li><a href="?code=200"><i class="icon-ok-sign"></i> OK</a></li>
			<li><a href="?code=301"><i class="icon-ok-sign"></i> Moved Permanently</a></li>
			<li><a href="?code=302"><i class="icon-ok-sign"></i> Moved Temporarily</a></li>
			<li><a href="?code=400"><i class="icon-remove-sign"></i> Bad Request</a></li>
			<li><a href="?code=403"><i class="icon-ban-circle"></i> Forbidden</a></li>
			<li><a href="?code=404"><i class="icon-question-sign"></i> Not Found</a></li>
			<li><a href="?code=500"><i class="icon-exclamation-sign"></i> Internal Server Error</a></li>
			<li><a href="?code=503"><i class="icon-fire"></i> Service Unavailable</a></li>
		</ul>
	</div> <!-- /container -->

<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
</body>
