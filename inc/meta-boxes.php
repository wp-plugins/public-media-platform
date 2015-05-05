<?php

/**
 * Render post meta box which allows user to subscribe to updates from PMP for the current post.
 *
 * @since 0.1
 */
function pmp_subscribe_to_updates_meta_box($post) {
	wp_nonce_field('pmp_subscribe_to_updates_meta_box', 'pmp_subscribe_to_updates_meta_box_nonce');
	$checked = get_post_meta($post->ID, 'pmp_subscribe_to_updates', true);
?>
	<p>Keep this post in sync with the original from PMP.</p>
	<p>Note: updates will overwrite any changes made to this post.</p>
	<label for="pmp_subscribe_to_updates">
		<input <?php checked(in_array($checked, array('on', '')), true); ?> type="checkbox" name="pmp_subscribe_to_updates" /> Subscribe to updates for this post.
	</label>
<?php
}

/**
 * Save the value of `pmp_subscribe_to_updates` post meta.
 *
 * @since 0.1
 */
function pmp_subscribe_to_update_meta_box_save($post_id) {
	if (!isset($_POST['pmp_subscribe_to_updates_meta_box_nonce']))
		return;

	if (!wp_verify_nonce($_POST['pmp_subscribe_to_updates_meta_box_nonce'], 'pmp_subscribe_to_updates_meta_box'))
		return;

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (!current_user_can('edit_post', $post_id))
		return;

	if (!isset($_POST['pmp_subscribe_to_updates']))
		$pmp_subscribe_to_updates = 'off';
	else
		$pmp_subscribe_to_updates = $_POST['pmp_subscribe_to_updates'];

	update_post_meta($post_id, 'pmp_subscribe_to_updates', $pmp_subscribe_to_updates);
}
add_action('save_post', 'pmp_subscribe_to_update_meta_box_save');
