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

	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-jumly" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> Jumly
			</a>
		</div>
		<link href='http://jumly.tmtk.net/release/jumly.min.css' rel="stylesheet"/>
		<script src='http://jumly.tmtk.net/release/jumly.min.js'></script>

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
<!--
<script src='http://code.jquery.com/jquery-2.1.0.min.js'></script>
 -->
<script>
// var jq2 = $.noConflict();
jQuery(function($) {
	var compile, id;
	function compileJUMLY() {
		var ex;
		$('#code').removeClass("failed");
		$("#notification").text("");
		try {
			window.JUMLY.eval($('#code'), {
				into: '#diagram_container'
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
		compileJUMLY();
	};
	setTimeout(load_fn, 100);
	id = -1;
	return $('#code').on("keyup", function(a, b, c) {
		clearTimeout(id);
		return id = setTimeout(compileJUMLY, 500);
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


