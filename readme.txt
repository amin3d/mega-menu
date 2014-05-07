=== Big Voodoo Mega Menu & Related Links Menu ===
Contributors: bigvoodoo, firejdl, geekmenina
Tags: menu, mega menu, admin, shortcode
Requires at least: 3.5
Tested up to: 3.6.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enhancements to the wp-admin Menu interface that allow for faster, more robust, and easier to edit menus. Also includes a Related Links Menu.

== Description ==

**NOTE: We do not currently offer support for this plugin. Use at your own risk. Thank you.**

This is a plugin for WordPress 3.5+ that enhances the Menu experience in several ways:

* adds enhancements to the wp-admin Menu interface (see below)
* saves menus to its own table to speed up generation of menus on the front-end
* adds two shortcodes to displays menus from the admin interface - `[mega_menu]` & `[related_links]` (see the Installation section)

License: [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)

= Menu Interface Enhancements =

* Adds the ability to add a Shortcode and/or custom HTML to any menu! Now you can display forms, widgets, anything you want inside of a menu.
* Adds the ability to add a "Column/Section" to any menu, which allows for logical division of menu items and both simpler and stronger styling.
  * Columns/Sections can have an optional header with an optional link.
* Adds the ability to add an existing menu to a menu. Menu items that repeat in several different places can be created as a menu and added multiple times.
  * Menus can have an optional header with an optional link.
* Adds a button to the menu item options to add descendents of page in the WP page hierarchy.

= Requirements =

* WordPress 3.5+
* PHP 5.3+

= TODO =

* improve documentation (including this document)
* i18n/L10n

= Inspirations & Thanks =

* [Gecka Submenu](https://github.com/Gecka-Apps/Wordpress_Gecka_Submenu)
* [Add Descendents as Submenu Items](http://wordpress.org/plugins/add-descendants-as-submenu-items)
* [Custom Post Type's Archive in WP Nav Menu](http://wordpress.org/plugins/add-custom-post-types-archive-to-nav-menus)
* [/wp-admin/includes/nav-menu.php:`wp_nav_menu_item_link_meta_box()`](http://core.trac.wordpress.org/browser/tags/3.3.1/wp-admin/includes/nav-menu.php#L573)
* [Big Voodoo Interactive](http://www.bigvoodoo.com) for letting me write and open-source this plugin :)

== Installation ==

1. Install the plugin in WordPress & activate it.
1. [register](http://codex.wordpress.org/Function_Reference/register_nav_menu) a menu location in your theme.
1. Setup the menu hierarchy under Appearance -> Menu.
1. Assign the menu from step 3 to the menu location in step 2.
1. Use one of the shortcodes to display a menu.

= Shortcodes =

**[mega_menu]**

Given a `theme_location` attribute, this shortcode displays a ul-style Mega Menu for the menu assigned to that location, which can be easily styled with CSS in your theme.
Options:

* `theme_location`: The location in the theme to be used - must be registered with [`register_nav_menu()`](http://codex.wordpress.org/Function_Reference/register_nav_menu) in order to be selectable by the user. **required**
* `before`: Output text before the `<a>` of the link
* `after`: Output text after the `</a>` of the link
* `link_before`: Output text before the link text
* `link_after`: Output text after the link text
* `ajax`: if "true", loads the Mega part of the menu via AJAX.

Example: `[mega_menu theme_location="mega" before="<div class='surround'>" after="</div>" link_before="<span>" link_after="</span>"]`

**[related_links]**

Given a `theme_location` attribute, the shortcode displays a Related Links Menu for the menu assigned to that location, which shows either children, siblings, or top-level pages (chosen in that order).
Options:

* `theme_location`: The location in the theme to be used - must be registered with [`register_nav_menu()`](http://codex.wordpress.org/Function_Reference/register_nav_menu) in order to be selectable by the user. **required**
* `before`: Output text before the `<a>` of the link
* `after`: Output text after the `</a>` of the link
* `link_before`: Output text before the link text
* `link_after`: Output text after the link text

Example: `[related_links theme_location="mega"]`

= Filters =

**walker_nav_menu_start_el**

Allows modification of the `$output`, called when the Walker has created an `<li>` and started populating it.

Arguments:

* `$output`: the output for the menu so far.
* `$item`: the current menu item.
* `$depth`: the current depth.
* `$args`: the arguments passed to `Walker_Nav_Mega_Menu`.

Example:

`function override_nav_menu_start_el( $output, $item, $depth, $args ) {
	if( $args->menu_type == 'mega' && $depth == 0 && $args->ajax !== "true" ) {
		// add header
		$output .= '<h2>' . get_the_title( $item->post_id ) . '</h2>';
	}
	return $output;
}
add_filter( 'walker_nav_menu_start_el', 'override_nav_menu_start_el', 99, 4 );`

**walker_nav_menu_end_el**

Allows modification of the `$output`, called before the Walker adds `</li>` and after any children are added to the `$output`.

Arguments:

* `$output`: the output for the menu so far.
* `$item`: the current menu item.
* `$depth`: the current depth.
* `$args`: the arguments passed to `Walker_Nav_Mega_Menu`.

Example:

`function override_nav_menu_end_el( $output, $item, $depth, $args ) {
	if( $args->menu_type == 'mega' && $depth == 0 && $args->ajax !== "true" ) {
		// add footer
		$output .= '<div class="menu_footer">footer for ' . get_the_title( $item->post_id ) . '</div>';
	}
	return $output;
}
add_filter( 'walker_nav_menu_end_el', 'override_nav_menu_end_el', 99, 4 );`

== Changelog ==

= 0.3.0 =

* added options to use some basic default JS & CSS
* added ability to load Mega part of the menu via AJAX
* fixed some bugs

= 0.2.0 =

* complete rewrite from the ground up

= 0.1.0 =

* initial release

== Upgrade Notice ==

= 0.3.0 =

* added options to use some basic default JS & CSS
* added ability to load Mega part of the menu via AJAX
* fixed some bugs

= 0.2.0 =

* complete rewrite from the ground up

= 0.1.0 =

* initial release
