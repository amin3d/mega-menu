<?php
/**
 * Plugin Name: Big Voodoo Mega Menu & Related Links Menu
 * Plugin URI: https://github.com/bigvoodoo/mega-menu
 * Description: Enhancements to the wp-admin Menu interface that allow for faster, more robust, and easier to edit menus. Also includes a Related Links Menu.
 * Version: 0.4.0
 * Author: Big Voodoo Interactive
 * Author URI: http://www.bigvoodoo.com
 * License: GPLv2
 * Copyright: Big Voodoo
 *
 * @author Joey Line <joey@bigvoodoo.com>
 */

if ( ! function_exists( 'add_action' ) ) {
	echo 'No direct access.';
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
 * Handles creating the Mega Menu and Related Links menu. Adds the following
 * shortcodes:
 * @shortcode mega_menu - generates the Mega Menu
 * @shortcode related_links - generates the Related Links menu
 */
class Mega_Menu {
	public static $table_name = 'mega_menu';

	/**
	 * Holds the currently active page & all its ancestors
	 */
	public static $active_pages = array();

	/**
	 * holds the menu structure so that both shortcodes can share it if
	 * necessary
	 */
	private $menu_structure = array();

	/**
	 * Constructor: adds the shortcodes & includes required files.
	 * @see add_shortcode()
	 */
	public function __construct() {
		add_shortcode( 'mega_menu', array( &$this, 'mega_menu_shortcode' ) );
		add_shortcode( 'related_links', array( &$this, 'related_links_shortcode' ) );

		add_filter( 'rewrite_rules_array', array( &$this, 'add_ajax_rewrite_rules' ) );
		add_action( 'parse_request', array( &$this, 'check_for_mega_menu_ajax' ) );

		// if we're on an admin page, include the admin part of this plugin
		if( is_admin() ) {
			require_once dirname( __FILE__ ) . '/mega-menu-admin.php';
		}

		require_once dirname( __FILE__ ) . '/mega-menu-admin-settings.php';
	}

	/**
	 * AJAX action hook to get the mega part of the mega menu.
	 * NOTE: this is not done with admin-ajax.php so that Contact Form 7, the rewrite and caching
	 * with W3 Total Cache will all work.
	 */
	public function check_for_mega_menu_ajax( $wp ) {
		parse_str( $wp->matched_query, $query );
		if( isset( $query['mega_menu_ajax'] ) && $query['mega_menu_ajax'] == 'true' ) {
			if( !isset( $query['theme_location'] ) || !isset( $query['parent'] ) ) {
				// need those query vars
				echo 'Error: bad request.';
				header('Status: 400 Bad Request');
				header('HTTP/1.1 400 Bad Request');
				die();
			}

			// output some headers
			$charset = get_bloginfo('charset');
			header('Content-Type: text/html; charset='.$charset);
			header('Expires: '.date(DATE_RFC1123, strtotime('+1 hour')));
			header('Cache-Control: public, must-revalidate, proxy-revalidate');
			header('Pragma: public');

			// output the shortcode with the given theme_location & parent
			echo $this->mega_menu_shortcode(array_merge($_GET, array(
				'theme_location' => $query['theme_location'],
				'ajax' => $query['parent'],
			)));

			die();
		}
	}

	/**
	 * Adds a rewrite rule for the AJAX request.
	 */
	public function add_ajax_rewrite_rules($rules) {
		$new_rules = array(
			'^ajax_mega_menu/([^/]+)/([0-9]+)' => 'index.php?mega_menu_ajax=true&theme_location=$matches[1]&parent=$matches[2]',
		);
		return array_merge($new_rules, $rules);
	}

	private function load_menu_items( $menu_id, $override_parent_id = null ) {
		global $wpdb;
		// $wpdb->show_errors(); // uncomment if need to debug query
		$table_name = $wpdb->prefix . Mega_Menu::$table_name;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `{$table_name}`.`ID`, `{$table_name}`.`post_id`, `{$wpdb->posts}`.`post_title`, ".($override_parent_id ? "$override_parent_id as `parent_id`" : "`{$table_name}`.`parent_id`").", `{$table_name}`.`position`, `{$table_name}`.`data`
					FROM `{$table_name}`
					LEFT JOIN `{$wpdb->posts}` ON `{$table_name}`.`post_id`=`{$wpdb->posts}`.`ID`
					WHERE `{$table_name}`.`menu_id`=%d
					ORDER BY `{$table_name}`.`position`",
				$menu_id
			)
		);
	}

	private function init_shortcode( $atts ) {
		$menus = get_nav_menu_locations();
		if( !isset( $menus[$atts['theme_location']] ) ) {
			throw new Exception('Menu location not configured: '.$atts['theme_location']);
		}

		$menu_id = $menus[$atts['theme_location']];

		$children_counts = array();

		if( !isset( $this->menu_structure[$menu_id] ) ) {
			$this->menu_structure[$menu_id] = $this->load_menu_items( $menu_id );

			for( $i = 0; $i < count( $this->menu_structure[$menu_id] ); $i++) {
				$menu_item = &$this->menu_structure[$menu_id][$i];

				$data = json_decode( $menu_item->data, true ); // return as assoc array
				foreach( $data as $key => $value ) {
					$key = str_replace( 'menu-item-', '', $key );
					$key = str_replace( '-', '_', $key );
					if( $value && !isset( $menu_item->$key ) ) {
						$menu_item->$key = $value;
					}
				}

				if( isset( $menu_item->title ) ) {
					$menu_item->post_title = $menu_item->title;
					unset( $menu_item->title );
				}

				if( isset( $menu_item->classes ) ) {
					if( is_string( $menu_item->classes ) ) {
						$menu_item->classes = explode( ' ', trim( $menu_item->classes ) );
					}
				} else {
					$menu_item->classes = array();
				}

				$menu_item->classes[] = 'menu-item-' . $menu_item->object;

				if( !isset( $children_counts[$menu_item->parent_id] ) ) {
					$children_counts[$menu_item->parent_id] = array();
				}
				if( !isset( $children_counts[$menu_item->parent_id][$menu_item->object] ) ) {
					$children_counts[$menu_item->parent_id][$menu_item->object] = 0;
				}

				$menu_item->classes[] = 'menu-item-' . $menu_item->object . '-' . $children_counts[$menu_item->parent_id][$menu_item->object];
				$children_counts[$menu_item->parent_id][$menu_item->object]++;

				if( $menu_item->type == 'menu' ) {
					$tmp = $this->load_menu_items( $menu_item->menu, $menu_item->ID );
					array_splice( $this->menu_structure[$menu_id], $i + 1, 0, $tmp );
				}
			}
		}

		return $this->menu_structure[$menu_id];
	}

	private function generate_menu_html( $ul_id, $menu_items, $depth, $args = array() ) {
		require_once dirname( __FILE__ ) . '/walker-nav-mega-menu.php';

		$data = '';
		foreach($args as $k => $v) {
			if($v === '' || $k == 'mega_wrapper' || $k == 'mega_wrapper_end') {
				continue;
			}
			$data .= ' data-'.htmlspecialchars($k).'="'.htmlspecialchars($v === true ? 'true' : $v).'"';
		}

		$walker = new Walker_Nav_Mega_Menu;
		return ( $args->mobile_toggle ? '<a href="#" class="mobile-toggle">' . $args->mobile_toggle . '</a>' : '' ) . PHP_EOL . '<ul id="' . $ul_id . '" class="mega-menu-container"'.$data.' data-home="' . home_url() . '">' . PHP_EOL . $walker->walk( $menu_items, $depth, $args ) . PHP_EOL . '</ul>' . PHP_EOL;
	}

	/**
	 * Generates the Mega Menu. Associated with the mega_menu shortcode.
	 * @param array The attributes passed by WordPress
	 * @return string The Mega Menu
	 */
	public function mega_menu_shortcode( $atts = array() ) {
		global $post;
		$menu_items = $this->init_shortcode( $atts );

		$args = (object) shortcode_atts(
			array( 'theme_location' => '', 'before' => '', 'after' => '', 'link_before' => '', 'link_after' => '', 'ajax' => false, 'mobile_toggle' => false ),
			$atts,
			'mega_menu'
		);

		$args->mega_wrapper = PHP_EOL . '<div class="mega-menu">';
		$args->mega_wrapper_end = '</div>' . PHP_EOL;
		$args->menu_type = 'mega';

		// figure out the currently active page & its ancestors
		$parent_id = null;
		for( $i = count( $menu_items ) - 1; $i >= 0; $i-- ) {
			$current = &$menu_items[$i];

			if( $current->post_id == $post->ID ) {
				$parent_id = $current->parent_id;
				self::$active_pages[] = $current->ID;
			} else if( $current->ID == $parent_id ) {
				if( $current->type == 'menu' ) {
					self::$active_pages = array();
					break;
				}

				$parent_id = $current->parent_id;
				self::$active_pages[] = $current->ID;
			}

			if( $parent_id === 0 ) {
				break;
			}
		}

		if( $args->ajax === "true" ) {
			// only output top-level menu items for AJAX menus
			$menu_items = self::filter_menu_items( $menu_items, 'parent_id', 0 );

			wp_register_script( 'bvi-mega-menu', plugins_url( 'js/mega-menu.js', __FILE__ ), array( 'jquery' ), false, true );
			wp_enqueue_script( 'bvi-mega-menu' );
		} else if( is_numeric( $args->ajax ) && $args->ajax !== false ) {
			$current_page = self::filter_menu_items( $menu_items, 'ID', $args->ajax );
			$menu_items = array_merge( $current_page, self::filter_menu_items( $menu_items, 'parent_id', $args->ajax, true ) );
		}

		return $this->generate_menu_html( 'mega-menu-' . $args->theme_location, $menu_items, 0, $args );
	}

	/**
	 * Runs array_filter() on $menu_items with a condition such that if $menu_item->$key == $value,
	 * the menu item is included.
	 */
	private static function filter_menu_items( $menu_items, $key, $value, $children = false ) {
		$values = array( $value );
		$menu_items = array_filter( $menu_items, function( $menu_item ) use ( $key, &$values, $children ) {
			if( in_array( $menu_item->$key, $values ) ) {
				if( $children ) {
					$values[] = $menu_item->ID;
				}
				return true;
			} else {
				return false;
			}
		});

		return $menu_items;
	}

	/**
	 * Generates the Related Links menu. Associated with the related_links
	 * shortcode.
	 * @param array The attributes passed by WordPress
	 * @return string The Related Links menu
	 */
	public function related_links_shortcode( $atts = array() ) {
		self::$active_pages = array();

		$menu_items = $this->init_shortcode( $atts );
		$display_menu_items = array();

		$post_menu_items = array_filter( $menu_items, function( $menu_item ) {
			// we need to use the current Post in here
			global $post;

			if( $menu_item->post_id == $post->ID ) {
				return true;
			} else {
				return false;
			}
		});

		foreach( $post_menu_items as $menu_item ) {
			// get children items
			$children = $this->get_menu_items_by_parent_id( $menu_items, $menu_item->ID );
			if( count( $children ) ) {
				// display children items
				$display_menu_items = array_merge( $display_menu_items, $children );
			} else {
				// else, display sibling items
				$display_menu_items = array_merge( $display_menu_items, $this->get_menu_items_by_parent_id( $menu_items, $menu_item->parent_id ) );
			}
		}

		if( empty( $display_menu_items ) ) {
			// we don't have anything to display yet, so display top-level items
			$display_menu_items = $this->get_menu_items_by_parent_id( $menu_items, 0 );
		}

		// filters out duplicates
		$display_menu_items = array_filter( $display_menu_items, function( $display_menu_item ) {
			static $ids = array();
			if(in_array($display_menu_item->post_id, $ids)) {
				return false;
			} else {
				$ids[] = $display_menu_item->post_id;

				return true;
			}
		});


		$args = (object) shortcode_atts(
			array('theme_location' => '', 'before' => '', 'after' => '', 'link_before' => '', 'link_after' => ''),
			$atts,
			'related_links'
		);

		return $this->generate_menu_html( 'related-links-' . $args->theme_location, $display_menu_items, -1, $args );
	}

	private function get_menu_items_by_parent_id( $menu_items, $parent_id, $get_children_of_columns = true ) {
		$children = array();

		foreach( $menu_items as $menu_item ) {
			if( $menu_item->parent_id == $parent_id ) {
				if( $menu_item->type == 'column' ) {
					if( $get_children_of_columns ) {
						$children = array_merge( $children, $this->get_menu_items_by_parent_id( $menu_items, $menu_item->ID, false ) );
					}
				} else if( $menu_item->type != 'menu' && $menu_item->type != 'shortcode' ) {
					$children[] = $menu_item;
				}
			}
		}

		return $children;
	}
}

// ooh, it's a shiny new Mega Menu!
new Mega_Menu;

if(get_option('bvi_mega_menu_css_val') == 1) {
	add_action( 'wp_enqueue_scripts', function() {
		// default mm2 styling
		wp_register_style( 'bvi-mega-menu-default', plugins_url( 'mega-menu-2/css/mega-menu-default.css' ));
		wp_enqueue_style( 'bvi-mega-menu-default' );
	});
}
