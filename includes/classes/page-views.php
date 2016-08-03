<?php
/**
 * @package Fusion
 */
 
/**
 * FusionCorePageViews class.
 *
 * Class for adding page view tracking to Fusion.
 *
 * @since 1.0.0
 */
 
$options = get_option( 'fsn_options' );
$fsn_page_views = $options['fsn_page_views'];

if ($fsn_page_views == 'on') :

class FusionCorePageViews	{
	public function __construct() {				
		
		// Initialize the language files
		load_plugin_textdomain( 'fusion', false, plugin_dir_url( __FILE__ ) . 'languages' );
		
		// Run view count incrementer
		add_action('wp_footer', array($this, 'increment_views'));
		
		// Add views update schedule on plugin activation
		register_activation_hook( __FILE__, array($this, 'views_update_schedule_activation') );
		
		// Remove views update schedule on plugin deactivation
		register_deactivation_hook( __FILE__, array($this, 'views_update_schedule_deactivation') );
		
		// Activation for MU Plugins
		add_action( 'init', array($this, 'views_update_schedule_activation') );
		
		// Add action to schedule hook
		add_action( 'fsn_views_update_schedule', array($this, 'views_update_actions') );
		
	}
	
	/**
	 * Increment views meta values
	 *
	 * @since 1.0.0
	 *
	 */
	
	public function increment_views() {
		global $post;
		if (is_singular()) {
			$current_views_daily = get_post_meta($post->ID, '_fsn_views_daily', true);
			$current_views_alltime = get_post_meta($post->ID, '_fsn_views_alltime', true);
			//daily views
			if ( !empty($current_views_daily) ) {
				$updated_views = intval($current_views_daily) + 1;
				update_post_meta($post->ID, '_fsn_views_daily', $updated_views);
			} else {
				update_post_meta($post->ID, '_fsn_views_daily', 1);
			}
			//all time views
			if ( !empty($current_views_alltime) ) {
				$updated_views = intval($current_views_alltime) + 1;
				update_post_meta($post->ID, '_fsn_views_alltime', $updated_views);
			} else {
				update_post_meta($post->ID, '_fsn_views_alltime', 1);
			}
			//set weekly and monthly integers if non existant
			$weekly_views_int = get_post_meta($post->ID, '_fsn_views_week_int', true);
			$monthly_views_int = get_post_meta($post->ID, '_fsn_views_month_int', true);
			if (!isset($weekly_views_int)) {
				update_post_meta($post->ID, '_fsn_views_week_int', 0);
			}
			if (!isset($monthly_views_int)) {
				update_post_meta($post->ID, '_fsn_views_month_int', 0);
			}
		}
	}
	
	/**
	 * Schedule daily task
	 *
	 * @since 1.0.0
	 *
	 */
	
	public function views_update_schedule_activation() {
		if ( ! wp_next_scheduled( 'fsn_views_update_schedule' ) ) {
			wp_schedule_event( time(), 'daily', 'fsn_views_update_schedule' );
		}
	}
	
	/**
	 * Un-schedule daily task on plugin deactivation
	 *
	 * @since 1.0.0
	 *
	 */
	 
	public function views_update_schedule_deactivation() {
		wp_clear_scheduled_hook( 'fsn_views_update_schedule' );
	}
	
	/**
	 * Run daily task
	 *
	 * @since 1.0.0
	 *
	 */
		
	public function views_update_actions() {
		$post_types = get_post_types();
		unset($post_types['attachment']);
		unset($post_types['revision']);
		unset($post_types['nav_menu_item']);
		if (!empty($post_types['component'])) {
			unset($post_types['component']);	
		}
		if (!empty($post_types['template'])) {
			unset($post_types['template']);	
		}
		if (!empty($post_types['notification'])) {
			unset($post_types['notification']);	
		}
		
		$all_items = get_posts(array(
			'post_type' => $post_types,
			'posts_per_page' => -1,
			'fields' => 'ids'
		));

		if (!empty($all_items)) {
			foreach($all_items as $item_id) {
				
				//add day views integer to month views array
				$month_views_array = get_post_meta($item_id, '_fsn_views_month_array', true);
				if (empty($month_views_array)) {
					$month_views_array = array();
				}
				$views_daily = get_post_meta($item_id, '_fsn_views_daily', true);
				if (empty($views_daily)) {
					$views_daily = 0;
				}
				$month_views_array[] = intval($views_daily);
				
				//remove oldest day from month views array if larger than 30
				if (count($month_views_array) > 30) {
					array_shift($month_views_array);
				}
				
				//update month views array meta
				update_post_meta($item_id, '_fsn_views_month_array', $month_views_array);
				
				//calculate month views integer from month views array
				$month_views_integer = 0;
				foreach($month_views_array as $month_views_single_day) {
					$month_views_integer = $month_views_integer + intval($month_views_single_day);
				}
				
				//update month views integer meta
				update_post_meta($item_id, '_fsn_views_month_int', $month_views_integer);
				
				//calculate week views integer from month views array
				$week_views_integer = 0;
				$week_views_array = array_slice($month_views_array, -7, 7);
				foreach($week_views_array as $week_views_single_day) {
					$week_views_integer = $week_views_integer + intval($week_views_single_day);
				}
				
				//update week views integer meta
				update_post_meta($item_id, '_fsn_views_week_int', $week_views_integer);
				
				//reset day views integer
				update_post_meta($item_id, '_fsn_views_daily', 0);
				
			}
		}
	}
	
}

$fsn_core_page_views = new FusionCorePageViews();

endif;

?>