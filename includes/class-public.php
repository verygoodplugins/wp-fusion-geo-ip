<?php

class WPF_Geo_IP_Public {

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	*/

	public function __construct() {

		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2);
		add_action( 'wp_login', array( $this, 'login' ), 10, 2 );

		// Buddypress integration
		add_action( 'wpf_loaded_geo_data', array( $this, 'set_buddypress_values' ), 10, 2 );

		add_shortcode( 'load_geo_data_link', array( $this, 'load_geo_data_link' ) );
		add_action( 'init', array( $this, 'maybe_load_geo_data' ) );

	}

	/**
	 * Loads geo data and saves it to user meta
	 *
	 * @since 1.0
	 * @return array / WP Error
	*/

	public static function load_geo_data( $user_id ) {

		$key = wp_fusion()->settings->get( 'geo_ip_api_key' );

		if( empty( $key ) ) {
			return new WP_Error( 'error', 'No API key specified' );
		}

		$url = 'https://api.ipgeolocation.io/ipgeo?apiKey=' . $key . '&ip=' . $_SERVER['REMOTE_ADDR'];

		$response = wp_remote_get( $url );

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if( isset( $response->message ) ) {
			return new WP_Error( 'error', $response->message );
		}

		$update_data = array(
			'geo_city'		=> $response->city,
			'geo_state'		=> $response->state_prov,
			'geo_zip'		=> $response->zipcode,
			'geo_timezone'	=> $response->time_zone->name,
			'geo_country'	=> $response->country_name
		);

		foreach( $update_data as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		do_action( 'wpf_loaded_geo_data', $user_id, $update_data );

		return $update_data;

	}

	/**
	 * Sync geo data when users register
	 *
	 * @since 1.0
	 * @return array User Meta
	*/

	public function user_register( $user_meta, $user_id ) {

		$geo_data = $this->load_geo_data( $user_id );

		if( is_wp_error( $geo_data ) ) {

			wp_fusion()->logger->handle( $geo_data->get_error_code(), $user_id, 'Error getting Geo IP data: ' . $geo_data->get_error_message(), array( 'source' => 'wpf-geo-ip' ) );
			return $user_meta;

		}

		$user_meta = array_merge( $user_meta, $geo_data );

		return $user_meta;

	}

	/**
	 * Update tags on login
	 *
	 * @access public
	 * @return void
	 */

	public function login( $user_login, $user ) {

		if( wp_fusion()->settings->get( 'geo_ip_login' ) == true ) {

			$geo_data = $this->load_geo_data( $user->ID );

			if( ! is_wp_error( $geo_data ) ) {

				wp_fusion()->user->push_user_meta( $user->ID, $geo_data );

			}

		}

	}


	/**
	 * Set BuddyPress field values if enabled
	 *
	 * @since 1.0
	 * @return void
	*/

	public static function set_buddypress_values( $user_id, $geo_data ) {

		if ( ! class_exists( 'BP_XProfile_ProfileData' ) ) {
			return;
		}

		foreach ( $geo_data as $key => $value ) {

			$key = str_replace( 'geo_', '', $key );

			$setting = wp_fusion()->settings->get( 'geo_ip_bp_' . $key, false );

			if( ! empty( $setting ) ) {

				$field        = new BP_XProfile_ProfileData( $setting, $user_id );
				$field->value = $value;
				$field->save();

			}

		}

	}

	/**
	 * Shortcode for loading geo data for current user
	 *
	 * @since 1.0
	 * @return mixed Shortcode output
	*/

	public static function load_geo_data_link( $user_id ) {

		return '<a href="?reload_geo_data=true" class="reload-geo-data-link">Reload Geo Data</a>';

	}

	/**
	 * Maybe load geo data if query var is present
	 *
	 * @since 1.0
	 * @return void
	*/

	public static function maybe_load_geo_data() {

		if( isset( $_GET['reload_geo_data'] ) ) {

			$geo_data = $this->load_geo_data( get_current_user_id() );

			if( ! is_wp_error( $geo_data ) ) {

				wp_fusion()->user->push_user_meta( get_current_user_id(), $geo_data );

			}

		}

	}


}

new WPF_Geo_IP_Public;