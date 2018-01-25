<?php
/**
 * Plugin Name:     WP Bubble Chat
 * Plugin URI:      https://github.com/gatespace/wp-bubble-chat
 * Description:     Embed a simple bubble chat in the content.
 * Author:          gatespace
 * Author URI:      https://gatespace.jp/
 * Text Domain:     wp-bubble-chat
 * Domain Path:     /languages
 * License:         GPLv2
 * Version:         0.1.0
 *
 * @package         WP_Bubble_Chat
 */

define( 'WP_Bubble_Chat_URL',  plugins_url( '', __FILE__ ) );
define( 'WP_Bubble_Chat_PATH', dirname( __FILE__ ) );

$wp_bubble_chat = new WP_Bubble_Chat();
$wp_bubble_chat->register();

class WP_Bubble_Chat {
private $version = '';
private $langs   = '';

	function __construct() {
		$data = get_file_data(
			__FILE__,
			array( 'ver' => 'Version', 'langs' => 'Domain Path' )
		);
		$this->version = $data['ver'];
		$this->langs   = $data['langs'];

		$this->post_type = 'wp_bubble_chat';
		$this->size      = 'wpbc_avatar';
		$this->meta_key  = 'wpbc_avatar_img';
		$this->no_image  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><defs><style>.cls-1{fill:#ccc;}.cls-2{font-size:10px;fill:#fff;font-family:ArialMT, Arial;}</style></defs><title>noimage</title><g id="bg_2" data-name="bg_2"><g id="bg_1" data-name="bg 1"><rect class="cls-1" width="50" height="50"/><text class="cls-2" transform="translate(3.32 28.2)">No Image</text></g></g></svg>';
	}

	public function register() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded() {
		load_plugin_textdomain(
			'wp-bubble-chat',
			false,
			dirname( plugin_basename( __FILE__ ) ).$this->langs
		);

		// Set avatar size
		add_action( 'init', array( $this, 'wpbc_add_image_size' ) );

		// Style for front.
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

		// Register WP Bubble Chat setting (Custom Post Type).
		add_action( 'init', array( $this, 'wpbc_register_post_type' ), 0 );

		// Add meta box
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );

		// Add Shortcode
		add_shortcode( 'chat', array( $this, 'wpbc_add_shortcode' ), 10, 2 );

		// Add column in WP Bubble Chat avatar list.
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'add_wpbc_posts_columns' ), 10, 1 );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'add_wpbc_posts_columns_callback' ), 10, 2 );

		add_action( 'edit_form_after_title', array( $this, 'view_shortcode_edit_form_after_title' ), 10, 2 );

	}

	// Set avatar size.
	public function wpbc_add_image_size() {
		add_image_size( $this->size, 50, 50, true );
	}

	// Get avatar image.
	public function wpbc_avatar_image( $post_id, $size = '' ) {
		$size    = $this->size;
		$default = apply_filters( 'wpbc_avatar_default', $this->no_image );
		
		// WPBC Post ID no set.
		if ( empty( $post_id ) ) {
			return $default;
		}

		// WPBC post status is not 'publish'.
		if ( get_post_status( $post_id ) != 'publish' ) {
			return $default;
		}
		
		// check post_meta(avatar image id)
		$avatar_id = get_post_meta( $post_id, $this->meta_key, true );
		if ( empty( $avatar_id ) ) {
			return $default;
		}

		$avatar_img = wp_get_attachment_image( $avatar_id, $size );

		return $avatar_img;
	}

	// Get avatar image.

	// Style
	public function wp_enqueue_scripts() {
		wp_enqueue_style(
			'wp-bubble-chat',
			plugins_url( 'css/wp-bubble-chat.css', __FILE__ ),
			array(),
			$this->version
		);
	}

	// Register WP Bubble Chat setting (Custom Post Type).
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

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$value = get_post_meta( $post->ID, $this->meta_key, true );

		$image      = ' button">Upload image';
		$image_size = 'post-thumbnails';
		$display    = 'none';
	 
		if( $image_attributes = wp_get_attachment_image_src( $value, $image_size ) ) {
	 
			// $image_attributes[0] - image URL
			// $image_attributes[1] - image width
			// $image_attributes[2] - image height
	 
			$image = '"><img src="' . $image_attributes[0] . '" style="max-width:95%;display:block;" />';
			$display = 'inline-block';
	 
		} 

		echo '
		<div>
			<a href="#" class="wpbc_upload_image_button' . $image . '</a>
			<input type="hidden" name="' . $this->meta_key . '" id="' . $this->meta_key . '" value="' . $value . '" />
			<a href="#" class="wpbc_remove_image_button" style="display:inline-block;display:' . $display . '">' .  __( 'Remove avatar image', 'wp-bubble-chat' ) . '</a>
		</div>';

	}

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
		if ( ! isset( $_POST['wpbc_meta_box_nonce'] ) ) {
			return;
		}
	
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpbc_meta_box_nonce'], 'wpbc_save_meta_box_data' ) ) {
			return;
		}
	
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'wp_bubble_chat' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */
		if ( ! isset( $_POST[$this->meta_key] ) ) {
			delete_post_meta( $post_id, $this->meta_key );
			return;
		}

		// Sanitize user input.
		$meta_data = absint( $_POST[$this->meta_key] );

		// Update the meta field in the database.
		update_post_meta( $post_id, $this->meta_key, $meta_data );
	}

	// Add column in WP Bubble Chat avatar list.
	public function add_wpbc_posts_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $column_name => $column_display_name ) {
			if ( $column_name == 'title' ) {
				$new_columns['avatar'] = __( 'Avatar', 'wp-bubble-chat' );
			} elseif ( $column_name == 'date' ) {
				$new_columns['bubble_chat_code'] = __( 'Short code', 'wp-bubble-chat' );
			}
			$new_columns[ $column_name ] = $column_display_name;
		}   
		return $new_columns;
	}

	public function add_wpbc_posts_columns_callback( $column, $post_id ) {
	    switch ( $column ) {
	        case 'avatar' : 
				// Avatar image
				$avatar_img = $this->wpbc_avatar_image( $post_id, $this->size );
	            echo '<div style="width: 50px;">' . $avatar_img . '</div>'; 
	            break;
	        case 'bubble_chat_code' : 
				// Chat chortcode
				echo $this->wpbc_print_shortcode( $post_id );
	            break;
	
	    }
	}
	
	public function view_shortcode_edit_form_after_title( $post ) {
		if ( $post->post_type != $this->post_type )
			return;

		echo $this->wpbc_print_shortcode( $post->ID );
	}

	// Add shortcode
	public function wpbc_add_shortcode( $atts, $content = null ) {
	
		// Attributes
		extract( shortcode_atts(
			array(
				'icon'   => '0',
				'name' => '',
				'pos'  => 'l',
			),
			$atts
		) );
		
		if ( empty( $icon ) ) {
			return;
		}
	
		$html = '';

		$size = apply_filters( 'wpbc_avatar_size', $this->size );

		// Avatar image
		$avatar_img = $this->wpbc_avatar_image( $icon, $size );

		// Avatar name
		$avatar_name = apply_filters( 'the_title', $name );

		$html .= '<div class="wpbc-outer wpbc-pos-' . $pos . '">' . "\n";
		$html .= '<div class="wpbc-avatar">' . "\n";
		$html .= '<div class="wpbc-avatar-image">' . $avatar_img . '</div>' . "\n";
		$html .= '<div class="wpbc-avatar-name">' . $avatar_name . '</div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="wpbc-avatar-text"><div class="wpbc-avatar-text-inner">' . $content . '</div></div>' . "\n";
		$html .= '</div><!-- //.wpbc-outer -->' . "\n";
	
		return apply_filters( 'wpbc_output', $html, $pos, $avatar_img, $name, $content );
	
	}

	// Print chat shortcode.
	public function wpbc_print_shortcode( $post_id ) {
		
		if ( empty( $post_id ) ) {
			return;
		}

		echo '<code>[chat icon="' . $post_id . '" name="' . get_the_title( $post_id ). '" pos="l|r"]' . __( 'Chat text.', 'wp-bubble-chat' ) . '[/chat]</code>'; 

	}

} // end class WP_Bubble_Chat
