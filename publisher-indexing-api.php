<?php
/**
 * Plugin Name: Publisher Indexing API
 * Plugin URI: https://www.pubindexapi.com/
 * Description: Indexing API for Google News publishers. Index your content in Google faster. Notifies Google in real-time when you publish or update content.
 * Version: 1.1.0
 * Author: PubIndexAPI
 * Author URI: https://www.pubindexapi.com/
 * License: GPL3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Add settings page
function pub_index_api_settings_page() {
  add_options_page(
    'Publisher Indexing API Plugin Settings',
    'Pub Index API',
    'manage_options',
    'pub_index_api_settings',
    'pub_index_api_render_settings_page'
  );
}
add_action( 'admin_menu', 'pub_index_api_settings_page' );

// Render settings page
function pub_index_api_render_settings_page() {
  ?>
  <div class="wrap">
    <h1>Publisher Indexing API Plugin Settings</h1>
    <form action="options.php" method="post">
      <?php
      settings_fields( 'pub_index_api_options' );
      do_settings_sections( 'pub_index_api_settings' );
      submit_button( 'Save Settings' );
      ?>
    </form>
    <br />
    <p>For more information visit <a href="https://pubindexapi.com/">PubIndexAPI.com</a></p>
    <p>To get an API key go to the <a href="https://app.pubindexapi.com/">API dashboard</a></p>
    <p>API <a href="https://pubindexapi.com/terms/">Terms of use</a></p>
  </div>
  <?php
}

// Register settings
function pub_index_api_register_settings() {
  register_setting(
    'pub_index_api_options',
    'pub_index_api_key',
    array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
    )
  );
  register_setting(
    'pub_index_api_options',
    'pub_index_api_post_types',
    array(
      'type' => 'array',
      'sanitize_callback' => 'pub_index_api_sanitize_post_types',
    )
  );
  add_settings_section(
    'pub_index_api_section',
    'Manage API Key and Post Types',
    'pub_index_api_render_settings_section',
    'pub_index_api_settings'
  );
  add_settings_field(
    'pub_index_api_key',
    'API Key',
    'pub_index_api_render_settings_field',
    'pub_index_api_settings',
    'pub_index_api_section',
    array( 'label_for' => 'pub_index_api_key' )
  );
  add_settings_field(
    'pub_index_api_post_types',
    'Post Types',
    'pub_index_api_render_post_types_field',
    'pub_index_api_settings',
    'pub_index_api_section',
    array( 'label_for' => 'pub_index_api_post_types' )
  );
}
add_action( 'admin_init', 'pub_index_api_register_settings' );

// Sanitize post types
function pub_index_api_sanitize_post_types( $post_types ) {
  if ( empty( $post_types ) || ! is_array( $post_types ) ) {
    $post_types = array( 'post' );
  }
  return array_map( 'sanitize_text_field', $post_types );
}

// Render settings section
function pub_index_api_render_settings_section() {
  echo '<p>Enter your API key and select post types to include:</p>';
  pub_index_api_display_feed_urls();
}

// Display feed URLs
function pub_index_api_display_feed_urls() {
  $selected_post_types = get_option( 'pub_index_api_post_types', array() );
  echo '<p>Feeds in use:</p>';
  echo '<ul>';
  foreach ( $selected_post_types as $post_type ) {
    $feed_url = get_bloginfo( 'rss2_url' );
    if ( $post_type !== 'post' ) {
      $feed_url .= '?post_type=' . urlencode( $post_type );
    }
    echo '<li><a href="' . esc_url( $feed_url ) . '">' . esc_html( $feed_url ) . '</a></li>';
  }
  echo '</ul>';
}

// Render settings field
function pub_index_api_render_settings_field() {
  $value = get_option( 'pub_index_api_key' );
  ?>
  <input type="text" id="pub_index_api_key" name="pub_index_api_key" value="<?php echo esc_attr( $value ); ?>">
  <?php
}

// Render post types field
function pub_index_api_render_post_types_field() {
  $selected_post_types = get_option( 'pub_index_api_post_types', array() );
  $post_types = get_post_types( array( 'public' => true ), 'names' );
  foreach ( $post_types as $post_type ) {
    $checked = in_array( $post_type, $selected_post_types ) ? 'checked' : '';
    ?>
    <input type="checkbox" id="pub_index_api_post_types_<?php echo esc_attr( $post_type ); ?>" name="pub_index_api_post_types[]" value="<?php echo esc_attr( $post_type ); ?>" <?php echo $checked; ?>>
    <label for="pub_index_api_post_types_<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( $post_type ); ?></label><br>
    <?php
  }
}

// Send data to API
function pub_index_api_send_data( $post_ID ) {
  $api_key = get_option( 'pub_index_api_key' );
  $selected_post_types = get_option( 'pub_index_api_post_types', array() );
  $post_type = get_post_type( $post_ID );
  
  if ( ! $api_key || ! in_array( $post_type, $selected_post_types ) ) {
    return;
  }
  
  $api_url = 'https://api.pubindex.dev/api/ping';
  $rss_feed_url = get_bloginfo( 'rss2_url' );
  
  if ( $post_type !== 'post' ) {
    $rss_feed_url .= '?post_type=' . urlencode( $post_type );
  }
  
  $request_url = $api_url . '?feed=' . urlencode( $rss_feed_url ) . '&api-key=' . urlencode( $api_key );
  $response = wp_remote_get( $request_url, array(
    'timeout' => 10,
    'headers' => array(
      'Content-Type' => 'application/json',
    ),
  ) );
  
  if ( ! is_wp_error( $response ) && $response['response']['code'] == 200 ) {
  }
}
add_action( 'publish_post', 'pub_index_api_send_data' );
add_action( 'post_updated', 'pub_index_api_send_data' );
