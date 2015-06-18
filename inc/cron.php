<?php

/**
 * Query for posts with `pmp_guid` -- an indication that the post was pulled from PMP
 *
 * @since 0.1
 */
function pmp_get_pmp_posts() {
	$sdk = new SDKWrapper();
	$me = $sdk->fetchUser('me');

	$meta_args = array(
		'relation' => 'AND',
		array(
			'key' => 'pmp_guid',
			'compare' => 'EXISTS'
		),
		array(
			'key' => 'pmp_owner',
			'compare' => '!=',
			'value' => pmp_get_my_guid()
		)
	);

	$query = new WP_Query(array(
		'meta_query' => $meta_args,
		'posts_per_page' => -1,
		'post_status' => 'any'
	));

	return $query->posts;
}

/**
 * For each PMP post in the WP database, fetch the corresponding Doc from PMP and check if
 * the WP post differs from the PMP Doc. If it does differ, update the post in the WP database.
 *
 * @since 0.1
 */
function pmp_get_updates() {
	$posts = pmp_get_pmp_posts();

	$sdk = new SDKWrapper();

	foreach ($posts as $post) {
		$custom_fields = get_post_custom($post->ID);

		if (empty($custom_fields['pmp_subscribe_to_updates']))
			$subscribe_to_updates = 'on';
		else
			$subscribe_to_updates = $custom_fields['pmp_subscribe_to_updates'][0];

		if ($subscribe_to_updates == 'on') {
			$guid = $custom_fields['pmp_guid'][0];
			if (!empty($guid)) {
				$doc = $sdk->fetchDoc($guid);
				if (!empty($doc)) {
					if (pmp_needs_update($post, $doc))
						pmp_update_post($post, $doc);
				} else {
					wp_delete_post($post->ID, true);
				}
			}
		}
	}
}

/**
 * Compare the md5 hash of a WP post and PMP Doc to determine whether or not the WP post is different
 * from PMP and therefore needs updating.
 *
 * @since 0.1
 */
function pmp_needs_update($wp_post, $pmp_doc) {
	$post_modified = get_post_meta($wp_post->ID, 'pmp_modified', true);
	if ($pmp_doc->attributes->modified !== $post_modified)
		return true;
	return false;
}

/**
 * Update an existing WP post which was originally pulled from PMP with the Doc data from PMP.
 *
 * @since 0.1
 */
function pmp_update_post($wp_post, $pmp_doc) {
	$post_data = pmp_get_post_data_from_pmp_doc($pmp_doc);
	$post_data['ID'] = $wp_post->ID;

	$the_post = wp_update_post($post_data);

	if (is_wp_error($the_post))
		return $the_post;

	$post_meta = pmp_get_post_meta_from_pmp_doc($pmp_doc);

	foreach ($post_meta as $key => $value)
		update_post_meta($the_post, $key, $value);

	return $the_post;
}

/**
 * For each saved search query, query the PMP and perform the appropriate action (e.g., auto draft, auto publish or do nothing)
 *
 * @since 0.3
 */
function pmp_import_for_saved_queries() {
	$search_queries = pmp_get_saved_search_queries();
	$sdk = new SDKWrapper();

	foreach ($search_queries as $id => $query_data) {
		if ($query_data->options->query_auto_create == 'off')
			continue;

		$default_opts = array(
			'profile' => 'story',
			'limit' => 25
		);

		$last_saved_search_cron = get_option('pmp_last_saved_search_cron_' . sanitize_title($query_data->options->title), false);
		if (!empty($last_saved_search_cron))
			$default_opts['startdate'] = $last_saved_search_cron;
		else {
			// First time pulling, honor the initial pull limit
			if (!empty($query_data->options->initial_pull_limit))
				$default_opts['limit'] = $query_data->options->initial_pull_limit;
		}

		$query_args = array_merge($default_opts, (array) $query_data->query);
		$result = $sdk->queryDocs($query_args);
		if (empty($result))
			continue;

		foreach ($result->items() as $item) {
			$meta_args = array(
				array(
					'key' => 'pmp_guid',
					'value' => $item->attributes->guid
				)
			);

			$query = new WP_Query(array(
				'meta_query' => $meta_args,
				'posts_per_page' => 1,
				'post_status' => 'any'
			));

			if (!$query->have_posts()) {
				if ($query_data->options->query_auto_create == 'draft')
					$result = _pmp_create_post(true, $item);
				else if ($query_data->options->query_auto_create == 'publish')
					$result = _pmp_create_post(false, $item);

				$post_id = $result['data']['post_id'];
			} else
				$post_id = $query->posts[0]->ID;

			if (isset($query_data->options->post_category))
				wp_set_post_categories($post_id, $query_data->options->post_category, true);
		}

		update_option('pmp_last_saved_search_cron_' . sanitize_title($query_data->options->title), date('c', time()));
	}
}
