<?php
/*
Plugin Name: GravityForms string migrate
Plugin URI: https://wpml.org/
Description: Looks for strings in GravityForms and copies existing translations if they exist
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 0.1
Plugin Slug: gravityforms-string-migrate
*/

class GF_String_Migrate {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'administration_menu' ) );
	}

	function administration_menu() {	
		$main_page = basename( ICL_PLUGIN_PATH ) . '/menu/languages.php';
		add_submenu_page( 'tools.php', 'GF Migrate Strings', 'GF Migrate Strings', 'manage_options', 'gfml-migrate-strings', array( $this, 'show_page' ) );
	}
	
	function show_page() {
		if ( isset( $_POST['migrate'])) {
			$migrated = $this->migrate_strings();
			if ( $migrated > 0 ) {
				?>
				<h3><?php echo $migrated; ?> translations have been found and have been used for strings with missing translations!</h3>
				<?php
			}
		}
		?>
		
		<form action="<?php echo admin_url('tools.php?page=gfml-migrate-strings') ?>" method="post">
			<p>
				Find strings that have translations and use the translations for strings that are missing there translations
			</p>
			<input class="button-primary" type="submit" value="Find translations" name="migrate"/>
		</form>
		<?php
	}
	
	private function migrate_strings() {
		
		global $wpdb, $sitepress;
		
		$languages = $sitepress->get_active_languages();
		
		$count = 0;
		
		$strings = $wpdb->get_results( "SELECT id, context, value, name, language FROM {$wpdb->prefix}icl_strings WHERE context LIKE 'gravity_form-%'");
		foreach ( $strings as $string ) {
			if ( !$this->endswith( $string->name, '-value' ) ) {
				$translations = $wpdb->get_results( "SELECT language, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id={$string->id}");
				
				foreach( $languages as $language ) {
					$found = false;
					foreach ( $translations as $translation ) {
						if ( $translation->language == $language['code'] && $translation->status == 10 ) {
							$found = true;
							break;
						}
					}
					if ( !$found ) {
						$possible_translation = $this->find_translation( $string->value, substr( $string->context, strlen( 'gravity_form-' ) ), $language['code'] );
						if ( $possible_translation && $possible_translation != $string->value ) {
							$count++;
							icl_add_string_translation( $string->id, $language['code'], $possible_translation, 10 );
						}
					}
					
				}
			}
			
		}
		
		return $count;
		
	}
	
	private function find_translation( $value, $form_id, $language) {
		global $wpdb;
		
		$translation = $wpdb->get_var( "
											SELECT st.value
											FROM {$wpdb->prefix}icl_strings s
											JOIN {$wpdb->prefix}icl_string_translations st
											 ON s.id = st.string_id
											WHERE s.value='{$value}'
											AND st.language='{$language}'
											AND st.status=10
											AND s.context='gravity_form'
											AND s.name LIKE '{$form_id}_%'
											LIMIT 1
										   ");
		
		return $translation;
	}
	
	private function endswith($string, $test) {
		$strlen = strlen($string);
		$testlen = strlen($test);
		if ($testlen > $strlen) return false;
		return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
	}
}

$gf_string_migrate = new GF_String_Migrate();

		