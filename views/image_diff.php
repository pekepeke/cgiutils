<div id="capture">
	<h2>Image Diff</h2>
	<p class="lead">
		Press Ctrl-V/Cmd-v, clipboard's image will display here.<br >
		If you want to clear image, double-click the image.
        <button id="btn-img-toggle" class="btn btn-primary">Expand</button>
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


