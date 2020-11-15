<?php
/**
 * Plugin Name: YT WP Books API
 * Plugin URI: http://omukiguy.com
 * Author: TechiePress
 * Author URI: http://omukiguy.com
 * Description: Plugin brings in the books API from the New York Times
 * Version: 0.1.0
 * License: GPL2
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: yt-wp-books-api
*/

defined( 'ABSPATH' ) or die;

add_action( 'admin_menu', 'techiepress_add_menu_page' );

function techiepress_add_menu_page() {
    add_menu_page(
        'NY Best sellers',
        'NY Best sellers',
        'manage_options',
        'yt-wp-books-api.php',
        'run_all_the_code_functions',
        'dashicons-book',
        16,
    );
}

function run_all_the_code_functions() {
    
    if ( false === get_option( 'yt_wp_books_info' ) ) {

        // Get all the api books.
        $info_books = get_books_api();
        
        // Save API call as a Transient.
        add_option( 'yt_wp_books_info', $info_books );

        return;
    }

    // Custom Tables
    if ( false === get_option( 'yt_wp_table_version' ) ) {
        create_database_table();
    }

    // Get the info stored in the database.
    save_database_table_info();

}

function get_books_api() {

    $key    = 'gKGrFc'; // Add your own key value
    
    $url = "https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json?api-key=$key&offset=20";

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body'    => array(),
    );

    $response = wp_remote_get( $url, $args );

    $response_code = wp_remote_retrieve_response_code( $response );
    $body         = wp_remote_retrieve_body( $response );

    var_dump($response);

    if ( 401 === $response_code ) {
        return "Unauthorized access";
    }

    if ( 200 !== $response_code ) {
        return "Error in pinging API";
    }

    if ( 200 === $response_code ) {
        return $body;
    }

}

function create_database_table() {
    
    global $yt_wp_table_version;
    global $wpdb;

    $yt_wp_table_version = '1.0.0';

    $table_name = $wpdb->prefix . 'yt_wp_table_version';

    $charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		title text(39),
		bookDescription text(116),
		contributor text(20),
		author text(20),
		price int(20),
		publisher text(20),
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

    // Save API call as a Transient.
    add_option( 'yt_wp_table_version', $yt_wp_table_version );
}


function save_database_table_info() {

    global $wpdb;
	
	$table_name = $wpdb->prefix . 'yt_wp_table_version';
    
    $results = json_decode( get_option( 'yt_wp_books_info' ) )->results;

    foreach( $results as $result ) {

        $wpdb->insert( 
            $table_name, 
            array( 
                'time'            => current_time( 'mysql' ), 
                'title'           => $result->title,
                'bookDescription' => $result->description,
                'contributor'     => $result->contributor,
                'author'          => $result->author,
                'price'           => $result->price,
                'publisher'       => $result->publisher,
            ) 
        );

    }

}