<div class="accordion" id="js-accordion-index">
	<div class="accordion-group">
		<div class="accordion-heading">
			<a href="#js-accordion-sql-binder" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-index">
				<i class="icon-random icon-white"></i> SQL Binder
			</a>
		</div>
		<div class="sql-binder accordion-body collapse in" id="js-accordion-sql-binder">
			<div class="accordion-inner">
				<form action="?action=index" method="GET" data-pjax="true">
					<p>
						<label>SQL</label>
						<textarea name="sql" rows="3" class="span6 sql-binder-enter-submit"><?php echo h($sql) ?></textarea>
						<label>Binds</label>
						<textarea name="binds" rows="3" class="span6 sql-binder-enter-submit"><?php echo h($binds) ?></textarea>
						<div class="control-group">
							<input type="submit" value="Render" class="btn">
						</div>
						<textarea id="sqlbinder-result" rows="4" class="span6" readonly onclick="this.select()"><?php if ($result){ echo h($result); } ?></textarea>
					</p>
				</form>
			</div>
		</div>
	</div>
	<div class="accordion-group">
		<div class="accordion-heading">

			<a href="#js-accordion-sql-format" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-index">
				<i class="icon-align-justify icon-white"></i> SQL Format
			</a>
		</div>
		<div id="js-accordion-sql-format" class="accordion-body collapse">
			<div class="accordion-inner">
				<textarea id="sql-text" name="json" rows="3" class="span6"></textarea>
				<div class="control-group">
					<button id="sql-format-exec" class="btn">Format</button>
				</div>
				<textarea id="sql-result" rows="10" class="span6" readonly onclick="this.select()"></textarea>
			</div>
		</div>
	</div>


	<!--
	<div class="accordion-group">
		<div class="accordion-heading">

			<a href="#js-accordion-xxx" class="accordion-toggle btn btn-inverse" data-toggle="collapse" data-parent="#js-accordion-index">
				<i class="icon-align-justify icon-white"></i>
			</a>
		</div>
		<div id="js-accordion-xxx" class="accordion-body collapse">
			<div class="accordion-inner">
			</div>
		</div>
	</div>
 	-->
</div>

