<div class="wrap">
<h2>PMP <?php echo $name; ?></h2>

	<div id="pmp-collection">
		<div id="pmp-collection-actions">
			<p class="submit">
				<input type="submit" name="pmp-create-collection" id="pmp-create-collection" class="button button-primary" value="Create new <?php echo strtolower($name); ?>">
			</p>
			<?php if (!empty($PMP['default_collection'])) { ?>
				<form id="pmp-unset-default-<?php echo $profile; ?>-form" method="post">
					<p class="submit">
						<input type="submit"
							name="pmp-unset-default-<?php echo $profile; ?>"
							id="pmp-unset-default-<?php echo $profile; ?>"
							class="button button-primary"
							value="Unset default <?php echo $profile; ?>">
					</p>
				</form>
			<?php } ?>
		</div>

		<div id="pmp-collection-container">
			<span class="spinner"></span>
			<div id="pmp-collection-list"></div>
		</div>
	</div>
</div>

<?php pmp_modal_underscore_template(); ?>

<script type="text/template" id="pmp-create-new-collection-form-tmpl">
	<h2>Create a <?php echo strtolower($name); ?></h2>
	<form id="pmp-collection-create-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="<?php echo $name; ?> title" required>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="<?php echo $name; ?> tags">
		<p class="pmp-hint">Separate tags with commas</p>
	</form>
</script>

<script type="text/template" id="pmp-modify-collection-form-tmpl">
	<h2>Modify <?php echo strtolower($name); ?></h2>
	<form id="pmp-collection-modify-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="<?php echo $name; ?> title" required
			<% if (collection.get('attributes').title) { %>value="<%= collection.get('attributes').title %>"<% } %>>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="<?php echo $name; ?> tags"
			<% if (collection.get('attributes').tags) { %>value="<%= collection.get('attributes').tags %>"<% } %>>
		<p class="pmp-hint">Separate tags with commas</p>

		<input type="hidden" name="guid" id="guid" value="<%= collection.get('attributes').guid %>" >
	</form>
</script>

<script type="text/template" id="pmp-default-collection-form-tmpl">
	<div class="pmp-collection-default-container">
		<h2>Set default <?php echo strtolower($name); ?> for new posts</h2>
		<p>Do you really want to set the <?php echo strtolower($name); ?><strong>"<%= collection.get('attributes').title %>"</strong> as the default <?php echo strtolower($name); ?> for all new posts?</p>
		<form id="pmp-collection-default-form">
			<input type="hidden" name="guid" id="guid" value="<%= collection.get('attributes').guid %>" >
		</form>
	</div>
</script>

<script type="text/template" id="pmp-collection-items-tmpl">
	<% collection.each(function(item) { %>
		<div class="pmp-collection-container">
			<h3><%= item.get('attributes').title %>
				<% if (item.get('attributes').guid == PMP.default_collection) { %><span class="pmp-default-collection">(default)</span><% } %></h3>
			<div class="pmp-collection-actions">
				<ul>
					<li>
						<a class="pmp-collection-modify" data-guid="<%= item.get('attributes').guid %>" href="#">Modify</a>
					</li>
					<% if (item.get('attributes').guid !== PMP.default_collection) { %>
					<li>
						<a class="pmp-collection-default" data-guid="<%= item.get('attributes').guid %>" href="#">Set as default</a>
					</li>
					<% } %>
				</ul>
			</div>
		</div>
	<% }); %>
</script>

<script type="text/javascript">
	var PMP = <?php echo json_encode($PMP); ?>;
</script>
