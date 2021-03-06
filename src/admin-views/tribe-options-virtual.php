<?php

$event_cats = [];

if ( 'online-events' === tribe_get_request_var( 'tab' ) ) {
	$taxonomy       = Tribe__Events__Main::instance()->get_event_taxonomy();
	$selected_terms = [];
	$taxonomy_obj   = get_taxonomy( $taxonomy );

	$terms = get_terms(
		$taxonomy,
		[
			'hide_empty' => false,
		]
	);

	if ( ! empty( $terms ) ) {
		$event_cats[0] = __( 'Select a category', 'tribe-ext-online-events' );
		foreach ( $terms as $term ) {
			$event_cats[ $term->term_id ] = $term->name;
		}
	}
}

$onlineTab = [
	'priority' => 30,
	'fields'   => [
		'info-start'                      => [
			'type' => 'html',
			'html' => '<div id="modern-tribe-info">',
		],
		'info-box-title'                  => [
			'type' => 'html',
			'html' => '<h2>' . __( 'Online Events', 'tribe-ext-online-events' ) . '</h2>',
		],
		'info-box-description'            => [
			'type' => 'html',
			'html' => '<p>' . __( '<p>Choose the category and fields for events that are Online or Virtual. </p>', 'tribe-ext-online-events' ) . '</p>',
		],
		'info-end'                        => [
			'type' => 'html',
			'html' => '</div>',
		],
		'tribe-form-content-start'        => [
			'type' => 'html',
			'html' => '<div class="tribe-settings-form-wrap">',
		],
		'eventsOnlineCategoryHelperTitle' => [
			'type' => 'html',
			'html' => '<h3>' . __( 'Online Event Category', 'tribe-ext-online-events' ) . '</h3>',
		],
		'eventsOnlineCategory'            => [
			'type'            => 'dropdown',
			'label'           => __( 'Category', 'tribe-ext-online-events' ),
			'default'         => false,
			'validation_type' => 'options',
			'options'         => $event_cats,
			'if_empty'        => __( 'No categories yet. Create a category under Events > Categories for your online events', 'tribe-ext-online-events' ),
			'can_be_empty'    => true,
		],
		'eventsOnlineFieldHelperTitle'    => [
			'type' => 'html',
			'html' => '<h3>' . __( 'Online Event Field', 'tribe-ext-online-events' ) . '</h3>',
		],
	]
];

$onlineTab['fields']['eventsOnlineField'] = [
	'type'         => 'text',
	'label'        => __( 'Custom field that contains Event link', 'tribe-ext-online-events' ),
	'default'      => '',
	'tooltip'      => __( 'To know more about Custom fields visit the WordPress <a target="_blank" href="https://wordpress.org/support/article/custom-fields/">Custom Fields Wiki</a>', 'tribe-ext-online-events' ),
	'can_be_empty' => true,
	'validation_type' => 'alpha_numeric_with_dashes_and_underscores',
	'size'            => 'medium',
];

$onlineTab['fields']['eventsOnlineFieldHelperEmail'] = [
	'type' => 'html',
	'html' => '<h3>' . __( 'Email options', 'tribe-ext-online-events' ) . '</h3>',
];

$onlineTab['fields']['eventsOnlineHeading'] = [
	'type'            => 'text',
	'label'           => __( 'Email Heading for link', 'tribe-ext-online-events' ),
	'tooltip'         => '',
	'default'         => 'Event Link',
	'validation_type' => 'html',
	'size'            => 'medium',
	'can_be_empty'    => false,
];