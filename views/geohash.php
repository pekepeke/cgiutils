<div>
	<form action="?action=geohash" method="POST" data-pjax="true">
		<div>
			<label><span class="span2">Latitude, Longitude</span>
				<input id="js-latlon" name="latlon" type="text" class="span6" onclick="this.select()" value="<?php echo h($latlon) ?>">
			</label>
			<div class="control-group" style="margin:0 auto;width: 200px">
				<button id="js-calc-geohash" class="btn"><i class="icon-arrow-down"></i>Hash</button>
				<button id="js-calc-latlon" class="btn"><i class="icon-arrow-up"></i>LatLon</button>
			</div>
			<label><span class="span2">GeoHash</span>
				<input id="js-geohash" type="text" class="span6" onclick="this.select()" value="<?php echo h($geohash); ?>">
			</label>
		</div>

		<div>
			<div class="control-group" style="margin:0 auto; width: 200px;">
				<button id="js-apply-latlon" class="btn"><i class="icon-arrow-up"></i>LatLon</button>
				<button id="js-apply-map" class="btn"><i class="icon-arrow-down"></i>Apply map</button>
			</div>
		</div>
	</form>

	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-map" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-jsutils">
				<i class="icon-align-justify icon-white"></i> Map
			</a>
		</div>
		<style>
			#js-map {
				width:450px;
				height:450px;
				float:left;
			}
			@media screen and (max-width: 450px) {
				#js-map {
					width:320px;
					height:320px;
				}
			}
		</style>
		<div id="js-accordion-map" class="accordion-body collapse">
			<div class="accordion-inner">
				<div id="js-map" style=""></div>
				<div class="control-group span6">
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

