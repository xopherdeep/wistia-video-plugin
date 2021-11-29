<?php
/**
 * @package Wistia Video Plugin
 * @version 0.0.1
 */
/*
  Plugin Name: Wistia Video Plugin
  Plugin URI: http://example.com
  Description: Display a Wistia Video on any page/post via shortcode: [wistia id=]. Anonymous users can only see 1:00 before being prompt to login.
  Author: Xopher Pollard
  Version: 0.0.1
  Author URI: http://github.com/xophz/
*/

/**
 * enqueue_bootstrap
 * Enqueue bootstrap
 *
 * @return void
 */
function wvp_enqueue_bootstrap(){
  wp_register_style("bootstrap", "https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css");
  wp_enqueue_style("bootstrap");

  wp_register_script("bootstrap", "https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js", ['jquery'], null, true);
  wp_enqueue_script("bootstrap");
}
add_action("wp_enqueue_scripts", "wvp_enqueue_bootstrap");

/**
 * ajax_login_init
 * loads login script for anonymous users and registers ajax_login call
 *
 * @return void
 */
function wvp_ajax_login_init(){
  if ( !is_user_logged_in() ) {
    wp_register_script('ajax-login-script', plugin_dir_url( __FILE__ ) . '/assets/js/login.js', ['jquery'] );
    wp_enqueue_script('ajax-login-script');

    wp_localize_script( 'ajax-login-script', 'ajax_login_object',[
        'ajaxurl'        => admin_url( 'admin-ajax.php' ),
        'loadingmessage' => __('Sending user info, please wait...')
    ]);

    // Enable the user with no privileges to run ajax_login() in AJAX
    add_action( 'wp_ajax_nopriv_ajaxlogin', 'wvp_ajax_login' );
  }
}
// Execute the action only if the user isn't logged in
add_action('init', 'wvp_ajax_login_init');

/**
 * ajax_login
 * handler for ajax login, checks nonce before attempting wp_signon
 *
 * @return void
 */
function wvp_ajax_login(){
    // First check the nonce, if it fails the function will break
    check_ajax_referer( 'ajax-login-nonce', 'security' );

    // Nonce is checked, get the POST data and sign user on
    $info = [
      'user_login'    => $_POST['username'],
      'user_password' => $_POST['password'],
      'remember' => true
    ];

    $user = wp_signon( $info, false );
    $is_successful = !is_wp_error( $user );

    if ( $is_successful ){
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        $json = [
          'loggedin' => true,
          'message' => __('Login successful. Enjoy the video!')
        ];
    }else{
        $error_code = array_key_first( $user->errors );
        $error_message = $user->errors[$error_code][0];

        $json = [
          'loggedin' => false,
          'message' =>__( $error_message )
        ];
    }

    echo json_encode($json);
    die();
}

/**
 * wistia_shortcode
 * register shortcode that accepts an id for a video
 *
 * @param  mixed $atts
 * @return void
 */
function wvp_shortcode($atts = []){
  // set up default parameters
  $atts = shortcode_atts([
      'id' => 'a74mrwu4wi'
  ], $atts);

  extract($atts);

  $embed = "<script src='//fast.wistia.com/embed/medias/{$id}.jsonp' async></script>
  <script src='//fast.wistia.com/assets/external/E-v1.js' async></script>
  <div class='wistia_embed wistia_async_{$id} playbar=false' style='height:349px;width:620px'>&nbsp;</div>";

  return $embed;
}
add_shortcode('wistia', 'wvp_shortcode');

function wvp_login_modal(){
  ?>
    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="login" action="login" method="post">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="loginModalLabel">Please login to continue watching...</h5>
            </div>
            <div class="modal-body">
                <p class="status"></p>
                <label for="username">Username</label>
                <input id="username" type="text" name="username">
                <label for="password">Password</label>
                <input id="password" type="password" name="password">
                <!-- <a clss="close" href="">(close)</a> -->
                <?php wp_nonce_field( 'ajax-login-nonce', 'security' ); ?>
            </div>
            <div class="modal-footer">
              <button class="submit_button btn btn-primary" type="submit" value="Login" name="submit">Login</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php
}
add_action('wp_footer', 'wvp_login_modal');