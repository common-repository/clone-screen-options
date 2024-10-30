<?php
/**
 *
 * @link              http://kodeo.io
 * @since             1.0
 * @package           Kodeo
 *
 * @wordpress-plugin
 * Plugin Name:       Clone Screen Options
 * Description:       This plugin will let you clone screen options from one user to another user or user role.
 * Version:           1.1.0
 * Author:            Kodeo
 * Author URI:        http://kodeo.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clone-screen-options
 * Domain Path: 			/languages
 */

final class CloneScreenOptions {
		function load_textdomain() {
			load_plugin_textdomain( 'clone-screen-options', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
		}
		
    function get_screen_options() {
    	global $wp_post_types;
    	$types = array();
    	if ( $wp_post_types ) {
    		foreach ( $wp_post_types AS $key => $value ) {
    			$types[] = $key;
    		}
    		$types[] = 'link';
    		$types[] = 'dashboard';

    		//  some of the items in this array are for compatibily with older WP
    		$metafields = array(
    			'wp_user-settings',
    			'managelink-managercolumnshidden',
    			'manageuploadcolumnshidden',
    			'edit_per_page',
    			'manageeditcolumnshidden',
    			'wp_usersettings',
    			'edit_pages_per_page',
    			'manageeditpagescolumnshidden',
    			'wp_metaboxorder_post',
    			'wp_metaboxorder_page',
    			'wp_metaboxorder_dashboard',
    			'upload_per_page',              // media screen options
    			'wp_media_library_mode',
    			'edit_comments_per_page',       //comments page
    			'manageedit-commentscolumnshidden',
    			'managenav-menuscolumnshidden',      // design->menus
    			'metaboxhidden_nav-menus',
    			'plugins_per_page',             // installed plugins
    			'managepluginscolumnshidden',
    			'users_per_page',               // all users page
    			'manageuserscolumnshidden'
    		);
    		foreach ( $types AS $type ) {
    			$metafields[] = 'metaboxhidden_' . $type;
    			$metafields[] = 'closedpostboxes_' . $type;
    			$metafields[] = 'screen_layout_' . $type;
    			$metafields[] = 'meta-box-order_' . $type;
    			$metafields[] = 'edit_' . $type . '_per_page';
    			$metafields[] = 'manageedit-' . $type . 'columnshidden';
    		}
    		$taxonomies = get_taxonomies();
    		foreach ( $taxonomies AS $tax ) {
    			$metafields[] = 'edit_' . $tax . '_per_page';
    			$metafields[] = 'manageedit-' . $tax . 'columnshidden';
    		}
    		return $metafields;
    	}
    }

    function add_option_page() {
    	$hook = add_management_page ( 'Clone Screen Options', 'Clone Screen Options', 'edit_pages', 'clone_screen_options', array($this, 'plugin_page') );
        add_action( 'admin_print_styles-'.$hook, array($this, 'print_plugin_css') );
    }

    function print_plugin_css() {
        ?><style>
        .clone-screen-options h2 {
        	margin-top: 30px!important;
        	margin-bottom: 20px!important;
        	padding-bottom: 10px!important;
        	border-bottom: 1px solid #CACACA;
        	font-weight: normal;
    			font-size: 23px;
    			line-height: 1.5!important;
        }
        .clone-screen-options form span {
          min-width: 200px;
          display: inline-block;
        }
        .clone-screen-options form select {
          min-width: 200px;
        }
        .clone-screen-options form input[type=button] {
          border: none;
          background: #70BF41;
          color: #FFF;
          padding: 10px 0;
          margin-top: 15px;
          width: 200px;
          text-align:center;
        }
        .clone-screen-options form input[type=button]:hover {
        	background:#7CC550;
        	cursor: pointer;
        }
        .clone-screen-options .notice {
					margin: 20px 0!important;
				}
        </style><?php
    }

    function plugin_page() {
        $source_user = isset($_POST["source_user"]) ? $_POST["source_user"] : null;
        $dest_type = isset($_POST["dest_type"]) ? $_POST["dest_type"] : null;
        $dest_user = isset($_POST["dest_user"]) ? $_POST["dest_user"] : null;

        $dest_role = isset($_POST["dest_role"]) ? $_POST["dest_role"] : null;
        $to_role = isset($_POST["to_role"]) ? $_POST["to_role"] : null;
        $from_user = isset($_POST["from_user"]) ? $_POST["from_user"] : null;

    	if ( $source_user !== null || $from_user !== null ):
    		global $wp_roles;
    		$screen_options_array = $this->get_screen_options();
    		if ( wp_verify_nonce( $_POST["_wpnonce"], 'clone-screen-options' ) ) {
    			// process form data

    			$source_name = get_userdata( $source_user );
    			$dest_name   = "";
    			if ( $dest_type !== null ) {
    				if ( $dest_type == "user" && $dest_user !== null && $source_user !== $dest_user ) {
    					$dest_name = get_userdata( $dest_user );
    					$dest_name = $dest_name->display_name;
    					foreach ( $screen_options_array as $option ) {
    						$value = get_user_meta( $source_user, $option, true );
    						update_user_meta( $dest_user, $option, $value );
    					}
    				} else if ( $dest_type == "role" ) {

    					$dest_name = __( $wp_roles->roles[ $dest_role ]["name"] );
    					$args      = array(
    						"role" => $dest_role,
    						"exclude" => array( $source_user )
    					);
    					$users     = get_users( $args );
    					foreach ( $users as $user ) {
    						foreach ( $screen_options_array as $option ) {
    							$value = get_user_meta( $source_user, $option, true );
    							update_user_meta( $user->ID, $option, $value );
    						}
    					}
    				}
    			}
    			$this->show_complete_notice( $source_name->display_name, $dest_name, $dest_type );

    		} else if ( wp_verify_nonce( $_POST["_wpnonce"], 'set-screen-options' ) ) {
    			foreach ( $screen_options_array as $meta_key ) {
    				$val = get_user_meta( $from_user, $meta_key, true );
    				if (!empty($val)) update_option( $to_role . "_kodeo_screen_options_" . $meta_key, $val );
    			}
    			$val = get_option( "clone_screen_options_roles" );
    			if ( ! $val ) {
    				$val = array();
    			}
    			$val[] = $to_role;
    			update_option( "clone_screen_options_roles", $val );
    			$from_user_data = get_userdata( $from_user ); ?>
    			<div class="notice notice-success is-dismissible">
    				<p>
    					<?php
    					printf( __( 'Done! Applied screen options from user "%s" as default options for role "%s"', 'clone-screen-options' ), $from_user_data->display_name, $wp_roles->roles[ $to_role ]["name"] );
    					?>
    				</p>
    			</div>
    		<?php } else {
    			die( "Security check failed!" );
    		}
    	endif;

    	?>
    	<div class="wrap clone-screen-options">
    		<div id="icon-tools" class="icon32"><br/></div>
    		<h2>Clone Screen Options</h2>

    		<form id="clone-screen-options" action="" method="post">
    			<?php wp_nonce_field( 'clone-screen-options' ); ?>
    			<span>
    			<?php _e( "Clone screen options from user", "clone-screen-options" ) ?>
    			</span>

    			<?php wp_dropdown_users( 'who=authors&name=source_user&selected=' . $source_user ); ?>
    			<p>
    				<span>
    					<input type="radio" <?=($dest_type == "user" ? "checked" : "")?> name="dest_type" value="user" id="type_user">
    					<label for="type_user"><?php _e( "to user", "clone-screen-options" ); ?></label>
    				</span>
    				<?php wp_dropdown_users( 'who=authors&name=dest_user&selected=' . $dest_user ); ?>
    			</p>
    			<p>
    				<span>
    					<input type="radio" <?=($dest_type == "role" ? "checked" : "")?> name="dest_type" value="role" id="type_role">
    					<label for="type_role"><?php _e( "to all users with role", "clone-screen-options" ); ?></label>
    				</span>
    				<select name="dest_role" id="dest_role">
    				<?php wp_dropdown_roles( $dest_role ) ?>
    				</select>
    			</p>
    			<input type="button" id="clone_options" value="<?php _e( "Clone options", 'clone-screen-options' ); ?>"
    			       name="clone_options"/>
    		</form>
				<br>
    		<h2>Set default options</h2>

    		<form id="set-screen-options" action="" method="post">
    			<?php wp_nonce_field( 'set-screen-options' ); ?>
    			<p>
    				<span><?php _e( "Apply screen options from user", "clone-screen-options" ) ?></span>
    				<?php wp_dropdown_users( 'who=authors&name=from_user&selected=' . $from_user ); ?>
    			</p>
    			<p>
    				<span><?php _e( "as default options for role", "clone-screen-options" ) ?></span>
    				<select name="to_role" id="to_role">
    					<?php wp_dropdown_roles( $to_role ) ?>
    				</select>
    			</p>
    			<input type="button" id="set_options" value="<?php _e( "Set options", 'clone-screen-options' ); ?>"
    			       name="set_default_options"/>
    		</form>

    	</div>
    	<script>
    		jQuery(function ($) {
    			$("#dest_user").click(function () {
    				$("#type_user").prop("checked", true);
    			});
    			$("#dest_role").click(function () {
    				$("#type_role").prop("checked", true);
    			});
    			$("#clone_options").click(function () {
    				if ($("form#clone-screen-options input[name=dest_type]:checked").length == 0) {
    					alert("<?php _e("You must choose type of destination", "clone-screen-options")?>");
    					return;
    				}

    				if (confirm("<?php _e("Do you really want to do this?", "clone-screen-options")?>") == true) {
    					$("form#clone-screen-options").submit();
    				}

    			});

    			$("#set_options").click(function () {
    				if (confirm("<?php _e("Do you really want to do this?", "clone-screen-options")?>") == true) {
    					$("form#set-screen-options").submit();
    				}
    			});
    		});
    	</script>
    	<?php
    }

    function show_complete_notice( $source, $dest, $type ) {
    	?>
    	<div class="notice notice-success is-dismissible">
    		<p>
    			<?php
    			printf( __( 'Done! Cloned screen options from user "%s" to ', 'clone-screen-options' ), $source );
    			if ( $type == 'user' ) {
    				printf( __( 'user "%s".', 'clone-screen-options' ), $dest );
    			} else if ( $type == 'role' ) {
    				printf( __( 'all users with role "%s".', 'clone-screen-options' ), $dest );
    			}
    			?>
    		</p>
    	</div>
    	<?php
    }

    /*
    When new user is registered he gets all the stored Screen Options
    */
    function set_default_options( $user_ID ) {
    	$user_object = new WP_User( $user_ID );
    	$roles       = $user_object->roles;
    	$role        = array_shift( $roles );
    	$cuo_roles = get_option( "clone_screen_options_roles" );
    	if ( !is_array($cuo_roles) ) return;
    	if ( !in_array($role, $cuo_roles)  ) {
    		return;
    	}

    	$options_array = $this->get_screen_options();

    	foreach ( $options_array as $meta_key ) {
    		$value = get_option( $role.'_kodeo_screen_options_' . $meta_key );
    		if ( $value ) {
    			update_user_meta( $user_ID, $meta_key, $value );
    		}
    	}

    	return;
    }
}

$cuo = new CloneScreenOptions();
add_action( 'plugins_loaded', array($cuo, 'load_textdomain') );
add_action( 'admin_menu', array($cuo, 'add_option_page') );
add_action( 'user_register', array($cuo, 'set_default_options') );
