<?php

/*
 * General functions: l10n, menu, etc.
 */
function ibuildapp_init()
{
    load_plugin_textdomain('ibuildapp', false, IBUILDAPP_PLUGIN_BASENAME . '/languages/');
}
add_action('plugins_loaded', 'ibuildapp_init');

function ibuildapp_plugin_action_links($links, $file)
{
    if ($file == plugin_basename(dirname(__FILE__) . '/ibuildapp.php')) {
        $links[] = '<a href="' . admin_url('admin.php?page=ibuildapp-conf') . '">' . _x('Settings', 'menu', 'ibuildapp') . '</a>';
    }

    return $links;
}
add_filter('plugin_action_links', 'ibuildapp_plugin_action_links', 10, 2);

function ibuildapp_admin_menu()
{
    if (!Ibuildapp::hasKeys()) {
        $dashboard = 'ibuildapp_conf';
    } elseif (!Ibuildapp::hasApps() || !Ibuildapp::getApp()->isCreated()) {
        $dashboard = 'ibuildapp_app_create';
    } else {
        $dashboard = 'ibuildapp_app_dashboard';
    }
    add_menu_page('', _x('iBuildApp', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp', $dashboard);
    add_submenu_page('ibuildapp', _x('Home', 'menu', 'ibuildapp'), _x('Home', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp', $dashboard);
    if (Ibuildapp::hasKeys()) {
        if (!Ibuildapp::hasApps() || !Ibuildapp::getApp()->isCreated()) {
            add_submenu_page('ibuildapp', _x('Create app', 'menu', 'ibuildapp'), _x('Create app', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp-app-create', 'ibuildapp_app_create');
        }
        if (Ibuildapp::hasApps() && Ibuildapp::getApp()->isCreated()) {
            add_submenu_page('ibuildapp', _x('Design', 'menu', 'ibuildapp'), _x('Design', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp-app-management', 'ibuildapp_app_management');
            add_submenu_page('ibuildapp', _x('Publish', 'menu', 'ibuildapp'), _x('Publish', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp-app-publishing', 'ibuildapp_app_publishing');
            add_submenu_page('ibuildapp', _x('Notification', 'menu', 'ibuildapp'), _x('Notification', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp-app-notifications', 'ibuildapp_app_notifications');
            add_submenu_page('ibuildapp', _x('Monetize', 'menu', 'ibuildapp'), _x('Monetize', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp-app-monetize', 'ibuildapp_app_monetize');
            add_submenu_page('ibuildapp', _x('Delete app', 'menu', 'ibuildapp'), _x('Delete app', 'menu', 'ibuildapp'), 'manage_options', 'ibuildapp-app-settings', 'ibuildapp_app_settings');
        }
    }
    add_submenu_page('ibuildapp', __('Settings', 'ibuildapp'), __('Settings', 'ibuildapp'), 'manage_options', 'ibuildapp-conf', 'ibuildapp_conf');

    add_submenu_page(null, '', '', 'manage_options', 'ibuildapp-iframe-redirect', 'ibuildapp_iframe_redirect');
}
add_action('admin_menu', 'ibuildapp_admin_menu');

function ibuildapp_common_jsReloadPage($page)
{
?>
<script type="text/javascript">
document.location.href = '<?php echo $page; ?>';
</script>
<?php
    exit();
}

function ibuildapp_iframe_redirect()
{
    $targetPage = (isset($_GET['targetPage']) ? $_GET['targetPage'] : 'ibuildapp');
?>
<script type="text/javascript">
parent.document.location.href = '<?php echo admin_url('admin.php?page=' . $targetPage); ?>';
</script>
<?php
    exit();
}

function ibuildapp_load_js_and_css($hook)
{
    if (
        'index.php' != $hook
        && 'admin.php' != $hook
        && 'toplevel_page_ibuildapp' != $hook
        && 0 !== strpos($hook, 'ibuildapp_page')
    ) {
        return;
    }

    wp_register_style('ibuildapp.css', IBUILDAPP_PLUGIN_URL . 'ibuildapp.css', array(), IBUILDAPP_VERSION);
    wp_enqueue_style('ibuildapp.css');

    wp_register_script('ibuildapp.js', IBUILDAPP_PLUGIN_URL . 'ibuildapp.js', array('jquery'), IBUILDAPP_VERSION);
    wp_enqueue_script('ibuildapp.js');

    $l10n = array();
    $l10n = apply_filters('ibuildapp_localize_script', $l10n, 'ibuildapp.js', 'WPIbuildapp');
    wp_localize_script('ibuildapp.js', 'WPIbuildapp', $l10n);
}
add_action('admin_enqueue_scripts', 'ibuildapp_load_js_and_css');

function ibuildapp_get_lw_endpoint()
{
    ;
    return admin_url('admin-ajax.php?action=ibuildapp_jsonp_ajax&token=' . Ibuildapp::generateJsonpToken());
}

require_once dirname(__FILE__) . '/admin_conf.php';
require_once dirname(__FILE__) . '/admin_app.php';
require_once dirname(__FILE__) . '/admin_jsonp.php';
