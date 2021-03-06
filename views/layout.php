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
            #img-base, #img-new {
                max-height: 100px;
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
		<script src='//cdnjs.cloudflare.com/ajax/libs/coffee-script/1.7.1/coffee-script.min.js'></script>

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
					}).on('click', '#btn-img-toggle', function() {
						var isBigState = $('#img-base').css('max-height').match(/100%/);
						var size = isBigState ? "100px" : "100%";
						$('#img-base').css('max-height', size);
						$('#img-new').css('max-height', size);
						$(this).text(isBigState ? "Expand" : "Shrink");
						return false;
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
					<ul class="nav pills">
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown">Debug<b class="caret"></b></a>
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
					</ul>
					<div class="nav-collapse">
						<ul class="nav">
							<li<?php if ($__action == "index") echo ' class="active"'; ?>><a href="?action=index">SQL Tools</a></li>
							<li<?php if ($__action == "diff") echo ' class="active"'; ?>><a href="?action=diff">Diff</a></li>
							<li<?php if ($__action == "image_diff") echo ' class="active"'; ?>><a href="?action=image_diff">Image Diff</a></li>
							<li<?php if ($__action == "jstools") echo ' class="active"'; ?>><a href="?action=jstools">JS Tools</a></li>
							<li<?php if ($__action == "easing") echo ' class="active"'; ?>><a href="?action=easing">Easing</a></li>
							<li<?php if ($__action == "geohash") echo ' class="active"'; ?>><a href="?action=geohash">GeoHash</a></li>
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

