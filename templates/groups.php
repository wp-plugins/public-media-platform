<div class="wrap">
	<h2>PMP Groups &amp; Permissions</h2>

	<div id="pmp-groups">
		<div id="pmp-groups-actions">
			<p class="submit">
				<input type="submit" name="pmp-create-group" id="pmp-create-group" class="button button-primary" value="Create new group">
			</p>
			<?php if (!empty($PMP['default_group'])) { ?>
				<form id="pmp-unset-default-group-form" method="post">
					<p class="submit">
						<input type="submit" name="pmp-unset-default-group" id="pmp-unset-default-group"
							class="button button-primary" value="Unset default group">
					</p>
				</form>
			<?php } ?>
		</div>

		<div id="pmp-groups-container">
			<span class="spinner"></span>
			<div id="pmp-groups-list"></div>
		</div>
	</div>
</div>

<?php pmp_modal_underscore_template(); ?>

<script type="text/template" id="pmp-create-new-group-form-tmpl">
	<h2>Create a group</h2>
	<form id="pmp-group-create-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Group title" required>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Group tags">
		<p class="pmp-hint">Separate tags with commas</p>
	</form>
</script>

<script type="text/template" id="pmp-modify-group-form-tmpl">
	<h2>Modify group</h2>
	<form id="pmp-group-modify-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Group title" required
			<% if (group.get('attributes').title) { %>value="<%= group.get('attributes').title %>"<% } %>>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Group tags"
			<% if (group.get('attributes').tags) { %>value="<%= group.get('attributes').tags %>"<% } %>>
		<p class="pmp-hint">Separate tags with commas</p>

		<input type="hidden" name="guid" id="guid" value="<%= group.get('attributes').guid %>" >
	</form>
</script>

<script type="text/template" id="pmp-default-group-form-tmpl">
	<div class="pmp-group-default-container">
		<h2>Set default group for new posts</h2>
		<p>Do you really want to set the group <strong>"<%= group.get('attributes').title %>"</strong> as the default group for all new posts?</p>
		<form id="pmp-group-default-form">
			<input type="hidden" name="guid" id="guid" value="<%= group.get('attributes').guid %>" >
		</form>
	</div>
</script>

<script type="text/template" id="pmp-manage-users-tmpl">
	<div class="pmp-manage-users-container">
		<p class="pmp-label">Manage users for group:</p>
		<h2><%= group.get('attributes').title %></h2>
		<div id="pmp-users-list">
			<form id="pmp-users-form">
			<% if (users.first().get('items').length > 0) { %>
					<% users.first().get('items').each(function(user) { %>
						<div class="pmp-user">
							<%= user.get('attributes').title %>
							<input type="hidden" name="pmp-users" value="<%= user.get('attributes').guid %>" />
							<span class="remove">&#10005;</span>
						</div>
					<% }); %>
			<% } else { %>
				<p class="error">No users found.</p>
			<% } %>
			</form>
		</div>
		<div id="pmp-add-users">
			<p class="pmp-label">Add users</p>
			<form id="pmp-add-users-form">
				<input type="text" id="pmp-user-search" name="pmp-user-search" placeholder="Start typing a user's name">
			</form>
		</div>
	</div>
</script>

<script type="text/template" id="pmp-groups-items-tmpl">
	<% groups.each(function(group) { %>
		<div class="pmp-group-container">
			<h3><%= group.get('attributes').title %>
				<% if (group.get('attributes').guid == PMP.default_group) { %><span class="pmp-default-group">(default)</span><% } %></h3>
			<div class="pmp-group-actions">
				<ul>
					<li>
						<a class="pmp-group-modify" data-guid="<%= group.get('attributes').guid %>" href="#">Modify</a>
					</li>
					<% if (group.get('attributes').guid !== PMP.default_group) { %>
					<li>
						<a class="pmp-group-default" data-guid="<%= group.get('attributes').guid %>" href="#">Set as default</a>
					</li>
					<% } %>
					<li>
						<a class="pmp-manage-users" data-guid="<%= group.get('attributes').guid %>" href="#">Manage users</a>
					</li>
				</ul>
			</div>
		</div>
	<% }); %>
</script>

<script type="text/javascript">
	var PMP = <?php echo json_encode($PMP); ?>;
</script>
