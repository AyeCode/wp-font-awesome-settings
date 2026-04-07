<?php
/**
 * Font Awesome Settings Framework Class
 *
 * Extends AyeCode Settings Framework to provide UI for Font Awesome configuration.
 *
 * @package WP_Font_Awesome_Settings
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class extending AyeCode Settings Framework.
 */
class WP_Font_Awesome_Settings_Framework extends \AyeCode\SettingsFramework\Settings_Framework {

	/**
	 * Framework configuration properties.
	 */
	protected $option_name   = 'wp-font-awesome-settings';
	protected $page_slug     = 'wp-font-awesome-settings';
	protected $plugin_name   = '<i class="fa-regular fa-font-awesome me-2 text-primary-emphasis fs-4 mb-1"></i> Font Awesome';
	protected $menu_title    = 'Font Awesome';
	protected $page_title    = 'Settings';
	protected $menu_icon     = 'dashicons-admin-generic';
	protected $menu_position = null;
//    protected $parent_menu_slug = 'options-general.php';
    protected $parent_slug = 'options-general.php';

	/**
	 * Reference to main WP_Font_Awesome_Settings instance.
	 *
	 * @var WP_Font_Awesome_Settings
	 */
	private $main_instance;

	/**
	 * Constructor.
	 *
	 * @param WP_Font_Awesome_Settings $main_instance Main plugin instance.
	 */
	public function __construct( $main_instance ) {
		$this->main_instance = $main_instance;


        parent::__construct();


        add_action( 'asf_execute_tool_' . $this->page_slug, [ $this, 'ajax_actions' ], 10, 2 );

    }

	/**
	 * Saves settings to the database, capturing old values for comparison.
	 *
	 * Overrides parent method to check for version/pro/api_key changes before saving.
	 *
	 * @param array $new_settings The raw settings data from the AJAX request.
	 * @return bool True on success, false on failure.
	 */
	public function save_settings( $new_settings ) {
		// Capture current settings BEFORE saving.
		$old_settings = $this->get_settings();

		// Call parent to handle sanitization and saving.
		$result = parent::save_settings( $new_settings );

		// If save was successful, check if we need to regenerate icon libraries.
		if ( $result ) {
			$new_settings_saved = $this->get_settings();
			$this->maybe_regenerate_icon_libraries( $old_settings, $new_settings_saved );
		}

		return $result;
	}

	/**
	 * Check if icon libraries need regeneration and trigger if needed.
	 *
	 * @param array $old_settings Settings before save.
	 * @param array $new_settings Settings after save.
	 */
	private function maybe_regenerate_icon_libraries( $old_settings, $new_settings ) {

		// Extract relevant settings.
		$old_version = isset( $old_settings['version'] ) ? $old_settings['version'] : '';
		$new_version = isset( $new_settings['version'] ) ? $new_settings['version'] : '';
		$old_pro     = isset( $old_settings['pro'] ) ? $old_settings['pro'] : false;
		$new_pro     = isset( $new_settings['pro'] ) ? $new_settings['pro'] : false;
		$old_api_key = isset( $old_settings['api_key'] ) ? $old_settings['api_key'] : '';
		$new_api_key = isset( $new_settings['api_key'] ) ? $new_settings['api_key'] : '';

		// Check if any relevant setting changed.
		$version_changed = $old_version !== $new_version;
		$pro_changed     = $old_pro !== $new_pro;
		$api_key_changed = $old_api_key !== $new_api_key;

		if ( ! $version_changed && ! $pro_changed && ! $api_key_changed ) {
			return; // No relevant changes.
		}

		// Generate icon libraries.
		$generator = \AyeCode\FontAwesome\Icon_Library_Generator::instance();
		$result    = $generator->generate_icon_libraries( $new_settings );

		// Handle result.
		if ( is_wp_error( $result ) ) {
			// Store error to display as admin notice.
			set_transient( 'fa_icon_gen_error', $result->get_error_message(), 60 );
		} else {
			// $result is an array of generated styles, e.g., ['solid', 'brands', 'regular']
			// Clean up old style files that are no longer needed.
			$old_icon_styles = isset( $old_settings['local_icon_styles'] ) ? $old_settings['local_icon_styles'] : array();
			// Decode JSON string if needed.
			if ( is_string( $old_icon_styles ) && ! empty( $old_icon_styles ) ) {
				$old_icon_styles = json_decode( $old_icon_styles, true );
				if ( ! is_array( $old_icon_styles ) ) {
					$old_icon_styles = array();
				}
			}

			if ( ! empty( $old_icon_styles ) ) {
				$cleanup_result = $generator->cleanup_old_styles( $old_icon_styles, $result );
				if ( is_wp_error( $cleanup_result ) ) {
					// Log cleanup error but don't fail the entire operation.
					error_log( 'Font Awesome cleanup error: ' . $cleanup_result->get_error_message() );
				}
			}

			// Update local_icon_version and local_icon_styles settings.
			$current_settings                       = get_option( $this->option_name, array() );
			$current_settings['local_icon_version'] = $new_version;
			$current_settings['local_icon_styles']  = is_array( $result ) ? wp_json_encode( $result ) : $result;
			update_option( $this->option_name, $current_settings );

			// Success - no need to display a notice, errors will be shown via toast.
		}
	}

	/**
	 * Get the settings configuration
	 *
	 * @return array Configuration array with sections and fields
	 */
	public function get_config() {
		$latest_version = $this->main_instance->get_latest_version( false, true );
//print_r(ayecode_get_custom_icons( false) );exit;
		return [
			'sections' => [
				// General Settings Section
				[
					'id'    => 'general',
					'name'  => __( 'General Settings', 'ayecode-connect' ),
					'icon'  => 'fa-solid fa-gear',
					'fields' => [
                        [
                            'id'      => 'pro_link',
                            'type'    => 'alert',
                            'alert_type' => 'primary',
                            'description' => '<div class="d-flex align-items-center justify-content-between fs-6">' . wp_sprintf(
                                __( '%sGet Font Awesome Pro - unlock 30,000+ premium icons right inside your icon picker%s %s(affiliate link)%s %sGet Pro%s', 'ayecode-connect' ),
                                    '<strong><i class="me-2 fa-solid fa-icons"></i>',
                                    '</strong>',
                                    '<span><span class="fs-xs text-secondary">',
                                    '</span>',
                                    '<a class="btn btn-sm btn-primary" target="_blank" href="https://fontawesome.com/referral?a=c9b89e1418">',
                                    '<i class="ms-2 fas fa-external-link-alt"></i></a></span>',
                            ) . '</div>',
                            'show_if' => '[%pro%]==""',
                        ],
						[
							'id'      => 'type',
							'type'    => 'select',
							'label'   => __( 'Loading Method', 'ayecode-connect' ),
							'description'    => __( 'Choose how to load Font Awesome.', 'ayecode-connect' ),
							'options' => [
								'CSS' => __( 'CSS (default)', 'ayecode-connect' ),
								'JS'  => 'JS',
								'KIT' => __( 'Kits (settings managed on fontawesome.com)', 'ayecode-connect' ),
								'SVG' => __( 'SVG - Inline Icons (No CSS/JS loaded on frontend)', 'ayecode-connect' ),
							],
							'default' => 'CSS',
						],
						[
							'id'      => 'svg_info',
							'type'    => 'alert',
							'alert_type' => 'info',
							'description' => __( '<strong>SVG Mode:</strong> Font Awesome CSS/JS files are NOT loaded on the frontend. Frontend icons must be rendered using <code>ayecode_get_icon()</code>. The backend automatically loads CSS for compatibility, so normal <code>&lt;i&gt;</code> tags work in admin areas.', 'ayecode-connect' ),
							'show_if' => '[%type%]=="SVG"',
						],
						[
							'id'          => 'kit-url',
							'type'        => 'text',
							'label'       => __( 'Kit URL', 'ayecode-connect' ),
							'description'        => wp_sprintf(
								__( 'Requires a free account with Font Awesome. %sGet kit url%s', 'ayecode-connect' ),
								'<a rel="noopener noreferrer" target="_blank" href="https://fontawesome.com/kits"><i class="fas fa-external-link-alt"></i> ',
								'</a>'
							),
                            'extra_attributes' => [
                                'required' => true
                            ],
							'placeholder' => 'https://kit.fontawesome.com/123abc.js',
							'default'     => '',
							'show_if' => '[%type%]=="KIT" || [%type%]=="SVG"',
						],
						[
							'id'      => 'version',
							'type'    => 'select',
							'label'   => __( 'Version', 'ayecode-connect' ),
							'description'    => __( 'Select Font Awesome version.', 'ayecode-connect' ),
                            //@todo we need to simplify versioning, maybe 7.x, 6.x, 5.x..
							'options' => array_merge(
								[ '' => wp_sprintf( __( '%s (default)', 'ayecode-connect' ), '6.7.2' ) ],
								( $latest_version && version_compare( $latest_version, '7.0.0', '>' ) ) ? [ $latest_version => esc_html( $latest_version ) ] : [],
								[
									'7.2.0'  => '7.2.0',
									'6.4.2'  => '6.4.2',
									'6.1.0'  => '6.1.0',
									'6.0.0'  => '6.0.0',
									'5.15.4' => '5.15.4',
									'5.6.0'  => '5.6.0',
									'5.5.0'  => '5.5.0',
									'5.4.0'  => '5.4.0',
									'5.3.0'  => '5.3.0',
									'5.2.0'  => '5.2.0',
									'5.1.0'  => '5.1.0',
									'4.7.0'  => '4.7.1 (CSS only)',
								]
							),
							'default' => '',
							'show_if' => '[%type%]!="KIT"',
						],
						[
							'id'      => 'enqueue',
							'type'    => 'select',
							'label'   => __( 'Enqueue', 'ayecode-connect' ),
							'description'    => __( 'Where to load Font Awesome.', 'ayecode-connect' ),
							'options' => [
								''         => __( 'Frontend + Backend (default)', 'ayecode-connect' ),
								'frontend' => __( 'Frontend', 'ayecode-connect' ),
								'backend'  => __( 'Backend', 'ayecode-connect' ),
							],
							'default' => '',
							'show_if' => '[%type%]!="SVG"',
						],
                        [
                            'id'      => 'pro',
                            'type'    => 'toggle',
                            'label'   => __( 'Enable Pro Icons', 'ayecode-connect' ),
                            'description'    => wp_sprintf(
                                __( 'Requires a subscription. %sLearn more%s  (affiliate link)', 'ayecode-connect' ),
                                '<a rel="noopener noreferrer" target="_blank" href="https://fontawesome.com/referral?a=c9b89e1418">',
                                ' <i class="fas fa-external-link-alt"></i></a>',
                            ),
                            'default' => false,
//                            'show_if' => '[%type%]!="KIT"',
                        ],
                        [
                            'id'      => 'pro_info',
                            'type'    => 'alert',
                            'alert_type' => 'danger',
                            'description' => __( '<strong>Warning</strong> Font Awesome Pro v6/7 requires the use of a <strong>KIT</strong> or <strong>SVG</strong>, please correct your loading method setting', 'ayecode-connect' ),
                            'show_if' => '[%pro%] && ([%type%]!="KIT" && [%type%]!="SVG") && ( [%version%]>"5.999.0" || [%version%]=="" )',
                        ],
                        [
                            'id'      => 'api_key',
                            'type'    => 'password',
                            'label'   => __( 'API Key', 'ayecode-connect' ),
                            'description'    => __( 'Enter your Font Awesome API Key', 'ayecode-connect' ),
                            'placeholder'    => __( 'Required if using v6/7', 'ayecode-connect' ),
                            'default' => '',
                            'show_if' => '[%pro%]',
                            'extra_attributes' => ['required' => true]
                        ],
                        [
                            'id'      => 'local',
                            'type'    => 'toggle',
                            'label'   => __( 'Load Fonts Locally', 'ayecode-connect' ),
                            'description'    => __( '(For free version only) Load FontAwesome fonts from locally. This downloads FontAwesome fonts from fontawesome.com & stores at the local site.', 'ayecode-connect' ),
                            'default' => false,
                            'show_if' => '[%type%]!="KIT" && [%pro%]==""',
                        ],
					],
				],

				// Compatibility Section
				[
					'id'    => 'compatibility',
					'name'  => __( 'Compatibility', 'ayecode-connect' ),
					'icon'  => 'fa-solid fa-puzzle-piece',
					'fields' => [
						[
							'id'      => 'shims',
							'type'    => 'toggle',
							'label'   => __( 'Enable v4 shims compatibility', 'ayecode-connect' ),
							'description'    => __( 'This enables v4 classes to work with v5, sort of like a band-aid until everyone has updated everything to v5.', 'ayecode-connect' ),
							'default' => false,
							'show_if' => '[%type%]!="KIT"',
						],
						[
							'id'      => 'js-pseudo',
							'type'    => 'toggle',
							'label'   => __( 'Enable JS pseudo elements (not recommended)', 'ayecode-connect' ),
							'description'    => __( 'Used only with the JS version, this will make pseudo-elements work but can be CPU intensive on some sites.', 'ayecode-connect' ),
							'default' => false,
							'show_if' => '[%type%]!="KIT"',
						],
						[
							'id'      => 'dequeue',
							'type'    => 'toggle',
							'label'   => __( 'Dequeue', 'ayecode-connect' ),
							'description'    => __( 'This will try to dequeue any other Font Awesome versions loaded by other sources if they are added with `font-awesome` or `fontawesome` in the name.', 'ayecode-connect' ),
							'default' => false,
						],
					],
				],


                // Custom Icons Section
                [
                    'id'    => 'custom_icons',
                    'name'  => __( 'Custom Icons', 'ayecode-connect' ),
                    'description'  => ayecode_is_fa_svg_mode() ? '' : '<div class="alert alert-danger" role="alert">'.__( 'Custom SVG icons only work in SVG mode.', 'ayecode-connect' ).'</div>',
                    'icon'  => 'fa-solid fa-icons',
                    'type'  => 'list_table',

                    'table_config' => [
                        'singular' => 'Icon',
                        'plural'   => 'Icons',
                        'ajax_action_get' => 'get_icons',
                        'ajax_action_bulk' => 'bulk_icon_action',

                        'columns' => [
                            'image'   => [ 'label' => 'Image' ],
                            'slug' => [ 'label' => 'Identifier' ],
                        ],
                        'bulk_actions' => [
                            'delete' => 'Delete',
                        ],
                    ],

                    'modal_config' => [
                        'title_add'  => 'Add New Icon',
                        'title_edit' => 'Edit Icon',
                        'ajax_action_create' => 'add_icon',
                        'ajax_action_update' => 'update_icon',
                        'ajax_action_delete' => 'delete_icon',

                        'fields' => [
                            [
                                'id'      => 'rename_info',
                                'type'    => 'alert',
                                'alert_type' => 'danger',
                                'description' => __( 'Changing the icon identifier will break all current usage of the icon.', 'ayecode-connect' ),
                                'show_if' => "[%id%] != null && [%id%] != ''"
                            ],
                            [
                                'id'      => 'icon_type',
                                'type'    => 'radio_group',
                                'label'   => __( 'SVG Type', 'ayecode-connect' ),
                                'description'    => __( 'Select your SVG format', 'ayecode-connect' ),
                                'options' => [
                                    'file' => __( 'File', 'ayecode-connect' ),
                                    'code' => __( 'Code', 'ayecode-connect' ),
                                ],
                                'default' => 'file',
                                'button_style' => 'outline-primary btn-sm',
                                'show_if' => "[%id%] == null || [%id%] == ''"
                            ],
                            [
                                'id'       => 'icon_code',
                                'type'     => 'textarea',
                                'label'    => __( 'Icon', 'ayecode-connect' ),
                                'description' => __( 'SVG code only', 'ayecode-connect' ),
                                'show_if' => "[%icon_type%] == 'code' && ( [%id%] == null || [%id%] == '' )"
                            ],
                            [
                                'id'       => 'icon_file',
                                'type'     => 'file',
                                'accept'   => '.svg,image/svg+xml',
                                'label'    => __( 'Icon', 'ayecode-connect' ),
                                'description' => __( 'SVG files only', 'ayecode-connect' ),
                                'show_if' => "[%icon_type%] == 'file' && ( [%id%] == null || [%id%] == '' )"
                            ],
                            [
                                'id' => 'optimize',
                                'type' => 'toggle',
                                'label'    => __( 'Optimize as Dynamic UI Icon', 'ayecode-connect' ),
                                'description' => __( 'Removes fixed colors and dimensions so the icon inherits your text color.', 'ayecode-connect' ),
                                'default' => true,
                                'show_if' => "[%id%] == null || [%id%] == ''"
                            ],
                            [
                                'id'      => 'slug',
                                'type'    => 'slug',
                                'label'   => __( 'Identifier', 'ayecode-connect' ),
                                'placeholder' => __( 'fishing-boat', 'ayecode-connect' ),
                                'description' => __( 'This will be used to search and identify the icon later (lowercase, hyphen separated)', 'ayecode-connect' ),
                                'extra_attributes' => ['required' => true]
                            ]
                        ]
                    ],
                ],

                // Tools
                [
                    'id'    => 'tools',
                    'name'  => __( 'Tools', 'ayecode-connect' ),
                    'icon'  => 'fa-solid fa-screwdriver-wrench',
                    'fields' => [
                        [
                            'id'           => 'tool_clear_icon_cache',
                            'type'         => 'action_button',
                            'label'        => __( 'Clear FA Icon Cache', 'ayecode-connect' ),
                            'description'  => __( 'This will delete all downloaded Font Awesome SVG icons. They will auto download when used again. (does not delete custom icons)', 'ayecode-connect' ),
                            'button_text'  => __( 'Clear Cache', 'ayecode-connect' ),
                            'button_class' => 'btn-primary',
                            'ajax_action'  => 'clear_icon_cache', // The unique ID for this action.
                        ],
                    ],
                ],


			],



		];
	}


    /**
     * Central handler for all tool actions on this page.
     *
     * @param string $tool_action The 'ajax_action' from the field config.
     * @param array  $post_data   The full $_POST data from the request.
     */
    public function ajax_actions( $tool_action, $post_data ) {

        if ('get_icons' === $tool_action) {

            // Get custom icons using helper class
            $custom_icons = \AyeCode\FontAwesome\Custom_Icons::instance();
            $icons = $custom_icons->get_all_icons();

            $response = [
                'items' => $icons,
                'counts' => [
                    'all' => count( $icons )
                ]
            ];

            wp_send_json_success($response);

        } elseif ('add_icon' === $tool_action) {

            // Parse all data fields
            $data = ! empty( $post_data['data'] ) ? json_decode( stripslashes( $post_data['data'] ), true ) : [];
            $slug = ! empty( $data['slug'] ) ? sanitize_text_field( $data['slug'] ) : '';
            $icon_type = ! empty( $data['icon_type'] ) ? sanitize_text_field( $data['icon_type'] ) : 'file';
            $optimize = ! empty( $data['optimize'] ); // Boolean

            // Upload icon using helper class
            $custom_icons = \AyeCode\FontAwesome\Custom_Icons::instance();

            if ( $icon_type === 'code' ) {
                // Code input mode
                $svg_code = ! empty( $data['icon_code'] ) ? $data['icon_code'] : '';
                if ( empty( $svg_code ) ) {
                    wp_send_json_error( [ 'message' => __( 'SVG code is required.', 'ayecode-connect' ) ] );
                }
                $result = $custom_icons->upload_icon( $svg_code, $slug, $optimize, true );
            } else {
                // File upload mode
                if ( empty( $_FILES['icon_file'] ) ) {
                    wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'ayecode-connect' ) ] );
                }
                $result = $custom_icons->upload_icon( $_FILES['icon_file'], $slug, $optimize, false );
            }

            // Handle errors
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( [ 'message' => $result->get_error_message() ] );
            }

            // Return icon data directly (no parent key)
            wp_send_json_success( $result );

        } elseif ('update_icon' === $tool_action) {

            // Parse data (old and new slug from JSON data field)
            $data = ! empty( $post_data['data'] ) ? json_decode( stripslashes( $post_data['data'] ), true ) : [];
            $old_slug = ! empty( $data['id'] ) ? sanitize_text_field( $data['id'] ) : '';
            $new_slug = ! empty( $data['slug'] ) ? sanitize_text_field( $data['slug'] ) : '';

            // Validate required fields
            if ( empty( $old_slug ) || empty( $new_slug ) ) {
                wp_send_json_error( [ 'message' => __( 'Missing required fields.', 'ayecode-connect' ) ] );
            }

            // Update icon using helper class
            $custom_icons = \AyeCode\FontAwesome\Custom_Icons::instance();
            $result = $custom_icons->update_icon( $old_slug, $new_slug );

            // Handle errors
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( [ 'message' => $result->get_error_message() ] );
            }

            // Return updated icon data directly (no parent key)
            wp_send_json_success( $result );

        } elseif ('delete_icon' === $tool_action) {

            // Parse data (slug from JSON data field)
            $data = ! empty( $post_data['data'] ) ? json_decode( stripslashes( $post_data['data'] ), true ) : [];
            $slug = ! empty( $data['id'] ) ? sanitize_text_field( $data['id'] ) : '';

            // Validate required field
            if ( empty( $slug ) ) {
                wp_send_json_error( [ 'message' => __( 'Missing icon identifier.', 'ayecode-connect' ) ] );
            }

            // Delete icon using helper class
            $custom_icons = \AyeCode\FontAwesome\Custom_Icons::instance();
            $result = $custom_icons->delete_icon( $slug );

            // Handle errors
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( [ 'message' => $result->get_error_message() ] );
            }

            // Return success message
            wp_send_json_success( [ 'message' => __( 'Icon deleted successfully.', 'ayecode-connect' ) ] );

        } elseif ('bulk_icon_action' === $tool_action) {

            // Parse data (item IDs and action from JSON data field)
            $data = ! empty( $post_data['data'] ) ? json_decode( stripslashes( $post_data['data'] ), true ) : [];
            $item_ids = ! empty( $data['item_ids'] ) ? $data['item_ids'] : [];
            $action = ! empty( $data['action'] ) ? sanitize_text_field( $data['action'] ) : '';

            // Validate required fields
            if ( empty( $item_ids ) || ! is_array( $item_ids ) ) {
                wp_send_json_error( [ 'message' => __( 'No icons selected.', 'ayecode-connect' ) ] );
            }

            if ( empty( $action ) ) {
                wp_send_json_error( [ 'message' => __( 'No action specified.', 'ayecode-connect' ) ] );
            }

            // Process bulk action
            $custom_icons = \AyeCode\FontAwesome\Custom_Icons::instance();
            $success_count = 0;
            $error_count = 0;
            $errors = [];

            if ( $action === 'delete' ) {
                foreach ( $item_ids as $id ) {
                    $slug = sanitize_text_field( $id );
                    $result = $custom_icons->delete_icon( $slug );

                    if ( is_wp_error( $result ) ) {
                        $error_count++;
                        $errors[] = sprintf( __( '%s: %s', 'ayecode-connect' ), $slug, $result->get_error_message() );
                    } else {
                        $success_count++;
                    }
                }

                // Regenerate JSON if at least one icon was deleted successfully
                if ( $success_count > 0 ) {
                    $json_result = $custom_icons->generate_custom_icons_json();
                    if ( is_wp_error( $json_result ) ) {
                        wp_send_json_error( [ 'message' => $json_result->get_error_message() ] );
                    }
                }

                // Build response message
                if ( $success_count > 0 && $error_count === 0 ) {
                    $message = sprintf(
                        _n( '%d icon deleted successfully.', '%d icons deleted successfully.', $success_count, 'ayecode-connect' ),
                        $success_count
                    );
                    wp_send_json_success( [ 'message' => $message ] );
                } elseif ( $success_count > 0 && $error_count > 0 ) {
                    $message = sprintf(
                        __( '%1$d icon(s) deleted successfully. %2$d failed: %3$s', 'ayecode-connect' ),
                        $success_count,
                        $error_count,
                        implode( ', ', $errors )
                    );
                    wp_send_json_success( [ 'message' => $message ] );
                } else {
                    wp_send_json_error( [ 'message' => __( 'Failed to delete icons: ', 'ayecode-connect' ) . implode( ', ', $errors ) ] );
                }
            } else {
                wp_send_json_error( [ 'message' => sprintf( __( 'Unknown bulk action: %s', 'ayecode-connect' ), $action ) ] );
            }
        } elseif ( 'clear_icon_cache' === $tool_action ) {
            // Clear the icon cache.
            $svg_loader = \AyeCode\FontAwesome\SVG_Loader::instance();
            $result = $svg_loader->clear_icon_cache();

            if ( \is_wp_error( $result ) ) {
                wp_send_json_error( [ 'message' => $result->get_error_message() ] );
            } else {
                wp_send_json_success( [ 'message' => __( 'Icon cache cleared successfully.', 'ayecode-connect' ) ] );
            }
        }

    }


}

