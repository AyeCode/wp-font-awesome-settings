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
					'name'  => __( 'General Settings', 'font-awesome-settings' ),
					'icon'  => 'fa-solid fa-gear',
					'fields' => [
						[
							'id'      => 'type',
							'type'    => 'select',
							'label'   => __( 'Loading Method', 'font-awesome-settings' ),
							'description'    => __( 'Choose how to load Font Awesome.', 'font-awesome-settings' ),
							'options' => [
								'CSS' => __( 'CSS (default)', 'font-awesome-settings' ),
								'JS'  => 'JS',
								'KIT' => __( 'Kits (settings managed on fontawesome.com)', 'font-awesome-settings' ),
								'SVG' => __( 'SVG - Inline Icons (No CSS/JS loaded on frontend)', 'font-awesome-settings' ),
							],
							'default' => 'CSS',
						],
						[
							'id'      => 'svg_info',
							'type'    => 'alert',
							'alert_type' => 'info',
							'description' => __( '<strong>SVG Mode:</strong> Font Awesome CSS/JS files are NOT loaded on the frontend. Frontend icons must be rendered using <code>ayecode_get_icon()</code>. The backend automatically loads CSS for compatibility, so normal <code>&lt;i&gt;</code> tags work in admin areas.', 'font-awesome-settings' ),
							'show_if' => '[%type%]=="SVG"',
						],
						[
							'id'          => 'kit-url',
							'type'        => 'text',
							'label'       => __( 'Kit URL', 'font-awesome-settings' ),
							'description'        => wp_sprintf(
								__( 'Requires a free account with Font Awesome. %sGet kit url%s', 'font-awesome-settings' ),
								'<a rel="noopener noreferrer" target="_blank" href="https://fontawesome.com/kits"><i class="fas fa-external-link-alt"></i> ',
								'</a>'
							),
							'placeholder' => 'https://kit.fontawesome.com/123abc.js',
							'default'     => '',
							'show_if' => '[%type%]=="KIT"',
						],
						[
							'id'      => 'version',
							'type'    => 'select',
							'label'   => __( 'Version', 'font-awesome-settings' ),
							'description'    => __( 'Select Font Awesome version.', 'font-awesome-settings' ),
							'options' => array_merge(
								[ '' => wp_sprintf( __( '%s (default)', 'font-awesome-settings' ), '6.7.2' ) ],
								( $latest_version && version_compare( $latest_version, '7.0.0', '>' ) ) ? [ $latest_version => esc_html( $latest_version ) ] : [],
								[
									'7.0.0'  => '7.0.0',
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
							'label'   => __( 'Enqueue', 'font-awesome-settings' ),
							'description'    => __( 'Where to load Font Awesome.', 'font-awesome-settings' ),
							'options' => [
								''         => __( 'Frontend + Backend (default)', 'font-awesome-settings' ),
								'frontend' => __( 'Frontend', 'font-awesome-settings' ),
								'backend'  => __( 'Backend', 'font-awesome-settings' ),
							],
							'default' => '',
							'show_if' => '[%type%]!="SVG"',
						],
                        [
                            'id'      => 'pro',
                            'type'    => 'toggle',
                            'label'   => __( 'Enable Pro Icons', 'font-awesome-settings' ),
                            'description'    => wp_sprintf(
                                __( 'Requires a subscription. %sLearn more%s  (affiliate link)', 'font-awesome-settings' ),
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
                            'description' => __( '<strong>Warning</strong> Font Awesome Pro v6/7 requires the use of a <strong>KIT</strong> or <strong>SVG</strong>, please correct your loading method setting', 'font-awesome-settings' ),
                            'show_if' => '[%pro%] && ([%type%]!="KIT" && [%type%]!="SVG") && ( [%version%]>"5.999.0" || [%version%]=="" )',
                        ],
                        [
                            'id'      => 'api_key',
                            'type'    => 'password',
                            'label'   => __( 'API Key', 'font-awesome-settings' ),
                            'description'    => __( 'Enter your Font Awesome API Key', 'font-awesome-settings' ),
                            'placeholder'    => __( 'Required if using v6/7', 'font-awesome-settings' ),
                            'default' => '',
                            'show_if' => '[%pro%]',
                        ],
                        [
                            'id'      => 'local',
                            'type'    => 'toggle',
                            'label'   => __( 'Load Fonts Locally', 'font-awesome-settings' ),
                            'description'    => __( '(For free version only) Load FontAwesome fonts from locally. This downloads FontAwesome fonts from fontawesome.com & stores at the local site.', 'font-awesome-settings' ),
                            'default' => false,
                            'show_if' => '[%type%]!="KIT" && [%pro%]==""',
                        ],
                        [
                            'id'      => 'local_version',
                            'type'    => 'hidden',
                            'default' => '',
                        ],


					],
				],

				// Pro & Local Settings Section
//				[
//					'id'    => 'pro_local',
//					'name'  => __( 'Pro & Local Settings', 'font-awesome-settings' ),
//					'icon'  => 'fa-solid fa-crown',
//					'fields' => [
//
//					],
//				],

				// Compatibility Section
				[
					'id'    => 'compatibility',
					'name'  => __( 'Compatibility', 'font-awesome-settings' ),
					'icon'  => 'fa-solid fa-puzzle-piece',
					'fields' => [
						[
							'id'      => 'shims',
							'type'    => 'toggle',
							'label'   => __( 'Enable v4 shims compatibility', 'font-awesome-settings' ),
							'description'    => __( 'This enables v4 classes to work with v5, sort of like a band-aid until everyone has updated everything to v5.', 'font-awesome-settings' ),
							'default' => false,
							'show_if' => '[%type%]!="KIT"',
						],
						[
							'id'      => 'js-pseudo',
							'type'    => 'toggle',
							'label'   => __( 'Enable JS pseudo elements (not recommended)', 'font-awesome-settings' ),
							'description'    => __( 'Used only with the JS version, this will make pseudo-elements work but can be CPU intensive on some sites.', 'font-awesome-settings' ),
							'default' => false,
							'show_if' => '[%type%]!="KIT"',
						],
						[
							'id'      => 'dequeue',
							'type'    => 'toggle',
							'label'   => __( 'Dequeue', 'font-awesome-settings' ),
							'description'    => __( 'This will try to dequeue any other Font Awesome versions loaded by other sources if they are added with `font-awesome` or `fontawesome` in the name.', 'font-awesome-settings' ),
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
                                'description' => __( 'Changing the icon identifier will break all current usage of the icon.', 'font-awesome-settings' ),
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
                                'label'    => __( 'Icon', 'geodirectory' ),
                                'description' => __( 'SVG code only', 'geodirectory' ),
                                'show_if' => "[%icon_type%] == 'code' || ( [%id%] == null || [%id%] == '' )"
                            ],
                            [
                                'id'       => 'icon_file',
                                'type'     => 'file',
                                'accept'   => '.svg,image/svg+xml',
                                'label'    => __( 'Icon', 'geodirectory' ),
                                'description' => __( 'SVG files only', 'geodirectory' ),
                                'show_if' => "[%icon_type%] == 'file' || ( [%id%] == null || [%id%] == '' )"
                            ],
                            [
                                'id' => 'optimize',
                                'type' => 'toggle',
                                'label'    => __( 'Optimize as Dynamic UI Icon', 'geodirectory' ),
                                'description' => __( 'Removes fixed colors and dimensions so the icon inherits your text color.', 'geodirectory' ),
                                'default' => true,
                                'show_if' => "[%id%] == null || [%id%] == ''"
                            ],
                            [
                                'id'      => 'slug',
                                'type'    => 'slug',
                                'label'   => __( 'Identifier', 'ayecode-connect' ),
                                'placeholder' => __( 'fishing-boat', 'geodirectory' ),
                                'description' => __( 'This will be used to search and identify the icon later (lowercase, hyphen separated)', 'geodirectory' ),
                                'extra_attributes' => ['required' => true]
                            ]
                        ]
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
            $custom_icons = WP_Font_Awesome_Custom_Icons::instance();
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
            $custom_icons = WP_Font_Awesome_Custom_Icons::instance();

            if ( $icon_type === 'code' ) {
                // Code input mode
                $svg_code = ! empty( $data['icon_code'] ) ? $data['icon_code'] : '';
                if ( empty( $svg_code ) ) {
                    wp_send_json_error( [ 'message' => __( 'SVG code is required.', 'font-awesome-settings' ) ] );
                }
                $result = $custom_icons->upload_icon( $svg_code, $slug, $optimize, true );
            } else {
                // File upload mode
                if ( empty( $_FILES['icon_file'] ) ) {
                    wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'font-awesome-settings' ) ] );
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
                wp_send_json_error( [ 'message' => __( 'Missing required fields.', 'font-awesome-settings' ) ] );
            }

            // Update icon using helper class
            $custom_icons = WP_Font_Awesome_Custom_Icons::instance();
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
                wp_send_json_error( [ 'message' => __( 'Missing icon identifier.', 'font-awesome-settings' ) ] );
            }

            // Delete icon using helper class
            $custom_icons = WP_Font_Awesome_Custom_Icons::instance();
            $result = $custom_icons->delete_icon( $slug );

            // Handle errors
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( [ 'message' => $result->get_error_message() ] );
            }

            // Return success message
            wp_send_json_success( [ 'message' => __( 'Icon deleted successfully.', 'font-awesome-settings' ) ] );

        } elseif ('bulk_icon_action' === $tool_action) {

            // Parse data (item IDs and action from JSON data field)
            $data = ! empty( $post_data['data'] ) ? json_decode( stripslashes( $post_data['data'] ), true ) : [];
            $item_ids = ! empty( $data['item_ids'] ) ? $data['item_ids'] : [];
            $action = ! empty( $data['action'] ) ? sanitize_text_field( $data['action'] ) : '';

            // Validate required fields
            if ( empty( $item_ids ) || ! is_array( $item_ids ) ) {
                wp_send_json_error( [ 'message' => __( 'No icons selected.', 'font-awesome-settings' ) ] );
            }

            if ( empty( $action ) ) {
                wp_send_json_error( [ 'message' => __( 'No action specified.', 'font-awesome-settings' ) ] );
            }

            // Process bulk action
            $custom_icons = WP_Font_Awesome_Custom_Icons::instance();
            $success_count = 0;
            $error_count = 0;
            $errors = [];

            if ( $action === 'delete' ) {
                foreach ( $item_ids as $id ) {
                    $slug = sanitize_text_field( $id );
                    $result = $custom_icons->delete_icon( $slug );

                    if ( is_wp_error( $result ) ) {
                        $error_count++;
                        $errors[] = sprintf( __( '%s: %s', 'font-awesome-settings' ), $slug, $result->get_error_message() );
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
                        _n( '%d icon deleted successfully.', '%d icons deleted successfully.', $success_count, 'font-awesome-settings' ),
                        $success_count
                    );
                    wp_send_json_success( [ 'message' => $message ] );
                } elseif ( $success_count > 0 && $error_count > 0 ) {
                    $message = sprintf(
                        __( '%1$d icon(s) deleted successfully. %2$d failed: %3$s', 'font-awesome-settings' ),
                        $success_count,
                        $error_count,
                        implode( ', ', $errors )
                    );
                    wp_send_json_success( [ 'message' => $message ] );
                } else {
                    wp_send_json_error( [ 'message' => __( 'Failed to delete icons: ', 'font-awesome-settings' ) . implode( ', ', $errors ) ] );
                }
            } else {
                wp_send_json_error( [ 'message' => sprintf( __( 'Unknown bulk action: %s', 'font-awesome-settings' ), $action ) ] );
            }
        }

    }


}
