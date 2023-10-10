<?php
/**
 * File contains class related to Admin views.
 *
 * @package inactive-logout
 */

// Not Permission to agree more or less then given.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Admin Views Class
 *
 * @since  1.0.0
 * @author  Deepen
 */
class Inactive_Logout_Admin_Views {

	/**
	 * Helper.
	 *
	 * @var Inactive_Logout_Helpers
	 */
	public $helper;

	/**
	 * Inactive_Logout_Admin_Views constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'ina_create_options_menu' ) );

		// Add Menu for multisite network.
		add_action( 'network_admin_menu', array( $this, 'ina_menu_multisite_network' ) );

		add_action( 'ina_before_settings_wrapper', array( $this, 'ina_before_settings_wrap' ) );
		add_action( 'ina_after_settings_wrapper', array( $this, 'ina_after_settings_wrap' ) );

		$this->helper = Inactive_Logout_Helpers::instance();
	}

	/**
	 * Add a Menu Option in settings
	 */
	public function ina_create_options_menu() {
		if ( is_multisite() ) {

			$options                           = get_site_option( '__ina_inactive_logout_options' );
			$idle_overrideby_multisite_setting = ( isset( $options['__ina_overrideby_multisite_setting'] ) ) ? $options['__ina_overrideby_multisite_setting'] : '';

			if ( empty( $idle_overrideby_multisite_setting ) ) {
				add_options_page(
					__( 'Inactive User Logout Settings', 'inactive-logout' ),
					__( 'Inactive Logout', 'inactive-logout' ),
					'manage_options',
					'inactive-logout',
					array( $this, 'ina__render_options' )
				);
			}
		} else {
			add_options_page(
				__( 'Inactive User Logout Settings', 'inactive-logout' ),
				__( 'Inactive Logout', 'inactive-logout' ),
				'manage_options',
				'inactive-logout',
				array( $this, 'ina__render_options' )
			);
		}
	}

	/**
	 * Add menu page.
	 */
	public function ina_menu_multisite_network() {
		add_menu_page(
			__( 'Inactive User Logout Settings', 'inactive-logout' ),
			__( 'Inactive Logout', 'inactive-logout' ),
			'manage_options',
			'inactive-logout',
			array( $this, 'ina__render_options' )
		);
	}

	/**
	 * Rendering the output.
	 */
	public function ina__render_options() {

		$submit = filter_input( INPUT_POST, 'submit', FILTER_SANITIZE_STRING );

		if ( isset( $submit ) ) {
			$this->ina__process_basic_settings();
		}

		// Css rules for Color Picker.
		wp_enqueue_style( 'wp-color-picker' );

		// Include Template.
		do_action( 'ina_before_settings_wrapper' );

		// BASIC.
		if ( is_network_admin() && is_multisite() ) {
			$options                           = get_site_option( '__ina_inactive_logout_options' );
			$idle_overrideby_multisite_setting = ( isset( $options['__ina_overrideby_multisite_setting'] ) ) ? $options['__ina_overrideby_multisite_setting'] : false;
		} else {
			$options = get_option( '__ina_inactive_logout_options' );
		}

		$time                    = ( isset( $options['__ina_logout_time'] ) ) ? $options['__ina_logout_time'] : 0;
		$countdown               = ( isset( $options['__ina_disable_countdown'] ) ) ? $options['__ina_disable_countdown'] : 10;
		$ina_full_overlay        = ( isset( $options['__ina_full_overlay'] ) ) ? $options['__ina_full_overlay'] : '';
		$ina_popup_overlay_color = ( isset( $options['__ina_popup_overlay_color'] ) ) ? $options['__ina_popup_overlay_color'] : '';
		$content                 = ( isset( $options['__ina_logout_message'] ) ) ? $options['__ina_logout_message'] : '';

		require_once INACTIVE_LOGOUT_VIEWS . '/tpl-inactive-logout-basic.php';

		do_action( 'ina_after_settings_wrapper' );
	}

	/**
	 * Manages Basic settings.
	 */
	public function ina__process_basic_settings() {

		$sm_nonce = filter_input( INPUT_POST, '_save_timeout_settings', FILTER_SANITIZE_STRING );
		$nonce    = isset( $sm_nonce ) ? $sm_nonce : '';
		$submit   = filter_input( INPUT_POST, 'submit', FILTER_SANITIZE_STRING );

		if ( isset( $submit ) && ! wp_verify_nonce( $nonce, '_nonce_action_save_timeout_settings' ) ) {
			wp_die( 'Not Allowed' );
			return;
		}

		$idle_timeout           = filter_input( INPUT_POST, 'idle_timeout', FILTER_SANITIZE_NUMBER_INT );
		$idle_timeout_message   = wp_kses_post( filter_input( INPUT_POST, 'idle_message_text' ) );
		$idle_disable_countdown = filter_input( INPUT_POST, 'idle_disable_countdown', FILTER_SANITIZE_NUMBER_INT );

		$ina_background_popup = trim( filter_input( INPUT_POST, 'ina_color_picker' ) );
		$ina_background_popup = wp_strip_all_tags( stripslashes( $ina_background_popup ) );

		$ina_full_overlay = filter_input( INPUT_POST, 'ina_full_overlay', FILTER_SANITIZE_NUMBER_INT );

		do_action( 'ina_before_update_basic_settings' );

		// If Mulisite is Active then Add these settings to mulsite option table as well.
		if ( is_network_admin() && is_multisite() ) {
			$idle_overrideby_multisite_setting = filter_input( INPUT_POST, 'idle_overrideby_multisite_setting', FILTER_SANITIZE_NUMBER_INT );

			$save_minutes = $idle_timeout * 60; // 60 minutes
			if ( $idle_timeout ) {

				$options = array(
					'__ina_overrideby_multisite_setting' => $idle_overrideby_multisite_setting,
					'__ina_logout_time'                  => $save_minutes,
					'__ina_logout_message'               => $idle_timeout_message,
					'__ina_disable_countdown'            => $idle_disable_countdown,
					'__ina_full_overlay'                 => $ina_full_overlay,
					'__ina_popup_overlay_color'          => $ina_background_popup,
				);

				update_site_option( '__ina_inactive_logout_options', $options );

			}
		}

		$save_minutes = $idle_timeout * 60; // 60 minutes
		if ( $idle_timeout ) {

			$options = array(
				'__ina_logout_time'         => $save_minutes,
				'__ina_logout_message'      => $idle_timeout_message,
				'__ina_disable_countdown'   => $idle_disable_countdown,
				'__ina_full_overlay'        => $ina_full_overlay,
				'__ina_popup_overlay_color' => $ina_background_popup,
			);

			update_option( '__ina_inactive_logout_options', $options );
		}

		do_action( 'ina_after_update_basic_settings' );
	}

	/**
	 * Settings wrapper html element.
	 */
	public function ina_before_settings_wrap() {
		echo '<div id="ina-cover-loading" style="display: none;"></div><div class="wrap">';
	}

	/**
	 * Settings wrapper html element.
	 */
	public function ina_after_settings_wrap() {
		echo '</div>';
	}
}
new Inactive_Logout_Admin_Views();
