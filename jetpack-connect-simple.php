<?php

use Automattic\Jetpack\Config;
use Automattic\Jetpack\Connection\Manager;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://automattic.com
 * @since             1.0.0
 * @package           Client_Example
 *
 * @wordpress-plugin
 * Plugin Name:       Jetpack Connect Simple
 * Plugin URI:        https://jetpack.com
 * Description:       Just the connection package at it's most MVP code.
 * Version:           1.0.0
 * Author:            Automattic
 * Author URI:        https://automattic.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jetpack-connect-simple
 * Domain Path:       /languages
 */

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';
function jpcs_load_plugin() {

	// Here we enable the Jetpack packages.
	$config = new Config();
	$config->ensure( 'connection' );
}

add_action( 'plugins_loaded', 'jpcs_load_plugin', 1 );

add_action( 'admin_post_register_site', 'jpcs_register_site' );
add_action( 'admin_post_connect_user', 'jpcs_connect_user' );
add_action( 'admin_post_disconnect_user', 'jpcs_disconnect_user' );
add_action( 'admin_post_disconnect_site', 'jpcs_disconnect_site' );

add_filter( 'jetpack_connection_secret_generator', function( $callable ) {
	return function() {
		return wp_generate_password( 32, false );
	};
} );

/**
 * Registers the site using the connection package.
 */
function jpcs_register_site() {
	check_admin_referer( 'register-site' );
	( new Manager() )->register();

	if ( wp_get_referer() ) {
		wp_safe_redirect( wp_get_referer() );
	} else {
		wp_safe_redirect( get_home_url() );
	}
}

/**
 * Connects the currently logged in user.
 */
function jpcs_connect_user() {
	check_admin_referer( 'connect-user' );
	( new Manager() )->connect_user();

	if ( wp_get_referer() ) {
		wp_safe_redirect( wp_get_referer() );
	} else {
		wp_safe_redirect( get_home_url() );
	}
}

/**
 * Disconnects the currently logged in user.
 */
function jpcs_disconnect_user() {
	check_admin_referer( 'disconnect-user' );
	( new Manager() )->disconnect_user( get_current_user_id() );

	if ( wp_get_referer() ) {
		wp_safe_redirect( wp_get_referer() );
	} else {
		wp_safe_redirect( get_home_url() );
	}
}

/**
 * Disconnects the site.
 */
function jpcs_disconnect_site() {
	check_admin_referer( 'disconnect-site' );
	( new Manager() )->disconnect_site_wpcom();
	( new Manager() )->delete_all_connection_tokens();

	if ( wp_get_referer() ) {
		wp_safe_redirect( wp_get_referer() );
	} else {
		wp_safe_redirect( get_home_url() );
	}
}

add_action( 'admin_menu', 'jpcs_register_admin_page', 1 );
function jpcs_register_admin_page() {
	add_menu_page( 'Jetpack Connect', 'Jetpack Connect', 'manage_options', 'jetpack-connect-simple', 'jpcs_render_main_page' );
}

function jpcs_render_main_page() {
	/**
	 * Provide a admin area view for the plugin
	 *
	 * This file is used to markup the admin-facing aspects of the plugin.
	 *
	 * @link       https://automattic.com
	 * @since      1.0.0
	 *
	 * @package    Client_Example
	 * @subpackage Client_Example/admin/partials
	 */

	$user_token = ( new Manager() )->get_access_token( get_current_user_id() );
	$blog_token = ( new Manager() )->get_access_token();
	$auth_url = ( new Manager() )->get_authorization_url( null, admin_url( '?page=jetpack-connect-simple' ) );
	?>

	<!-- This file should primarily consist of HTML with a little bit of PHP. -->

	<h1>Jetpack Connection Package</h1>

	<p>This page shows you debugging data. <strong>Keep in mind that this data is sensitive, do not share it without cleaning up the token values first.</strong></p>

	<h2>Site Registration / Blog token</h2>
	<hr />
	<p>This is the first step and prerequisite for any Jetpack connection. "Registering" the site basically means creating a blog token,
		and "registering" the site with wpcom. It is required before any user authentication can proceed.</p>

	<pre>
		<?php

echo htmlspecialchars( '<form action="/wp-admin/admin-post.php" method="post">
	<input type="hidden" name="action" value="register_site">
	<?php wp_nonce_field( \'register-site\' ); ?>
	<input type="submit" value="Register this site" class="button button-primary">
</form>'
);
echo "<br /><br />";
echo htmlspecialchars(
"add_action( 'admin_post_register_site', 'jpcs_register_site' );
function jpcs_register_site() {
	check_admin_referer( 'register-site' );
	( new Manager() )->register();

	if ( wp_get_referer() ) {
		wp_safe_redirect( wp_get_referer() );
	} else {
		wp_safe_redirect( get_home_url() );
	}
}"
);




			?>


	</pre>

	<strong>Current site registration status: </strong>

	<?php if ( ! $blog_token ) : ?>
		<p>Unregistered :(</p>
		<form action="/wp-admin/admin-post.php" method="post">
			<input type="hidden" name="action" value="register_site">
			<?php wp_nonce_field( 'register-site' ); ?>
			<input type="submit" value="Register this site" class="button button-primary">
		</form>
	<?php else: ?>
		<p>Woohoo! This site is registered with wpcom, and has a functioning blog token for authenticated site requests!
			You should be able to see the token value in the Private Options dump lower in this page.</p>

		<form action="/wp-admin/admin-post.php" method="post">
			<strong>Disconnect / deregister</strong>
			<p>Now that the site is registered, you may de-register (disconnect) it! Be weary though,
				it will also delete any and all user tokens with it, since those rely on the blog token too!</p>
			<input type="hidden" name="action" value="disconnect_site">
			<?php wp_nonce_field( 'disconnect-site' ); ?>
			<input type="submit" value="Disconnect site" class="button">
		</form>
	<?php endif; ?>

	<br>
	<h2>User auth / user token creation.</h2>
	<hr />
	<?php if ( $blog_token ) : ?>
		<p>Now that we have a registered site, we can authenticate users!</p>

		<?php if ( $user_token ) : ?>
			<form action="/wp-admin/admin-post.php" method="post">
				<p>Awesome! You are connected as an authenticated user! You even have your own token! much wow. Now you may destroy it :)</p>
				<p><strong>Unless...</strong> you are also the "master user", in which case it will fail (we could use some error handling instead)</p>
				<input type="hidden" name="action" value="disconnect_user">
				<?php wp_nonce_field( 'disconnect-user' ); ?>
				<input type="submit" value="Disconnect current user" class="button">
			</form>
		<?php else: ?>
			<form action="/wp-admin/admin-post.php" method="post">
				<input type="hidden" name="action" value="connect_user">
				<?php wp_nonce_field( 'connect-user' ); ?>
				<input type="submit" value="Authorize current user" class="button button-primary">
				<label for="connect_user">Classic flow through wp.com</label>
			</form>

			<br>
			<p>OR! You can try this fancy in-place authorize flow in an iframe. But remember, you need to register the site first.</p>
			<iframe
				class="jp-jetpack-connect__iframe"
				style="
						width: 100%;
						background: white;
						height: 250px;
						padding-top: 30px;
					"
			/></iframe>
		<?php endif; ?>
	<?php else: ?>
		<p><strong>Wait! Before we do any user authentication, we need to register the site above!</strong></p>
	<?php endif; ?>

	<br>
	<h2>Jetpack options dump</h2>
	<hr />

	<p>When a Jetpack-powered site is registered, it should be assigned an ID, which should be present in the list below.</p>

	<pre>
	<?php print_r( get_option( 'jetpack_options', array() ) ); ?>
	</pre>

	<h2>Jetpack private options dump</h2>
	<p>Even though Jetpack is not installed on your site, the dump below should display the blog_token for your site if you have pressed the Register button. </p>
	<pre>
	<?php print_r( get_option( 'jetpack_private_options', array() ) ); ?>
	</pre>

	<script type="application/javascript">
		jQuery( function( $ ) {
			var authorize_url = <?php echo wp_json_encode( $auth_url ); ?>;
			$( '.jp-jetpack-connect__iframe' ).attr( 'src', authorize_url );

			window.addEventListener('message', (event) => {
				if ( 'close' === event.data ) {
					location.reload(true);
				}
			} );
		} );
	</script>

<?php }