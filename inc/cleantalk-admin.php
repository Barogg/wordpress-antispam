<?php

use Cleantalk\ApbctWP\CleantalkSettingsTemplates;

require_once('cleantalk-settings.php');

// Add buttons to comments list table
add_action( 'manage_comments_nav', 'apbct_add_buttons_to_comments_and_users', 10, 1 );
add_action( 'manage_users_extra_tablenav', 'apbct_add_buttons_to_comments_and_users', 10, 1 );

// Check renew banner
add_action( 'wp_ajax_apbct_settings__check_renew_banner', 'apbct_settings__check_renew_banner');

// Crunch for Anti-Bot
add_action( 'admin_head','apbct_admin_set_cookie_for_anti_bot' );

function apbct_admin_set_cookie_for_anti_bot(){
	global $apbct;
	echo '<script ' . ( class_exists('Cookiebot_WP') ? 'data-cookieconsent="ignore"' : '' ) . '>var ctSecure = location.protocol === "https:" ? "; secure" : ""; document.cookie = "wordpress_apbct_antibot=' . hash( 'sha256', $apbct->api_key . $apbct->data['salt'] ) . '; path=/; expires=0; samesite=lax" + ctSecure;</script>';
}

function apbct_add_buttons_to_comments_and_users( $unused_argument ) {

    global $apbct;
    $current_screen = get_current_screen();

    if( 'users' == $current_screen->base ) {
        $button_url__check = $current_screen->base . '.php?page=ct_check_users';
        $button_description = 'users';
    } elseif ( 'edit-comments' == $current_screen->base ) {
        $button_url__check = $current_screen->base . '.php?page=ct_check_spam';
        $button_description = 'comments';
    } else {
        return;
    }

    echo '
    <a href="' . $button_url__check . '" class="button" style="margin:1px 0 0 0; display: inline-block;">
        <img src="' . $apbct->logo__small__colored . '" alt="Cleantalk Antispam logo"  height="" style="width: 17px; vertical-align: text-bottom;" />
        ' . sprintf(__( 'Find spam %s', 'cleantalk-spam-protect'), $button_description ) . '
    </a>
    ';

}

//Adding widget
function ct_dashboard_statistics_widget() {
	
	global $apbct;
	
	if(apbct_is_user_role_in(array('administrator'))){
		wp_add_dashboard_widget(
			'ct_dashboard_statistics_widget',
			$apbct->plugin_name,
			'ct_dashboard_statistics_widget_output'
		);
	}
}

// Outputs statistics widget content
function ct_dashboard_statistics_widget_output( $post, $callback_args ) {

	global $apbct, $current_user;
	
	echo "<div id='ct_widget_wrapper'>";
?>
        <div class='ct_widget_top_links'>
            <img src="<?php echo plugins_url('/cleantalk-spam-protect/inc/images/preloader.gif'); ?>" class='ct_preloader'>
            <?php echo sprintf(__("%sRefresh%s", 'cleantalk-spam-protect'),    "<a href='#ct_widget' class='ct_widget_refresh_link'>", "</a>"); ?>
            <?php echo sprintf(__("%sConfigure%s", 'cleantalk-spam-protect'), "<a href='{$apbct->settings_link}' class='ct_widget_settings_link'>", "</a>"); ?>
        </div>
        <form id='ct_refresh_form' method='POST' action='#ct_widget'>
			<input type='hidden' name='ct_brief_refresh' value='1'>
		</form>
		<h4 class='ct_widget_block_header' style='margin-left: 12px;'><?php _e('7 days anti-spam stats', 'cleantalk-spam-protect'); ?></h4>
		<div class='ct_widget_block ct_widget_chart_wrapper'>
			<div id='ct_widget_chart'></div>
		</div>
		<h4 class='ct_widget_block_header'><?php _e('Top 5 spam IPs blocked', 'cleantalk-spam-protect'); ?></h4>
		<hr class='ct_widget_hr'>
<?php	
	if(!apbct_api_key__is_correct() || (isset($apbct->data['brief_data']['error_no']) && $apbct->data['brief_data']['error_no'] == 6)){
?>		<div class='ct_widget_block'>
			<form action='<? echo $apbct->settings_link; ?>' method='POST'>
				<h2 class='ct_widget_activate_header'><?php _e('Get Access key to activate Anti-Spam protection!', 'cleantalk-spam-protect'); ?></h2>
				<input class='ct_widget_button ct_widget_activate_button' type='submit' name='get_apikey_auto' value='ACTIVATE' />
			</form>
		</div>
<?php
	}elseif(!empty($apbct->data['brief_data']['error'])){
		echo '<div class="ct_widget_block">'
			.'<h2 class="ct_widget_activate_header">'
				.sprintf(__('Something went wrong! Error: "%s".', 'cleantalk-spam-protect'), "<u>{$apbct->brief_data['error']}</u>")
			.'</h2>';
			if($apbct->user_token && !$apbct->white_label){
				echo '<h2 class="ct_widget_activate_header">'
					.__('Please, visit your dashboard.', 'cleantalk-spam-protect')
				.'</h2>'
				.'<a target="_blank" href="https://cleantalk.org/my?user_token='.$apbct->user_token.'&cp_mode=antispam">'
					.'<input class="ct_widget_button ct_widget_activate_button ct_widget_resolve_button" type="button" value="VISIT CONTROL PANEL">'
				.'</a>';
			}
		echo '</div>';
	}
	
	if(apbct_api_key__is_correct() && empty($apbct->data['brief_data']['error'])){
?>
		<div class='ct_widget_block'>
			<table cellspacing="0">
				<tr>
					<th><?php _e('IP', 'cleantalk-spam-protect'); ?></th>
					<th><?php _e('Country', 'cleantalk-spam-protect'); ?></th>
					<th><?php _e('Block Count', 'cleantalk-spam-protect'); ?></th>
				</tr>
<?php			foreach($apbct->brief_data['top5_spam_ip'] as $val){ ?>				
					<tr>
						<td><?php echo $val[0]; ?></td>

						<td class="ct_widget_block__country_cell">
                            <?php echo $val[1] ? "<img src='" . APBCT_URL_PATH . "/inc/images/flags/".strtolower( isset( $val[1]['country_code'] ) ? $val[1]['country_code'] : 'a1' ).".png'>" : ''; ?>
                            <?php echo isset( $val[1]['country_name'] ) ? $val[1]['country_name'] : 'Unknown'; ?>
                        </td>

						<td style='text-align: center;'><?php echo $val[2]; ?></td>
					</tr>
<?php			} ?>
			</table>
<?php		if($apbct->user_token){ ?>
				<a target='_blank' href='https://cleantalk.org/my?user_token=<?php echo $apbct->user_token; ?>&cp_mode=antispam'>
					<input class='ct_widget_button' id='ct_widget_button_view_all' type='button' value='View all'>
				</a>
<?php		} ?>
		</div>

<?php
	}
	// Notice at the bottom
	if(isset($current_user) && in_array('administrator', $current_user->roles)){
		
		if($apbct->spam_count && $apbct->spam_count > 0){
			echo '<div class="ct_widget_wprapper_total_blocked">'
				.'<img src="'.$apbct->logo__small__colored.'" class="ct_widget_small_logo"/>'
				.'<span title="'.sprintf(__('This is the count from the %s\'s cloud and could be different to admin bar counters', 'cleantalk-spam-protect').'">', $apbct->plugin_name)
					.sprintf(
						/* translators: %s: Number of spam messages */
						__( '%s%s%s has blocked %s spam for all time. The statistics are automatically updated every 24 hours.', 'cleantalk-spam-protect'),
						!$apbct->white_label ? '<a href="https://cleantalk.org/my/?user_token='.$apbct->user_token.'&utm_source=wp-backend&utm_medium=dashboard_widget&cp_mode=antispam" target="_blank">' : '',
						$apbct->plugin_name,
						!$apbct->white_label ? '</a>' : '',
						number_format($apbct->data['spam_count'], 0, ',', ' ')
					)
				.'</span>'
				.(!$apbct->white_label
					? '<br><br>'
				.'<b style="font-size: 16px;">'
					.sprintf(
						__('Do you like CleanTalk? %sPost your feedback here%s.', 'cleantalk-spam-protect'),
						'<u><a href="https://wordpress.org/support/plugin/cleantalk-spam-protect/reviews/#new-post" target="_blank">',
						'</a></u>'
					)
				.'</b>'
					: ''
				)
			.'</div>';
		}
	}
	echo '</div>';
}

/**
 * Admin action 'admin_init' - Add the admin settings and such
 */
function apbct_admin__init(){
 
	global $apbct, $spbc;
    
    // Admin bar
    
    $apbct->admin_bar_enabled =  $apbct->settings['admin_bar__show'] &&
        current_user_can( 'activate_plugins' ) &&
        apbct_api_key__is_correct() !== false;
    
//    ( defined( 'CLEANTALK_SHOW_ADMIN_BAR_FORCE' ) && CLEANTALK_SHOW_ADMIN_BAR_FORCE ) &&
    if( $apbct->admin_bar_enabled ){
        if(
            ! has_action('admin_bar_menu', 'apbct_admin__admin_bar__add_structure' ) &&
            ! has_action('admin_bar_menu',  'spbc_admin__admin_bar__add_structure' )
        ){
            add_action( 'admin_bar_menu', 'apbct_admin__admin_bar__add_structure', 999 );
        }
        
        add_action( 'cleantalk_admin_bar__prepare_counters',        'apbct_admin__admin_bar__prepare_counters' );
        // Temporary disable the icon
        //add_filter( 'cleantalk_admin_bar__parent_node__before', 'apbct_admin__admin_bar__add_parent_icon', 10, 1 );
        add_filter( 'cleantalk_admin_bar__parent_node__after', 'apbct_admin__admin_bar__add_counter', 10, 1 );
        
        add_action( 'admin_bar_menu',                               'apbct_admin__admin_bar__add_child_nodes', 1000 );
        if( ! $spbc ){
            add_filter( 'admin_bar_menu', 'apbct_spbc_admin__admin_bar__add_child_nodes', 1001 );
        }
    }
    
	
	// Getting dashboard widget statistics
    if(!empty($_POST['ct_brief_refresh'])){
	    cleantalk_get_brief_data( $apbct->api_key );
	}

	// Getting key like hoster. Only once!
    if(!is_main_site() && $apbct->white_label && ( empty($apbct->api_key) || $apbct->settings['apikey'] == $apbct->network_settings['apikey'] ) ){
	    $res = apbct_settings__get_key_auto( true );
	    if( isset( $res['auth_key'], $res['user_token'] ) ) {
		    $settings = apbct_settings__validate(array(
			    'apikey' => $res['auth_key'],
            ));
		    $apbct->api_key = $settings['apikey'];
		    $apbct->save('settings');
        }
    }

	// Settings
	add_action('wp_ajax_apbct_settings__get__long_description', 'apbct_settings__get__long_description'); // Long description

	add_action( 'wp_ajax_apbct_sync', 'apbct_settings__sync' );

	add_action( 'wp_ajax_apbct_get_key_auto', 'apbct_settings__get_key_auto' );

	// Settings Templates
    if( ! is_multisite() || is_main_site() || ( ! is_main_site() && $apbct->network_settings['multisite__allow_custom_settings'] ) ) {
	    new CleantalkSettingsTemplates( $apbct->api_key );
    }
    
    // Check compatibility
    do_action( 'apbct__check_compatibility' );
    
}

/**
 * Manage links in plugins list
 * @return array
*/
function apbct_admin__plugin_action_links($links, $file) {
	
	global $apbct;
	
	$settings_link = '<a href="' . $apbct->settings_link . '">' . __( 'Settings' ) . '</a>';
		
	array_unshift( $links, $settings_link ); // before other links
	return $links;
}

/**
 * Manage links and plugins page
 * @return array
*/
function apbct_admin__register_plugin_links($links, $file){
	
	global $apbct;
	
	//Return if it's not our plugin
	if ($file != $apbct->base_name)
		return $links;
		
	if($apbct->white_label){
		$links = array_slice($links, 0, 1);
		$links[] = "<script " . ( class_exists('Cookiebot_WP') ? 'data-cookieconsent="ignore"' : '' ) . ">jQuery('.plugin-title strong').each(function(i, item){
		if(jQuery(item).html() == 'Anti-Spam by CleanTalk')
			jQuery(item).html('{$apbct->plugin_name}');
		});</script>";
		return $links;
	}
	
	if(substr(get_locale(), 0, 2) != 'en')
		$links[] = '<a class="ct_meta_links ct_translate_links" href="'
				.sprintf('https://translate.wordpress.org/locale/%s/default/wp-plugins/cleantalk-spam-protect', substr(get_locale(), 0, 2))
				.'" target="_blank">'
				.__('Translate', 'cleantalk-spam-protect')
			.'</a>';
			
	$links[] = '<a class="ct_meta_links" href="'.$apbct->settings_link.'" target="_blank">' . __( 'Start here','cleantalk-spam-protect') . '</a>';
	$links[] = '<a class="ct_meta_links ct_faq_links" href="https://wordpress.org/plugins/cleantalk-spam-protect/faq/" target="_blank">' . __( 'FAQ','cleantalk-spam-protect') . '</a>';
	$links[] = '<a class="ct_meta_links ct_support_links"href="https://wordpress.org/support/plugin/cleantalk-spam-protect" target="_blank">' . __( 'Support','cleantalk-spam-protect') . '</a>';
	$trial = apbct_admin__badge__get_premium(false);
	if(!empty($trial))
		$links[] = apbct_admin__badge__get_premium(false);
	
	return $links;
}

/**
 * Admin action 'admin_enqueue_scripts' - Enqueue admin script of reloading admin page after needed AJAX events
 * @param 	string $hook URL of hooked page
 */
function apbct_admin__enqueue_scripts($hook){
	
	global $apbct;
	
	// Scripts to all admin pages
	wp_enqueue_script('ct_admin_js_notices', plugins_url('/cleantalk-spam-protect/js/cleantalk-admin.min.js'),   array(), APBCT_VERSION);
	wp_enqueue_style ('ct_admin_css',        plugins_url('/cleantalk-spam-protect/css/cleantalk-admin.min.css'), array(), APBCT_VERSION, 'all');

	wp_localize_script( 'ct_admin_js_notices', 'ctAdminCommon', array(
		'_ajax_nonce'         => wp_create_nonce( 'ct_secret_nonce' ),
		'_ajax_url'           => admin_url( 'admin-ajax.php', 'relative' ),
		'plugin_name'        => $apbct->plugin_name,
		'logo'               => '<img src="' . $apbct->logo . '" alt=""  height="" style="width: 17px; vertical-align: text-bottom;" />',
		'logo_small'         => '<img src="' . $apbct->logo__small . '" alt=""  height="" style="width: 17px; vertical-align: text-bottom;" />',
		'logo_small_colored' => '<img src="' . $apbct->logo__small__colored . '" alt=""  height="" style="width: 17px; vertical-align: text-bottom;" />',
	) );
	
	// DASHBOARD page JavaScript and CSS
	if($hook == 'index.php' && apbct_is_user_role_in(array('administrator'))){
		
		wp_enqueue_style('ct_admin_css_widget_dashboard',     plugins_url('/cleantalk-spam-protect/css/cleantalk-dashboard-widget.min.css'), array(), APBCT_VERSION, 'all');
	    wp_enqueue_style ('ct_icons',                         plugins_url('/cleantalk-spam-protect/css/cleantalk-icons.min.css'), array(),            APBCT_VERSION, 'all');
		
		wp_enqueue_script('ct_gstatic_charts_loader',         plugins_url('/cleantalk-spam-protect/js/cleantalk-dashboard-widget--google-charts.min.js'), array(),              APBCT_VERSION);
		wp_enqueue_script('ct_admin_js_widget_dashboard', 	  plugins_url('/cleantalk-spam-protect/js/cleantalk-dashboard-widget.min.js'),   array('ct_gstatic_charts_loader'), APBCT_VERSION);
		
		// Preparing widget data
		// Parsing brief data 'spam_stat' {"yyyy-mm-dd": spam_count, "yyyy-mm-dd": spam_count} to [["yyyy-mm-dd", "spam_count"], ["yyyy-mm-dd", "spam_count"]]
		$to_chart = array();
		
		// Crunch. Response contains error.
		if(!empty($apbct->data['brief_data']['error']))
			$apbct->data['brief_data'] = array_merge($apbct->data['brief_data'], $apbct->def_data['brief_data']);
		
		if (isset($apbct->data['brief_data']['spam_stat']) && is_array($apbct->data['brief_data']['spam_stat'])) {
			foreach( $apbct->data['brief_data']['spam_stat'] as $key => $value ){
				$to_chart[] = array( $key, $value );
			} unset( $key, $value );
		}
		
		wp_localize_script( 'ct_admin_js_widget_dashboard', 'apbctDashboardWidget', array(
			'data' => $to_chart,
		));
	}
	
	// SETTINGS's page JavaScript and CSS
	if( $hook == 'settings_page_cleantalk' ){
		
		// jQueryUI
		wp_enqueue_script('jqueryui',    plugins_url('/cleantalk-spam-protect/js/jquery-ui.min.js'),  array('jquery'), '1.12.1'       );
		wp_enqueue_style('jqueryui_css', plugins_url('/cleantalk-spam-protect/css/jquery-ui.min.css'),array(),         '1.21.1', 'all');
		
		wp_enqueue_script('cleantalk_admin_js_settings_page', plugins_url('/cleantalk-spam-protect/js/cleantalk-admin-settings-page.min.js'),   array(),     APBCT_VERSION);
		wp_enqueue_style('cleantalk_admin_css_settings_page', plugins_url('/cleantalk-spam-protect/css/cleantalk-admin-settings-page.min.css'), array(),     APBCT_VERSION, 'all');
	    wp_enqueue_style ('ct_icons',                         plugins_url('/cleantalk-spam-protect/css/cleantalk-icons.min.css'), array(),                   APBCT_VERSION, 'all');
		
		wp_localize_script( 'cleantalk_admin_js_settings_page', 'ctSettingsPage', array(
			'ct_subtitle'   => $apbct->ip_license ? __('Hosting AntiSpam', 'cleantalk-spam-protect') : '',
			'ip_license'    => $apbct->ip_license ? true : false,
            'key_changed'   => ! empty( $apbct->data['key_changed'] ) ? true : false,
		));

		wp_enqueue_script('cleantalk-modal', plugins_url( '/cleantalk-spam-protect/js/cleantalk-modal.min.js' ),   array(),     APBCT_VERSION);
	}

    // COMMENTS page JavaScript
    if($hook == 'edit-comments.php'){
        wp_enqueue_script('ct_comments_editscreen', plugins_url('/cleantalk-spam-protect/js/cleantalk-comments-editscreen.min.js'), array(), APBCT_VERSION);
        wp_localize_script( 'ct_comments_editscreen', 'ctCommentsScreen', array(
            'ct_ajax_nonce'               => wp_create_nonce('ct_secret_nonce'),
            'spambutton_text'             => __("Find spam comments", 'cleantalk-spam-protect'),
            'ct_feedback_msg_whitelisted' => __("The sender has been whitelisted.", 'cleantalk-spam-protect'),
            'ct_feedback_msg_blacklisted' => __("The sender has been blacklisted.", 'cleantalk-spam-protect'),
            'ct_feedback_msg'             => sprintf(__("Feedback has been sent to %sCleanTalk Dashboard%s.", 'cleantalk-spam-protect'), $apbct->user_token ? "<a target='_blank' href=https://cleantalk.org/my?user_token={$apbct->user_token}&cp_mode=antispam>" : '', $apbct->user_token ? "</a>" : ''),
            'ct_show_check_links'		  => (bool)$apbct->settings['comments__show_check_links'],
            'ct_img_src_new_tab'          => plugin_dir_url(__FILE__)."images/new_window.gif",
        ));
    }

    // USERS page JavaScript
    if($hook == 'users.php'){
        wp_enqueue_style ('ct_icons',                plugins_url('/cleantalk-spam-protect/css/cleantalk-icons.min.css'),          array(), APBCT_VERSION, 'all');
        wp_enqueue_script('ct_users_editscreen',     plugins_url('/cleantalk-spam-protect/js/cleantalk-users-editscreen.min.js'), array(), APBCT_VERSION);
        wp_localize_script( 'ct_users_editscreen', 'ctUsersScreen', array(
            'spambutton_text'             => __("Find spam-users", 'cleantalk-spam-protect'),
            'ct_show_check_links'		  => (bool)$apbct->settings['comments__show_check_links'],
            'ct_img_src_new_tab'          => plugin_dir_url(__FILE__)."images/new_window.gif"
        ));
    }

}

/**
 * Notice blog owner if plugin is used without Access key 
 * @return bool 
 */
function apbct_admin__notice_message(){
	
	global $apbct;
	
	$page = get_current_screen();
	
	//General notice control flags
	$self_owned_key = 	($apbct->moderate_ip == 0 && !defined('CLEANTALK_ACCESS_KEY') ? true : false);
	$is_dashboard = 	(is_network_admin() || is_admin() ? true : false);
	$is_admin = 		(current_user_can('activate_plugins') ? true : false);
	
	$page_is_ct_settings = (in_array($page->id, array('settings_page_cleantalk', 'settings_page_cleantalk-network', 'comments_page_ct_check_spam', 'users_page_ct_check_users')) ? true : false);
	
	//Misc
	$user_token =    ($apbct->user_token ? '&user_token='.$apbct->user_token : '');

	if( is_network_admin() ) {
		$site_url = get_site_option('siteurl');
		$site_url = preg_match( '/\/$/', $site_url ) ? $site_url : $site_url . '/';
		$settings_link = $site_url . 'wp-admin/options-general.php?page=cleantalk';
    } else {
		$settings_link = 'options-general.php?page=cleantalk';
    }
		
	if($self_owned_key && $is_dashboard && $is_admin){
		// Auto update notice
		/* Disabled at 09.09.2018
		if($apbct->notice_auto_update == 1 && $apbct->auto_update != -1 && empty($_COOKIE['apbct_update_banner_closed'])){
			$link 	= '<a href="https://cleantalk.org/help/cleantalk-auto-update" target="_blank">%s</a>';
			$button = sprintf($link, '<input type="button" class="button button-primary" value="'.__('Learn more', 'cleantalk-spam-protect').'"  />');
			echo '<div class="error notice is-dismissible apbct_update_notice">'
				.'<h3>'
					.__('Do you know that Anti-Spam by CleanTalk has auto update option?', 'cleantalk-spam-protect')
					.'</br></br>'
					.$button
				.'</h3>'
			.'</div>';
		}
		*/
		//Unable to get key automatically (if apbct_admin__init().getAutoKey() returns error)
		if ($apbct->notice_show && !empty($apbct->errors['get_key']) && !$apbct->white_label){
			echo '<div class="error">
				<h3>' . sprintf(__("Unable to get Access key automatically: %s", 'cleantalk-spam-protect'), $apbct->api_key).
					"<a target='__blank' style='margin-left: 10px' href='https://cleantalk.org/register?platform=wordpress&email=" . urlencode(ct_get_admin_email())."&website=" . urlencode(parse_url(get_option('siteurl'),PHP_URL_HOST))."'>".__('Get the Access key', 'cleantalk-spam-protect').'</a>
				</h3>
			</div>';
		}
		
		//key == "" || "enter key"
		if ( ( ! apbct_api_key__is_correct() && $apbct->moderate_ip == 0 ) && ! $apbct->white_label ){
			echo "<div class='error'>"
				."<h3>"
					.sprintf(__("Please enter Access Key in %s settings to enable anti spam protection!", 'cleantalk-spam-protect'), "<a href='{$settings_link}'>$apbct->plugin_name</a>")
				."</h3>"
			."</div>";
			$apbct->notice_show = false;
		}
		
		//"Trial period ends" notice from apbct_admin__init().api_method__notice_paid_till()
		if ($apbct->notice_show && $apbct->notice_trial == 1 && $apbct->moderate_ip == 0 && !$apbct->white_label) {
			if(isset($_GET['page']) && in_array($_GET['page'], array('cleantalk', 'ct_check_spam', 'ct_check_users'))){
				echo '<div class="error" id="apbct_trial_notice">
					<h3>' . sprintf(__("%s trial period ends, please upgrade to %s!", 'cleantalk-spam-protect'),
						"<a href='{$settings_link}'>".$apbct->plugin_name."</a>", 
						"<a href=\"https://cleantalk.org/my/bill/recharge?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%20backend%20trial$user_token&cp_mode=antispam\" target=\"_blank\"><b>premium version</b></a>") .
					'</h3>
					<h4 style = "color: gray">' . esc_html__( 'Account status updates every 24 hours.', 'cleantalk-spam-protect' ) . '</h4>
				</div>';
				$apbct->notice_show = false;
			}
		}
		
		//Renew notice from apbct_admin_init().api_method__notice_paid_till()
		if ($apbct->notice_show && $apbct->notice_renew == 1 && $apbct->moderate_ip == 0 && !$apbct->white_label) {
			if(isset($_GET['page']) && in_array($_GET['page'], array('cleantalk', 'ct_check_spam', 'ct_check_users'))){
				$renew_link = "<a href=\"https://cleantalk.org/my/bill/recharge?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%%20backend%%20renew$user_token&cp_mode=antispam\" target=\"_blank\">%s</a>";
				$button_html 	= sprintf($renew_link, '<input type="button" class="button button-primary" value="'.__('RENEW ANTI-SPAM', 'cleantalk-spam-protect').'"  />');
				$link_html 		= sprintf($renew_link, "<b>".__('next year', 'cleantalk-spam-protect')."</b>");

				echo '<div class="updated" id="apbct_renew_notice">
				<h3>'.
				     sprintf(__("Please renew your anti-spam license for %s.", 'cleantalk-spam-protect'), $link_html).
				     '</h3>
				<h4 style = "color: gray">' . esc_html__( 'Account status updates every 24 hours.', 'cleantalk-spam-protect' ) . '</h4>
				'.$button_html.'
				<br/><br/>
			</div>';
				$apbct->notice_show = false;
			}
		}
		
		//"Wrong access key" notice (if ct_update_option().METHOD_notice_validate_key returns a error)
		if ($apbct->notice_show && $page_is_ct_settings && !$apbct->data['key_is_ok'] && $apbct->moderate_ip == 0 && !$apbct->white_label){
			echo '<div class="error">
				<h3><b>'.
					__("Wrong <a href='{$settings_link}'><b style=\"color: #49C73B;\">Clean</b><b style=\"color: #349ebf;\">Talk</b> access key</a>! Please check it or ask <a target=\"_blank\" href=\"https://wordpress.org/support/plugin/cleantalk-spam-protect/\">support</a>.", 'cleantalk-spam-protect').
				'</b></h3>
			</div>';
		}

		//notice_incompatibility
        if( ! empty( $apbct->data['notice_incompatibility'] ) && $page_is_ct_settings ){
		    foreach ($apbct->data['notice_incompatibility'] as $notice) {
			    echo '<div class="error">' . $notice . '</div>';
            }
		}
	}

	return true;
}

function apbct_admin__badge__get_premium($print = true, $out = ''){
	
	global $apbct;
	
	if($apbct->license_trial == 1 && $apbct->user_token){
		$out .= '<b style="display: inline-block; margin-top: 10px;">'
			.($print ? __('Make it right!', 'cleantalk-spam-protect').' ' : '')
			.sprintf(
				__('%sGet premium%s', 'cleantalk-spam-protect'),
				'<a href="https://cleantalk.org/my/bill/recharge?user_token='.$apbct->user_token.'" target="_blank">',
				'</a>'
			)
		.'</b>';
	}
	
	if($print)
		echo $out;
	else
		return $out;
}

function apbct_admin__admin_bar__add_structure( $wp_admin_bar ) {
    
    global $spbc, $apbct;
    
    do_action( 'cleantalk_admin_bar__prepare_counters' );
    
    // Adding parent node
    $wp_admin_bar->add_node( array(
        'id'    => 'cleantalk_admin_bar__parent_node',
        'title' =>
            apply_filters('cleantalk_admin_bar__parent_node__before', '' ) .
            '<span class="cleantalk_admin_bar__title">' . __('CleanTalk', 'cleantalk-spam-protect') . '</span>' .
            apply_filters('cleantalk_admin_bar__parent_node__after', '' ),
        'meta' => array( 'class' => 'cleantalk-admin_bar--list_wrapper'),
    ) );
    
    // Security
    $title = $apbct->notice_trial
        ? "<span><a style='color: red;' href='https://cleantalk.org/my/bill/recharge?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%20backend%20trial&user_token={$apbct->user_token}&cp_mode=antispam' target='_blank'>" . __('Renew Anti-Spam', 'cleantalk-spam-protect') . '</a></span>'
        : '<a>' . __( 'Anti-Spam', 'cleantalk-spam-protect' ) . '</a>';
    
    $wp_admin_bar->add_node( array(
        'parent' => 'cleantalk_admin_bar__parent_node',
        'id'    => 'apbct__parent_node',
        'title' => '<div class="cleantalk-admin_bar__parent">'
                . $title
            . '</div>',
    ) );
    
    // Antispam
    // Install link
    if( ! $spbc ){
        $spbc_title = '<a>' . __( 'Security', 'security-malware-firewall' ) . '</a>';
    }elseif( $spbc->admin_bar_enabled ){
        $spbc_title = $spbc->trial == 1
            ? "<span><a style='color: red;' href='https://cleantalk.org/my/bill/security?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%20backend%20renew_security&user_token={$spbc->user_token}&cp_mode=security' target='_blank'>" . __( 'Renew Security', 'security-malware-firewall' ) . '</a></span>'
            : '<a>' . __( 'Security', 'security-malware-firewall' ) . '</a>';
    }
    
    if( isset( $spbc_title ) ){
        $wp_admin_bar->add_node( array(
            'parent' => 'cleantalk_admin_bar__parent_node',
            'id'    => 'spbc__parent_node',
            'title' => '<div class="cleantalk-admin_bar__parent">'
                    . $spbc_title
                . '</div>'
        ) );
    }
}

/**
 * Prepares properties for counters in $apbct
 * Handles counter reset
 *
 * @return void
 */
function apbct_admin__admin_bar__prepare_counters(){
    
    global $apbct;
    
    //Reset or create user counter
    if(!empty($_GET['ct_reset_user_counter'])){
        $apbct->data['user_counter']['accepted'] = 0;
        $apbct->data['user_counter']['blocked'] = 0;
        $apbct->data['user_counter']['since'] = date('d M');
        $apbct->saveData();
    }
    //Reset or create all counters
    if(!empty($_GET['ct_reset_all_counters'])){
        $apbct->data['admin_bar__sfw_counter']      = array('all' => 0, 'blocked' => 0);
        $apbct->data['admin_bar__all_time_counter'] = array('accepted' => 0, 'blocked' => 0);
        $apbct->data['user_counter']     = array('all' => 0, 'accepted' => 0, 'blocked' => 0, 'since' => date('d M'));
        $apbct->data['array_accepted']   = array();
        $apbct->data['array_blocked']    = array();
        $apbct->data['current_hour']     = '';
        $apbct->saveData();
    }
    
    $apbct->counter__sum = 0;
    
    $apbct->counter__user = array(
        'accepted' => $apbct->data['user_counter']['accepted'],
        'blocked'  => $apbct->data['user_counter']['blocked'],
        'all'      => $apbct->data['user_counter']['accepted'] + $apbct->data['user_counter']['blocked'],
        'since'    => $apbct->data['user_counter']['since']
    );
    $apbct->counter__sum += $apbct->counter__user['all'];
    
    if( $apbct->settings['admin_bar__all_time_counter'] ){
        $apbct->counter__all_time = array(
            'accepted' => $apbct->data['admin_bar__all_time_counter']['accepted'],
            'blocked'  => $apbct->data['admin_bar__all_time_counter']['blocked'],
            'all'      => $apbct->data['admin_bar__all_time_counter']['accepted'] + $apbct->data['admin_bar__all_time_counter']['blocked']
        );
        $apbct->counter__sum += $apbct->counter__all_time['all'];
    }
    
    if( $apbct->settings['admin_bar__daily_counter'] ){
        $apbct->counter__daily = array(
            'accepted' => array_sum( $apbct->data['array_accepted'] ),
            'blocked'  => array_sum( $apbct->data['array_blocked'] ),
            'all'      => array_sum( $apbct->data['array_accepted'] ) + array_sum( $apbct->data['array_blocked'] )
        );
        $apbct->counter__sum += $apbct->counter__daily['all'];
    }
    
    if( $apbct->settings['admin_bar__sfw_counter'] && $apbct->settings['sfw__enabled'] ){
        $apbct->counter__sfw = array(
            'all'     => $apbct->data['admin_bar__sfw_counter']['all'],
            'blocked' => $apbct->data['admin_bar__sfw_counter']['blocked']
        );
        $apbct->counter__sum += $apbct->counter__sfw['all'];
    }
}

function apbct_admin__admin_bar__add_parent_icon( $icon ){
    
    return $icon
        . '<img class="cleantalk_admin_bar__apbct_icon" src="' . APBCT_URL_PATH . '/inc/images/logo.png" alt="">&nbsp;';
}

function apbct_admin__admin_bar__add_counter( $after ){
    
    global $apbct;
    
    $counter__sum__layout = ( $after ? ' / ' : '<div class="cleantalk_admin_bar__sum_counter">' ) .
            '<span title="All anti-spam events">' . $apbct->counter__sum . '</span>' .
        '</div>';
    
    return ( $after ? substr( $after, 0, -6 ) : $after )
        . $counter__sum__layout;
    
}

function apbct_admin__admin_bar__add_child_nodes( $wp_admin_bar ) {
	
	global $apbct;
    
    // User's counter
    $wp_admin_bar->add_node( array(
        'parent' => 'apbct__parent_node',
        'id'	 => 'apbct_admin_bar__counter__user',
        'title'  => '<a>' . __('Since', 'cleantalk-spam-protect') . '&nbsp;' . $apbct->counter__user['since'] . ': '
            . '<span style="color: green;">' . $apbct->counter__user['accepted']. '</span> / '
            . '<span style="color: red;">' . $apbct->counter__user['blocked'] . '</span></a>',
    ) );
    
    // All-time counter
    if($apbct->settings['admin_bar__all_time_counter'] ){
        $wp_admin_bar->add_node( array(
            'parent' => 'apbct__parent_node',
            'id'     => 'apbct_admin_bar__counter__all_time',
            'title'  =>
                '<a><span title="' . __('All / Allowed / Blocked submissions. The number of submissions is being counted since CleanTalk plugin installation.', 'cleantalk-spam-protect').'">'
                . __('Since installation', 'cleantalk-spam-protect') .  ': '
                . '<span style="color: white;">' . $apbct->counter__all_time['all']. '</span> / '
                . '<span style="color: green;">' . $apbct->counter__all_time['accepted']. '</span> / '
                . '<span style="color: red;">' .$apbct->counter__all_time['blocked']. '</span>'
                . '</span></a>',
        ) );
    }
    
    // Daily counter
    if( $apbct->settings['admin_bar__daily_counter'] ){
        $wp_admin_bar->add_node( array(
            'parent' => 'apbct__parent_node',
            'id'	 => 'apbct_admin_bar__counter__daily',
            'title'  =>
                '<a><span title="'.__('Allowed / Blocked submissions. The number of submissions for past 24 hours. ', 'cleantalk-spam-protect').'">'
                . __('Day', 'cleantalk-spam-protect') . ': '
                . '<span style="color: green;">' .$apbct->counter__daily['accepted']. '</span> / '
                . '<span style="color: red;">' .$apbct->counter__daily['blocked']. '</span>'
                . '</span></a>',
        ) );
    }
    
    // SFW counter
    if( $apbct->settings['admin_bar__sfw_counter'] && $apbct->settings['sfw__enabled'] ){
        $wp_admin_bar->add_node( array(
            'parent' => 'apbct__parent_node',
            'id'	 => 'apbct_admin_bar__counter__sfw',
            'title'  =>
                '<a><span title="'.__('All / Blocked events. Access attempts triggered by SpamFireWall counted since the last plugin activation.', 'cleantalk-spam-protect').'">'
                . __('SpamFireWall', 'cleantalk-spam-protect' ) . ': '
                . '<span style="color: white;">'. $apbct->counter__sfw['all']. '</span> / '
                . '<span style="color: red;">' .$apbct->counter__sfw['blocked']. '</span>'
                . '</span></a>'
        ) );
    }
    
    // Counter separator
    if( $apbct->counter__sum ){
        $wp_admin_bar->add_node( array(
            'parent' => 'apbct__parent_node',
            'id'     => 'apbct_admin_bar__separator',
            'title'  =>'<hr style="margin-top: 7px;" />',
            'meta' => array( 'class' => 'cleantalk_admin_bar__separator')
        ) );
    }
    
    // Dashboard link
    if(!$apbct->white_label){
        $wp_admin_bar->add_node( array(
        'parent' => 'apbct__parent_node',
        'id'	 => 'ct_dashboard_link',
        'title'  => '<a href="https://cleantalk.org/my/?user_token='.$apbct->user_token.'&utm_source=wp-backend&utm_medium=admin-bar&cp_mode=antispam " target="_blank">'.__('Dashboard', 'cleantalk-spam-protect').'</a>',
        ));
    }

    $wp_admin_bar->add_node( array(
        'parent' => 'apbct__parent_node',
        'id'	 => 'ct_settings_link',
        'title'  => '<a href="'.$apbct->settings_link.'">'.__('Settings', 'cleantalk-spam-protect').'</a>',
    ));
    
    // Add a child item to our parent item. Bulk checks.
    if(!is_network_admin()){
        $wp_admin_bar->add_node( array(
            'parent' => 'apbct__parent_node',
            'id'	 => 'ct_settings_bulk_comments',
            'title'  => '<hr style="margin-top: 7px;" /><a href="edit-comments.php?page=ct_check_spam" title="'.__('Bulk spam comments removal tool.', 'cleantalk-spam-protect').'">'.__('Check comments for spam', 'cleantalk-spam-protect').'</a>',
        ) );
    }
    
    // Add a child item to our parent item. Bulk checks.
    if(!is_network_admin()){
        $wp_admin_bar->add_node( array(
            'parent' => 'apbct__parent_node',
            'id'	 => 'ct_settings_bulk_users',
            'title'  => '<a href="users.php?page=ct_check_users" title="Bulk spam users removal tool.">'.__('Check users for spam', 'cleantalk-spam-protect').'</a>',
        ) );
    }
    
    // User counter reset.
    $args = array(
        'parent' => 'apbct__parent_node',
        'id'	 => 'ct_reset_counter',
        'title'  => '<hr style="margin-top: 7px;"><a href="?' . http_build_query(array_merge( $_GET, array( 'ct_reset_all_counters' => 1  ) ) ) . '" title="Reset your personal counter of submissions.">' . __('Reset first counter', 'cleantalk-spam-protect') . '</a>',
    );
    $wp_admin_bar->add_node( $args );// add a child item to our parent item. Counter reset.
    
    // Reset ALL counter
    $args = array(
        'parent' => 'apbct__parent_node',
        'id'	 => 'ct_reset_counters_all',
        'title'  => '<a href="?' . http_build_query(array_merge( $_GET, array( 'page' => 1  ) ) ). '" title="Reset all counters.">'.__('Reset all counters', 'cleantalk-spam-protect').'</a>',
    );
    $wp_admin_bar->add_node( $args );
    
    // Support link
    if(!$apbct->white_label){
        $wp_admin_bar->add_node( array(
        'parent' => 'apbct__parent_node',
        'id'	 => 'ct_admin_bar_support_link',
        'title'  => '<hr style="margin-top: 7px;" /><a target="_blank" href="https://wordpress.org/support/plugin/cleantalk-spam-protect">'.__('Support', 'cleantalk-spam-protect').'</a>',
        ));
    }
}

function apbct_spbc_admin__admin_bar__add_child_nodes( $wp_admin_bar ){
    
    // Installation link
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'	 => 'apbct_admin_bar__install',
        'title'  => '<a target="_blank" href="plugin-install.php?s=Security%20and%20Malware%20scan%20by%20CleanTalk%20&tab=search">' . __( 'Install Security by CleanTalk', 'cleantalk-spam-protect' ) . '</a>',
    ) );
    
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'     => 'install_separator',
        'title'  =>'<hr style="margin-top: 7px;" />',
        'meta' => array( 'class' => 'cleantalk_admin_bar__separator' )
    ) );
    
    // Users online counter
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'title'  => '<a><span>' . __( 'Users online:', 'cleantalk-spam-protect' ) . '</span>'
                    . '&nbsp;<b class="spbc-admin_bar--user_counter">' .  0 . '</b></a>',
        'id'     => 'spbc_admin_bar__counter__online',
        'meta' => array( 'class' => 'cleantalk_admin_bar__blocked' ),
    ) );
    
    // Failed / success login attempts counter
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'     => 'spbc_admin_bar__counter__logins',
        'title'  => '<a><span>' . __('Logins:', 'cleantalk-spam-protect') . '</span>&nbsp;'
            . '<span style="color: white;" title="' . __('Blocked login attempts in the local database.', 'cleantalk-spam-protect') . '">'
                . '<span style="color: white;">'
                . '<span style="color: green;">'
                    .  0
                . '</span> / '
                . '<span style="color: red;">'
                    .  0
                . '</span>'
            . '</span></a>',
        'meta' => array( 'class' => 'cleantalk_admin_bar__blocked' ),
    ) );
    
    
    // Firewall blocked / allowed counter
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'     => 'spbc_admin_bar__counter__firewall',
        'title'  => '<a><span>' .__( 'Security Firewall: ', 'cleantalk-spam-protect' ) .'</span>&nbsp;'
            . '<span style="color: white;" title="'.__('Passed / Blocked requests by Security Firewall.', 'cleantalk-spam-protect').'">'
                . '<span style="color: green;">'
                    . 0
                . '</span> / '
                . '<span style="color: red;">'
                    . 0
                . '</span>'
            . '</span></a>',
        'meta' => array( 'class' => 'cleantalk_admin_bar__blocked' ),
    ) );
    
    // Counter separator
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'     => 'spbc_admin_bar__separator',
        'title'  =>'<hr style="margin-top: 7px;" />',
        'meta' => array( 'class' => 'cleantalk_admin_bar__separator')
    ) );
    
    // Dashboard
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'     => 'spbc_admin_bar__dashboard_link',
        'title'  => '<a>' . __( 'Dashboard', 'cleantalk-spam-protect' ) . '</a>',
        'meta' => array( 'class' => 'cleantalk_admin_bar__blocked' ),
    ) );
    
    // Settings
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'     => 'spbc_admin_bar__settings_link',
        'title'  => '<a>' . __( 'Settings', 'cleantalk-spam-protect' ) . '</a>',
        'meta' => array( 'class' => 'cleantalk_admin_bar__blocked' ),
    ) );
    
    // Scanner
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'id'     => 'spbc_admin_bar__scanner_link',
        'title'  => '<a style="display:inline">' . __( 'Scanner', 'cleantalk-spam-protect' ) . '</a>'
                    . '/'
                    . '<a style="display:inline">' . __( 'Start scan', 'cleantalk-spam-protect' ) . '</a>',
        'meta' => array( 'class' => 'cleantalk_admin_bar__blocked' ),
    ) );
    
    // Support link
    $wp_admin_bar->add_node( array(
        'parent' => 'spbc__parent_node',
        'title'  => '<hr style="margin-top: 7px;" /><a>' . __( 'Support', 'cleantalk-spam-protect' ) . '</a>',
        'id'     => 'spbc_admin_bar__support_link',
        'meta' => array( 'class' => 'cleantalk_admin_bar__blocked' ),
    ) );
}


/**
 * Unmark bad words
 * @param string $message
 * @return string Cleat comment
 */
function apbct_comment__unmark_red($message) {
	$message = preg_replace("/\<font rel\=\"cleantalk\" color\=\"\#FF1000\"\>(\S+)\<\/font>/iu", '$1', $message);

	return $message;
}

// Ajax action feedback form comments page.
function apbct_comment__send_feedback($comment_id = null, $comment_status = null, $change_status = false, $direct_call = null){
	
	// For AJAX call
    if( ! $direct_call ){
        check_ajax_referer('ct_secret_nonce', 'security');
    }
    
    $comment_id     = ! $comment_id && isset( $_POST['comment_id'] )         ? $_POST['comment_id']     : false;
    $comment_status = ! $comment_status && isset( $_POST['comment_status'] ) ? $_POST['comment_status'] : false;
    $change_status  = ! $change_status && isset( $_POST['change_status'] )   ? $_POST['change_status']  : false;
    
	// If enter params is empty exit
	if( ! $comment_id || ! $comment_status )
		die();
	
	// $comment = get_comment($comment_id, 'ARRAY_A');
	$hash = get_comment_meta($comment_id, 'ct_hash', true);
	
	// If we can send the feedback
	if($hash){
		
		// Approving
		if($comment_status == '1' || $comment_status == 'approve'){
			$result = ct_send_feedback($hash.":1");
			// $comment['comment_content'] = apbct_comment__unmark_red($comment['comment_content']);
			// wp_update_comment($comment);
			$result === true ? 1 : 0;
		}
		
		// Disapproving	
		if($comment_status == 'spam'){
			$result = ct_send_feedback($hash.":0");
			$result === true ? 1 : 0;
		}
	}else{
		$result = 'no_hash';
	}
	
	// Changing comment status(folder) if flag is set. spam || approve
	if($change_status !== false)
		wp_set_comment_status($comment_id, $comment_status);
		
	if(!$direct_call){
		echo !empty($result) ? $result : 0;
		die();
	}else{
		
	}
}

// Ajax action feedback form user page.
function apbct_user__send_feedback($user_id = null, $status = null, $direct_call = null){
	
	check_ajax_referer('ct_secret_nonce', 'security');
	
	if(!$direct_call){
		$user_id = $_POST['user_id'];
		$status  = $_POST['status'];
	}
		
	$hash = get_user_meta($user_id, 'ct_hash', true);
	
	if($hash){
		if($status == 'approve' || $status == 1){
			$result = ct_send_feedback($hash.":1");
			$result === true ? 1 : 0;
		}
		if($status == 'spam' || $status == 'disapprove' || $status == 0){
			$result = ct_send_feedback($hash.":0");
			$result === true ? 1 : 0;
		}
	}else{
		$result = 'no_hash';
	}
	
	if(!$direct_call){
		echo !empty($result) ? $result : 0;
		die();
	}else{
		
	}
	
}

/**
 * Send feedback when user deleted
 * @return null 
 */
function apbct_user__delete__hook($user_id, $reassign = null){
	
	$hash = get_user_meta($user_id, 'ct_hash', true);
	if ($hash !== '') {
		ct_feedback($hash, 0);
	}
}

function apbct_test_connection(){
    
    $url_to_test = array(
        'https://apix1.cleantalk.org',
        'https://apix2.cleantalk.org',
        'https://apix3.cleantalk.org',
        'https://apix4.cleantalk.org',
        'https://apix5.cleantalk.org',
    );
    
    foreach($url_to_test as $url){
        $start = microtime(true);
        $result = \Cleantalk\ApbctWP\Helper::http__request__get_content($url);
        $exec_time = microtime(true) - $start;
        $out[$url] = array(
            'result' => $result,
            'exec_time' => $exec_time,
            'error' => !empty($result['error']) ? $result['error']	: 'OK',
        ) ;
    }
    return $out;
}

/**
 * Check compatibility action
 */
add_action('apbct__check_compatibility', 'apbct__check_compatibility_handler');
function apbct__check_compatibility_handler() {
    new \Cleantalk\Common\Compatibility();
}