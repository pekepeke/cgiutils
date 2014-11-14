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


