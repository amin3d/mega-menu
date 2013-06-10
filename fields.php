<?php
/**
 * Registers the Advanced Custom Fields fields for the Mega Menu
 * @author Joey Line
 */

add_action( 'acf/register_fields', 'mega_menu_register_fields' );
function mega_menu_register_fields() {
	include_once WP_PLUGIN_DIR.'/acf-flexible-content/flexible-content.php';
	include_once WP_PLUGIN_DIR.'/acf-repeater/repeater.php';
	include_once WP_PLUGIN_DIR.'/acf-options-page/acf-options-page.php';
}

if( function_exists( 'register_options_page' ) ) {
	register_options_page('Mega Menu');
}

if( function_exists( "register_field_group" ) ) {
	register_field_group(array(
		'id' => 'acf_mega-menu',
		'title' => 'Mega Menu',
		'fields' => array(
			array(
				'key' => 'field_519ce1eeff9c1',
				'label' => '',
				'name' => 'mega_menu',
				'type' => 'repeater',
				'sub_fields' => array(
					array(
						'post_type' => array(
							0 => 'page',
						),
						'taxonomy' => array(
							0 => 'all',
						),
						'multiple' => 0,
						'allow_null' => 0,
						'key' => 'field_519ce215ff9c2',
						'label' => 'Link',
						'name' => 'link',
						'type' => 'post_object',
						'column_width' => 10,
					),
					array(
						'key' => 'field_519ce23aff9c3',
						'label' => 'Columns',
						'name' => 'columns',
						'type' => 'flexible_content',
						'column_width' => '',
						'layouts' => array(
							array(
								'label' => 'Hierarchical',
								'name' => 'hierarchical',
								'display' => 'table',
								'sub_fields' => array(
									array(
										'key' => 'field_519ce249ff9c4',
										'label' => '',
										'name' => '',
										'type' => 'message',
										'column_width' => '',
										'message' => 'This column will be generated based on the WordPress page hierarchy. Choose the max number of items per column at right.',
									),
									array(
										'key' => 'field_519cfdf2ccf49',
										'label' => 'Max items per column',
										'name' => 'max_items_per_column',
										'type' => 'number',
										'column_width' => '',
										'default_value' => '13',
									),
								),
							),
							array(
								'label' => 'Custom Column',
								'name' => 'custom_column',
								'display' => 'table',
								'sub_fields' => array(
									array(
										'key' => 'field_519ce336ff9ca',
										'label' => 'Section',
										'name' => 'section',
										'type' => 'repeater',
										'column_width' => '',
										'sub_fields' => array(
											array(
												'key' => 'field_519ce346ff9cb',
												'label' => 'Section Title',
												'name' => 'section_title',
												'type' => 'text',
												'column_width' => '',
												'default_value' => '',
												'formatting' => 'html',
											),
											array(
												'post_type' => array(
													0 => 'all',
												),
												'taxonomy' => array(
													0 => 'all',
												),
												'multiple' => 0,
												'allow_null' => 1,
												'key' => 'field_519ce350ff9cc',
												'label' => 'Section Link',
												'name' => 'section_link',
												'type' => 'post_object',
												'column_width' => '',
											),
											array(
												'key' => 'field_519ce369ff9cd',
												'label' => 'Sublinks',
												'name' => 'sublinks',
												'type' => 'flexible_content',
												'column_width' => '',
												'layouts' => array(
													array(
														'label' => 'Page/Post',
														'name' => 'post',
														'display' => 'table',
														'sub_fields' => array(
															array(
																'key' => 'field_519ce397ff9ce',
																'label' => 'Override Text',
																'name' => 'title',
																'type' => 'text',
																'column_width' => '',
																'default_value' => '',
																'formatting' => 'html',
															),
															array(
																'post_type' => array(
																	0 => 'all',
																),
																'taxonomy' => array(
																	0 => 'all',
																),
																'multiple' => 0,
																'allow_null' => 0,
																'key' => 'field_519ce3a3ff9cf',
																'label' => 'Page/Post',
																'name' => 'post',
																'type' => 'post_object',
																'column_width' => '',
															),
														),
													),
													array(
														'label' => 'Click 2 Call',
														'name' => 'c2c',
														'display' => 'table',
														'sub_fields' => array(
															array(
																'key' => 'field_519ce3dcff9d1',
																'label' => 'Text',
																'name' => 'title',
																'type' => 'text',
																'column_width' => '',
																'default_value' => '',
																'formatting' => 'html',
															),
														),
													),
													array(
														'label' => 'Custom',
														'name' => 'custom',
														'display' => 'table',
														'sub_fields' => array(
															array(
																'key' => 'field_519ce3faff9d3',
																'label' => 'Text',
																'name' => 'title',
																'type' => 'text',
																'column_width' => '',
																'default_value' => '',
																'formatting' => 'html',
															),
															array(
																'key' => 'field_519ce406ff9d4',
																'label' => 'URL',
																'name' => 'url',
																'type' => 'text',
																'column_width' => '',
																'default_value' => '',
																'formatting' => 'none',
															),
														),
													),
													array(
														'label' => 'Shortcode',
														'name' => 'shortcode',
														'display' => 'table',
														'sub_fields' => array(
															array(
																'key' => 'field_519ce43dff9d6',
																'label' => 'Value',
																'name' => 'shortcode',
																'type' => 'textarea',
																'column_width' => '',
																'default_value' => '',
																'formatting' => 'none',
															),
														),
													),
												),
												'button_label' => 'Add link',
											),
										),
										'row_min' => 0,
										'row_limit' => '',
										'layout' => 'table',
										'button_label' => 'Add section',
									),
								),
							),
						),
						'button_label' => 'Add column',
					),
				),
				'row_min' => 0,
				'row_limit' => '',
				'layout' => 'table',
				'button_label' => 'Add top-level link',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'options_page',
					'operator' => '==',
					'value' => 'acf-options-mega-menu',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array(
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => array(
			),
		),
		'menu_order' => 0,
	));
}
