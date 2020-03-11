<?php

class WPF_Geo_IP_Admin {

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	*/

	public function __construct() {

		add_filter( 'wpf_configure_sections', array($this, 'configure_sections'), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// Meta fields
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 20 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 10 );

	}



	/**
	 * Adds Addons tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections($page, $options) {

		if(!isset($page['sections']['addons']))
			$page['sections'] = wp_fusion()->settings->insert_setting_before('import', $page['sections'], array('addons' => __('Addons', 'wp-fusion' )));

		return $page;

	}

	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['geo_ip_header'] = array(
				'title'   => __('Geo IP', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'heading',
				'section' => 'addons'
		);

		$settings['geo_ip_api_key'] = array(
				'title'   => __('API Key', 'wp-fusion' ),
				'desc'    => 'Enter your API key from <a href="https://ipgeolocation.io/">ipgeolocation.io</a>.',
				'std'     => false,
				'type'    => 'text',
				'section' => 'addons'
		);

		$settings['geo_ip_login'] = array(
				'title'   => __('Login Sync', 'wp-fusion' ),
				'desc'    => __('Sync updated data each time a user logs in.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'addons',
		);

		if ( class_exists( 'BP_XProfile_ProfileData' ) ) {

			$options = array();

			$r = array(
				'user_id'          => get_current_user_id(),
				'member_type'      => 'any',
				'fetch_fields'     => true,
				'fetch_field_data' => true,
			);

			$profile_template = new BP_XProfile_Data_Template( $r );

			foreach ( $profile_template->groups as $group ) {

				if ( ! empty( $group->fields ) ) {

					foreach ( $group->fields as $field ) {
						$options[ $field->id ] = $field->name;
					}

				}

			}

			$settings['geo_ip_bp_city'] = array(
					'title'   => __('City', 'wp-fusion' ),
					'std'     => false,
					'type'    => 'select',
					'placeholder' => 'Select a BuddyPress field',
					'section' => 'addons',
					'choices' => $options
			);

			$settings['geo_ip_bp_state'] = array(
					'title'   => __('State', 'wp-fusion' ),
					'std'     => false,
					'type'    => 'select',
					'placeholder' => 'Select a BuddyPress field',
					'section' => 'addons',
					'choices' => $options
			);

			$settings['geo_ip_bp_zip'] = array(
					'title'   => __('Postal Code', 'wp-fusion' ),
					'std'     => false,
					'type'    => 'select',
					'placeholder' => 'Select a BuddyPress field',
					'section' => 'addons',
					'choices' => $options
			);

			$settings['geo_ip_bp_country'] = array(
					'title'   => __('Country', 'wp-fusion' ),
					'std'     => false,
					'type'    => 'select',
					'placeholder' => 'Select a BuddyPress field',
					'section' => 'addons',
					'choices' => $options
			);

			$settings['geo_ip_bp_timezone'] = array(
					'title'   => __('Time Zone', 'wp-fusion' ),
					'std'     => false,
					'type'    => 'select',
					'placeholder' => 'Select a BuddyPress field',
					'section' => 'addons',
					'choices' => $options
			);

		}

		return $settings;

	}


	/**
	 * Adds Geo IP field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if( !isset( $field_groups['geoip'] ) ) {
			$field_groups['geoip'] = array( 'title' => 'Geo IP', 'fields' => array() );
		}

		return $field_groups;

	}

	/**
	 * Sets field labels and types for Custom Geo IP fields
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['geo_city']   		= array( 'label' => 'City', 'type' => 'text', 'group' => 'geoip' );
		$meta_fields['geo_state']   	= array( 'label' => 'State', 'type' => 'text', 'group' => 'geoip' );
		$meta_fields['geo_zip']   		= array( 'label' => 'Postal Code', 'type' => 'text', 'group' => 'geoip' );
		$meta_fields['geo_country'] 	= array( 'label' => 'Country', 'type' => 'text', 'group' => 'geoip' );
		$meta_fields['geo_timezone'] 	= array( 'label' => 'Time Zone', 'type' => 'text', 'group' => 'geoip' );

		return $meta_fields;

	}

}

new WPF_Geo_IP_Admin;