<?php

/**
 * TODO: documentation
 */
class Mega_Menu_Admin_Settings {
	/**
	 * Constructor: adds the shortcodes & includes required files.
	 * @see add_shortcode()
	 */
	public function __construct() {
		if(is_admin()){
			add_action( 'admin_menu', array( &$this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );

			add_action( 'wp_ajax_mega_menu_fix_wp_menu', array( &$this, 'fix_wp_menu' ), 1 );
		}
	}

	/**
	 * this creates the options page under the title "BVI Settings"
	 */
	public function add_plugin_page() {
		// This page will be under "BVI Settings"
		add_options_page( 'BVI Mega Menu', 'BVI Mega Menu', 'manage_options', 'bvi_mega_menu_settings_admin', array( $this, 'create_admin_page' ) );
	}

	/**
	 * this is the html/functionality you see on the admin page
	 */
	public function create_admin_page() {
		wp_register_script( 'mega-menu-admin-settings', plugins_url( 'js/mega-menu-admin-settings.js', __FILE__ ) );
		wp_enqueue_script( 'mega-menu-admin-settings' );
		?>
		<div class="wrap">
			<h2>Big Voodoo Interactive Mega Menu Settings</h2>
			<form method="post" action="options.php">
				<?php
				// pulls all existing values from fields registered under 'bvi_settings_options'
				settings_fields( 'bvi_settings_options' );
				// this is saying, get all the sections that have been assigned to 'bvi_mega_menu_settings_admin'
				do_settings_sections( 'bvi_mega_menu_settings_admin' );
				submit_button(); ?>
			</form>
			<form id="fix_wp_menu_form">
				<p>
					Use this if your WordPress Menu page (Appearance->Menus) isn't right for your Mega Menu, or to ease importing of Mega Menus - copy just the `<?php echo Mega_Menu::$table_name; ?>` table to this site's database, then use this. Note that this will overwrite the WordPress menu at the selected Theme Location, so use this with caution - if you don't have anything stored in the `<?php echo Mega_Menu::$table_name; ?>` table for the selected Theme Location, you will basically just be wiping the contents of the menu at the selected Theme Location.
				</p>
				<p>
					After completing this operation, check to make sure the WordPress Menu page (Appearance->Menus) looks right for the selected Theme Location. If it doesn't, there may be an issue here. If it looks fine, save the menu in that interface to update the `<?php echo Mega_Menu::$table_name; ?>` table &amp; see the changes reflected in the front end.
				</p>
				<p>
					<label>Menu location</label>
					<select name="theme_location">
					<?php
						$locations = get_nav_menu_locations();
						foreach($locations as $name => $location):
							?><option value="<?php echo $location; ?>"<?php echo substr($name, 0, 4) == 'mega' ? ' selected' : ''; ?>><?php echo $name; ?></option><?php
						endforeach;
					?>
					</select>
				</p>
				<p class="submit">
					<input type="submit" name="fix_wp_menu" id="fix_wp_menu" class="button button-primary" value="Fix the WordPress menu from the Mega Menu table" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * this function is where all the "magic" happens
	 */
	public function register_settings() {
		// this registers the settings array that will contain all of your option values
		// ie. everything will get saved in array_key[option_name_you_used]
		// the first value is the id of this functionality, so you can access it with settings_fields() function later
		register_setting( 'bvi_settings_options', 'array_key', array( $this, 'validateThis' ) );

		// sets up sections
		// 1 => section id, 2 => section H1, 3 => the function callback to show the section's description text, 4 => name of assigned settings sections
		// ie. 4 is what gets called by do_settings_sections - so if you assign them all to the same one, like bvi_mega_menu_settings_admin
		// then all of these sections will appear when you call do_settings_sections('bvi_mega_menu_settings_admin')
		// guess it gives you the options to separate the sections if you were doing some crazy front-end display
		$sections = array(
			'default' => array( 'bvi_settings_section_default', 'Default CSS & JS Settings', array( $this, 'bvi_settings_section_text_default' ), 'bvi_mega_menu_settings_admin' ),
		);

		// sets up fields
		// 1 => field id (NOT the id="" of the input field), 2 => field label, 3 => what input function to use
		// ie. &this means to use the 6th part of the array to assign the names to a generic input function
		// this lets you have a single function for each different kind of input, like input type="text"
		// and then define unique name, ids, values for the field without repeating the function
		// 4 => like the settings section, this will tell it to appear in that same sections call do_settings_section (so redundant) because ...
		// 5 => what section it should appear under that you define in sections (thats why 4 is redundant)
		// 6 => this allows you to set a unique name for the id, name, and value for the input field (in conjunction with the 3th array value)
		$fields = array(
			'default_css' => array( 'bvi_settings_css_input', 'Enable Default CSS?', array( &$this, 'bvi_settings_input_checkbox' ), 'bvi_mega_menu_settings_admin', 'bvi_settings_section_default', array( 'field' => 'bvi_mega_menu_css_val' ) ),
		);

		// yeah, we're not calling add_settings_section() 5 times over and over -
		// parses through the sections array you just made, yay!
		foreach( $sections as $section ) {
			add_settings_section( $section[0], $section[1], $section[2], $section[3] );
		}

		// same thing - not going to call this over and over
		// parses through the fields array you just made, yay!
		foreach( $fields as $field ) {
			add_settings_field( $field[0], $field[1], $field[2], $field[3], $field[4], $field[5] );
		}
	}

	/**
	 * this is a redundant function, but I guess you should check that the field is valid...
	 * regardless, this is the validation function that HAS to be called, or WordPress will cry
	 */
	public function validateThis( $input ) {
		$valid = array();

		// checkboxes not checked will just not return anything,
		// so to make sure the value gets updated,
		// this will add the zero value for these options
		// when they are submitted blank
		if( !array_key_exists( 'bvi_mega_menu_css_val', $input ) ) {
			$input['bvi_mega_menu_css_val'] = '0';
		}

		if( !empty( $input ) ) {
			// checks each input that has been added
			foreach( $input as $key => $value ) {
				// does a basic check to make sure that the database value is there
				if( get_option( $key === FALSE ) ) {
					// adds the field if its not there
					add_option( $key, $value );
				} else {
					// updates the field if its already there
					update_option( $key, $value );
				}

				// you have to return the value or WordPress will cry
				$valid[$key] = $value;
			}
		}

		// return it and prevent WordPress depression
		return $valid;
	}

	/**
	 * the actual input field for type text
	 */
	public function bvi_settings_input_checkbox( $data ) {
		?><input type="checkbox" id="<?php echo $data['field']; ?>" name="array_key[<?php echo $data['field']; ?>]" value="1" <?php checked( true, get_option( $data['field'] ) ); ?> /><?php
	}

	/**
	 * text label that appears in google analytics section under the section title
	 * the rest of the functions under this are the exact same thing
	 */
	public function bvi_settings_section_text_default() {
		?><p>When checked, these default CSS and Javascript files will be included.</p><?php
	}

	public function fix_wp_menu() {
		if ( ! current_user_can( 'edit_theme_options' ) )
			wp_die( -1 );
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		require_once ABSPATH . 'wp-includes/post.php';

		global $wpdb;
		// $wpdb->show_errors(); // uncomment if need to debug query
		$menus = get_nav_menu_locations();
		$menu_id = $_POST['theme_location'];

		if( !in_array( $menu_id, $menus ) ) {
			throw new Exception('Menu location not configured: '.$_POST['theme_location']);
		}

		// delete existing menu items (they be b0rk3d)
		$deleted_menu_ids = array();
		$unsorted_menu_items = wp_get_nav_menu_items( $menu_id, array( 'orderby' => 'ID', 'output' => ARRAY_A, 'output_key' => 'ID', 'post_status' => 'draft,publish' ) );
		$menu_items = array();
		// Index menu items by db ID
		foreach ( $unsorted_menu_items as $_item )
			$menu_items[$_item->db_id] = $_item;
		// Remove menu items from the menu that weren't in $_POST
		if ( ! empty( $menu_items ) ) {
			foreach ( array_keys( $menu_items ) as $menu_item_id ) {
				if ( is_nav_menu_item( $menu_item_id ) ) {
					wp_delete_post( $menu_item_id );
					$deleted_menu_ids[] = $menu_item_id;
				}
			}
		}

		echo 'Deleted '.count($deleted_menu_ids).' menu items<br />';

		$table_name = $wpdb->prefix . Mega_Menu::$table_name;
		$menu_items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `{$table_name}`.`ID`, `{$table_name}`.`post_id`, `{$table_name}`.`parent_id`, `{$table_name}`.`position`, `{$table_name}`.`data`
					FROM `{$table_name}`
					WHERE `{$table_name}`.`menu_id`=%d
					ORDER BY `{$table_name}`.`position`",
				$menu_id
			)
		);

		// stores a map of old menu item IDs to new menu item IDs (for parents)
		$map_menu_item_ids = array();

		foreach($menu_items as $menu_item) {
			$data = json_decode( $menu_item->data, true ); // return as assoc array
			foreach( $data as $key => $value ) {
				$key = str_replace( 'menu-item-', '', $key );
				$key = str_replace( '-', '_', $key );
				if( $value && !isset( $menu_item->$key ) ) {
					$menu_item->$key = $value;
				}
			}

			if($menu_item->type == 'column') {
				$title = json_encode(array('url' => $menu_item->url ?: '', 'title' => $menu_item->title ?: ''));
			} else if($menu_item->type == 'menu') {
				$title = json_encode(array('menu' => $menu_item->menu, 'title' => $menu_item->title ?: ''));
			} else {
				$title = $menu_item->title ?: '';
			}

			$menu_item_data = array(
				'menu-item-db-id' => 0,
				'menu-item-object-id' => $menu_item->object_id,
				'menu-item-object' => $menu_item->object,
				'menu-item-parent-id' => $map_menu_item_ids[$menu_item->parent_id],
				'menu-item-position' => $menu_item->position,
				'menu-item-type' => $menu_item->type,
				'menu-item-title' => $title,
				'menu-item-url' => isset($menu_item->url) ? $menu_item->url : '',
				'menu-item-description' => $menu_item->description,
				'menu-item-attr-title' => $menu_item->attr_title,
				'menu-item-target' => isset($menu_item->target) ? $menu_item->target : '',
				'menu-item-classes' => implode(' ', $menu_item->classes),
				'menu-item-xfn' => isset($menu_item->xfn) ? $menu_item->xfn : '',
				'menu-item-status' => 'publish',
			);

			$id = wp_update_nav_menu_item( $menu_id, 0, $menu_item_data );
			$map_menu_item_ids[$menu_item->db_id] = $id;
		}

		echo 'Inserted '.count($map_menu_item_ids).' menu items<br />';
		die();
	}
}

// create the Mega_Menu_Admin_Settings object!
new Mega_Menu_Admin_Settings();
