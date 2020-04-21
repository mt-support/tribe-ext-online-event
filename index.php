<?php
/**
 * Plugin Name:     Events Tickets Extension: Virtual / Online Event Tickets
 * Description:     An extension that allows you to send event links in ticket email to registrants only
 * Version:         1.0.1
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

	private static $version = "1.0.1";

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
		add_action( 'tribe_settings_do_tabs', array( $this, 'add_settings_tabs' ) );

		//hide the saved field in the frontend
		add_filter( 'tribe_get_custom_fields', array( $this, 'hide_online_event_fields_from_details' ) );

		//add Event Link in the Ticket Email
		add_action( 'tribe_tickets_ticket_email_ticket_bottom', array( $this, 'render_online_link_in_email' ) );

		//disable QR Code
		add_filter( 'tribe_tickets_plus_qr_enabled', array( $this, 'disable_qr_code' ), 10, 2 );

		//hide the venue details

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
}