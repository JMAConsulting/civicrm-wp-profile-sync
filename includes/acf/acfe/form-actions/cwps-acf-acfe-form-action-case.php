<?php
/**
 * "Case" ACFE Form Action Class.
 *
 * Handles the "Case" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "Case" ACFE Form Action Class.
 *
 * A class that handles the "Case" ACFE Form Action.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Case extends CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

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
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acfe;

	/**
	 * ACFE Form object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $form The ACFE Form object.
	 */
	public $form;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Form Action Name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $action_name The unique name of the Form Action.
	 */
	public $action_name = 'cwps_case';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = 'field_cwps_case_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = 'cwps_case_action_';

	/**
	 * Public Case Fields to add.
	 *
	 * These are not mapped for Past Type Sync, so need to be added.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_add The Public Case Fields to add.
	 */
	public $fields_to_add = [
		'details' => 'wysiwyg',
		'subject' => 'text',
	];

	/**
	 * Public Case Fields to ignore.
	 *
	 * These are mapped for Past Type Sync, but need special handling.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_ignore The Public Case Fields to ignore.
	 */
	public $fields_to_ignore = [
		'contact_id' => 'civicrm_contact',
		'creator_id' => 'civicrm_contact',
		//'manager_id' => 'civicrm_contact',
	];

	/**
	 * Case Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_ignore The Public Case Fields to ignore.
	 */
	public $contact_fields = [
		'contact_id' => 'civicrm_contact',
		'creator_id' => 'civicrm_contact',
		//'manager_id' => 'civicrm_contact',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acfe = $parent->acfe;
		$this->form = $parent;
		$this->civicrm = $this->acf_loader->civicrm;

		// Label this Form Action.
		$this->action_label = __( 'CiviCRM Case action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->alias_placeholder = __( 'CiviCRM Case', 'civicrm-wp-profile-sync' );

		// Register hooks.
		$this->register_hooks();

		// Init parent.
		parent::__construct();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}



	/**
	 * Configure this object.
	 *
	 * @since 0.5
	 */
	public function configure() {

		// Get the public Case Fields for all Case Types.
		$this->public_case_fields = $this->civicrm->case_field->get_public_fields( 'create' );

		/*
		// Prepend the ones that are needed in ACFE Forms (i.e. Subject and Details).
		foreach ( $this->fields_to_add as $name => $field_type ) {
			array_unshift( $this->public_case_fields, $this->civicrm->case_field->get_by_name( $name ) );
		}
		*/

		// Populate public mapping Fields.
		foreach ( $this->public_case_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Handle Contact Fields.
		foreach ( $this->contact_fields as $name => $field_type ) {

			// Populate mapping Fields.
			$field = $this->civicrm->case_field->get_by_name( $name, 'create' );

			$this->mapping_field_filters_add( $field['name'] );

			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( $this->field_name . 'ref_' . $field['name'] );

			// Also build array of data for CiviCRM Fields.
			$this->fields_for_contacts[] = $field;

			// Pre-load with "Generic" values.
			//$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $field['name'];
			//add_filter( $filter, [ $this, 'prepare_choices' ], 5 );

		}

		// Add "Manager ID" to Contact Fields.
		$field = [
			'name' => 'manager_id',
			'title' => __( 'Case Manager', 'civicrm-wp-profile-sync' ),
		];
		$this->mapping_field_filters_add( $field['name'] );
		$this->js_model_contact_reference_field_add( $this->field_name . 'ref_' . $field['name'] );
		$this->fields_for_contacts[] = $field;

		// Get the Custom Groups and Fields for all Case Types.
		$this->custom_fields = $this->plugin->civicrm->custom_group->get_for_cases();
		$this->custom_field_ids = [];

		// Populate mapping Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
					// Also build Custom Field IDs.
					$this->custom_field_ids[] = (int) $custom_field['id'];
				}
			}
		}

	}



	/**
	 * Pre-load mapping Fields with "Generic" choices.
	 *
	 * Not used but leaving this here for future use.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing array of Field data.
	 * @param array $field The modified array of Field data.
	 */
	public function prepare_choices( $field ) {

		// --<
		return $field;

	}



	/**
	 * Performs the action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function make( $form, $current_post_id, $action ) {

		// Bail a filter has overridden the action.
		if ( false === $this->make_skip( $form, $current_post_id, $action ) ) {
			return;
		}

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Populate Case, Email, Relationship and Custom Field data arrays.
		$case = $this->form_case_data( $form, $current_post_id, $action );
		$custom_fields = $this->form_custom_data( $form, $current_post_id, $action );

		// Save the Case with the data from the Form.
		$case = $this->form_case_save( $case, $custom_fields );

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $case );

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_append() {

		// Define Case Type Field.
		$case_types_field = [
			'key' => $this->field_key . 'case_types',
			'label' => __( 'Case Type', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'case_types',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field'
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->case_type->choices_get(),
		];

		// Define Case Status Field.
		$case_status_field = [
			'key' => $this->field_key . 'case_status_id',
			'label' => __( 'Case Status', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'case_status_id',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field'
			],
			'acfe_permissions' => '',
			'default_value' => $this->civicrm->option_value_default_get( 'case_status' ),
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->case_field->options_get( 'case_status_id' ),
		];

		// Define Case "Activity Medium" Field.
		$case_activity_medium_field = [
			'key' => $this->field_key . 'case_medium_id',
			'label' => __( 'Activity Medium', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'case_medium_id',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field'
			],
			'acfe_permissions' => '',
			'default_value' => $this->civicrm->option_value_default_get( 'encounter_medium' ),
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->case_field->options_get( 'case_medium_id' ),
		];

		// Define "Dismiss if exists" Field.
		$dismiss_if_exists_field = [
			'key' => $this->field_key . 'dismiss_if_exists',
			'label' => __( 'Create Case?', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'dismiss_if_exists',
			'type' => 'true_false',
			'instructions' => __( 'Skip creating a Case if the Contact already has a Case of this Type.', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field'
			],
			'acfe_permissions' => '',
			'message' => '',
			'default_value' => 0,
			'ui' => 1,
			'ui_on_text' => '',
			'ui_off_text' => '',
		];

		// Init Fields.
		$fields = [
			$case_types_field,
			$case_status_field,
			$case_activity_medium_field,
			$dismiss_if_exists_field,
		];

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_add() {

		// Get Tab Header.
		$mapping_tab_header = $this->tab_mapping_header();

		// Build Contacts Accordion.
		$mapping_contacts_accordion = $this->tab_mapping_accordion_contacts_add();

		// Build Case Details Accordion.
		$mapping_case_accordion = $this->tab_mapping_accordion_case_add();

		// Build Custom Fields Accordion.
		$mapping_custom_accordion = $this->tab_mapping_accordion_custom_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_contacts_accordion,
			$mapping_case_accordion,
			$mapping_custom_accordion
		);

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Contacts" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_contacts_add() {

		// Init return.
		$fields = [];

		// "Case" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_open',
			'label' => __( 'Contact References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add Contact Reference Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key' => $this->field_key . 'contact_group_' . $field['name'],
				'label' => $field['title'],
				'name' => $this->field_name . 'contact_group_' . $field['name'],
				'type' => 'group',
				'instructions' => sprintf( __( 'Use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $field['title'] ),
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'required' => 0,
				'layout' => 'block',
			];

			// Define Contact Action Reference Field.
			$contact_group_field['sub_fields'][] = [
				'key' => $this->field_key . 'ref_' . $field['name'],
				'label' => __( 'CiviCRM Contact Action', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'ref_' . $field['name'],
				'type' => 'cwps_acfe_contact_action_ref',
				'instructions' => __( 'Select a Contact Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'cid_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Contact ID Field.
			$cid_field = [
				'key' => $this->field_key . 'cid_' . $field['name'],
				'label' => __( 'CiviCRM Contact ID', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'cid_' . $field['name'],
				'type' => 'civicrm_contact',
				'instructions' => __( 'Select a CiviCRM Contact ID from the database.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'ref_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Contact ID Field.
			$contact_group_field['sub_fields'][] = $cid_field;

			// Define Custom Contact Reference Field.
			$title = sprintf( __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' ), $field['title'] );
			$mapping_field = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions'] = __( 'Define a custom Contact Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field' => $this->field_key . 'ref_' . $field['name'],
						'operator' => '==empty',
					],
					[
						'field' => $this->field_key . 'cid_' . $field['name'],
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Contact Reference Field.
			$contact_group_field['sub_fields'][] = $mapping_field;

			// Add Contact Reference Group.
			$fields[] = $contact_group_field;

		}

		// "Case" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_close',
			'label' => __( 'Case Contacts', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Case Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_case_add() {

		// Init return.
		$fields = [];

		// "Case" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_case_open',
			'label' => __( 'Case Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->public_case_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Case" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_case_close',
			'label' => __( 'Case Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Custom Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_custom_add() {

		// Init return.
		$fields = [];

		// "Custom Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_custom_open',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Get top-level Case Types.
		$case_types = $this->civicrm->case_type->choices_get();

		// Add "Mapping" Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Skip if there are no Custom Fields.
			if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
				continue;
			}

			// Get the Case Type ID.
			$case_type_ids = [];
			if ( ! empty( $custom_group['extends_entity_column_value'] ) ) {
				$case_type_ids = $custom_group['extends_entity_column_value'];
			}

			// Init conditional logic.
			$conditional_logic = [];

			// Add Sub-types as OR conditionals if present.
			if ( ! empty( $case_type_ids ) ) {
				foreach ( $case_type_ids as $case_type_id ) {

					$case_type = [
						'field' => $this->field_key . 'case_types',
						'operator' => '==contains',
						'value' => $case_type_id,
					];

					$conditional_logic[] = [
						$case_type,
					];

				}
			}

			// Bundle the Custom Fields into a container group.
			$custom_group_field = [
				'key' => $this->field_key . 'custom_group_' . $custom_group['id'],
				'label' => $custom_group['title'],
				'name' => $this->field_name . 'custom_group_' . $custom_group['id'],
				'type' => 'group',
				'instructions' => '',
				'instruction_placement' => 'field',
				'required' => 0,
				'layout' => 'block',
				'conditional_logic' => $conditional_logic,
			];

			// Init sub Fields array.
			$sub_fields = [];

			// Add "Map" Fields for the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$sub_fields[] = $this->mapping_field_get( $code, $custom_field['label'], $conditional_logic );
			}

			// Add the Sub-fields.
			$custom_group_field['sub_fields'] = $sub_fields;

			// Add the Sub-fields.
			$fields[] = $custom_group_field;

		}

		// "Custom" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_custom_close',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Case data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Case data.
	 */
	public function form_case_data( $form, $current_post_id, $action ) {

		// Build Fields array.
		$fields = [];
		foreach ( $this->public_case_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[ $field['name'] ] = get_sub_field( $this->field_key . 'map_' . $field['name'] );
			}
		}

		// Populate data array with values of mapped Fields.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get the "Dismiss if exists" Field.
		$data['dismiss_if_exists'] = get_sub_field( $this->field_key . 'dismiss_if_exists' );

		// Get the Case Type.
		$data['case_type_id'] = get_sub_field( $this->field_key . 'case_types' );

		// Only set "Case Status" and "Encounter Medium" when there is no mapped Field.
		if ( empty( $data['status_id'] ) ) {
			$data['status_id'] = get_sub_field( $this->field_key . 'case_status_id' );
		}
		if ( empty( $data['medium_id'] ) ) {
			$data['medium_id'] = get_sub_field( $this->field_key . 'case_medium_id' );
		}

		// Get the Case Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = get_sub_field( $this->field_key . 'contact_group_' . $field['name'] );

			// Check Action Reference Field.
			$contact_id = false;
			if ( ! empty( $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ] ) ) {
				$action_name = $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ];
				$contact_id = $this->form_contact_id_get_mapped( $action_name );
			}

			// Check Contact ID Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'cid_' . $field['name'] ] ) ) {
					$contact_id = $contact_group_field[ $this->field_name . 'cid_' . $field['name'] ];
				}
			}

			// Check mapped Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'map_' . $field['name'] ] ) ) {
					$reference = [ $field['name'] => $contact_group_field[ $this->field_name . 'map_' . $field['name'] ] ];
					$reference = acfe_form_map_vs_fields( $reference, $reference, $current_post_id, $form );
					if ( ! empty( $reference[ $field['name'] ] ) && is_numeric( $reference[ $field['name'] ] ) ) {
						$contact_id = $reference[ $field['name'] ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
				$data[ $field['name'] ] = $contact_id;
			}

		}

		// --<
		return $data;

	}



	/**
	 * Saves the CiviCRM Case given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $case_data The array of Case data.
	 * @param array $custom_data The array of Custom Field data.
	 * @return array|bool $case The Case data array, or false on failure.
	 */
	public function form_case_save( $case_data, $custom_data ) {

		// Init return.
		$case = false;

		// When skipping, get the existing Case and return early if it exists.
		if ( ! empty( $case_data['dismiss_if_exists'] ) ) {
			$case = $this->civicrm->case->get_by_type_and_contact( $case_data['case_type_id'], $case_data['contact_id'] );
			if ( $case !== false ) {
				return $case;
			}
		}

		// Add Custom Field data if present.
		if ( ! empty ( $custom_data ) ) {
			$case_data += $custom_data;
		}

		// Strip out empty Fields.
		$case_data = $this->form_data_prepare( $case_data );

		// If a Case ID is set.
		if ( ! empty( $case_data['id'] ) && is_numeric( $case_data['id'] ) ) {

			// Build params to create a "Case Contact".
			$params = [
				'case_id' => $case_data['id'],
				'contact_id' => $case_data['contact_id'],
			];

			// Add the Case Contact.
			$result = $this->civicrm->case->contact_create( $params );

			// Bail on failure.
			if ( $result === false ) {
				return $case;
			}

			// Get the Case.
			$case = $this->civicrm->case->get_by_id( $case_data['id'] );

			// Bail early regardless.
			return $case;

		}

		// Okay, let's create the Case.
		$result = $this->civicrm->case->create( $case_data );

		// Bail on failure.
		if ( $result === false ) {
			return $case;
		}

		// Get the full Case data.
		$case = $this->civicrm->case->get_by_id( $result['id'] );

		// Assign the Case Manager if defined.
		$this->civicrm->case->manager_add( $case_data, $case );

		// --<
		return $case;

	}



	/**
	 * Finds the linked Contact ID when it has been mapped.
	 *
	 * @since 0.5
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get_mapped( $action_name ) {

		// Init return.
		$contact_id = false;

		// Get the Contact data for that Action.
		$related_contact = acfe_form_get_action( $action_name, 'contact' );
		if ( empty( $related_contact['id'] ) ) {
			return $contact_id;
		}

		// Assign return.
		$contact_id = (int) $related_contact['id'];

		// --<
		return $contact_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Custom Field data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_custom_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Build data array.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Fresh Fields array.
			$fields = [];

			// Get Group Field.
			$custom_group_field = get_sub_field( $this->field_key . 'custom_group_' . $custom_group['id'] );
			foreach ( $custom_group_field as $field ) {

				// Get mapped Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$code = 'custom_' . $custom_field['id'];
					$fields[ $code ] = $custom_group_field[ $this->field_name . 'map_' . $code ];
				}

			}

			// Populate data array with values of mapped Fields.
			$data += acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		}

		// --<
		return $data;

	}



} // Class ends.



