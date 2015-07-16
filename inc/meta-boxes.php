<?php

/**
 * The PMP meta box to end all PMP meta boxes.
 *
 * @since 0.3
 */
function pmp_mega_meta_box($post) {
	wp_nonce_field('pmp_mega_meta_box', 'pmp_mega_meta_box_nonce');

	$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);

	pmp_last_modified_meta($post);

	if (!empty($pmp_guid) && !pmp_post_is_mine($post->ID)) {
		pmp_subscribe_to_updates_markup($post);
	} else {
		/*
		 * Container elements for async select menus for Groups, Series and Property for the post
		 */
		if ($post->post_status == 'publish') {
		?>
		 <div id="pmp-override-defaults">
			<p>Modify the Group, Series and Property settings for this post.</p>
			<?php foreach (array('group', 'series', 'property') as $type) { ?>
			<div id="pmp-<?php echo $type; ?>-select-for-post" class="pmp-select-for-post">
				<span class="spinner"></span>
			</div>
			<?php } ?>
		</div><?php
		}

		pmp_publish_and_push_to_pmp_button($post);

		/*
		 * Javascript required for the async select menus for Groups, Series, Property
		 */ ?>
		<script type="text/javascript">
			var PMP = <?php echo json_encode(pmp_json_obj(array('post_id' => $post->ID))); ?>;
		</script><?php

		pmp_async_select_template();
	}
}

/**
 * Prints markup for the "Keep this post in sync with PMP" functionality
 *
 * @since 0.3
 */
function pmp_subscribe_to_updates_markup($post) {
	$checked = get_post_meta($post->ID, 'pmp_subscribe_to_updates', true);
?>
	<div id="pmp-subscribe-to-updates">
		<p>Keep this post in sync with the original from PMP.</p>
		<p>Note: updates will overwrite any changes made to this post.</p>
		<label for="pmp_subscribe_to_updates">
			<input <?php checked(in_array($checked, array('on', '')), true); ?> type="checkbox" name="pmp_subscribe_to_updates" /> Subscribe to updates for this post.
		</label>
	</div>
<?php
}

/**
 * Save the value of `pmp_subscribe_to_updates` post meta.
 *
 * @since 0.1
 */
function pmp_subscribe_to_update_save($post_id) {
	$pmp_guid = get_post_meta($post_id, 'pmp_guid', true);

	if (!empty($pmp_guid) && !pmp_post_is_mine($post_id)) {
		if (!isset($_POST['pmp_subscribe_to_updates']))
			$pmp_subscribe_to_updates = 'off';
		else
			$pmp_subscribe_to_updates = $_POST['pmp_subscribe_to_updates'];

		update_post_meta($post_id, 'pmp_subscribe_to_updates', $pmp_subscribe_to_updates);
	}
}

/**
 * Save the per-post settings for Group, Series, Property
 *
 * @since 0.3
 */
function pmp_save_override_defaults($post_id) {
	// Only update the override meta if we're actually pushing to PMP,
	// otherwise this is meaningless and potentially confusing.
	if (!isset($_POST['pmp_update_push']))
		return;

	$types = array('group', 'series', 'property');
	foreach ($types as $type) {
		$meta_key = 'pmp_' . $type . '_override';
		$default_guid = get_option('pmp_default_' . $type, false);

		if (isset($_POST[$meta_key])) {
			$override_guid = $_POST[$meta_key];

			// Indicate that the $type was explicitly net to false
			if (empty($override_guid))
				$override_guid = false;

			// Otherwise, set the override meta
			update_post_meta($post_id, $meta_key, $override_guid);
		}
	}
}

/**
 * Save function for the PMP mega meta box
 *
 * @since 0.3
 */
function pmp_mega_meta_box_save($post_id) {
	if (!isset($_POST['pmp_mega_meta_box_nonce']))
		return;

	if (!wp_verify_nonce($_POST['pmp_mega_meta_box_nonce'], 'pmp_mega_meta_box'))
		return;

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (!current_user_can('edit_post', $post_id))
		return;

	pmp_subscribe_to_update_save($post_id);
	pmp_save_override_defaults($post_id);
}
add_action('save_post', 'pmp_mega_meta_box_save');
