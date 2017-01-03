<?php
/**
 * @package Fusion
 */

/**
 * Insert Component Fusion Extension.
 *
 * Function for adding an Insert Component element to the Fusion Engine
 *
 * @since 1.0.0
 */

/**
 * Map Shortcode
 */
 
add_action('init', 'fsn_init_insert_component_element', 12);
function fsn_init_insert_component_element() {
	
	if (function_exists('fsn_map')) {
		
		fsn_map(array(
			'name' => __('Component', 'fusion'),
			'shortcode_tag' => 'fsn_component',
			'description' => __('Insert a Component. Components are re-usable Rows/Elements that can be placed anywhere throughout the site and are useful for pieces of a layout that will be repeated across several different pages.', 'fusion'),
			'icon' => 'widgets',
			'params' => array(
				array(
					'type' => 'components',
					'param_name' => 'component_id',
					'label' => __('Choose Component', 'fusion'),
					'help' => __('Choose component to insert, edit this component, or add new component.', 'fusion')
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'component_collapse',
					'label' => __('Collapse Component', 'fusion'),
					'help' => __('Check to load component in a collapsed state.', 'fusion'),
					'section' => 'advanced'
				)			
			)
		));
	}
}

/**
 * Output Shortcode
 */

function fsn_component_shortcode( $atts, $content ) {		
	extract( shortcode_atts( array(							
		'component_id' => false,
		'component_collapse' => false
	), $atts ) );
	
	$output = '';
	
	//before fusion component action hook
	ob_start();
	do_action('fsn_before_component', $atts);
	$output .= ob_get_clean();
	
	if (!empty($component_id)) {
		$component = get_post($component_id);
		if (!empty($component) && $component->post_status == 'publish') {
			$output .= '<div id="component-'. esc_attr($component_id) .'" class="component '. fsn_style_params_class($atts) . (!empty($component_collapse) ? ' collapse' : '') .'">';
				$output .= apply_filters('fsn_the_content', $component->post_content);
			$output .= '</div>';
		}
	}
	
	//after fusion component action hook
	ob_start();
	do_action('fsn_after_component', $atts);
	$output .= ob_get_clean();
	
	return $output;
}
add_shortcode('fsn_component', 'fsn_component_shortcode');

?>