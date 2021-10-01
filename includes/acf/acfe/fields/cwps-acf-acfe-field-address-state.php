<?php
/**
 * ACFE "CiviCRM State Field" Class.
 *
 * Provides a "CiviCRM State Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM State Field.
 *
 * A class that encapsulates a "CiviCRM State Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_State extends acf_field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The Advanced Custom Fields object.
	 */
	public $acf;

	/**
	 * ACF Extended object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The Advanced Custom Fields object.
	 */
	public $acfe;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $name The Field Type name.
	 */
	public $name = 'cwps_acfe_address_state';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $label The Field Type label.
	 */
	public $label = '';

	/**
	 * Field Type category.
	 *
	 * Choose between the following categories:
	 *
	 * basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
	 *
	 * @since 0.5
	 * @access public
	 * @var string $label The Field Type category.
	 */
	public $category = 'CiviCRM';

	/**
	 * Field Type defaults.
	 *
	 * Array of default settings which are merged into the Field object.
	 * These are used later in settings.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $defaults The Field Type defaults.
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $settings The Field Type settings.
	 */
	public $settings = [
		'version' => CIVICRM_WP_PROFILE_SYNC_VERSION,
		'url' => CIVICRM_WP_PROFILE_SYNC_URL,
		'path' => CIVICRM_WP_PROFILE_SYNC_PATH,
	];

	/**
	 * Field Type translations.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Array of strings that are used in JavaScript. This allows JS strings
	 * to be translated in PHP and loaded via:
	 *
	 * var message = acf._e( 'civicrm_contact', 'error' );
	 *
	 * @since 0.5
	 * @access public
	 * @var array $l10n The Field Type translations.
	 */
	public $l10n = [];



	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf = $parent->acf_loader->acf;
		$this->acfe = $parent;
		$this->civicrm = $this->acf_loader->civicrm;

		// Define label.
		$this->label = __( 'CiviCRM State', 'civicrm-wp-profile-sync' );

		// Define category.
		$this->category = __( 'CiviCRM ACFE Forms', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [];

		// Call parent.
		parent::__construct();

		// Define AJAX callbacks.
		add_action( 'wp_ajax_cwps_get_country_field', [ $this, 'ajax_query' ] );

	}



	/**
	 * Create extra Settings for this Field Type.
	 *
	 * These extra Settings will be visible when editing a Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being edited.
	 */
	public function render_field_settings( $field ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Define "Source Country" setting Field.
		$country_source = [
			'label' => __( 'Source Country', 'civicrm-wp-profile-sync' ),
			'name' => 'country_source',
			'type' => 'radio',
			'instructions' => __( 'The source for the States/Provinces in this Field.', 'civicrm-wp-profile-sync' ),
			'allow_null' => 0,
			'required' => 0,
			'default_value' => 1,
			'layout' => 'vertical',
			'return_format' => 'value',
			'choices' => [
				1 => __( 'The default Country in CiviCRM', 'civicrm-wp-profile-sync' ),
				2 => __( 'A CiviCRM Country Field', 'civicrm-wp-profile-sync' ),
				3 => __( 'A specific Country', 'civicrm-wp-profile-sync' ),
			],
		];

		// Now add it.
		acf_render_field_setting( $field, $country_source );

		// Define "Country Field Reference" setting Field.
		$country_ref = [
			'label' => __( 'Country Field', 'civicrm-wp-profile-sync' ),
			'name' => 'state_country',
			'type' => 'select',
			'instructions' => __( 'Filter the visible States/Provinces by the selected Country Field.', 'civicrm-wp-profile-sync' ),
			'allow_null' => 1,
			'ui' => 1,
			'ajax' => 1,
            'ajax_action' => 'cwps_get_country_field',
            'placeholder' => __( 'Select the Country Field', 'civicrm-wp-profile-sync' ),
			'default_value' => 0,
			'required' => 0,
			'conditional_logic' => [ [ [
				'field' => 'country_source',
				'operator' => '==contains',
				'value' => 2,
			] ] ],
		];

		// Add existing choice if present.
		if ( ! empty( $field['state_country'] ) ) {
			$country_field = acf_get_field( $field['state_country'] );
			if( ! empty( $country_field ) ) {
				$label = acf_maybe_get( $country_field, 'label', $country_field['name'] );
				$country_ref['choices'] = [ $field['state_country'] => "{$label} ({$country_field['key']})" ];
			}
		}

		// Now add it.
		acf_render_field_setting( $field, $country_ref );

		// Define "Country ID" setting Field.
		$country_id = [
			'label' => __( 'Country', 'civicrm-wp-profile-sync' ),
			'name' => 'country_id',
			'type' => 'select',
			'instructions' => __( 'Use the States/Provinces in this Country.', 'civicrm-wp-profile-sync' ),
			'allow_null' => 1,
			'ui' => 1,
			'ajax' => 0,
            'placeholder' => __( 'Select the Country', 'civicrm-wp-profile-sync' ),
			'default_value' => 0,
			'required' => 0,
			'choices' => CRM_Core_PseudoConstant::country(),
			'conditional_logic' => [ [ [
				'field' => 'country_source',
				'operator' => '==contains',
				'value' => 3,
			] ] ],
		];

		// Now add it.
		acf_render_field_setting( $field, $country_id );

	}



	/**
	 * AJAX Query callback.
	 *
	 * @since 0.5
	 */
	public function ajax_query() {

		// Validate.
		if ( ! acf_verify_ajax() ) {
			die();
		}

		// Get response.
		$response = $this->ajax_get_response( $_POST );

		// Send results.
		acf_send_ajax_results( $response );

	}



	/**
	 * AJAX Query callback.
	 *
	 * @since 0.5
	 *
	 * @param array $options The options that define the query.
	 * @return array $response The query results.
	 */
	public function ajax_get_response( $options = [] ) {

		// Init response.
		$response = [
			'results' => [],
			'limit' => 25,
		];

		// Init defaults.
		$defaults = [
			'post_id' => 0,
			's' => '',
			'field_key' => '',
			'paged' => 1,
		];

   		// Parse the incoming POST array.
   		$options = acf_parse_args( $options, $defaults );

		// Bail if there's no search string.
		if ( empty( $options['s'] ) ) {
			return $response;
		}

		// Grab the Post ID.
		$post_id = absint( $options['post_id'] );

		// Strip slashes - search may be an integer.
		$search = wp_unslash( (string) $options['s'] );

		// Get the Fields in this Field Group.
		$field_group = acf_get_field_group( $post_id );
		$fields_in_group = acf_get_fields( $field_group );

		// Get the Fields as choices for the select.
		$choices = $this->ajax_get_country_fields( [], $fields_in_group, $field_group );

		// Format and filter the choices for returning.
		$formatted = [];
		foreach ( $choices as $title => $fields ) {
			$title = (string) $title;
			$data = [];
			foreach ( $fields as $key => $label ) {
				$label = (string) $label;
				if ( ! empty( $search ) ) {
					if (
						stripos( strtolower( $label ), $search ) !== false
						OR
						stripos( strtolower( $title ), $search ) !== false
					) {
						$data[] = [ 'id' => $key, 'text' => $label ];
					}
				}
			}
			if ( ! empty( $data ) ) {
				$formatted[] = [ 'text' => $title, 'children' => $data ];
			}
		}

		// Add to the response.
		$response['results'] = $formatted;

  		// --<
		return $response;

	}



	/**
	 * Get the State Fields for the AJAX Query.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The choices for the select.
	 * @param array $fields The array of ACF Fields.
	 * @param array $container The container of ACF Field, e.g. Group or Clone.
	 * @return array $choices The choices for the select.
	 */
	public function ajax_get_country_fields( $choices, $fields, $container = [] ) {

		// Sanity check.
		if ( empty( $fields ) ) {
			return $choices;
		}

		// Look at each Field in turn.
		foreach ( $fields as $field ) {

			// Recurse when there are Sub-fields, e.g. when looking at Groups and Clones.
			if ( acf_maybe_get( $field, 'sub_fields' ) ) {
				$choices = $this->ajax_get_country_fields( $choices, $field['sub_fields'], $field );
				continue;
			}

			// Filter all but Fields of type "CiviCRM Country".
			if ( $field['type'] !== 'cwps_acfe_address_country' ) {
				continue;
			}

			// Add to choices.
			$label = acf_maybe_get( $field, 'label', $field['name'] );
			$title = acf_maybe_get( $container, 'label', $container['name'] );
			if ( empty( $title ) ) {
				$title = __( 'Top Level', 'civicrm-wp-profile-sync' );
			} else {
				$title = sprintf( __( 'Inside: %s', 'civicrm-wp-profile-sync' ), $title );
			}
			$choices[ $title ][ $field['key'] ] = "{$label} ({$field['key']})";

		}

		// --<
		return $choices;

	}



	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a select Field.
		$field['type'] = 'select';

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		// Given precedence to the saved value.
		if ( ! empty( $field['value'] ) ) {

			// Add existing choice if present.
			$state = CRM_Core_PseudoConstant::stateProvince( $field['value'] );
			if( ! empty( $state ) ) {

				// Try and get the Country ID.
				$country_id = CRM_Core_PseudoConstant::countryIDForStateID( $field['value'] );
				if ( ! empty( $country_id ) ) {
					$field['choices'] = CRM_Core_PseudoConstant::stateProvinceForCountry( $country_id );
				}

			}

		} elseif ( ! empty( $field['country_id'] ) ) {

			// Add choices from specific Country ID if present.
			$field['choices'] = CRM_Core_PseudoConstant::stateProvinceForCountry( $field['country_id'] );

		} else {

			// Add choices from the default Country.
			$country_id = $config->defaultContactCountry;
			if ( ! empty( $config->defaultContactCountry ) ) {
				$field['choices'] = CRM_Core_PseudoConstant::stateProvinceForCountry( $country_id );
				// Also try and set the default value.
				if ( ! empty( $config->defaultContactStateProvince ) ) {
					$field['default_value'] = $config->defaultContactStateProvince;
					$field['value'] = $config->defaultContactStateProvince;
				}
			}

		}

		// Render.
		acf_render_field( $field );

	}



	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer|string $post_id The ACF "Post ID" from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	public function load_value( $value, $post_id, $field ) {

		// Assign State for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_state( $value, $post_id, $field );
		}

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is applied to the $value before it is saved in the database.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	public function update_value( $value, $post_id, $field ) {

		// Assign State for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_state( $value, $post_id, $field );
		}

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is applied to the value after it is loaded from the database
	 * and before it is returned to the template.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The value which was loaded from the database.
	 * @param mixed $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	public function format_value( $value, $post_id, $field ) {

		// Bail early if no value.
		if ( empty( $value ) ) {
			return $value;
		}

		// Apply setting.
		if ( $field['font_size'] > 12 ) {

			// format the value
			// $value = 'something';

		}

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is used to perform validation on the value prior to saving.
	 *
	 * All values are validated regardless of the Field's required setting.
	 * This allows you to validate and return messages to the user if the value
	 * is not correct.
	 *
	 * @since 0.5
	 *
	 * @param bool $valid The validation status based on the value and the Field's required setting.
	 * @param mixed $value The $_POST value.
	 * @param array $field The Field array holding all the Field options.
	 * @param string $input The corresponding input name for $_POST value.
	 * @return string|bool $valid False if not valid, or string for error message.
	public function validate_value( $valid, $value, $field, $input ) {

		// Basic usage.
		if ( $value < $field['custom_minimum_setting'] ) {
			$valid = false;
		}

		// Advanced usage.
		if ( $value < $field['custom_minimum_setting'] ) {
			$valid = __( 'The value is too little!', 'civicrm-wp-profile-sync' ),
		}

		// --<
		return $valid;

	}
	 */



	/**
	 * This action is fired after a value has been deleted from the database.
	 *
	 * Please note that saving a blank value is treated as an update, not a delete.
	 *
	 * @since 0.5
	 *
	 * @param integer $post_id The Post ID from which the value was deleted.
	 * @param string $key The meta key which the value was deleted.
	public function delete_value( $post_id, $key ) {

	}
	 */



	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function load_field( $field ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		$field['allow_null'] = 1;
		$field['multiple'] = 0;
		$field['ui'] = 1;
		$field['ajax'] = 0;
		$field['choices'] = [];
		$field['default_value'] = 0;

		// If there's a Country Field.
		if ( ! empty( $field['state_country'] ) ) {
			$field['wrapper']['class'] = 'cwps-country-' . $field['state_country'];
		} else {
			$field['wrapper']['class'] = 'cwps-country-none';
		}

		// --<
		return $field;

	}



	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	public function update_field( $field ) {

		// --<
		return $field;

	}
	 */



	/**
	 * This action is fired after a Field is deleted from the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	public function delete_field( $field ) {

	}
	 */



	/**
	 * This method is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.5
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/acfe/fields/civicrm-address-state-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION // Version.
		);

		// Get the States keyed by Country ID.
		$states = $this->plugin->civicrm->address->states_get_for_countries();

		// Build data array.
		$vars = [
			'settings' => [
				'states' => $states,
			],
		];

		// Localize our script.
		wp_localize_script(
			'acf-input-' . $this->name,
			'CWPS_ACFE_State_Vars',
			$vars
		);

	}



} // Class ends.



