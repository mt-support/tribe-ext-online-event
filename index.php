<?php
/**
 * Plugin Name:     Events Tickets Extension: Virtual / Online Event Tickets
 * Plugin URI:        https://theeventscalendar.com/extensions/add-a-private-event-link-to-ticket-emails/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-online-event
 * Description:     An extension that allows you to send event links in ticket email to registrants only
 * Version:         1.2.0
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

    private static $version = "1.2.0";

    /**
     * Setup the Extension's properties.
     *
     */
    public function construct() {
        $this->add_required_plugin( 'Tribe__Events__Main' );
        $this->add_required_plugin( 'Tribe__Tickets__Main');
    }

    /**
     * Extension initialization and hooks.
     */
    public function init() {
        //add settings panel
        add_action( 'tribe_settings_do_tabs', [ $this, 'add_settings_tabs' ] );

        //hide the saved field in the frontend
        add_filter( 'tribe_get_custom_fields', [ $this, 'hide_online_event_fields_from_details' ] );

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
            'label'           => __( 'Events Additional Field that contains Event link', 'tribe-ext-online-events' ),
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
    public function hide_online_event_fields_from_details( $data ) {

        $fields = $this->get_fields();
        $blacklist = [];

        foreach( $fields as $field => $args) {
            if( isset( $args['option'] ) ) {
                $blacklist[] = $args['option'];
            }
        }

        foreach( tribe_get_option( 'custom-fields' ) as $field ) {

            if( in_array( $field['name'], $blacklist ) ) {

                if( isset( $data[ $field['label'] ] ) ) {
                    unset( $data[ $field['label'] ] );
                }
            }
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
        $additional_email_fields = $this->get_fields();
        require_once( dirname( __FILE__ ) . '/src/admin-views/tribe-options-virtual.php' );
        new Tribe__Settings_Tab( 'online-events', __( 'Online Events', 'tribe-events-calendar-pro' ), $onlineTab );
    }

    /**
     * Disable showing QR Code for Events with selected category
     *
     * @since 1.0.0
     *
     * @param $enabled bool
     * @param $ticket array
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
     * Check if the event contains the Selected category
     *
     * @param $event WP_Post
     *
     * @return bool
     */
    public function is_online_event( $event ) {

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
     * Get Fields
     *
     * @since 1.1.0
     *
     * @return array
     */
    private function get_fields() {

        $fields = [
            'id' => [
                'label' => __('Meeting ID', 'tribe-ext-online-events'),
                'option' => 'eventsOnlineID',
                'content' => '%s'
            ],
            'password' => [
                'label' => __('Password', 'tribe-ext-online-events'),
                'option' => 'eventsOnlinePassword',
                'content' => '%s'
            ],
            'link' => [
                'label' => __('Link', 'tribe-ext-online-events'),
                'option' => 'eventsOnlineLink',
                'content' => '<a href="%1$s">%1$s</a>'
            ]
        ];

        $fields = apply_filters( 'tribe/events-online/email-fields', $fields );

        return $fields;
    }

    /**
     * Render Event Link within Ticket Email
     *
     * @since 1.0.0
     *
     * @param $ticket array
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

        $heading = tribe_get_option( 'eventsOnlineHeading' );
        $fields = $this->get_fields();
        ?>
        <div style="background:#f7f7f7;padding:25px;margin:15px 0;">
            <h3 style="color:#0a0a0e;margin:0 0 25px 0!important;font-family:'Helvetica Neue',Helvetica,sans-serif;text-decoration: underline;font-weight:700;font-size:28px;text-align:left;">
                <span style="color:#0a0a0e!important"><?php _e( $heading, 'tribe-ext-online-events' ); ?></span>
            </h3>
            <table class="content" cellspacing="0" cellpadding="0" border="0" style="width:100%;" >
                <tbody>
                    <tr>
                        <?php foreach( $fields as $field => $args):
                            $value = get_post_meta( $event_id, tribe_get_option( $args['option'] ), true );
                            if( ! $value ) continue; ?>
                            <td valign="top" align="left" width="120">
                                <h6 style="color:#909090!important;margin:0 0 10px 0;font-family:'Helvetica Neue',Helvetica,sans-serif;text-transform:uppercase;font-size:13px;font-weight:700!important;">
                                    <?php echo esc_html( $args['label'] ) ?>
                                </h6>
                                <span style="color:#0a0a0e!important;font-family:'Helvetica Neue',Helvetica,sans-serif;font-size:15px;">
                                    <?php printf( $args['content'], esc_html( $value ) ) ?>
                                </span>
                            </td>
                        <?php endforeach ?>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
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
