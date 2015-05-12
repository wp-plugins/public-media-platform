<?php

/**
 * Enqueue styles and scripts for the search page
 *
 * @since 0.1
 */
function pmp_enqueue_assets() {
	wp_register_script('pmp-common', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-common.js',
		array('jquery', 'underscore', 'backbone'), PMP_VERSION, true);

	wp_register_style('pmp-common', PMP_PLUGIN_DIR_URI . '/assets/css/style.css');

	wp_register_script('pmp-typeahead', PMP_PLUGIN_DIR_URI . '/assets/js/vendor/typeahead.bundle.js',
		array('jquery'), PMP_VERSION, true);

	if (isset($_GET['page'])) {
		$page = $_GET['page'];

		if ($page == 'pmp-search') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script(
				'pmp-search', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-search.js',
				array('pmp-common'), PMP_VERSION, true);
		}

		if ($page == 'pmp-groups-menu') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script(
				'pmp-groups-menu', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-groups-menu.js',
				array('pmp-common', 'pmp-typeahead'), PMP_VERSION, true);
		}

		if (in_array($page, array('pmp-series-menu', 'pmp-properties-menu'))) {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script(
				'pmp-collections-menu', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-collections-menu.js',
				array('pmp-common'), PMP_VERSION, true);
		}

		if ($page == 'pmp-options-menu') {
			wp_enqueue_script(
				'pmp-options-menu', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-options.js',
				array('jquery', 'underscore'), PMP_VERSION, true);
		}

		return;
	}

	$screen = get_current_screen();
	if ($screen->base == 'post' && $screen->post_type == 'post')
		wp_enqueue_style('pmp-common');
}
add_action('admin_enqueue_scripts', 'pmp_enqueue_assets');

/**
 * Print the underscore template for the PMP.Modal view.
 *
 * @since 0.2
 */
function pmp_modal_underscore_template() { ?>
<script type="text/template" id="pmp-modal-tmpl">
	<div class="pmp-modal-header">
		<div class="pmp-modal-close"><span class="close">&#10005;</span></div>
	</div>
	<div class="pmp-modal-content"><% if (content) { %><%= content %><% } %></div>
	<div class="pmp-modal-actions">
		<span class="spinner"></span>
		<% _.each(actions, function(v, k) { %>
			<a href="#" class="<%= k %> button button-primary"><%= k %></a>
		<% }); %>
	</div>
</script><?php
}

/**
 * Builds a PMP object with common attributes used throughout the plugin's javascript files.
 *
 * @since 0.2
 */
function pmp_json_obj($add=array()) {
	return array_merge(array(
		'creators' => array_flip(pmp_get_creators()),
		'ajax_nonce' => wp_create_nonce('pmp_ajax_nonce')
	), $add);
}
