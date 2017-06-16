<?php
/**
 * @package Fusion
 */

/**
 * Fusion Class
 *
 * Class for handling images
 *
 * @since 1.3.0
 */
 
class FusionImages {
	private static $instance;
	
	public static function get_instance() {
		if ( ! isset(self::$instance) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		
		if ( function_exists( 'add_image_size' ) ) { 
			add_image_size('hi-res', 2560, 9999);
			add_image_size('mobile', 640, 9999);
		}
		
		// Filter Selectable Image Sizes
		add_filter('fsn_selectable_image_sizes', array($this, 'selectable_image_sizes'));
		
	}
	
	/**
	 * Filter image sizes
	 *
	 * Filter out image sizes that should not be user-selectable
	 *
	 * @since 1.3.0
	 */
	 
	public function selectable_image_sizes($fsn_selectable_image_sizes) {
		//unset WordPress medium large image size
		unset($fsn_selectable_image_sizes['medium_large']);
		unset($fsn_selectable_image_sizes['hi-res']);
		unset($fsn_selectable_image_sizes['mobile']);
		return $fsn_selectable_image_sizes;
	}
}

$fusion_images = FusionImages::get_instance();

?>