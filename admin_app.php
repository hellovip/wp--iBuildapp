<?php

/*
 * Application management
 */

/**
 * Create an app
 */

function ibuildapp_app_create()
{
    $app = Ibuildapp::getApp();

    $step = 'create';

    if (isset($_REQUEST['step']) && 'create' == $_REQUEST['step'] && is_null($app)) {
        Ibuildapp::createApp();
        $app = Ibuildapp::getApp();
    }

    if (!is_null($app)) {
        if ($app->isJustCreated()) {
            try {
                $state = $app->parse(get_site_url());
                if (IbuildappApiClient::PARSER_STATE_CREATED == $state) {
                    $step = 'parse';
                }
            } catch (Exception $ex) {
            }
        } elseif ($app->isParsing()) {
            try {
                $state = $app->checkIfParsed();
                if (IbuildappApiClient::PARSER_STATE_DONE != $state) {
                    $step = 'parse';
                }
            } catch (Exception $ex) {
            }
        }
        if ($app->isParsed()) {
            $step = 'layout';
        }
    }

    switch ($step) {
        case 'create':
            ibuildapp_app_create_stepCreate();
            break;
        case 'parse':
            ibuildapp_app_create_stepParse($app);
            break;
        case 'layout':
            ibuildapp_app_create_stepLayout($app);
            break;
        default:
            // do smth!
    }
}

function ibuildapp_app_create_stepCreate()
{
?>
<div class="wrap">
    <div class="ibuildapp-content">
        <h1><?php _e('Create an App', 'ibuildapp'); ?></h1>

        <div class="ibuildapp-infoblock">
            <br />
            <p class="ibuildapp-center ibuildapp-hint" style="font-size:1.1em;"><?php _e('iBuildApp WordPress Plugin has been successfully configured.', 'ibuildapp'); ?></p>
            <br/>
            <p class="ibuildapp-center"><?php _e('Now you are ready to create an app!<br />Click on the button below to continue.', 'ibuildapp'); ?></p>
        </div>

        <div class="ibuildapp-btns">
            <form method="post" action="<?php echo admin_url('admin.php?page=ibuildapp-app-create'); ?>">
                <input type="hidden" name="step" value="create" />
                <input type="submit" class="ibuildapp-btn_grey ibuildapp-btn_arrow" value="<?php _e('Next', 'ibuildapp'); ?>" />
            </form>
        </div>
        <p class="ibuildapp-center ibuildapp-hint"><?php _e('Let&#8217;s create your app by first running a site analysis.', 'ibuildapp'); ?></p>
    </div>
</div>
<?php
}

function ibuildapp_app_create_stepParse($app)
{
    if (is_null($app) || $app->isParsed()) {
        return;
    }

?>
<div class="wrap">
    <div class="ibuildapp-content">
        <h2><?php _e('Parsing your website', 'ibuildapp'); ?></h2>

        <div class="ibuildapp-center ibuildapp-loader">&nbsp;</div>
        <div class="ibuildapp-center ibuildapp-hint"><?php _e('this could take a few minutes', 'ibuildapp'); ?></div>
    </div>
</div>
<script type="text/javascript">
jQuery(function() {
    setInterval(function() {
        WPIbuildapp.parser.check(function(data) {
            if ('done' == data.state) {
                document.location.reload();
            }
        });
    }, 5000);
});
</script>
<?php
}

function ibuildapp_app_create_stepParse_localize($l10n, $handle, $objectName)
{
    $l10n['ibuildapp_app_create_stepParse_nonce'] = wp_create_nonce('ibuildapp_app_create_stepParse');

    return $l10n;
}
add_action('ibuildapp_localize_script', 'ibuildapp_app_create_stepParse_localize', 10, 3);

function ibuildapp_app_create_stepParse_ajax()
{
    $app = Ibuildapp::getApp();
    if (is_null($app)) {
        exit();
    }

    $nonce = 'ibuildapp_app_create_stepParse';

    if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['data'], $_POST['data']['action'])) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }
        $data = $_POST['data'];

        $response = array();

        if ('check' === $data['action'] && $app->isParsing()) {
            try {
                $response['state'] = $app->checkIfParsed();
                $response['data'] = $app->getParsedData();
            } catch (Exception $ex) {
                $response['error'] = $ex->getMessage();
            }
        } elseif ($app->isParsed()) {
            $response['state'] = 'done';
        }

        echo json_encode($response);
    }

    exit();
}
add_action('wp_ajax_ibuildapp_app_create_stepParse_ajax', 'ibuildapp_app_create_stepParse_ajax');

function ibuildapp_app_create_stepLayout($app)
{
    if (is_null($app) || !$app->isParsed()) {
        return;
    }

    $nonce = 'ibuildapp_app_create_stepLayout';

    if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['action']) && 'create' === $_POST['action']) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }

        if (!isset($_POST['ibuildapp_template'])) {
            exit();
        }

        try {
            $response = $app->create($_POST['ibuildapp_template']);
            ibuildapp_common_jsReloadPage('?page=ibuildapp-app-management');
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        exit();
    }

    try {
        $response = IbuildappApiClient::parser_getTemplatesList();
    } catch (Exception $ex) {
        echo $ex->getMessage();
        exit();
    }
    $templates = $response['templates'];

?>
<div class="wrap">
    <div class="ibuildapp-content">
        <h1><?php _e('Parsing your website', 'ibuildapp'); ?></h1>

        <div class="ibuildapp-infoblock">
            <br />
            <p class="ibuildapp-center ibuildapp-hint" style="font-size:1.1em;"><?php _e('Your website has been successfully parsed!', 'ibuildapp'); ?></p>
            <br/>
            <p class="ibuildapp-center"><?php _e('Now you are ready to create an app!<br />Click on the button below to continue.', 'ibuildapp'); ?></p>
        </div>

        <div class="ibuildapp-btns">
            <form method="post" action="<?php echo admin_url('admin.php?page=ibuildapp-app-create'); ?>">
                <?php wp_nonce_field($nonce); ?>
                <input type="hidden" name="action" value="create" />
                <input type="hidden" name="ibuildapp_template" value="<?php echo $templates[0]['id']; ?>" />
                <input type="submit" class="ibuildapp-btn_grey ibuildapp-btn_arrow" value="<?php _e('Next', 'ibuildapp'); ?>" />
            </form>
        </div>
    </div>
</div>
<?php
}

/**
 * Display an iframe
 */

function ibuildapp_app_displayIframe($url)
{
    $url = sprintf(
        '%s%s?action=remote_login&amp;id=%s&amp;key=%s&amp;redirect_to=%s&no_redirect=1',
        Ibuildapp::getOption('site_url'),
        Ibuildapp::LOGIN_ENDPOINT,
        Ibuildapp::getOption('user_api_id'),
        Ibuildapp::getOption('user_api_key'),
        rawurlencode($url)
    );
?>
<iframe
    src="<?php echo $url ?>"
    style="min-width: 1020px; width: 100%; min-height: 750px; height: 90%; border: 0;"
    frameBorder="0"
></iframe>
<?php
}

/**
 * Application: dashboard
 */

function ibuildapp_app_dashboard()
{
    ibuildapp_app_displayIframe(sprintf(
        '/myapplications.php?action=dashboard&projectid=%d&_lightweight=1&_lightweightEndpoint=%s',
        Ibuildapp::getApp()->getAppId(),
        rawurlencode(ibuildapp_get_lw_endpoint())
    ));
}

/**
 * Application: management
 */

function ibuildapp_app_management()
{
    ibuildapp_app_displayIframe(sprintf(
        '/myapplications.php?action=builder&projectid=%d&_lightweight=1&_lightweightEndpoint=%s',
        Ibuildapp::getApp()->getAppId(),
        rawurlencode(ibuildapp_get_lw_endpoint())
    ));
}

/**
 * Application: publishing
 */

function ibuildapp_app_publishing()
{
    ibuildapp_app_displayIframe(sprintf(
        '/app.publishing.php?app=%d&_lightweight=1&_lightweightEndpoint=%s',
        Ibuildapp::getApp()->getAppId(),
        rawurlencode(ibuildapp_get_lw_endpoint())
    ));
}

/**
 * Application: notifications
 */

function ibuildapp_app_notifications()
{
    ibuildapp_app_displayIframe(sprintf(
        '/pushns.php?app=%d&_lightweight=1&_lightweightEndpoint=%s',
        Ibuildapp::getApp()->getAppId(),
        rawurlencode(ibuildapp_get_lw_endpoint())
    ));
}

/**
 * Application: monetize
 */

function ibuildapp_app_monetize()
{
    ibuildapp_app_displayIframe(sprintf(
        '/monetize.php?app=%d&_lightweight=1&_lightweightEndpoint=%s',
        Ibuildapp::getApp()->getAppId(),
        rawurlencode(ibuildapp_get_lw_endpoint())
    ));
}

/**
 * Application: settings (TO BE REMOVED)
 */

function ibuildapp_app_settings()
{
    $nonce = 'ibuildapp_app_settings';

    if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['delete_app']) && 'yes' == $_POST['delete_app']) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }

        try {
            if (Ibuildapp::getApp()->delete()) {
                ibuildapp_common_jsReloadPage('?page=ibuildapp');
                exit();
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

?>
<div class="wrap">
    <div class="ibuildapp-content">
        <h1><?php _e('Delete the App', 'ibuildapp'); ?></h1>

        <div class="ibuildapp-infoblock">
            <p class="ibuildapp-center" style="font-size:1.1em;"><?php _e('If you delete the app,<br />all content you&#8217;ve created before will be removed', 'ibuildapp'); ?></p>
        </div>

        <div class="ibuildapp-btns">
            <form method="post" action="" onsubmit="return confirm('<?php echo(addslashes(__('Are you sure you want to delete the app?', 'ibuildapp'))); ?>');">
                <input type="submit" class="ibuildapp-btn_grey" value="<?php _e('Delete app', 'ibuildapp'); ?>" />
                <?php wp_nonce_field($nonce); ?>
                <input type="hidden" name="delete_app" value="yes" />
            </form>
        </div>
    </div>
</div>
<?php
}
