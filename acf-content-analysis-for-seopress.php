<?php
/*
@package  ACF-Content-Analysis-For-Seopress

Plugin Name: ACF Content Analysis For Seopress
Plugin URI: https://wordpress.org/plugins/acf-content-analysis-for-seopress/
Description: Ensure that SeoPress analyzes Advanced Custom Fields content including Flexible Content and Repeaters.
Version: 1.0.3
Author: Ziepman
Author URI: 
License: GPLv3 or later
Text Domain: acf-content-analysis-for-seopress
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here?' );

if ( !class_exists( 'ACFSeopress' ) ) {

	class ACFSeopress
	{
		public $interested_single_field_types = array('text', 'textarea', 'wysiwyg');
		public $interested_complex_field_types = array('repeater','flexible_content','group');

		public function __construct() {
            $this->register();
		}

		public function register() {
			add_action( 'plugins_loaded', array( $this, 'register_text_domain' ) );
			add_action( 'admin_notices', array($this,'check_activation_seopress') );			
			add_filter( 'seopress_content_analysis_content', array( $this, 'add_content_acf_to_seopress' ), 10, 2 );		
		}

		public function check_activation_seopress() {
			if ( !is_plugin_active( 'wp-seopress/seopress.php' ) && current_user_can( 'manage_options' ) ):
			?>
				<div class="error notice">
					<p>
					<?php _e( 'Please enable <strong>SEOPress</strong> in order to use the plugin ACF Content Analysis For Seopress.', 'acf-content-analysis-for-seopress' ); ?>
					</p>
				</div>
			<?php
				deactivate_plugins( plugin_basename( __FILE__ ) );

				// remove "plugin activated message"
				if (isset($_GET['activate'])) unset( $_GET['activate'] );	
			endif;
			
			if( ! class_exists('ACF') && current_user_can( 'manage_options' )) :
			//if ( (!is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) && !is_plugin_active( 'advanced-custom-fields/acf.php' )) && current_user_can( 'manage_options' ) ):
				?>
					<div class="error notice">
						<p>
						<?php _e( 'Please enable <strong>Advanced Custom Fields</strong> in order to use the plugin ACF Content Analysis For Seopress.', 'acf-content-analysis-for-seopress' ); ?>
						</p>
					</div>
				<?php
					deactivate_plugins( plugin_basename( __FILE__ ) );
	
					// remove "plugin activated message"
					if (isset($_GET['activate'])) unset( $_GET['activate'] );
			endif;
		}

		public function register_text_domain() {
			load_plugin_textdomain( 'acf-content-analysis-for-seopress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		public function add_content_acf_to_seopress( $content, $id ) {
			//$content = default WP editor 
			//$id = current post ID 
			$content = $content . $this->collect_acf_content( $id );
			$content = strip_tags($content);
			return $content;
		}

		public function collect_acf_content($id) {
			$all_fields = get_field_objects( $id );
			$content = '';
			if ($all_fields):
				foreach ($all_fields as $field):
					$content .= $this->acf_individual_field_content($field, $id);
				endforeach;
			endif;

			return $content;
		}

		public function acf_individual_field_content( $field, $id )	{
			if (in_array($field['type'], $this->interested_single_field_types)):
				 $content = $field['value'].' ';
				 return $content;
			else: 
				if (in_array( $field['type'], $this->interested_complex_field_types)): 
					if (have_rows( $field['name'], $id)):
						$content = '';
						while(have_rows($field['name'], $id)):
							$row = the_row();
							foreach ($row as $row_field_key => $row_field):
								$subfield_object_array = get_sub_field_object($row_field_key);
								if ($subfield_object_array) $content .= $this->acf_individual_field_content($subfield_object_array, $id);
							endforeach;
						endwhile;
						return $content;
					endif;
				endif;
			endif;
						
			return '';
		}

	}

	$contentACFSeopress = new ACFSeopress();
}
