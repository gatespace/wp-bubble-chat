<?php
/**
 * Plugin Name:     WP Bubble Chat
 * Plugin URI:      https://github.com/gatespace/wp-bubble-chat
 * Description:     Embed a simple bubble chat in the content.
 * Author:          gatespace
 * Author URI:      https://gatespace.jp/
 * Text Domain:     wp-bubble-chat
 * Domain Path:     /languages
 * License:         GNU General Public License v2 or later
 * License URI:     LICENSE
 * Version:         0.1.0
 *
 * @package         WP_Bubble_Chat
 */

define( 'WP_BUBBLE_CHAT_URL',  plugins_url( '', __FILE__ ) );
define( 'WP_BUBBLE_CHAT_PATH', dirname( __FILE__ ) );

$wp_bubble_chat = new WP_Bubble_Chat();
$wp_bubble_chat->register();

/**
 * WP_Bubble_Chat class.
 */
class WP_Bubble_Chat {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin languages directory
	 *
	 * @var string
	 */
	private $langs;

	/**
	 * Construct.
	 */
	function __construct() {
		$data = get_file_data(
			__FILE__,
			array( 'ver' => 'Version', 'langs' => 'Domain Path' )
		);
		$this->version = $data['ver'];
		$this->langs   = $data['langs'];

		$this->post_type = 'wp_bubble_chat';
		$this->meta_key  = 'wpbc_avatar_img';
		$this->size_name = 'wpbc_avatar';
		$this->size_w    = 50;
		$this->size_h    = 50;
		$this->no_image  = '<img src="' . plugins_url( 'images/no-image.png', __FILE__ ) . '" alt="No Image" style="width: 50px;">';
	}

	/**
	 * Register.
	 */
	public function register() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Plugin loaded.
	 */
	public function plugins_loaded() {
		load_plugin_textdomain(
			'wp-bubble-chat',
			false,
			dirname( plugin_basename( __FILE__ ) ) . $this->langs
		);

		// Set avatar image size.
		add_action( 'init', array( $this, 'wpbc_add_image_size' ) );

		// Scripts and styles for the front end.
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

		// Register WP Bubble Chat setting (Custom Post Type).
		add_action( 'init', array( $this, 'wpbc_register_post_type' ), 0 );

		// Add meta box.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );

		// Add WP Bubble Chat shortcode.
		add_shortcode( 'chat', array( $this, 'wpbc_add_shortcode' ), 10, 2 );

		// Add column the WP Bubble Chat list table.
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'add_wpbc_posts_columns' ), 10, 1 );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'add_wpbc_posts_columns_callback' ), 10, 2 );

		// Print shortcode after the title field.
		add_action( 'edit_form_after_title', array( $this, 'view_shortcode_edit_form_after_title' ), 10, 2 );

	}

	/**
	 * Set avatar image size.
	 */
	public function wpbc_add_image_size() {
		add_image_size( $this->size_name, $this->size_w, $this->size_h, true );
	}

	/**
	 * Get avatar image.
	 *
	 * @param int          $post_id Image attachment ID.
	 * @param string|array $size Optional. Image size. Accepts any valid image size,
	 *                           or an array of width and height values in pixels (in that order).
	 *                           Default 'wpbc_avatar'.
	 * @return string HTML img element.
	 */
	public function wpbc_avatar_image( $post_id, $size ) {
		// Default avatar size.
		if ( empty( empty( $size ) ) ) {
			/**
			 * Filters the avatar default size.
			 *
			 * @param string $size_name Size of avatar default image.
			 */
			$size = apply_filters( 'wpbc_avatar_default_size', $this->size_name );
		}

		// Default avatar image.
		/**
		 * Filters the avatar default image tag.
		 *
		 * @param HTML $no_image Avatar default image.
		 */
		$default = apply_filters( 'wpbc_avatar_default_image', $this->no_image );

		// WPBC Post ID no set.
		if ( empty( $post_id ) ) {
			return $default;
		}

		// WPBC post status is not 'publish'.
		if ( get_post_status( $post_id ) !== 'publish' ) {
			return $default;
		}

		// Check post_meta( $this->meta_key ).
		$avatar_id = get_post_meta( $post_id, $this->meta_key, true );
		if ( empty( $avatar_id ) ) {
			return $default;
		}

		// Get avatar image.
		$avatar_img = wp_get_attachment_image( $avatar_id, $size );
		if ( ! empty( $avatar_img ) ) {
			return $avatar_img;
		}

		return $default;
	}

	/**
	 * Scripts and styles for the front end.
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_style(
			'wp-bubble-chat',
			plugins_url( 'css/wp-bubble-chat.css', __FILE__ ),
			array(),
			$this->version
		);
	}

	/**
	 * Register WP Bubble Chat setting (Custom Post Type).
	 */
	public function wpbc_register_post_type() {

		$labels = array(
			'name'                  => _x( 'Chat avatars', 'Post Type General Name', 'wp-bubble-chat' ),
			'singular_name'         => _x( 'Chat avatar', 'Post Type Singular Name', 'wp-bubble-chat' ),
			'menu_name'             => __( 'Chat avatar', 'wp-bubble-chat' ),
			'name_admin_bar'        => __( 'Chat avatar', 'wp-bubble-chat' ),
			'archives'              => __( 'Item Archives', 'wp-bubble-chat' ),
			'attributes'            => __( 'Item Attributes', 'wp-bubble-chat' ),
			'parent_item_colon'     => __( 'Parent Item:', 'wp-bubble-chat' ),
			'all_items'             => __( 'All Items', 'wp-bubble-chat' ),
			'add_new_item'          => __( 'Add New Item', 'wp-bubble-chat' ),
			'add_new'               => __( 'Add New', 'wp-bubble-chat' ),
			'new_item'              => __( 'New Item', 'wp-bubble-chat' ),
			'edit_item'             => __( 'Edit Item', 'wp-bubble-chat' ),
			'update_item'           => __( 'Update Item', 'wp-bubble-chat' ),
			'view_item'             => __( 'View Item', 'wp-bubble-chat' ),
			'view_items'            => __( 'View Items', 'wp-bubble-chat' ),
			'search_items'          => __( 'Search Item', 'wp-bubble-chat' ),
			'not_found'             => __( 'Not found', 'wp-bubble-chat' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'wp-bubble-chat' ),
			'featured_image'        => __( 'Avatar Image', 'wp-bubble-chat' ),
			'set_featured_image'    => __( 'Set avatar image', 'wp-bubble-chat' ),
			'remove_featured_image' => __( 'Remove avatar image', 'wp-bubble-chat' ),
			'use_featured_image'    => __( 'Use as avatar image', 'wp-bubble-chat' ),
			'insert_into_item'      => __( 'Insert into item', 'wp-bubble-chat' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'wp-bubble-chat' ),
			'items_list'            => __( 'Items list', 'wp-bubble-chat' ),
			'items_list_navigation' => __( 'Items list navigation', 'wp-bubble-chat' ),
			'filter_items_list'     => __( 'Filter items list', 'wp-bubble-chat' ),
		);
		$args = array(
			'label'                 => __( 'Chat avatars', 'wp-bubble-chat' ),
			'description'           => __( 'WP Bubble Chat setting.', 'wp-bubble-chat' ),
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => true,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 25,
			'menu_icon'             => 'dashicons-format-chat',
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
			'show_in_rest'          => false,
		);
		register_post_type( $this->post_type, $args );
	}

	/**
	 * Adds a box to the main column on the wp_bubble_chat edit screens.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'wpbcdiv',
			__( 'Avatar Image', 'wp-bubble-chat' ),
			array( $this, 'wpbc_metabox_callback' ),
			$this->post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Prints the box content.
	 *
	 * @param WP_Post $post The object for the current post.
	 */
	public function wpbc_metabox_callback( $post ) {
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'wpbc_save_meta_box_data', 'wpbc_meta_box_nonce' );

		/**
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$value = get_post_meta( $post->ID, $this->meta_key, true );

		$image_class = 'wpbc_upload_image_button button';
		$image_item  = esc_html__( 'Upload image', 'wp-bubble-chat' );
		$image_size  = 'thumbnail';
		$display     = 'none';

		if ( $image_attributes = wp_get_attachment_image_src( $value, $image_size ) ) {
			$image_class = 'wpbc_upload_image_button';
			$image_item  = '<img src="' . esc_attr( $image_attributes[0] ) . '" style="max-width:95%;display:block;" />';
			$display     = 'inline-block';
		}

		echo '
		<div>
			<a href="#" class="' . sanitize_html_class( $image_class ) . '">' . wp_kses_post( $image_item ) . '</a>
			<input type="hidden" name="' . esc_attr( $this->meta_key ) . '" id="' . esc_attr( $this->meta_key ) . '" value="' . esc_attr( $value ) . '" />
			<a href="#" class="wpbc_remove_image_button" style="display:inline-block;display:' . esc_attr( $display ) . '">' . esc_html__( 'Remove avatar image', 'wp-bubble-chat' ) . '</a>
		</div>';

	}

	/**
	 * Scripts and styles for the wp-bubble-chat edit screen.
	 */
	public function admin_enqueue_scripts() {
		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_script(
			'wp-bubble-chat-uploads',
			plugins_url( 'js/customscript.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			false
		);
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_post( $post_id ) {

		// Check if our nonce is set.
		$wpbc_meta_box_nonce = filter_input( INPUT_POST, 'wpbc_meta_box_nonce' );
		if ( ! isset( $wpbc_meta_box_nonce ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $wpbc_meta_box_nonce ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		$wpbc_post_type = filter_input( INPUT_POST, 'post_type' );
		if ( isset( $wpbc_post_type ) && 'wp_bubble_chat' === $wpbc_post_type ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */
		$wpbc_meta_value = filter_input( INPUT_POST, $this->meta_key );
		if ( ! isset( $wpbc_meta_value ) ) {
			delete_post_meta( $post_id, $this->meta_key );
			return;
		}

		// Sanitize user input.
		$meta_data = absint( $wpbc_meta_value );

		// Update the meta field in the database.
		update_post_meta( $post_id, $this->meta_key, $meta_data );
	}

	/**
	 * Add column the WP Bubble Chat list table.
	 *
	 * @param array $columns An array of column names.
	 * @return array An array of column names.
	 */
	public function add_wpbc_posts_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $column_name => $column_display_name ) {
			if ( 'title' === $column_name  ) {
				$new_columns['avatar'] = __( 'Avatar', 'wp-bubble-chat' );
			} elseif ( 'date' === $column_name ) {
				$new_columns['bubble_chat_code'] = __( 'Shortcode', 'wp-bubble-chat' );
			}
			$new_columns[ $column_name ] = $column_display_name;
		}
		return $new_columns;
	}

	/**
	 * Add data in each custom column in the the WP Bubble Chat list table.
	 *
	 * @param string $column  The name of the column to display.
	 * @param int    $post_id The current post ID.
	 */
	public function add_wpbc_posts_columns_callback( $column, $post_id ) {
		switch ( $column ) {
			case 'avatar' :
				// Avatar image.
				echo wp_kses_post( $this->wpbc_avatar_image( $post_id, $this->size_name ) );
				break;
			case 'bubble_chat_code' :
				// Chat shortcode.
				echo wp_kses_post( $this->wpbc_print_shortcode( $post_id ) );
				break;

		}
	}

	/**
	 * Print shortcode after the title field.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function view_shortcode_edit_form_after_title( $post ) {
		if ( $post->post_type !== $this->post_type ) {
			return;
		}

		echo wp_kses_post( $this->wpbc_print_shortcode( $post->ID ) );
	}

	/**
	 * Add WP Bubble Chat shortcode.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content.
	 * @return string HTML WP Bubble Chat content.
	 */
	public function wpbc_add_shortcode( $atts, $content = null ) {

		// Attributes.
		$args = shortcode_atts(
			array(
				'icon' => '0',
				'name' => '',
				'pos'  => 'l',
			),
			$atts
		);
		$icon = (int) $args['icon'];
		$name = $args['name'];
		$pos  = $args['pos'];

		// Avatar size.
		/**
		 * Filters the avatar size for output.
		 *
		 * @param string|array $size_name Size of avatar image.
		 *                                Avatar image size or array of width and height values (in that order).
		 *                                Default 'wpbc_avatar'.
		 */
		$size = apply_filters( 'wpbc_avatar_size', $this->size_name );

		// Avatar image.
		$avatar_img = $this->wpbc_avatar_image( $icon, $size );

		// Avatar name.
		$avatar_name = apply_filters( 'the_title', $name );

		// Output.
		$html  = '';
		$html .= '<div class="wpbc-outer wpbc-pos-' . $pos . '">' . "\n";
		$html .= '<div class="wpbc-avatar">' . "\n";
		$html .= '<div class="wpbc-avatar-image">' . $avatar_img . '</div>' . "\n";
		$html .= '<div class="wpbc-avatar-name">' . $avatar_name . '</div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="wpbc-avatar-text"><div class="wpbc-avatar-text-inner">' . do_shortcode( $content ) . '</div></div>' . "\n";
		$html .= '</div><!-- //.wpbc-outer -->' . "\n";

		/**
		 * Filters the shortcode result.
		 *
		 * @param HTML   $html       Output html code.
		 * @param string $pos        Css class for avatar image position.
		 * @param HTML   $avatar_img Avatar image html code.
		 * @param string $name       Avatar name.
		 * @param HTML   $content    Inner content.
		 */
		return apply_filters( 'wpbc_output', $html, $pos, $avatar_img, $name, $content );

	}

	/**
	 * Print WP Bubble Chat shortcode.
	 *
	 * @param int $post_id WP Bubble Chat ID.
	 */
	public function wpbc_print_shortcode( $post_id ) {

		if ( empty( $post_id ) ) {
			return;
		}

		echo '<code>[chat icon="' . esc_html( $post_id ) . '" name="' . get_the_title( $post_id ) . '" pos="l|r"]' . esc_html__( 'Chat text.', 'wp-bubble-chat' ) . '[/chat]</code>';

	}

} // end class WP_Bubble_Chat
