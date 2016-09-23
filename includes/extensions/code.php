<?php
/**
 * @package Fusion
 */

/**
 * Code Fusion Extension.
 *
 * Function for adding a Code element to the Fusion Engine
 *
 * @since 1.0.0
 */

/**
 * Map Shortcode
 */

add_action('init', 'fsn_init_code', 12);
function fsn_init_code() {
	
	if (function_exists('fsn_map')) {	
		fsn_map(array(
			'name' => __('Code', 'fusion'),
			'shortcode_tag' => 'fsn_code',
			'description' => __('Input HTML, CSS, or JavaScript code. Useful for developers looking to add in embeds and code that may normally be stripped or altered by WordPress Editor.', 'fusion'),
			'icon' => 'code',
			'params' => array(						
				array(
					'type' => 'textarea',
					'param_name' => 'code',
					'label' => __('Code', 'fusion'),
					'content_field' => true,
					'encode_base64' => true
				)			
			)
		));
	}
}

/**
 * Output Shortcode
 */

function fsn_code( $atts, $content ) {		
	
	return '<div class="fsn-code '. fsn_style_params_class($atts) .'">'. base64_decode( wp_strip_all_tags($content) ) .'</div>';
}
add_shortcode('fsn_code', 'fsn_code');

?>