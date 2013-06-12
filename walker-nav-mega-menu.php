<?php

/**
 * Create HTML list of Mega Menu items.
 * @see Walker_Nav_Menu
 * @see Walker
 * @author firejdl
 */
class Walker_Nav_Mega_Menu extends Walker_Nav_Menu {
	private $num_items = 0;
	private $current_max_items = 99999;

	/**
	 * @see Walker::$tree_type
	 */
	var $tree_type = array( 'mega_menu' );

	/**
	 * @see Walker::$db_fields
	 */
	var $db_fields = array(
		'parent' => 'post_parent',
		'id' => 'ID'
	);

	/**
	 * Overrides Walker::display_element() to handle the max number of items
	 * per column setting.
	 * This method shouldn't be called directly, use the walk() method instead.
	 * @see Walker::display_element()
	 */
	function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {
		if ( !$element )
			// no element - do nothing
			return;

		// only handle the max number of items per column if it is set & if
		// we're in a hierarchical menu
		if ( $max_depth == 0 && isset( $element->max_items_per_column ) ) {
			// save the old current_max_items value (probably 0)
			$old_max_items = $this->current_max_items;
			$this->current_max_items = $element->max_items_per_column;
			$this->num_items = 0;
		}

		// if we've already displayed enough items for this column...
		if ( $this->num_items >= $this->current_max_items ) {
			// set up the arguments for the end_lvl, end_el, start_lvl, and
			// start_el functions
			$el_args = array_merge( array(&$output, $element, $depth + 1), $args);
			$lvl_args = array_merge( array(&$output, $depth + 1), $args);

			// fake an element to send to start_el/end_el
			$el_args[1] = new StdClass;
			$el_args[1]->ID = $element->ID;
			$el_args[1]->classes = $element->classes;
			$el_args[1]->type = '';

			// close all open uls & lis (except the very top one - the menu
			// itself)
			for ( $i = 0 ; $i < $depth - 1 ; $i++ ) {
				call_user_func_array( array( $this, 'end_lvl' ), $lvl_args );
				call_user_func_array( array( $this, 'end_el' ), $el_args );
			}

			// open the same number of uls & lis as we closed
			for ( $i = 0 ; $i < $depth - 1 ; $i++ ) {
				call_user_func_array( array( $this, 'start_el' ), $el_args );
				call_user_func_array( array( $this, 'start_lvl' ), $lvl_args );
			}

			// reset the current number of items for the next column
			$this->num_items = 0;
		}

		// call Walker::display_element() to actually handle displaying the
		// element
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );

		// reset current_max_items & num_items if we set them
		if ( isset( $old_max_items ) ) {
			$this->current_max_items = $old_max_items;
			$this->num_items = 0;
		}
	}

	/**
	 * Overrides Walker_Nav_Menu::start_el() to display some of our special stuffs.
	 *
	 * @see Walker_Nav_Menu::start_el()
	 * @see Walker::start_el()
	 * @see Walker::walk()
	 */
	function start_el( &$output, $item, $depth = 0, $args = array() ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $value . $class_names .'>';

		$item_output = $args->before;
		$item_output .= $this->output_item( $item, $args );
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );

		// increment our num_items counter but only if we're on an actual item
		// & not a section/column
		if ( isset( $item->url ) || isset( $item->post_title ) || isset( $item->type ) ) {
			$this->num_items++;
		}
	}

	/**
	 * Generates the HTML for the item itself, whether it be a link, title, or
	 * shortcode.
	 *
	 * @param WP_Post|stdClass The item to generate HTML for
	 * @param array The arguments passed to Walker_Nav_Mega_Menu::walk()
	 */
	private function output_item( $item, $args ) {
		if ( $item instanceof WP_Post || isset( $item->url ) ) {
			if ( $item instanceof WP_Post ) {
				$url = get_permalink( $item );
			} else {
				$url = $item->url;
			}

			$attributes = '';
			$attributes .= ! empty( $url )               ? ' href="'   . esc_attr( $url              ) .'"' : '';
			// TODO: title attribute
			// $attributes  .= ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
			// TODO: target attribute
			// $attributes .= ! empty( $item->target )      ? ' target="' . esc_attr( $item->target     ) .'"' : '';
			// TODO: rel/xfn attribute
			// $attributes .= ! empty( $item->xfn )         ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';

			return '<a'. $attributes .'>' . $args->link_before . apply_filters( 'the_title', $item->post_title, $item->ID ) . $args->link_after . '</a>';
		} else if ( $item->type == 'shortcode' ) {
			return do_shortcode( htmlspecialchars_decode( $item->shortcode, ENT_QUOTES ) );
		} else if ( isset( $item->post_title ) ) {
			return $item->post_title;
		} else {
			return '';
		}
	}
}
