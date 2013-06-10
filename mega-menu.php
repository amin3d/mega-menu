<?php
/**
 * Plugin Name: Ken's Mega Menu & Related Links Menu
 * Plugin URI: http://www.bigvoodoo.com
 * Description: This Mega Menu is even more Mega than the rest, because it has Ken's name on it! It also includes a Related Links Menu.
 * Version: 0.1.0
 * Author: Joey Line
 * Author URI: https://github.com/firejdl
*/

if ( ! function_exists( 'add_action' ) ) {
	echo 'No direct access.';
	die();
}

/**
 * Handles creating the Mega Menu and Related Links menu. Adds the following
 * shortcodes:
 * @shortcode mega_menu - generates the Mega Menu
 * @shortcode related_links - generates the Related Links menu
 * @author Joey Line
 */
class Mega_Menu {
	/**
	 * holds the menu structure so that both shortcodes can share it if
	 * necessary
	 */
	private $menu_structure;

	/**
	 * Constructor: adds the shortcodes & includes required files.
	 * @see add_shortcode()
	 */
	public function __construct() {
		require_once dirname( __FILE__ ) . '/fields.php';
		require_once dirname( __FILE__ ) . '/utils.php';
		add_shortcode( 'mega_menu', array( $this, 'mega_menu_short_code' ) );
		add_shortcode( 'related_links', array( $this, 'related_links_short_code' ) );
	}

	/**
	 * Loads the shortcode attributes & menu structure
	 * @param array The attributes passed by WordPress
	 * @see Mega_Menu::load_menu_structure()
	 */
	private function init( $atts ) {
		extract( shortcode_atts( array(
			'menu_acf_key' => 'mega_menu',
			'parent_post_id' => 'options',
		), $atts ) );

		if ( ! $this->menu_structure ) {
			$this->menu_structure = self::load_menu_structure( $menu_acf_key, $parent_post_id );
		}
	}

	/**
	 * Generates the Mega Menu. Associated with the mega_menu shortcode.
	 * @param array The attributes passed by WordPress
	 * @return string The Mega Menu
	 * @see Mega_Menu::init()
	 * @see Mega_Menu::display()
	 */
	public function mega_menu_short_code( $atts = array() ) {
		// initialize the attributes & menu structure
		$this->init( $atts );

		// display the Mega Menu
		return self::display( $this->menu_structure, 'mega_menu' );
	}

	/**
	 * Generates the Related Links menu. Associated with the related_links
	 * shortcode.
	 * @param array The attributes passed by WordPress
	 * @return string The Related Links menu
	 * @see Mega_Menu::init()
	 * @see Mega_Menu::load_menu_structure()
	 * @see Mega_Menu::load_hierarchy()
	 * @see Mega_Menu::display()
	 */
	public function related_links_short_code( $atts = array() ) {
		// we need to use the current Post in here
		global $post;

		// initialize the attributes & menu structure
		$this->init( $atts );

		// load the hierarchy for the current post
		$children = self::load_hierarchy( $post );

		if ( ! empty( $children ) ) {
			// the current post has children, so use them
			$menu_structure = $children;
		} else if ( $post->post_parent != 0 ) {
			// the current post has no children but it is a child itself, so
			// use its siblings
			$menu_structure = self::load_hierarchy( $post->post_parent );
		}

		if ( empty( $menu_structure) ) {
			// no menu structure found above - use the Mega Menu structure
			$menu_structure = $this->menu_structure;
		}

		// display the Related Links menu. the 1 parameter is to tell the
		// Walker to only show the top level items.
		return self::display( $menu_structure, 'related_links', 1 );
	}

	/**
	 * Loads the Mega Menu structure from Advanced Custom Fields.
	 * @param string The ACF key to search under
	 * @param string The post_id that the ACF key is stored under
	 * @return array The menu structure
	 * @see Mega_Menu::load_hierarchy()
	 */
	private static function load_menu_structure( $menu_acf_key, $parent_post_id ) {
		$menu_structure = array();

		if ( get_field( $menu_acf_key, $parent_post_id ) ) {
			// loop over the top-level links
			while ( has_sub_field( $menu_acf_key, $parent_post_id ) ) {
				$top_level = get_sub_field( 'link' );
				$top_level->children = array();
				$top_level->post_parent = 0;

				// the $column_index is used for the column classes
				$column_index = 1;

				// loop over the columns
				while ( has_sub_field( 'columns', $parent_post_id ) ) {
					$column = new stdClass;
					$column->children = array();
					$column->classes = array( 'column', "column$column_index", get_row_layout() );

					if ( get_row_layout() == 'hierarchical' ) {
						// hierarchical column - load the hierarchy from the DB
						$column->max_items_per_column = get_sub_field( 'max_items_per_column' );
						$column->children = self::load_hierarchy( $top_level );
					} else if ( get_row_layout() == 'custom_column' ) {
						// custom column - use the custom links provided

						// loop over the sections of links
						while ( has_sub_field( 'section', $parent_post_id ) ) {
							// section can have an optional link
							$section = get_sub_field( 'section_link' );
							if ( ! $section ) {
								// no section link defined
								$section = new stdClass;
							}

							// section can have an optional title
							if ( get_sub_field( 'section_title' ) ) {
								$section->post_title = get_sub_field( 'section_title' );
							}

							$section->children = array();

							// loop over the section's links
							while ( has_sub_field( 'sublinks', $parent_post_id ) ) {
								$sublink = new stdClass;
								$sublink->type = get_row_layout();

								if ( get_row_layout() == 'post' ) {
									// page or post link
									$sublink = get_sub_field( 'post' );
									$sublink->type = $sublink->post_type;
								} else if (get_row_layout() == 'c2c' ) {
									// click 2 call link
									$sublink->url = '#';
									$sublink->classes = array( 'c2c' );
								} else if ( get_row_layout() == 'custom' ) {
									// custom link
									$sublink->url = get_sub_field( 'url' );
								} else if ( get_row_layout() == 'shortcode' ) {
									// shortcode! whee
									$sublink->shortcode = get_sub_field( 'shortcode' );
								}

								// links can have an optional title
								if ( get_sub_field( 'title' ) ) {
									$sublink->post_title = get_sub_field( 'title' );
								}

								// store the link in the section
								$section->children[] = $sublink;
							} // while ( has_sub_field( 'sublinks', $parent_post_id ) )

							// store the section in the column
							$column->children[] = $section;
						} // while ( has_sub_field( 'section', $parent_post_id ) )
					} // if ( get_row_layout() == 'custom_column' )

					// store the column in the top-level link
					$top_level->children[] = $column;
					$column_index++;
				} // while ( has_sub_field( 'columns', $parent_post_id ) )

				// store the top-level link in the menu structure
				$menu_structure[] = $top_level;
			} // while ( has_sub_field( $menu_acf_key, $parent_post_id ) )
		} // if ( get_field( $menu_acf_key, $parent_post_id ) )

		// yay, all done!
		return $menu_structure;
	}

	/**
	 * Displays a menu using self::flatten_menu_structure() &
	 * Walker_Nav_Mega_Menu.
	 * @param array The menu structure to display
	 * @param string The id attribute of the <ul> tag
	 * @param int The depth of the menu, as defined in Walker::walk()
	 *		(-1 means flatly display all, 0 means display all levels,
	 *		 > 0 specifies the number of levels)
	 * @see Walker::walk()
	 * @see Mega_Menu::flatten_menu_structure()
	 */
	private static function display( $menu_structure, $ul_id, $depth = 0 ) {
		require_once dirname( __FILE__ ) . '/walker-nav-mega-menu.php';
		$walker = new Walker_Nav_Mega_Menu;
		return PHP_EOL.'<ul id="' . $ul_id . '">' . PHP_EOL . $walker->walk( self::flatten_menu_structure( $menu_structure ), $depth ) . PHP_EOL . '</ul>' . PHP_EOL;
	}

	/**
	 * Loads a page/post hierarchy using get_posts()
	 * @param int|WP_Post The parent to load from
	 * @return array The descendents of $parent
	 * @see get_posts()
	 */
	private static function load_hierarchy( $parent ) {
		$children = get_posts( array(
			'post_parent' => is_object( $parent ) ? $parent->ID : $parent,
			'order' => 'ASC',
			'orderby' => 'menu_order',
			'post_type' => 'any',
			'post_status' => 'publish',
			'output' => ARRAY_A,
			'output_key' => 'menu_order',
			'nopaging' => true,
		) );

		foreach ( $children as &$child ) {
			$child->children = self::load_hierarchy( $child );
		}

		return $children;
	}

	/**
	 * Flattens the menu structure into the format expected by Walker.
	 * @param array The menu structure to flatten
	 * @return array The flattened menu structure
	 * @see Walker
	 */
	private static function flatten_menu_structure( $items ) {
		// we need to check the current page/post in here
		global $post;

		// create a new array to return
		$new_items = array();

		foreach ( $items as $item ) {
			// NOTE: if we don't clone $item, $this->menu_structure gets
			// modified, because PHP objects are handled by reference
			$new_item = clone $item;

			// make sure $new_item has an ID
			if ( ! isset( $new_item->ID ) ) {
				// NOTE: the md5(serialize()) call below doesn't always work
				// because of the following:
				// http://php.net/manual/en/language.operators.comparison.php
				// "If you compare a number with a string or the comparison
				// involves numerical strings, then each string is converted
				// to a number and the comparison performed numerically."
				// http://www.php.net/manual/en/language.types.string.php#language.types.string.conversion
				// "The value is given by the initial portion of the string.
				// If the string starts with valid numeric data, this will be
				// the value used. Otherwise, the value will be 0 (zero)."
				// NOTE: so, we're generating a random ID instead.
				$new_item->ID = mt_rand( 1000, mt_getrandmax() );
				// $new_item->ID = md5( serialize( $new_item ) );
			}

			// is this the current post/page?
			if ( $post->ID == $new_item->ID ) {
				// add an 'active' class
				if ( isset( $new_item->classes ) ) {
					$new_item->classes[] = 'active';
				} else {
					$new_item->classes = array( 'active' );
				}
			}

			if ( isset( $new_item->children ) ) {
				// for all children, set the post_parent attribute
				$children = array_map( function( $child ) use ( $new_item ) {
					// clone $child so we're not modifying the original
					$new_child = clone $child;
					$new_child->post_parent = $new_item->ID;
					return $new_child;
				}, $new_item->children );

				// remove the children attribute
				unset( $new_item->children );
			} else {
				// no children - use an empty array
				$children = array();
			}

			// merge the existing $new_items array with the current $new_item
			// and a recursive call to self::flatten_menu_structure for the
			// children of $new_item
			$new_items = array_merge(
				$new_items,
				array( $new_item ),
				self::flatten_menu_structure( $children )
			);
		}

		// yay, menu is flat
		return $new_items;
	}
}

// ooh, it's a shiny new Mega Menu!
new Mega_Menu;
