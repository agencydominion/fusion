<?php
/**
 * @package Fusion
 */

/**
 * FusionCoreComponents class.
 *
 * Class for adding Components support to Fusion
 *
 * @since 1.0.0
 */

class FusionCoreComponents	{
	
	public function __construct() {
		
		// Register components post type
		add_action('init', array($this, 'init_components_post_type'));
		
		//add components field type
		add_filter('fsn_input_types', array($this, 'add_components_field_type'), 10, 3);
		
		// Initialize AJAX modals
		add_action( 'wp_ajax_components_modal', array($this, 'render_components_modal'));
		
		// Save component
		add_action( 'wp_ajax_update_component', array($this, 'update_component'));
		
		// Output attached modal components in footer
		add_action( 'wp_footer', array($this, 'output_attached_modal_components'));
	}
	
	/**
	 * Init Components Post Type
	 *
	 * @since 1.0.0
	 */
	
	public function init_components_post_type() {
		$labels = array(
			'name'               => _x( 'Components', 'post type general name', 'fusion' ),
			'singular_name'      => _x( 'Component', 'post type singular name', 'fusion' ),
			'menu_name'          => _x( 'Components', 'admin menu', 'fusion' ),
			'name_admin_bar'     => _x( 'Component', 'add new on admin bar', 'fusion' ),
			'add_new'            => _x( 'Add New', 'component', 'fusion' ),
			'add_new_item'       => __( 'Add New Component', 'fusion' ),
			'new_item'           => __( 'New Component', 'fusion' ),
			'edit_item'          => __( 'Edit Component', 'fusion' ),
			'view_item'          => __( 'View Component', 'fusion' ),
			'all_items'          => __( 'All Components', 'fusion' ),
			'search_items'       => __( 'Search Components', 'fusion' ),
			'parent_item_colon'  => __( 'Parent Components:', 'fusion' ),
			'not_found'          => __( 'No components found.', 'fusion' ),
			'not_found_in_trash' => __( 'No components found in Trash.', 'fusion' ),
		);
	
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'exclude_from_search'=> true,
			'menu_icon'			 => 'dashicons-schedule',
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'revisions' )
		);
	
		register_post_type( 'component', $args );
		
		function fsn_component_updated_messages( $messages ) {
		  global $post, $post_ID;
		
		  $messages['component'] = array(
		    0 => '', // Unused. Messages start at index 1.
		    1 => sprintf( __('Component updated. <a href="%s">View component</a>', 'fusion'), esc_url( get_permalink($post_ID) ) ),
		    2 => __('Custom field updated.', 'fusion'),
		    3 => __('Custom field deleted.', 'fusion'),
		    4 => __('Component updated.', 'fusion'),
		    /* translators: %s: date and time of the revision */
		    5 => isset($_GET['revision']) ? sprintf( __('Component restored to revision from %s', 'fusion'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		    6 => sprintf( __('Component published. <a href="%s">View component</a>', 'fusion'), esc_url( get_permalink($post_ID) ) ),
		    7 => __('Component saved.', 'fusion'),
		    8 => sprintf( __('Component submitted. <a target="_blank" href="%s">Preview component</a>', 'fusion'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		    9 => sprintf( __('Component scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview component</a>', 'fusion'),
		      // translators: Publish box date format, see http://php.net/date
		      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		    10 => sprintf( __('Component draft updated. <a target="_blank" href="%s">Preview component</a>', 'fusion'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		  );
		
		  return $messages;
		}
		add_filter( 'post_updated_messages', 'fsn_component_updated_messages' );
	}
	
	/**
	 * Add Components input type
	 *
	 * @since 1.0.0
	 *
	 * @param string $input The HTML for the input field(s)
	 * @param array $param The input parameters
	 * @param string $param_value The saved parameter value
	 * @return string The HTML for the input field(s)
	 */
	 
	public function add_components_field_type($input, $param, $param_value = '') {
		if ($param['type'] == 'components') {
			
			$post_id = intval($_POST['post_id']);
			
			$all_components = fsn_get_post_ids_by_type('component');
			$attached_components = array();
			$nonattached_components = array();
			foreach ($all_components as $component) {
				if (wp_get_post_parent_id($component) == $post_id) {
					$attached_components[] = $component;
				} else {
					$nonattached_components[] = $component;
				}
			}
			
			$input .= '<label for="fsn_'. $param['param_name'] .'">'. $param['label'] .'</label>';
			$input .= !empty($param['help']) ? '<p class="help-block">'. $param['help'] .'</p>' : '';
			$input .= '<div class="component-select chosen">';
				$input .= '<select data-placeholder="Choose a Component." class="form-control element-input'. ($param['nested'] == true ? ' nested' : '') .'" name="'. $param['param_name'] .'">';
				$input .= '<option value=""></option>';
				if (!empty($attached_components)) {
					$input .= '<optgroup label="Components Attached to this Post">';
					foreach($attached_components as $attached_component) {
						$input .= '<option value="'. $attached_component .'"'. selected( $param_value, $attached_component, false ) .'>'. get_the_title($attached_component) .'</option>';
					}
					$input .= '</optgroup>';
				}
				if (!empty($nonattached_components)) {
					$input .= '<optgroup label="Other Components">';
					foreach($nonattached_components as $nonattached_component) {
						$input .= '<option value="'. $nonattached_component .'"'. selected( $param_value, $nonattached_component, false ) .'>'. get_the_title($nonattached_component) .'</option>';
					}
					$input .= '</optgroup>';
				}
				$input .= '</select>';
			$input .= '</div>';
			$input .= '<a href="#" class="button component-add-new">Add New</a>';
			$input .= '<a href="#" class="button component-edit">Edit Selected</a>';
		}
		
		return $input;
	}
	
	/**
	 * Render Components modal.
	 *
	 * @since 1.0.0
	 */
	 
	public function render_components_modal() {
		//verify nonce
		check_ajax_referer( 'fsn-admin-edit', 'security' );
		
		//verify capabilities
		if ( !current_user_can( 'edit_post', intval($_POST['component_id']) ) )
			die( '-1' );
			
		$component_id = intval($_POST['component_id']);
		?>
		<div class="modal fade" id="componentsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<span class="components-modal-close" data-dismiss="modal" aria-label="Close">&times;</span>
				<div id="components-modal-inner">
					<h2>Add / Edit Post Component</h2>
					<form id="edit_component" method="post">
						<?php
						echo '<input type="text" id="component_title" name="component_title" '. (!empty($component_id) ? 'value="'. get_the_title($component_id) .'"' : 'value="" placeholder="New Component"') .'>';
						echo '<input type="hidden" name="component_id" value="'. (!empty($component_id) ? $component_id : '') .'">';
						echo '<div class="fsn-main-controls">';
							echo '<p class="description">Click the "Save" button below to save changes to this Component.</p>';
							echo '<a href="#" class="button fsn-save-template">'. __('Save Template', 'fusion') .'</a>';
							echo '<a href="#" class="button fsn-load-template" style="margin-left:5px;">'. __('Load Template', 'fusion') .'</a>';
							//echo '<a href="#" class="button fsn-toggle-previews" style="margin-left:5px;">'. __('Hide Element Previews', 'fusion') .'</a>';
							echo '<div class="fsn-component-controls">';
								echo '<button type="button" class="button"  data-dismiss="modal">'. __('Cancel', 'fusion') .'</button>';
								echo '<a href="#" class="button button-primary fsn-save-component" style="margin-left:5px;">'. __('Save', 'fusion') .'</a>';
								echo '<span class="spinner"></span>';
							echo '</div>';
						echo '</div>';
						echo '<div class="fsn-interface-container">';			
							//output grid content
							echo '<div class="fsn-interface-grid">';			
								if (!empty($component_id)) {
									$component = get_post($component_id);
									echo do_shortcode($component->post_content);
								}
							echo '</div>';
						echo '</div>';	
						?>
					</form>
				</div>
			</div>
		</div>
		<?php
		exit;
	}
	
	/**
	 * Update component
	 *
	 * @since 1.0.0
	 */
	
	public function update_component() {
		//verify nonce
		check_ajax_referer( 'fsn-admin-edit', 'security' );
		
		//verify capabilities
		if ( !current_user_can( 'edit_post', intval($_POST['component_id']) ) )
			die( '-1' );
		
		$post_id = intval($_POST['post_id']);
		$component_id = intval($_POST['component_id']);
		$component_title = sanitize_text_field($_POST['component_title']);
		$component_content = wp_filter_post_kses($_POST['component_content']);
		
		if (!empty($component_id)) {
			$updated_component_id = wp_update_post(array(
				'ID' => $component_id,
				'post_title' => $component_title,
				'post_content' => $component_content
			));
			if (!empty($updated_component_id)) {
				$notice = 'Component updated.';
				$notice_class = 'notice-success';
			} else {
				$notice = 'Error updating component. Please try again.';
				$notice_class = 'notice-error';
			}
		} else {
			$new_component_id = wp_insert_post(array(
				'post_title' => $component_title,
				'post_content' => $component_content,
				'post_type' => 'component',
				'post_status' => 'publish',
				'post_parent' => $post_id
			));
			if (!empty($new_component_id))	{
				$notice = 'Component created.';	
				$notice_class = 'notice-success';
			} else {
				$notice = 'Error creating component. Please try again.';
				$notice_class = 'notice-error';
			}
		}
		
		echo '<div class="notice '. $notice_class .' is-dismissible"'. (!empty($new_component_id) ? ' data-new-component-id="'. $new_component_id .'"' : '') .'><p>'. $notice .' <a href="#" data-dismiss="modal">Done Editing</a></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
		
		exit;
	}
	
	/**
	 * Output attached modal components
	 *
	 * @since 1.0.0
	 */
	
	public function output_attached_modal_components() {
		//get global attached modals array
		global $fsn_attached_modals;
		if (!empty($fsn_attached_modals)) {
			//remove duplicates
			$attached_modals = array_unique($fsn_attached_modals);
			//output modals
			foreach($attached_modals as $attached_modal) {
				echo '<div id="modal-component-'. $attached_modal .'" class="component modal fade">';
					echo '<div class="modal-component-inner container">';
						echo '<div class="modal-component-controls clearfix">';
							echo '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="material-icons md-48">&#xE5CD;</i></button>';
						echo '</div>';
						echo do_shortcode('[fsn_component component_id="'. $attached_modal .'"]');
					echo '</div>';
				echo '</div>';
			}
		}		
		//unset global
		unset($GLOBALS['fsn_attached_modals']);
	}
	
}

$fsn_core_components = new FusionCoreComponents();

?>