<?php
namespace WP_Stream;

class Alert_Type_Menu_Alert extends Alert_Type {
	/**
	 * Alert type name
	 *
	 * @var string
	 */
	public $name = 'Create Menu Alert';

	/**
	 * Alert type slug
	 *
	 * @var string
	 */
	public $slug = 'menu-alert';

	/**
	 * Class Constructor
	 *
	 * @param Plugin $plugin Plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		parent::__construct( $plugin );
		add_action( 'admin_bar_menu', array( $this, 'menu_alert' ), 99 );
	}

	/**
	 * Notify user of triggered alert.
	 *
	 * @param int   $record_id Record that triggered alert.
	 * @param array $recordarr Record details.
	 * @param array $options Alert options.
	 * @return void
	 */
	public function alert( $record_id, $recordarr, $options ) {
		$this->add_message( $recordarr['summary'] );
		return;
	}

	/**
	 * Displays a settings form for the alert type
	 *
	 * @param Alert   $alert Alert object for the currently displayed alert.
	 * @param WP_Post $post Post object representing the current alert.
	 * @return void
	 */
	public function display_settings_form( $alert, $post ) {
		$options = wp_parse_args( $alert->alert_meta, array(
			'clear_immediate' => false,
		) );

		$form = new Form_Generator;
		$form->add_field( 'checkbox', array(
			'name'  => 'wp_stream_menu_alert_clear_immediate',
			'text'  => esc_attr( __( 'Clear alerts after seen.', 'stream' ) ),
			'value' => $options['clear_immediate'],
			'title' => __( 'Menu Bar', 'stream' ),
		) );

		echo $form->render_all(); // xss ok
	}

	/**
	 * Validates and saves form settings for later use.
	 *
	 * @param Alert   $alert Alert object for the currently displayed alert.
	 * @param WP_Post $post Post object representing the current alert.
	 * @return void
	 */
	public function process_settings_form( $alert, $post ) {
		check_admin_referer( 'save_post', 'wp_stream_alerts_nonce' );
		$alert->alert_meta['clear_immediate'] = ! empty( $_POST['wp_stream_menu_alert_clear_immediate'] );
	}

	/**
	 * Display and clear all unviewed menu alerts
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Representation of the WP Admin Bar.
	 * @return bool True if notifications were displayed, false otherwise.
	 */
	public function menu_alert( $wp_admin_bar ) {
		$messages = $this->get_messages();
		if ( ! $messages ) {
			return false;
		}

		$wp_admin_bar->add_node( array(
			'id' => 'wp_stream_alert_notify',
			'parent' => false,
			'title' => __( 'New Stream Alert', 'stream' ),
			'href' => '#',
			'meta' => array( 'class' => 'opposite' ),
		) );

		foreach ( $messages as $key => $message ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'wp_stream_alert_notify_' . $key,
				'parent' => 'wp_stream_alert_notify',
				'title'  => esc_html( $message ),
				'href'   => '#',
				'meta'   => array( 'class' => 'opposite' ),
			) );
		}

		$this->clear_messages();
		return true;
	}

	/**
	 * Get a list of all current alert messages for current user.
	 *
	 * @return array List of alert messages
	 */
	public function get_messages() {
		$current_user	= wp_get_current_user();
		$messages = get_user_meta( $current_user->ID, $this->get_key(), false );
		return $messages;
	}

	/**
	 * Adds a new alert message for the current user.
	 *
	 * @param string $message Alert message to add.
	 * @return void
	 */
	public function add_message( $message ) {
		$current_user	= wp_get_current_user();
		add_user_meta( $current_user->ID, $this->get_key(), $message, false );
	}

	/**
	 * Clears all alert messages for the current user.
	 *
	 * @param bool $global Whether to clear globally.
	 * @return void
	 */
	public function clear_messages( $global = false ) {
		$current_user	= wp_get_current_user();
		delete_user_meta( $current_user->ID, $this->get_key(), $global );
	}

	/**
	 * Returns meta key for pending alerts.
	 *
	 * @return string Meta key
	 */
	public function get_key() {
		return 'wp_stream_alerts_menu_pending';
	}
}
