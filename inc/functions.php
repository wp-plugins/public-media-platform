<?php

/**
 * Render a template by specifying a filename and context.
 *
 * @param (string) $template -- the filename of the template to render.
 * @param (array) $context -- associative array of values used within the template.
 *
 * @since 0.1
 */
function pmp_render_template($template, $context=false) {
	if (!empty($context))
		extract($context);

	include PMP_TEMPLATE_DIR . '/' . $template;
}

/**
 * Return a hash where keys are creator names and values are their respective GUIDs.
 *
 * @since 0.1
 */
function pmp_get_creators() {
	return array(
		'APM' => '98bf597a-2a6f-446c-9b7e-d8ae60122f0d',
		'NPR' => '6140faf0-fb45-4a95-859a-070037fafa01',
		'PBS' => 'fc53c568-e939-4d9c-86ea-c2a2c70f1a99',
		'PRI' => '7a865268-c9de-4b27-a3c1-983adad90921',
		'PRX' => '609a539c-9177-4aa7-acde-c10b77a6a525'
	);
}

/**
 * Return a has where keys are content type names and values are respective profile aliases.
 *
 * @since 0.1
 */
function pmp_get_profiles() {
	return array(
		'Story' => 'story',
		'Audio' => 'audio',
		'Video' => 'video',
		'Image' => 'image',
		'Series' => 'series',
		'Episode' => 'episode'
	);
}

/**
 * Similar to `media_sideload_image` except that it simply returns the attachment's ID on success
 *
 * @param (string) $file the url of the image to download and attach to the post
 * @param (integer) $post_id the post ID to attach the image to
 * @param (string) $desc an optional description for the image
 *
 * @since 0.1
 */
function pmp_media_sideload_image($file, $post_id, $desc=null) {
	if (!empty($file)) {
		// Set variables for storage, fix file filename for query strings.
		preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);
		$file_array = array();
		$file_array['name'] = basename($matches[0]);

		// Download file to temp location.
		$file_array['tmp_name'] = download_url($file);

		// If error storing temporarily, return the error.
		if (is_wp_error($file_array['tmp_name'])) {
			return $file_array['tmp_name'];
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload($file_array, $post_id, $desc);

		// If error storing permanently, unlink.
		if (is_wp_error($id)) {
			@unlink($file_array['tmp_name']);
		}

		return $id;
	}
}

/**
 * Verify that we have all settings required to successfully query the PMP API.
 *
 * @since 0.1
 */
function pmp_verify_settings() {
	$options = get_option('pmp_settings');
	return (
		!empty($options['pmp_api_url']) &&
		!empty($options['pmp_client_id']) &&
		!empty($options['pmp_client_secret'])
	);
}

/**
 * Verify that a post's publish date is set according to data retrieved from the PMP API
 * when a draft post transitions to published post.
 *
 * @since 0.2
 */
function pmp_on_post_status_transition($new_status, $old_status, $post) {
	if ($old_status == 'draft' && $new_status == 'publish') {
		$custom_fields = get_post_custom($post->ID);

		if (!empty($custom_fields['pmp_guid'][0]) && !empty($custom_fields['pmp_published'][0])) {
			$post_data = array(
				'ID' => $post->ID,
				'post_date' => date('Y-m-d H:i:s', strtotime($custom_fields['pmp_published'][0]))
			);

			$updated_post = wp_update_post($post_data);
		}
	}
}
add_action('transition_post_status',  'pmp_on_post_status_transition', 10, 3 );

/**
 * Add a "PMP Pushed" date to the meta actions box.
 *
 * @since 0.2
 */
function pmp_last_modified_meta() {
	global $post;

	// Only show meta if this post came from the PMP
	$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);
	$pmp_mod = get_post_meta($post->ID, 'pmp_modified', true);
	if (empty($pmp_guid)) return;

	// Link to the PMP support searcher (for now)
	$options = get_option('pmp_settings');
	if ($options && $options['pmp_api_url'] && strpos($options['pmp_api_url'], 'sandbox')) {
		$pmp_link = 'https://support.pmp.io/sandboxsearch?text=guid%3A' . $pmp_guid;
	}
	else {
		$pmp_link = 'https://support.pmp.io/search?text=guid%3A' . $pmp_guid;
	}

	// Format similar to WP's published date
	$pmp_local = get_date_from_gmt(date('Y-m-d H:i:s', strtotime($pmp_mod)), 'M n, Y @ G:i');
?>
  <div id="pmp-publish-meta">
		<div class="misc-pub-section curtime">
			<span id="timestamp">PMP guid: <b><a target="_blank" href="<?php echo $pmp_link; ?>"><?php echo substr($pmp_guid, 0, 8); ?><span class="ext-link dashicons dashicons-external"></span></a></b></span>
		</div>
		<div class="misc-pub-section curtime">
			<span id="timestamp">PMP modified: <b><?php echo $pmp_local; ?></b></span>
		</div>
	</div>
<?php
}
add_action('post_submitbox_misc_actions', 'pmp_last_modified_meta');

/**
 * Add a "Publish and push to PMP" button the post publish actions meta box.
 *
 * @since 0.2
 */
function pmp_publish_and_push_to_pmp_button() {
	global $post;

	// Check if post is in the PMP, and if it's mine
	$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);
	$pmp_mine = pmp_post_is_mine($post->ID);
	if ($pmp_guid && !$pmp_mine) return;

	// Base display/disabled on post status
	$is_disabled = ($post->post_status != 'publish');
	if ($is_disabled) {
		$helper_text = 'You must publish first!';
	}
	else if (!$pmp_guid) {
		$helper_text = 'Not in PMP';
	}
	else {
		$helper_text = 'Post will be updated';
	}
?>
	<div id="pmp-publish-actions">
		<p class="helper-text"><?php echo $helper_text; ?></p>
		<input type="submit" name="pmp_update_push" id="pmp-update-push"
			<?php if ($is_disabled) echo 'disabled'; ?>
			class="button button-pmp button-large" value="Push to PMP">
	</div>
<?php
}
add_action('post_submitbox_start', 'pmp_publish_and_push_to_pmp_button');

/**
 * Push content to PMP when user clicks "Push to PMP"
 *
 * @since 0.2
 */
function pmp_push_to_pmp($post_id) {
	if (isset($_POST['pmp_update_push']) && !wp_is_post_revision($post_id)) {
		return pmp_handle_push($post_id);
	}
}
add_action('save_post', 'pmp_push_to_pmp', 11);

/**
 * Handle pushing post content to PMP. Works with posts and attachments (images).
 *
 * @since 0.2
 */
function pmp_handle_push($post_id) {
	$post = get_post($post_id);
	$author = get_user_by('id', $post->post_author);

	$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);

	$sdk = new SDKWrapper();

	$obj = new \StdClass();
	$obj->attributes = (object) array(
		'published' => date('c', strtotime($post->post_date))
	);

	if ($post->post_type == 'post') {
		$obj->attributes = (object) array_merge((array) $obj->attributes, array(
			'description' => strip_tags(apply_filters('the_content', $post->post_content)),
			'title' => $post->post_title,
			'byline' => $author->display_name,
			'contentencoded' => apply_filters('the_content', $post->post_content),
			'teaser' => $post->post_excerpt
		));
	} else if ($post->post_type == 'attachment') {
		$alt_text = get_post_meta($post_id, '_wp_attachment_image_alt', true);
		$obj->attributes = (object) array_merge((array) $obj->attributes, array(
			'title' => (!empty($alt_text))? $alt_text : $post->post_title,
			'description' => $post->post_excerpt
		));
	}

	// Set default collections (series & property), permissions group
	$obj->links = new \StdClass();

	// Set the alternate link
	if ($post->post_type == 'post')
		$alternate = get_permalink($post->ID);
	else if ($post->post_type == 'attachment')
		$alternate = wp_get_attachment_url($post->ID);

	$obj->links->alternate[] = (object) array('href' => $alternate);

	// Build out the collection array
	if ($post->post_type == 'post') {
		$obj->links->collection = array();

		$default_series = get_option('pmp_default_series', false);
		if (!empty($default_series))
			$obj->links->collection[] = (object) array('href' => $sdk->href4guid($default_series));

		$default_property = get_option('pmp_default_property', false);
		if (!empty($default_property))
			$obj->links->collection[] = (object) array('href' => $sdk->href4guid($default_property));
	}

	// Build out the permissions group profile array
	$default_group = get_option('pmp_default_group', false);
	if (!empty($default_group))
		$obj->links->permission[] = (object) array('href' => $sdk->href4guid($default_group));

	// If this is a post with a featured image, push the featured image as a PMP Doc and include
	// it as a link in the Doc.
	if ($post->post_type == 'post' && has_post_thumbnail($post->ID)) {
		$featured_img_guid = pmp_handle_push(get_post_thumbnail_id($post->ID));

		$obj->links->item = array();

		if (!empty($featured_img_guid))
			$obj->links->item[] = (object) array('href' => $sdk->href4guid($featured_img_guid));
	}

	// If this is an attachment post, build out the enclosures array to be sent over the wire.
	if ($post->post_type == 'attachment') {
		$obj->links->enclosure = pmp_enclosures_for_media($post->ID);
	}

	if (!empty($pmp_guid)) {
		$doc = $sdk->fetchDoc($pmp_guid);
		$doc->attributes = (object) array_merge((array) $doc->attributes, (array) $obj->attributes);
		$doc->links = (object) array_merge((array) $doc->links, (array) $obj->links);
	} else {
		if ($post->post_type == 'post')
			$doc = $sdk->newDoc('story', $obj);
		else if ($post->post_type == 'attachment')
			$doc = $sdk->newDoc('image', $obj);
	}

	if (empty($doc->attributes->itags))
		$doc->attributes->itags = array();

	if (!in_array('wp_pmp_push', $doc->attributes->itags))
		$doc->attributes->itags = array_merge($doc->attributes->itags, array('wp_pmp_push'));

	$doc = apply_filters('pmp_before_push', $doc, $post->ID);
	$doc->save();
	do_action('pmp_after_push', $doc, $post->ID);

	$post_meta = pmp_get_post_meta_from_pmp_doc($doc);

	if ($post->post_type == 'attachment') {
		$post_meta = array_merge($post_meta, array(
			'_wp_attachment_image_alt' => $doc->attributes->title, // alt text
		));
	}

	foreach ($post_meta as $key => $value)
		update_post_meta($post->ID, $key, $value);

	return $doc->attributes->guid;
}

/**
 * Build an array of enclosures for a given "media"/attachment post. Currently works with
 * image attachments only.
 *
 * @since 0.2
 */
function pmp_enclosures_for_media($media_id) {
	$allowed_sizes = array(
		'thumbnail',
		'small',
		'medium',
		'large',
		'original'
	);

	$media_metadata = wp_get_attachment_metadata($media_id);
	$enclosures = array();
	foreach ($media_metadata['sizes'] as $name => $meta) {
		if (in_array($name, $allowed_sizes)) {
			$src = wp_get_attachment_image_src($media_id, $name);
			$enclosures[] = (object) array(
				'href' => $src[0],
				'meta' => (object) array(
					'crop' => $name,
					'height' => $meta['height'],
					'width' => $meta['width']
				),
				'type' => $meta['mime-type']
			);
		}
	}

	$enclosures[] = (object) array(
		'href' => wp_get_attachment_url($media_id),
		'meta' => (object) array(
			'crop' => 'original',
			'height' => $media_metadata['height'],
			'width' => $media_metadata['width'],
		),
		'type' => get_post_mime_type($media_id)
	);

	return $enclosures;
}

/**
 * Find out if your PMP API user is the owner of a given post/PMP Doc
 *
 * @since 0.2
 */
function pmp_post_is_mine($post_id) {
	$pmp_owner = get_post_meta($post_id, 'pmp_owner', true);
	if (!empty($pmp_owner))
		return ($pmp_owner == pmp_get_my_guid());

	return true;
}

/**
 * Build an associatvie array of post data from a PMP Doc suitable for use with wp_insert_post or
 * wp_update_post.
 *
 * @since 0.2
 */
function pmp_get_post_data_from_pmp_doc($pmp_doc) {
	$data = json_decode(json_encode($pmp_doc), true);

	$post_data = array(
		'post_title' => $data['attributes']['title'],
		'post_content' => $data['attributes']['contentencoded'],
		'post_excerpt' => $data['attributes']['teaser'],
		'post_date' => date('Y-m-d H:i:s', strtotime($data['attributes']['published']))
	);

	return $post_data;
}

/**
 * Build an associative array of post meta based on a PMP Doc suitable for use in saving post meta.
 *
 * @since 0.2
 */
function pmp_get_post_meta_from_pmp_doc($pmp_doc) {
	$data = json_decode(json_encode($pmp_doc), true);

	$post_meta = array(
		'pmp_guid' => $data['attributes']['guid'],
		'pmp_created' => $data['attributes']['created'],
		'pmp_modified' => $data['attributes']['modified'],
		'pmp_published' => $data['attributes']['published'],
		'pmp_owner' => SDKWrapper::guid4href($data['links']['owner'][0]['href'])
	);

	if (!empty($data['attributes']['byline']))
		$post_meta['pmp_byline'] = $data['attributes']['byline'];

	return $post_meta;
}

/**
 * When querying for attachments, only show those items that belong to the current PMP user,
 * or items that have not been pushed to the PMP.
 *
 * @since 0.2
 */
function pmp_filter_media_library($wp_query) {
	if (isset($_POST['action']) && $_POST['action'] == 'query-attachments') {
		$meta_args = array(
			'relation' => 'OR',
			array(
				'key' => 'pmp_guid',
				'compare' => 'NOT EXISTS'
			),
			array(
				'key' => 'pmp_owner',
				'compare' => '==',
				'value' => pmp_get_my_guid()
			)
		);

		$wp_query->set('meta_query', $meta_args);
	}
}
add_action('pre_get_posts', 'pmp_filter_media_library');

/**
 * Get the current user's PMP GUID
 *
 * @since 0.2
 */
function pmp_get_my_guid() {
	$pmp_my_guid_transient_key = 'pmp_my_guid';
	$pmp_my_guid_transient = get_transient($pmp_my_guid_transient_key);

	if (!empty($pmp_my_guid_transient))
		return $pmp_my_guid_transient;

	$sdk = new SDKWrapper();
	$me = $sdk->fetchUser('me');

	$pmp_my_guid_transient = $me->attributes->guid;
	set_transient($pmp_my_guid_transient_key, $pmp_my_guid_transient, 0);
	return $pmp_my_guid_transient;
}

/**
 * Update the transient that stores the current user's PMP GUID
 *
 * @since 0.2
 */
function pmp_update_my_guid_transient() {
	pmp_get_my_guid();
}

if (!function_exists('var_log')) {
	/**
	 * Log anything in a human-friendly format.
	 *
	 * @param mixed $stuff the data structure to send to the error log.
	 * @since 0.2
	 */
	function var_log($stuff) { error_log(var_export($stuff, true)); }
}
