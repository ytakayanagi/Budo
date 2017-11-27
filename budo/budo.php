<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://yujintakayanagi.com
 * @since             1.0.0
 * @package           Budo
 *
 * @wordpress-plugin
 * Plugin Name:       Budo
 * Plugin URI:        https://github.com/ytakayanagi/Budo
 * Description:       Manage your students at your martial arts dojo.
 * Version:           1.0.0
 * Author:            Yujin Takayanagi
 * Author URI:        http://yujintakayanagi.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       budo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-budo-activator.php
 */
function activate_budo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-budo-activator.php';
	Budo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-budo-deactivator.php
 */
function deactivate_budo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-budo-deactivator.php';
	Budo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_budo' );
register_deactivation_hook( __FILE__, 'deactivate_budo' );
register_activation_hook( __FILE__, 'budo_create_table' );

function budo_create_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE my_custom_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            due_date date NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";

    if ( ! function_exists('dbDelta') ) {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    }

    dbDelta( $sql );
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-budo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_budo() {

	$plugin = new Budo();
	$plugin->run();

}
run_budo();

global $budo_db_version;
$budo_db_version = '1.0';

class WPBudo {


    /**
     * Constructor. Called when plugin is initialised
     */
    function __construct() {
        add_option( 'budo_db_version', '1.0' );
        add_action( 'plugin_loaded', 'budo_update_table' );
        add_action( 'admin_enqueue_scripts', array( $this, 'budo_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'budo_scripts' ) );
        add_action( 'init', array( $this, 'register_custom_post_type' ) );
        add_action( 'init', array( $this, 'rank_register_taxonomy' ) );
        add_action( 'init', array( $this, 'membership_register_taxonomy' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        add_filter( 'manage_edit-student_columns', array( $this, 'add_table_columns' ) );
        add_action( 'manage_student_posts_custom_column', array( $this, 'output_table_columns_data'), 10, 2 );
    }

    function budo_styles()
    {
        // Register the style like this for a plugin:
        wp_register_style( 'custom-style', plugins_url( 'assets/css/style.css', __FILE__ ) );
        wp_register_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );

        // For either a plugin or a theme, you can then enqueue the style:
        wp_enqueue_style( 'custom-style' );
        wp_enqueue_style( 'jquery-ui' );
    }

    function budo_scripts() {
        // Register the script like this for a plugin:
        wp_register_script( 'custom-script', plugins_url( 'assets/js/script.js', __FILE__ ), array( 'jquery', 'jquery-ui-datepicker' ), true );

        // For either a plugin or a theme, you can then enqueue the script:
        wp_enqueue_script( 'custom-script' );
    }

    /**
     * Registers a Custom Post Type called contact
     */
    function register_custom_post_type() {
        register_post_type( 'student', array(
            'labels' => array(
                'name'               => _x( 'Students', 'post type general name', 'budo-student' ),
                'singular_name'      => _x( 'Student', 'post type singular name', 'budo-student' ),
                'menu_name'          => _x( 'Students', 'admin menu', 'budo-student' ),
                'name_admin_bar'     => _x( 'Student', 'add new on admin bar', 'budo-student' ),
                'add_new'            => _x( 'Add New Student', 'contact', 'budo-student' ),
                'add_new_item'       => __( 'Add New Student', 'budo-student' ),
                'new_item'           => __( 'New Student', 'budo-student' ),
                'edit_item'          => __( 'Edit Student', 'budo-student' ),
                'view_item'          => __( 'View Student', 'budo-student' ),
                'all_items'          => __( 'All Students', 'budo-student' ),
                'search_items'       => __( 'Search Students', 'budo-student' ),
                'parent_item_colon'  => __( 'Parent Students:', 'budo-student' ),
                'not_found'          => __( 'No students found.', 'budo-student' ),
                'not_found_in_trash' => __( 'No students found in Trash.', 'budo-student' ),
            ),

            // Frontend
            'has_archive'        => false,
            'public'             => false,
            'publicly_queryable' => false,

            // Admin
            'capability_type' => 'post',
            'menu_icon'     => 'dashicons-admin-users',
            'menu_position' => 10,
            'query_var'     => true,
            'show_in_menu'  => true,
            'show_ui'       => true,
            'supports'      => array(
                'title'
            ),
        ) );
    }

    // register two taxonomies to go with the post type
    function rank_register_taxonomy()
    {
        // set up labels
        $labels = array(
            'name' => 'Rank',
            'singular_name' => 'Rank Type',
            'search_items' => 'Search Rank Types',
            'all_items' => 'All Rank Types',
            'edit_item' => 'Edit Rank Type',
            'update_item' => 'Update Rank Type',
            'add_new_item' => 'Add New Rank Type',
            'new_item_name' => 'New Rank Type',
            'menu_name' => 'Rank Types'
        );
        // register taxonomy
        register_taxonomy('ranktype', 'student', array(
            'hierarchical' => true,
            'labels' => $labels,
            'query_var' => true,
            'show_admin_column' => true
        ));
    }

    // register two taxonomies to go with the post type
    function membership_register_taxonomy()
    {
        // set up labels
        $labels = array(
            'name' => 'Membership',
            'singular_name' => 'Membership Category',
            'search_items' => 'Search Membership Categories',
            'all_items' => 'All Membership Category',
            'edit_item' => 'Edit Membership Category',
            'update_item' => 'Update Membership Category',
            'add_new_item' => 'Add New Membership Category',
            'new_item_name' => 'New Membership Category',
            'menu_name' => 'Membership'
        );
        // register taxonomy
        register_taxonomy('membership', 'student', array(
            'hierarchical' => true,
            'labels' => $labels,
            'query_var' => true,
            'show_admin_column' => true
        ));
    }

        /**
     * Registers a Meta Box on our Contact Custom Post Type, called 'Student Details'
     */
    function register_meta_boxes() {
        add_meta_box( 'student-details', 'Student Details', array( $this, 'output_meta_box' ), 'student', 'normal', 'high' );
    }

    /**
     * Output a Contact Details meta box
     *
     * @param WP_Post $post WordPress Post object
     */
    function output_meta_box($post) {

        // Get date of birth
        $dob = get_post_meta( $post->ID, '_student_dob', true );

        // Get email address
        $email = get_post_meta( $post->ID, '_student_email', true );

        // Get phone number
        $phone = get_post_meta( $post->ID, '_student_phone', true );

        $due_date = "11/20/2017";

        // Get status
        $status= get_post_meta( $post->ID, '_student_status', true );

        // Add a nonce field so we can check for it later.
        wp_nonce_field( 'save_student', 'students_nonce' );

        // Output label and field
        echo ( '<div class="field-wrapper">' );
        echo ( '<label for="student_dob">' . __('Date of Birth (MM/DD/YYYY)', 'budo-student') . '</label>' );
        echo ( '<input type="text" name="student_dob" id="student_dob" pattern="\d{1,2}/\d{1,2}/\d{4}" value="' . esc_attr($dob) . '" />' );
        echo ( '</div>' );
        echo ( '<div class="field-wrapper">' );
        echo ( '<label for="student_email">' . __('Email Address', 'budo-student') . '</label>' );
        echo ( '<input type="email" name="student_email" id="student_email" value="' . esc_attr($email) . '" />' );
        echo ( '</div>' );
        echo ( '<div class="field-wrapper">' );
        echo ( '<label for="student_phone">' . __('Phone Number', 'budo-student') . '</label>' );
        echo ( '<input type="tel" name="student_phone" id="student_phone" value="' . esc_attr($phone) . '" />' );
        echo ( '</div>' );
        echo ( '<div class="field-wrapper">' );
        echo ( '<label for="student_due_date">' . __('Due Date', 'budo-student') . '</label>' );
        echo ( '<input type="text" name="student_due_date" id="student_due_date" value="' . esc_attr($due_date) . '" />' );
        echo ( '</div>' );
        echo ( '<div class="field-wrapper">' );
        echo ( '<label for="student_status">' . __('Status', 'budo-student') . '</label>' );
        echo ( '<select name="student_status" id="student_status" selected="' . esc_attr($status) . '">' );
        echo ( '<option value="Not Paid">Not Paid</option>' );
        if(esc_attr($status) == "Paid")
            echo ( '<option value="Paid" selected="selected">Paid</option>' );
        else
            echo ( '<option value="Paid">Paid</option>' );
        echo ( '</select>' );
        echo ( '</div>' );

    }

    /**
     * Saves the meta box field data
     *
     * @param int $post_id Post ID
     */
    function save_meta_boxes($post_id) {

        // Check if our nonce is set.
        if ( !isset( $_POST['students_nonce'] ) ) {
            return $post_id;
        }

        // Verify that the nonce is valid.
        if ( !wp_verify_nonce( $_POST['students_nonce'], 'save_student' ) ) {
            return $post_id;
        }

        // Check this is the Contact Custom Post Type
        if ( 'student' != $_POST['post_type'] ) {
            return $post_id;
        }

        // Check the logged in user has permission to edit this post
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        // OK to save meta data
        $dob = sanitize_text_field( $_POST['student_dob'] );
        update_post_meta( $post_id, '_student_dob', $dob );
        $email = sanitize_text_field( $_POST['student_email'] );
        update_post_meta( $post_id, '_student_email', $email );
        $phone = sanitize_text_field( $_POST['student_phone'] );
        update_post_meta( $post_id, '_student_phone', $this->localize_us_number($phone) );
        $status = sanitize_text_field( $_POST['student_status'] );
        update_post_meta( $post_id, '_student_status', $status );
        $due_date = "11/20/2017";
        update_post_meta( $post_id, '_student_due_date', $due_date );
    }

    function localize_us_number($phone) {
        $numbers_only = preg_replace("/[^\d]/", "", $phone);
        return preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "($1) $2-$3", $numbers_only);
    }

    /**
     * Adds table columns to the Students WP_List_Table
     *
     * @param array $columns Existing Columns
     * @return array New Columns
     */
    function add_table_columns( $columns ) {

        $columns['_student_dob'] = __( 'Date of Birth', 'budo-student' );
        $columns['_student_email'] = __( 'Email Address', 'budo-student' );
        $columns['_student_phone'] = __( 'Phone Number', 'budo-student' );
        $columns['_student_status'] = __( 'Status', 'budo-student' );
        $columns['_student_due_date'] = __( 'Due Date', 'budo-student' );

        return $columns;

    }

    /**
     * Outputs our Student custom field data, based on the column requested
     *
     * @param string $columnName Column Key Name
     * @param int $post_id Post ID
     */
    function output_table_columns_data( $columnName, $post_id ){
        echo get_post_meta( $post_id, $columnName, true );
    }

}

$wpBudo = new WPBudo;