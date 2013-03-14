<?php

if (!function_exists('h')) {
	function h($s) {
		return is_array($s) ? array_map("h", $s) : htmlspecialchars($s, ENT_QUOTES);
	}
}
class IndexMaker {
	var $entries;
	var $format = "";
	static function getInstance() {
		return new static();
	}

	function __construct() {
		$this->init();
	}

	function init(){
		$self = basename(__FILE__);
		$entries = array();
		$dh = opendir(dirname(__FILE__));
		while (false !== ($entry = readdir($dh))) {
			// if ($entry == "." || $entry == "..") {
			// 	continue;
			// }
			if ($entry == $self) {
				continue;
			}
			$is_dir = is_dir($entry);
			$entries[] = array(
				"is_dir" => $is_dir,
				"path" => $entry,
			);
		}
		uasort($entries, array($this, 'sortEntries'));
		$this->entries = $entries;
	}

	function sortEntries($a, $b) {
		if ($a["is_dir"] == $b["is_dir"]) {
			return $a["path"] < $b["path"] ? -1 : 1;
		}
		return $b["is_dir"] ? 1 : -1;
	}
}

$index = IndexMaker::getInstance();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<title><?php echo $_SERVER["REQUEST_URI"]; ?></title>
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap.min.css"></link>
	<link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap-responsive.min.css"></link>
	<!--[if lt IE 9]>
		<script src="http://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.6.2/html5shiv.js"></script>
	<![endif]-->
	<style type="text/css">
		.contents { padding : 60px; }
	</style>
</head>
<body>
	<div class="navbar navbar-fixed-top navbar-inverse">
		<div class="navbar-inner">
			<div class="container">
				<a class="brand" href=""><?php echo $_SERVER["REQUEST_URI"]; ?></a>
			</div>
		</div>
	</div>
	<div class="container contents">

		<ul class="nav nav-list">
<?php foreach ($index->entries as $item): ?>
			<li><a href="<?php echo h($item["path"]) ?>"><i class="<?php echo $item["is_dir"] ? 'icon-folder-open' : 'icon-file' ?>"></i><?php echo h($item["path"])?></a></li>
<?php endforeach; ?>
		</ul>
	</div> <!-- /container -->
</body>
