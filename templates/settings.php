<div class="wrap">
	<h2>PMP Settings</h2>

	<?php settings_errors(); ?>

	<form action="<?php echo admin_url('options.php'); ?>" method="post">
		<?php settings_fields('pmp_settings_fields'); ?>
		<?php do_settings_sections('pmp_settings'); ?>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
		</p>
	</form>

	<script type="text/template" id="pmp_client_secret_input_tmpl">
		<input id="pmp_client_secret" name="pmp_settings[pmp_client_secret]" type="password" value="" />
	</script>
</div>
