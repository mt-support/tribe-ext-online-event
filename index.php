<?php
/**
 * Plugin Name:     Events Tickets Extension: Virtual / Online Event Tickets
 * Plugin URI:        https://theeventscalendar.com/extensions/add-a-private-event-link-to-ticket-emails/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-online-event
 * Description:     An extension that allows you to send event links in ticket email to registrants only
 * Version:         1.2
 * Extension Class: Tribe__Extension__Virtual__Event__Ticket
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     tribe-ext-online-events
 */

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

class Tribe__Extension__Virtual__Event__Ticket extends Tribe__Extension {

	private static $version = '1.2';

	/**
	 * Setup the Extension's properties.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		//add settings panel
		add_action( 'tribe_settings_do_tabs', [ $this, 'add_settings_tabs' ] );

		//hide the saved field in the frontend
		add_filter( 'tribe_get_custom_fields', [ $this, 'hide_online_link_field_from_details' ] );
		add_filter( 'tribe_get_custom_fields', [ $this, 'hide_online_link_field_from_details2' ] );
		add_filter( 'tribe_get_custom_fields', [ $this, 'hide_online_link_field_from_details3' ] );

		//add Event Link in the Ticket Email
		add_action( 'tribe_tickets_ticket_email_ticket_bottom', [ $this, 'render_online_link_in_email' ] );

		//disable QR Code
		add_filter( 'tribe_tickets_plus_qr_enabled', [ $this, 'disable_qr_code' ], 10, 2 );

		//add support for TEC Pro
		$this->add_support_tec_pro();

		//add support for Events Control Extension
		$this->add_support_events_control_extension();
	}

	/**
	 * Add The Events Calendar PRO support
	 */
	public function add_support_tec_pro() {
		if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
			add_filter( 'tribe_ext_online_event_setting_options', [ $this, 'add_tec_pro_setting' ], 10 );
		}
	}

	/**
	 * Filter Setting options
	 *
	 * @param $options
	 *
	 * @return mixed
	 */
	public function add_tec_pro_setting( $options ) {

		$fields = [];

		//created additional fields
		$custom_fields = tribe_get_option( 'custom-fields' );

		if ( ! empty( $custom_fields ) ) {
			$fields[0] = __( 'Select a Field', 'tribe-ext-online-events' );
			foreach ( $custom_fields as $field ) {
				$fields[ $field['name'] ] = $field['label'];
			}
		}

		$options['fields']['eventsOnlineField'] = [
			'type'            => 'dropdown',
			'label'           => __( '<strong>Event Link #1</strong> Additional Field', 'tribe-ext-online-events' ),
			'default'         => false,
			'validation_type' => 'options',
			'options'         => $fields,
			'if_empty'        => __( 'No Fields are found. You need to create an additional field. For help visit <a target="_blank" href="https://theeventscalendar.com/knowledgebase/k/pro-additional-fields/">here</a>', 'tribe-ext-online-events' ),
			'can_be_empty'    => true,
		];
		$options['fields']['eventsOnlineField2'] = [
			'type'            => 'dropdown',
			'label'           => __( '<strong>Event Link #2</strong> Additional Field', 'tribe-ext-online-events' ),
			'default'         => false,
			'validation_type' => 'options',
			'options'         => $fields,
			'if_empty'        => __( 'No Fields are found. You need to create an additional field. For help visit <a target="_blank" href="https://theeventscalendar.com/knowledgebase/k/pro-additional-fields/">here</a>', 'tribe-ext-online-events' ),
			'can_be_empty'    => true,
		];
		$options['fields']['eventsOnlineField3'] = [
			'type'            => 'dropdown',
			'label'           => __( '<strong>Event Link #3</strong> Additional Field', 'tribe-ext-online-events' ),
			'default'         => false,
			'validation_type' => 'options',
			'options'         => $fields,
			'if_empty'        => __( 'No Fields are found. You need to create an additional field. For help visit <a target="_blank" href="https://theeventscalendar.com/knowledgebase/k/pro-additional-fields/">here</a>', 'tribe-ext-online-events' ),
			'can_be_empty'    => true,
		];

		return $options;
	}

	/**
	 * Hide the selected additional field from Event Details
	 *
	 * @param $data array
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function hide_online_link_field_from_details( $data ) {

		$saved_field = $this->get_event_online_field();
		if ( empty( $saved_field ) ) {
			return $data;
		}

		$custom_fields = tribe_get_option( 'custom-fields' );

		$selected_field = '';

		foreach ( $custom_fields as $field ) {
			if ( $field['name'] == $saved_field ) {
				$selected_field = $field['label'];
				break;
			}
		}

		if ( isset( $data[ $selected_field ] ) ) {
			unset( $data[ $selected_field ] );
		}

		return $data;
	}
	public function hide_online_link_field_from_details2( $data ) {

		$saved_field = $this->get_event_online_field2();
		if ( empty( $saved_field ) ) {
			return $data;
		}

		$custom_fields = tribe_get_option( 'custom-fields' );

		$selected_field = '';

		foreach ( $custom_fields as $field ) {
			if ( $field['name'] == $saved_field ) {
				$selected_field = $field['label'];
				break;
			}
		}

		if ( isset( $data[ $selected_field ] ) ) {
			unset( $data[ $selected_field ] );
		}

		return $data;
	}
	public function hide_online_link_field_from_details3( $data ) {

		$saved_field = $this->get_event_online_field3();
		if ( empty( $saved_field ) ) {
			return $data;
		}

		$custom_fields = tribe_get_option( 'custom-fields' );

		$selected_field = '';

		foreach ( $custom_fields as $field ) {
			if ( $field['name'] == $saved_field ) {
				$selected_field = $field['label'];
				break;
			}
		}

		if ( isset( $data[ $selected_field ] ) ) {
			unset( $data[ $selected_field ] );
		}

		return $data;
	}

	/**
	 * Register the settings tab and fields
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_settings_tabs() {
		require_once( dirname( __FILE__ ) . '/src/admin-views/tribe-options-virtual.php' );
		new Tribe__Settings_Tab( 'online-events', __( 'Online Events', 'tribe-events-calendar-pro' ), apply_filters( 'tribe_ext_online_event_setting_options', $onlineTab ) );
	}

	/**
	 * Check if the event is online
	 *
	 * @param $event WP_Post
	 *
	 * @return bool
	 */
	public function is_online_event( $event ) {
		return apply_filters( 'tribe_ext_online_event_is_online', $this->has_online_category( $event ), $event );
	}

	/**
	 * Check if event has online category
	 *
	 * @param $event
     *
     * @since 1.1.0
	 *
	 * @return bool
	 */
	public function has_online_category( $event ) {

		$online_id = $this->get_online_category();

		//bail out if no online category id is set
		if ( ! $online_id ) {
			return false;
		}

		//get event cats
		$event_cats = wp_get_post_terms( $event->ID, Tribe__Events__Main::instance()->get_event_taxonomy() );
		$cat_ids    = wp_list_pluck( $event_cats, 'term_id' );

		if ( empty( $cat_ids ) ) {
			return false;
		}

		//check if cat exists
		return in_array( $online_id, $cat_ids );
	}

	/**
	 * Get selected category
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_online_category() {
		return tribe_get_option( 'eventsOnlineCategory' );
	}

	/**
	 * Get selected Field
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function get_event_online_field() {
		return apply_filters( 'tribe_ext_online_event_online_field', tribe_get_option( 'eventsOnlineField' ) );
	}
	public function get_event_online_field2() {
		return apply_filters( 'tribe_ext_online_event_online_field', tribe_get_option( 'eventsOnlineField2' ) );
	}
	public function get_event_online_field3() {
		return apply_filters( 'tribe_ext_online_event_online_field', tribe_get_option( 'eventsOnlineField3' ) );
	}

	/**
	 * Get email template output for an individual event link with header
	 *
	 * @since 1.2
	 *
	 * @return string
	 */
	public function get_email_link_template($online_link = '',$heading = '') {
		$output = '<table class="content" align="center" width="620" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
	           style="margin:15px auto 15px; padding:0;">
	        <tr>
	            <td align="center" valign="top" class="wrapper" width="620">
	                <table class="inner-wrapper" border="0" cellpadding="0" cellspacing="0" width="620"
	                       bgcolor="#f7f7f7" style="margin:0 auto !important; width:620px; padding:0;">
	                    <tr>
	                        <td valign="center" class="ticket-content" align="center" border="0" cellpadding="20"
	                            cellspacing="0" style="padding:20px; background:#f7f7f7;">';
	                            if(!empty($heading)){
		                            $output.= '<h3 style="color:#0a0a0e; margin:0 0 10px 0 !important; font-family: \'Helvetica Neue\', Helvetica, sans-serif; font-style:normal; text-decoration: underline; font-weight:700; font-size:28px; letter-spacing:normal; text-align:center;line-height: 100%;">
	                                <span style="color:#0a0a0e !important">';
	                                $output.= __( $heading, 'tribe-ext-online-events' );
	                                $output.= '</span>
	                            </h3>';
	                            }
	                            $output.= '
	                            <p>
	                                <a href="';
	                                $output.= esc_attr( $online_link );
	                                $output.= '">';
										$output.= $online_link;
	                                $output.= '</a>
	                            </p>
	                        </td>
	                    </tr>
	                </table>
	            </td>
	        </tr>
	    </table>
	    ';
	    return $output;
	}

	/**
	 * Render Event Link within Ticket Email
	 *
	 * @param $ticket array
     *
     * @since 1.0.0
     *
     * @return void
	 */
	public function render_online_link_in_email( $ticket ) {

		$event_id = isset( $ticket['event_id'] ) ? $ticket['event_id'] : 0;

		$event = tribe_get_event( $event_id );

		if ( ! empty( $event ) && ! tribe_is_event( $event ) ) {
			return;
		}

		if ( ! $this->is_online_event( $event ) ) {
			return;
		}

		$online_link = get_post_meta( $event_id, $this->get_event_online_field(), true );
		$heading = tribe_get_option( 'eventsOnlineHeading' );
		if ( !empty( $online_link ) && !is_array( $online_link1 ) ) {
			echo $this->get_email_link_template($online_link,$heading);
		}

		$online_link2 = get_post_meta( $event_id, $this->get_event_online_field2(), true );
		$heading2 = tribe_get_option( 'eventsOnlineHeading2' );
		if ( !empty( $online_link2 ) && !is_array( $online_link2 ) ) {
			echo $this->get_email_link_template($online_link2,$heading2);
		}

		$online_link3 = get_post_meta( $event_id, $this->get_event_online_field3(), true );
		$heading3 = tribe_get_option( 'eventsOnlineHeading3' );
		if ( !empty( $online_link3 ) && !is_array( $online_link3 ) ) {
			echo $this->get_email_link_template($online_link3,$heading3);
		}
	}

	/**
	 * Disable showing QR Code for Events with selected category
	 *
	 * @param $enabled bool
	 * @param $ticket array
	 *
     * @since 1.0.0
     *
	 * @return bool
	 */
	public function disable_qr_code( $enabled, $ticket ) {
		if ( ! isset( $ticket['event_id'] ) ) {
			return $enabled;
		}

		$event = tribe_get_event( $ticket['event_id'] );

		if ( ! $this->is_online_event( $event ) ) {
			return $enabled;
		}

		return false;
	}

	/**
	 * Add Support for Events Control
	 *
	 * @since 1.1.0
	 */
	public function add_support_events_control_extension() {
		if ( class_exists( 'Tribe\Extensions\EventsControl\Event_Meta' ) ) {
			//Provide an option to select
			add_filter( 'tribe_ext_online_event_is_online', [ $this, 'events_control_is_online' ], 10, 2 );
			add_filter( 'tribe_ext_online_event_online_field', [ $this, 'events_control_online_field' ] );
			add_filter( 'tribe_ext_online_event_setting_options', [ $this, 'events_control_options' ], 20 );
			add_filter( 'tribe_template_pre_html', [ $this, 'remove_location_marker_from_frontend' ], 20, 4 );
		}
	}

	/**
	 * Check if Event is online from Event Controls
	 *
	 * @param $is_online
	 * @param $event
     *
     * @since 1.1.0
	 *
	 * @return boolean
	 */
	public function events_control_is_online( $is_online, $event ) {
		$event_meta        = tribe( 'Tribe\Extensions\EventsControl\Event_Meta' );
		$event_meta_online = $event_meta->is_online( $event );

		return $event_meta_online ? $event_meta_online : $is_online;
	}

	/**
	 * Filter Event Link URL for Events Control
	 *
	 * @param $field
     *
     * @since 1.1.0
	 *
	 * @return mixed
	 */
	public function events_control_online_field( $field ) {
		$event_meta = tribe( 'Tribe\Extensions\EventsControl\Event_Meta' );

		return $event_meta::$key_online_url;
	}

	/**
	 * Add options for Events Control extension
	 *
	 * @param $options
     *
     * @since 1.1.0
	 *
	 * @return array
	 */
	public function events_control_options( $options ) {
		$remove_fields = [
			'eventsOnlineCategoryHelperTitle',
			'eventsOnlineCategory',
			'eventsOnlineFieldHelperTitle',
			'eventsOnlineField',
			'eventsOnlineField2',
			'eventsOnlineField3',
		];

		foreach ( $remove_fields as $field ) {
			unset( $options['fields'][ $field ] );
		}

		$options['fields']['info-box-description']['html'] = __( 'You have <a target="_blank" href="https://theeventscalendar.com/extensions/event-statuses/">The Events Control</a> extension installed with options for selecting Online Events and an Event URL. <p>Event URLs for marked online events will be sent in ticket email.</p>', 'tribe-ext-online-events' );

		$options['fields']['eventsControlHideLink'] = [
			'type'            => 'checkbox_bool',
			'label'           => esc_html__( 'Hide Event\'s Online URL in the Event Page', 'tribe-ext-online-events' ),
			'default'         => false,
			'validation_type' => 'boolean',
		];

		return $options;
	}

	/**
     * Filter HTML template to hide the online URL
     *
	 * @param $pre_html
	 * @param $file
	 * @param $name
	 * @param $template_class
     *
     * @since 1.1.0
	 *
	 * @return string
	 */
	public function remove_location_marker_from_frontend( $pre_html, $file, $name, $template_class ) {

		if ( 'single/online-marker' != implode( '/', $name ) ) {
			return $pre_html;
		}

		if ( ! tribe_is_truthy( tribe_get_option( 'eventsControlHideLink' ) ) ) {
			return $pre_html;
		}

		return '';
	}
}
