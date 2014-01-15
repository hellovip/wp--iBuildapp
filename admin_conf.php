<?php

/*
 * Site-level plugin configuration
 */
function ibuildapp_conf()
{
?>
<div class="wrap">

<?php
    $currentTab = (isset($_GET['tab']) ? $_GET['tab'] : null);
    if (is_null($currentTab)) {
        $currentTab = 'quick-setup';
    }

    switch ($currentTab) {
        case 'quick-setup':
            ibuildapp_conf_quickSetup();
            break;
        case 'api-key':
            ibuildapp_conf_apiKey();
            break;
        case 'advanced':
            ibuildapp_conf_advanced();
            break;
        case 'remote-login':
            if (Ibuildapp::hasKeys()) {
                ibuildapp_conf_remoteLogin();
            }
            break;
    }
?>

</div>
<?php
}

function ibuildapp_conf_quickSetup()
{
    $nonceLogin = 'ibuildapp_conf_quickSetup_remoteLogin';

    $state = 'no-config';

    if (Ibuildapp::hasKeys()) {
        $state = 'has-config';
    }

    if (
        isset(
            $_GET['action'],
            $_GET['remoteLoginData'],
            $_GET['remoteLoginData']['id'],
            $_GET['remoteLoginData']['token']
        ) && 'getToken' === $_GET['action']
    ) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonceLogin)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }

        try {
            $response = IbuildappApiClient::user_getApiKey($_GET['remoteLoginData']['id'], $_GET['remoteLoginData']['token']);

            Ibuildapp::setOption('user_api_id', $response['id']);
            Ibuildapp::setOption('user_api_key', $response['key']);

            $response = IbuildappApiClient::user_login();

            if ($response['id'] != Ibuildapp::getOption('user_api_id')) {
                throw new Exception('Wrong data');
            }

            Ibuildapp::setUserInfo($response);

            Ibuildapp::saveOptions();

            if (Ibuildapp::hasApps()) {
                Ibuildapp::getApp()->deleteLocal();
            }

?>
<script type="text/javascript">
setTimeout(function() {
    parent.notifySuccessfulRemoteLogin(<?php echo json_encode(Ibuildapp::getUserInfo());?>);
}, 100);
</script>
<?php
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }

        exit();
    }

?>
    <div id="ibuildapp_conf_quickSetup_screen_info" class="ibuildapp-content" style="display: none;">
        <h1 class="ibuildapp-has-config"><?php _e('Plugin Configuration', 'ibuildapp'); ?></h1>
        <h1 class="ibuildapp-just-configured"><?php _e('Successful Plugin Configuration', 'ibuildapp'); ?></h1>

        <div class="ibuildapp-infoblock">
            <br />
            <div class="ibuildapp-center"><i class="ibuildapp-ico-link">&nbsp;</i></div>
            <p class="ibuildapp-center ibuildapp-hint ibuildapp-has-config" style="font-size:1.1em;"><?php _e('WordPress Plugin is linked to the iBuildApp account below:', 'ibuildapp'); ?></p>
            <p class="ibuildapp-center ibuildapp-hint ibuildapp-just-configured" style="font-size:1.1em;"><?php _e('WordPress Plugin is now linked to the iBuildApp account below:', 'ibuildapp'); ?></p>
            <div class="ibuildapp-center"><div class="ibuildapp-inputblock" id="ibuildapp_conf_quickSetup_info_userLogin"><?php echo Ibuildapp::getUserInfo('login'); ?></div></div>
            <br/><br/>
            <p class="ibuildapp-center ibuildapp-has-config"><?php _e('To link your Plugin to another iBuildApp account,<br/>click on Reconfigure below.', 'ibuildapp'); ?></p>
            <p class="ibuildapp-center ibuildapp-just-configured"><?php _e('Congrats!<br/>Your plugin has been successfully configured.', 'ibuildapp'); ?></p>
        </div>

        <div class="ibuildapp-btns ibuildapp-has-config">
            <a href="javascript:void(0);" id="ibuildapp_conf_quickSetup_reconfigureButton" class="ibuildapp-btn_grey"><?php _e('Reconfigure', 'ibuildapp'); ?></a>
        </div>
        <div class="ibuildapp-btns ibuildapp-just-configured">
            <form method="post" action="<?php echo admin_url('admin.php?page=ibuildapp-app-create'); ?>">
                <input type="submit" class="ibuildapp-btn_grey ibuildapp-btn_arrow" value="<?php _e('Next', 'ibuildapp'); ?>" />
                <input type="hidden" name="step" value="create" />
            </form>
        </div>
<?php
    if (Ibuildapp::hasApps()) {
?>
        <p class="ibuildapp-center ibuildapp-hint ibuildapp-has-config"><?php _e('Note: Current app will be deleted once a plugin is reconfigured.', 'ibuildapp'); ?></p>
<?php
    }
?>
        <p class="ibuildapp-center ibuildapp-hint ibuildapp-just-configured"><?php _e('Let&#8217;s create your app by first running a site analysis.', 'ibuildapp'); ?></p>
    </div>

    <div id="ibuildapp_conf_quickSetup_screen_validateForm" class="ibuildapp-content" style="display: none;">
        <h1><?php _e('iBuildApp Wordpress Plugin', 'ibuildapp'); ?></h1>

        <div class="ibuildapp-infoblock">
            <br/>
            <p class="ibuildapp-center"><?php _e('iBuildApp WordPress plugin enables you to turn your WordPress site into an iPhone, Android and mobile web app in a matter of minutes.', 'ibuildapp'); ?></p>
            <br/>
            <p class="ibuildapp-center ibuildapp-hint"><?php _e('Click on the button below to configure your iBuildApp Plugin.', 'ibuildapp'); ?></p>
            <br/>
        </div>

        <div class="ibuildapp-btns"><a href="javascript:void(0);" id="ibuildapp_conf_quickSetup_validateButton" class="ibuildapp-btn_grey"><?php _e('Configure Plugin', 'ibuildapp'); ?></a></div>
    </div>

    <div id="ibuildapp_conf_quickSetup_screen_validating" class="ibuildapp-content" style="display: none;">
        <h2><?php _e('Installing iBuildApp Plugin', 'ibuildapp'); ?></h2>

        <div class="ibuildapp-center ibuildapp-loader">&nbsp;</div>
        <div class="ibuildapp-center ibuildapp-hint"><?php _e('this will be quick', 'ibuildapp'); ?></div>
    </div>

    <div id="ibuildapp_conf_quickSetup_screen_validateError" class="ibuildapp-content" style="display: none;">
        <h1><?php _e('An error occured', 'ibuildapp'); ?></h1>

        <div class="ibuildapp-infoblock">
            <br/>
            <p class="ibuildapp-center"><?php _e('An error occured during Plugin configuration process:', 'ibuildapp'); ?></p>
            <p class="ibuildapp-center" id="ibuildapp_conf_quickSetup_validateError"></p>
            <br/>
            <p class="ibuildapp-center ibuildapp-hint"><?php _e('Please wait a little and try once more.', 'ibuildapp'); ?></p>
            <br/>
        </div>

        <div class="ibuildapp-btns"><a href="javascript:void(0);" id="ibuildapp_conf_quickSetup_retryValidationButton" class="ibuildapp-btn_grey"><?php _e('Retry', 'ibuildapp'); ?></a></div>
    </div>

    <div id="ibuildapp_conf_quickSetup_screen_loginForm" style="display: none;">
        <form
            id="ibuildapp_conf_quickSetup_loginForm"
            method="post"
            action="<?php echo Ibuildapp::getOption('site_url') . Ibuildapp::LOGIN_ENDPOINT; ?>?_lightweight=1&_lightweightEndpoint=<?php echo rawurlencode(ibuildapp_get_lw_endpoint()); ?>"
            target="ibuildapp_conf_quickSetup_loginIframe"
        >
            <input type="hidden" name="remoteLogin[logout]" value="force" />
            <input type="hidden" name="remoteLogin[action]" value="getToken" />
            <input type="hidden" name="remoteLogin[return_url]" value="<?php echo admin_url('admin.php?page=ibuildapp-conf&tab=quick-setup&noheader=true'); ?>" />
            <?php wp_nonce_field($nonceLogin, 'remoteLoginData[_wpnonce]', false); ?>
            <input type="hidden" name="remoteLoginData[action]" value="getToken" />
            <p class="submit"><input type="submit" class="button-primary" /></p>
        </form>
    </div>

    <div id="ibuildapp_conf_quickSetup_screen_loginLoading" class="ibuildapp-content" style="display: none;">
        <h2><?php _e('Installing iBuildApp Plugin', 'ibuildapp'); ?></h2>

        <div class="ibuildapp-center ibuildapp-loader">&nbsp;</div>
        <div class="ibuildapp-center ibuildapp-hint"><?php _e('this will be quick', 'ibuildapp'); ?></div>
    </div>

    <div id="ibuildapp_conf_quickSetup_screen_loginIframe" style="display: none; height: 100%;">
        <iframe
            id="ibuildapp_conf_quickSetup_loginIframe"
            name="ibuildapp_conf_quickSetup_loginIframe"
            src=""
            style="min-width: 1020px; width: 100%; min-height: 750px; height: 90%; border: 0;"
            frameBorder="0"
        ></iframe>
    </div>

<script type="text/javascript">
jQuery(function() {
    var
        $screens = {
            'info': null,
            'validateForm': null,
            'validating': null,
            'validateError': null,
            'loginForm': null,
            'loginLoading': null,
            'loginIframe': null
        },
        $form = jQuery('#ibuildapp_conf_quickSetup_loginForm'),
        $iframe = jQuery('#ibuildapp_conf_quickSetup_loginIframe');
    for (var i in $screens) {
        $screens[i] = jQuery('#ibuildapp_conf_quickSetup_screen_' + i);
    }

    function showScreen(screen) {
        jQuery.each($screens, function() {
            this.hide();
        });
        $screens[screen].show();
    }

    jQuery('#ibuildapp_conf_quickSetup_reconfigureButton, #ibuildapp_conf_quickSetup_validateButton, #ibuildapp_conf_quickSetup_retryValidationButton').click(function() {
        showScreen('validating');

        WPIbuildapp.system.validateInstallation(function(response) {
            if (response.valid) {
                $form.submit();
            } else {
                showScreen('validateError');
                jQuery('#ibuildapp_conf_quickSetup_validateError').html(response.error);
            }
        });
    });

    $form.submit(function() {
        $iframe.unbind('load').load(function() {
            showScreen('loginIframe');
        });
        showScreen('loginLoading');
    });

    window.notifySuccessfulRemoteLogin = function(userInfo) {
        $screens.info
            .find('#ibuildapp_conf_quickSetup_info_userLogin').html(userInfo.login).end()
            .find('.ibuildapp-has-config').hide().end()
            .find('.ibuildapp-just-configured').show();
        showScreen('info');
    };

<?php if ('no-config' == $state) { ?>
    showScreen('validateForm');
<?php } elseif ('has-config' == $state) { ?>
    showScreen('info');
<?php } ?>
});
</script>
<?php
}

function ibuildapp_conf_quickSetup_validateInstallation_localize($l10n, $handle, $objectName)
{
    $l10n['ibuildapp_conf_quickSetup_validateInstallation_nonce'] = wp_create_nonce('ibuildapp_conf_quickSetup_validateInstallation');

    return $l10n;
}
add_action('ibuildapp_localize_script', 'ibuildapp_conf_quickSetup_validateInstallation_localize', 10, 3);

function ibuildapp_conf_quickSetup_validateInstallation_ajax()
{
    $nonce = 'ibuildapp_conf_quickSetup_validateInstallation';

    if ('POST' == $_SERVER['REQUEST_METHOD']) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }

        $response = array(
            'valid' => true,
            'error' => ''
        );

        try {
            $apiResponse = IbuildappApiClient::system_status();
            if (!isset($apiResponse['status']) || IbuildappApiClient::SYSTEM_STATUS_OK != $apiResponse['status']) {
                throw new Exception(__('iBuildApp server hadn&#8217;t responded', 'ibuildapp'));
            }

            $apiResponse = IbuildappApiClient::system_validatePlugin();
            if (!$apiResponse['valid']) {
                $message = '';
                switch ($apiResponse['errorCode']) {
                    case IbuildappApiClient::SYSTEM_VALIDATE_ERRORCODE_OUTDATED:
                        $message = __('Your plugin is outdated. Please update', 'ibuildapp');
                        break;
                    default:
                        $message = __('An unknown error occured', 'ibuildapp');
                        break;
                }

                throw new Exception($message);
            }
        } catch (Exception $ex) {
            $response['valid'] = false;
            $response['error'] = $ex->getMessage();
        }

        echo json_encode($response);
    }

    exit();
}
add_action('wp_ajax_ibuildapp_conf_quickSetup_validateInstallation_ajax', 'ibuildapp_conf_quickSetup_validateInstallation_ajax');

function ibuildapp_conf_apiKey()
{
    $nonceForm = 'ibuildapp_conf_apiKey_form';
    $nonceLogin = 'ibuildapp_conf_apiKey_remoteLogin';

    if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['ibuildapp_user_api_id'], $_POST['ibuildapp_user_api_key'])) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonceForm)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }

        Ibuildapp::setOption('user_api_id', $_POST['ibuildapp_user_api_id']);
        Ibuildapp::setOption('user_api_key', $_POST['ibuildapp_user_api_key']);

        if (empty($_POST['ibuildapp_user_api_id']) && empty($_POST['ibuildapp_user_api_key'])) {
            Ibuildapp::saveOptions();
        } else {
            try {
                $response = IbuildappApiClient::user_login();

                if ($response['id'] == Ibuildapp::getOption('user_api_id')) {
                    Ibuildapp::saveOptions();
                } else {
                    throw new Exception('Wrong data');
                }
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
        }
    } elseif (
        isset(
            $_GET['action'],
            $_GET['remoteLoginData'],
            $_GET['remoteLoginData']['id'],
            $_GET['remoteLoginData']['token']
        ) && 'getToken' === $_GET['action']
    ) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonceLogin)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }

        try {
            $response = IbuildappApiClient::user_getApiKey($_GET['remoteLoginData']['id'], $_GET['remoteLoginData']['token']);

            Ibuildapp::setOption('user_api_id', $response['id']);
            Ibuildapp::setOption('user_api_key', $response['key']);

            Ibuildapp::saveOptions();

            ibuildapp_common_jsReloadPage('?page=ibuildapp-conf');
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

?>
    <form method="post" action="<?php echo Ibuildapp::getOption('site_url') . Ibuildapp::LOGIN_ENDPOINT; ?>">
    <input type="hidden" name="remoteLogin[action]" value="getToken" />
    <input type="hidden" name="remoteLogin[return_url]" value="<?php echo rawurlencode((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ;?>" />
    <?php wp_nonce_field($nonceLogin, 'remoteLoginData[_wpnonce]', false); ?>
    <input type="hidden" name="remoteLoginData[action]" value="getToken" />
    <p class="submit"><input type="submit" class="button-primary" value="Login/register on iBuildApp" /></p>
    </form>

    <form method="post" action="">
    <?php wp_nonce_field($nonceForm); ?>
    <table class="form-table"><tbody>
        <tr valign="top">
            <th scope="row"><label for="ibuildapp_user_api_id">User ID on iBuildApp</label></th>
            <td>
                <input type="text" id="ibuildapp_user_api_id" name="ibuildapp_user_api_id" value="<?php echo Ibuildapp::getOption('user_api_id'); ?>" size="64" class="regular-text code" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="ibuildapp_user_api_key">User API key</label></th>
            <td>
                <input type="text" id="ibuildapp_user_api_key" name="ibuildapp_user_api_key" value="<?php echo Ibuildapp::getOption('user_api_key'); ?>" size="64" class="regular-text code" />
            </td>
        </tr>
    </tbody></table>
    <p class="submit"><input type="submit" class="button-primary" value="Save Changes" /></p>
    </form>
<?php
}

function ibuildapp_conf_advanced()
{
    $nonce = 'ibuildapp_conf_advanced';

    if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['ibuildapp_site_url'], $_POST['ibuildapp_api_url'])) {
        if (
            function_exists('current_user_can') && !current_user_can('manage_options')
            || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)
        )
        {
            die(__('Cheatin&#8217; uh?'));
        }

        Ibuildapp::setOption('site_url', $_POST['ibuildapp_site_url']);
        Ibuildapp::setOption('api_url', $_POST['ibuildapp_api_url']);
        Ibuildapp::saveOptions();
    }

?>
    <form method="post" action="">
    <?php wp_nonce_field($nonce); ?>
    <table class="form-table"><tbody>
        <tr valign="top">
            <th scope="row"><label for="ibuildapp_site_url">iBuildApp site URL</label></th>
            <td>
                <input type="text" id="ibuildapp_site_url" name="ibuildapp_site_url" value="<?php echo Ibuildapp::getOption('site_url'); ?>" size="64" class="regular-text code" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="ibuildapp_api_url">iBuildApp API URL</label></th>
            <td>
                <input type="text" id="ibuildapp_api_url" name="ibuildapp_api_url" value="<?php echo Ibuildapp::getOption('api_url'); ?>" size="64" class="regular-text code" />
            </td>
        </tr>
    </tbody></table>
    <p class="submit"><input type="submit" class="button-primary" value="Save Changes" /></p>
    </form>
<?php
}

function ibuildapp_conf_remoteLogin()
{
?>
    <form method="get" action="<?php echo Ibuildapp::getOption('site_url') . Ibuildapp::LOGIN_ENDPOINT; ?>" target="_blank">
    <input type="hidden" name="action" value="remote_login" />
    <input type="hidden" name="id" value="<?php echo Ibuildapp::getOption('user_api_id'); ?>" />
    <input type="hidden" name="key" value="<?php echo Ibuildapp::getOption('user_api_key'); ?>" />
    <p class="submit"><input type="submit" class="button-primary" value="Login" /></p>
    </form>
<?php
}
