<?php
/**
 * Plugin Name:     Events Tickets Extension: Virtual / Online Event Tickets
 * Description:     An extension that allows you to send event links in ticket email to registrants only
 * Version:         1.0.0
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

	private static $version = "1.0.0";

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
		$this->add_required_plugin( 'Tribe__Tickets__Main', '4.10.1' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		//add settings panel
		add_action( 'tribe_settings_do_tabs', array( $this, 'add_settings_tabs' ) );

		//hide the saved field in the frontend
		add_filter( 'tribe_get_custom_fields', array( $this, 'hide_online_link_field_from_details' ) );

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

	/**
	 * Register the settings tab and fields
     *
     * @since 1.0.0
     *
     * @return void
	 */
	public function add_settings_tabs() {
		require_once( dirname( __FILE__ ) . '/src/admin-views/tribe-options-virtual.php' );
		new Tribe__Settings_Tab( 'online-events', __( 'Online Events', 'tribe-events-calendar-pro' ), $onlineTab );
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
		return tribe_get_option( 'eventsOnlineField' );
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

		$online_link = get_post_meta( $event_id, $this->get_event_online_field(), true );

		if ( empty( $online_link ) ) {
			return;
		}

		$heading = tribe_get_option( 'eventsOnlineHeading' );
		?>
        <table class="content" align="center" width="620" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
               style="margin:15px auto 0; padding:0;">
            <tr>
                <td align="center" valign="top" class="wrapper" width="620">
                    <table class="inner-wrapper" border="0" cellpadding="0" cellspacing="0" width="620"
                           bgcolor="#f7f7f7" style="margin:0 auto !important; width:620px; padding:0;">
                        <tr>
                            <td valign="center" class="ticket-content" align="center" border="0" cellpadding="20"
                                cellspacing="0" style="padding:20px; background:#f7f7f7;">
                                <h3 style="color:#0a0a0e; margin:0 0 10px 0 !important; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-style:normal; text-decoration: underline; font-weight:700; font-size:28px; letter-spacing:normal; text-align:center;line-height: 100%;">
                                    <span style="color:#0a0a0e !important"><?php _e( $heading, 'tribe-ext-online-events' ); ?></span>
                                </h3>
                                <p>
                                    <a href="<?php esc_attr_e( $online_link ) ?>">
										<?php echo $online_link ?>
                                    </a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
		<?php
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
			return;
		}

		$event = tribe_get_event( $ticket['event_id'] );

		if ( ! $this->is_online_event( $event ) ) {
			return $enabled;
		}

		return false;
	}
}