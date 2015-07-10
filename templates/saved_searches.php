<div class="wrap">
	<h2>Manage saved searches</h2>

	<?php if (empty($searches)) { ?>
		<div id="message" class="error below-h2">
			<p>No saved search queries found!</p>
		</div>
	<?php } ?>

	<div id="pmp-saved-searches-list">
		<?php foreach ($searches as $id => $search) { ?>
		<div class="pmp-saved-search" data-search-id="<?php echo $id; ?>">
				<h3 class="pmp-saved-search-title"><?php echo $search->options->title; ?></h3>
				<div class="pmp-saved-search-details">
				<?php if (!empty($search->options->query_auto_create)) {
					if ($search->options->query_auto_create == 'draft') { ?>
						<p><strong>Draft</strong> posts will be automatically created from results for this query.</p>
					<?php } else if ($search->options->query_auto_create == 'publish') { ?>
						<p>Posts will be automatically <strong>published</strong> from results for this query.</p>
					<?php } else if ($search->options->query_auto_create == 'off') { ?>
						<p><strong>Do nothing</strong> with results for this query.</p>
					<?php }
				} ?>
				</div>
				<div class="pmp-saved-search-actions">
					<a href="<?php echo admin_url('admin.php?page=pmp-search&search_id=' . $id); ?>">View and edit this search query</a> |
					<a data-search-id="<?php echo $id; ?>" class="pmp-delete-saved-search" href="#">Delete</a>
				</div>
			</div>
		<?php } ?>
	</div>
</div>

<?php pmp_save_search_query_template(); ?>
<?php pmp_modal_underscore_template(); ?>

<script type="text/javascript">
	var PMP = <?php echo json_encode($PMP); ?>;
</script>
