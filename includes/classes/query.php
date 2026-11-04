<?php
/**
 * @package Fusion
 */

/**
 * FusionCoreQuery class.
 *
 * Class for getting querying the WordPress database using AJAX
 *
 * @since 1.3.0
 */

class FusionCoreQuery	{
		
	public function __construct() {
		
		//get post count
		add_action( 'wp_ajax_nopriv_fsn_ajax_get_post_count', array($this, 'ajax_get_post_count') );
		add_action( 'wp_ajax_fsn_ajax_get_post_count', array($this, 'ajax_get_post_count') );
		
		//get posts
		add_action( 'wp_ajax_nopriv_fsn_ajax_get_posts', array($this, 'ajax_get_posts') );
		add_action( 'wp_ajax_fsn_ajax_get_posts', array($this, 'ajax_get_posts') );
	}
	
	/**
	 * AJAX Get Post Count
	 *
	 * @since 1.3.0
	 *
	 */
	
	public function ajax_get_post_count() {
		//verify nonce
		check_ajax_referer( 'fsn-query', 'security' );
		
		$post_type = !empty($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : 'post';
		
		//only count post types that are viewable on the front end
		if (!post_type_exists($post_type) || !is_post_type_viewable($post_type)) {
			wp_send_json(new stdClass());
		}
		
		$post_count_object = wp_count_posts($post_type);
		
		wp_send_json($post_count_object);
	}
	
	/**
	 * AJAX Get Posts
	 *
	 * @since 1.3.0
	 *
	 */
	
	public function ajax_get_posts() {
		//verify nonce
		check_ajax_referer( 'fsn-query', 'security' );
		
		$args = !empty($_POST['args']) && is_array($_POST['args']) ? wp_unslash($_POST['args']) : array();
		
		$get_posts = get_posts(self::sanitize_query_args($args));
		
		wp_send_json($get_posts);
	}
	
	/**
	 * Sanitize Query Args
	 *
	 * Restrict request-supplied query arguments to an allow-list. Visitors and users who can't
	 * edit others' posts of the requested type only get published (or attachment) posts that
	 * aren't password protected.
	 *
	 * @since 1.7.1
	 *
	 * @param array $args Raw query arguments from the request.
	 * @return array Sanitized arguments for get_posts().
	 */
	
	public static function sanitize_query_args($args) {
		$post_type = !empty($args['post_type']) && is_string($args['post_type']) ? sanitize_key($args['post_type']) : 'post';
		if (!post_type_exists($post_type) || !is_post_type_viewable($post_type)) {
			$post_type = 'post';
		}
		$post_type_object = get_post_type_object($post_type);
		
		$query_args = array(
			'post_type' => $post_type,
			'posts_per_page' => isset($args['posts_per_page']) ? intval($args['posts_per_page']) : 5,
			'paged' => !empty($args['paged']) ? max(1, intval($args['paged'])) : 1,
			'suppress_filters' => true,
		);
		
		//post status: publish only (inherit for attachments) unless the user can edit others' posts of this type
		$default_status = $post_type == 'attachment' ? 'inherit' : 'publish';
		$can_view_all = !empty($post_type_object) && current_user_can($post_type_object->cap->edit_others_posts);
		if ($can_view_all && !empty($args['post_status'])) {
			$post_status = is_array($args['post_status']) ? array_map('sanitize_key', $args['post_status']) : sanitize_key($args['post_status']);
		} else {
			$post_status = $default_status;
		}
		$query_args['post_status'] = $post_status;
		if (!$can_view_all) {
			$query_args['has_password'] = false;
		}
		
		//scalar args
		if (isset($args['offset'])) {
			$query_args['offset'] = max(0, intval($args['offset']));
		}
		if (!empty($args['order']) && is_string($args['order'])) {
			$query_args['order'] = strtoupper($args['order']) == 'ASC' ? 'ASC' : 'DESC';
		}
		if (!empty($args['orderby']) && is_string($args['orderby'])) {
			$allowed_orderby = array('none', 'ID', 'author', 'title', 'name', 'type', 'date', 'modified', 'parent', 'rand', 'comment_count', 'menu_order', 'post__in');
			if (in_array($args['orderby'], $allowed_orderby, true)) {
				$query_args['orderby'] = $args['orderby'];
			}
		}
		if (isset($args['post_parent'])) {
			$query_args['post_parent'] = intval($args['post_parent']);
		}
		if (!empty($args['author'])) {
			$query_args['author'] = intval($args['author']);
		}
		if (!empty($args['category']) || !empty($args['cat'])) {
			$query_args['category'] = intval(!empty($args['category']) ? $args['category'] : $args['cat']);
		}
		if (!empty($args['category_name']) && is_string($args['category_name'])) {
			$query_args['category_name'] = sanitize_title($args['category_name']);
		}
		if (!empty($args['tag']) && is_string($args['tag'])) {
			$query_args['tag'] = sanitize_title($args['tag']);
		}
		if (!empty($args['s']) && is_string($args['s'])) {
			$query_args['s'] = sanitize_text_field($args['s']);
		}
		
		//ID list args
		foreach (array('include', 'exclude', 'post__in', 'post__not_in') as $key) {
			if (!empty($args[$key])) {
				$query_args[$key] = wp_parse_id_list($args[$key]);
			}
		}
		
		return $query_args;
	}

}

$fsn_core_query = new FusionCoreQuery();

?>