<?php

$event_cats = [];
$fields     = [];

if ( 'online-events' === tribe_get_request_var( 'tab' ) ) {

	$taxonomy       = Tribe__Events__Main::instance()->get_event_taxonomy();
	$selected_terms = [];
	$taxonomy_obj   = get_taxonomy( $taxonomy );

	$terms = get_terms( $taxonomy, ['hide_empty' => false] );

	if ( ! empty( $terms ) ) {
		$event_cats[0] = __( 'Select a category', 'tribe-ext-online-events' );
		foreach ( $terms as $term ) {
			$event_cats[ $term->term_id ] = $term->name;
		}
	}

	$custom_fields = tribe_get_option( 'custom-fields' );

	if ( ! empty( $custom_fields ) ) {
		$fields[0] = __( 'Select a Field', 'tribe-ext-online-events' );
		foreach ( $custom_fields as $field ) {
			$fields[ $field['name'] ] = $field['label'];
		}
	}
}

$onlineTab = [
	'priority' => 30,
	'fields'   => [
		'info-start' => [
			'type' => 'html',
			'html' => '<div id="modern-tribe-info">',
        ],
		'info-box-title' => [
			'type' => 'html',
			'html' => '<h2>' . __( 'Online Events', 'tribe-ext-online-events' ) . '</h2>',
        ],
		'info-box-description' => [
			'type' => 'html',
			'html' => '<p>' . __( '<p>Choose the category and fields for events that are Online or Virtual. </p>', 'tribe-ext-online-events' ) . '</p>',
        ],
		'info-end' => [
			'type' => 'html',
			'html' => '</div>',
        ],
		'tribe-form-content-start' => [
			'type' => 'html',
			'html' => '<div class="tribe-settings-form-wrap">',
        ],
		'eventsOnlineCategoryHelperTitle' => [
			'type' => 'html',
			'html' => '<h3>' . __( 'Online Event Category', 'tribe-ext-online-events' ) . '</h3>',
        ],
		'eventsOnlineCategory' => [
			'type'            => 'dropdown',
			'label'           => __( 'Category', 'tribe-ext-online-events' ),
			'default'         => false,
			'validation_type' => 'options',
			'options'         => $event_cats,
			'if_empty'        => __( 'No categories yet. Create a category under Events > Categories for your online events', 'tribe-ext-online-events' ),
			'can_be_empty'    => true,
        ],
        'eventsOnlineFieldHelperEmail' => [
            'type' => 'html',
            'html' => '<h3>' . __( 'Email options', 'tribe-ext-online-events' ) . '</h3>',
        ],
        'eventsOnlineHeading' => [
            'type'            => 'text',
            'label'           => __( 'Email Section Title' ),
            'tooltip'         => '',
            'default'         => 'Online Event Details',
            'validation_type' => 'html',
            'size'            => 'medium',
            'can_be_empty'    => false,
        ],
        'eventsOnlineFieldHelperTitle' => [
            'type' => 'html',
            'html' => '<h3>' . __( 'Online Event Fields', 'tribe-ext-online-events' ) . '</h3>',
        ]
    ]
];

foreach( $additional_email_fields as $additional_email_field => $args ) {

    $field_id = $args['option'];
    $field_label = sprintf( __( 'Field for %s', 'tribe-ext-online-events' ), $args['label'] );

    if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
        $onlineTab['fields'][$field_id] = [
            'type'            => 'dropdown',
            'label'           => $field_label,
            'default'         => false,
            'validation_type' => 'options',
            'options'         => $fields,
            'if_empty'        => __( 'No Fields are found. You need to create an additional field. For help visit <a target="_blank" href="https://theeventscalendar.com/knowledgebase/k/pro-additional-fields/">here</a>', 'tribe-ext-online-events' ),
            'can_be_empty'    => true,
        ];
    }
    else {
        $onlineTab['fields'][$field_id] = [
            'type'            => 'text',
            'label'           => $field_label,
            'default'         => '',
            'can_be_empty'    => true,
        ];
    }
}

if ( ! class_exists( 'Tribe__Events__Pro__Main' ) ) {
    $onlineTab['fields']['eventsOnlineFieldHelperNotes'] = [
        'type' => 'html',
        'html' => '<p>' .  __( 'To learn more about custom fields visit the WordPress <a target="_blank" href="https://wordpress.org/support/article/custom-fields/">Custom Fields Wiki</a>', 'tribe-ext-online-events' ) . '</p>',
    ];
}