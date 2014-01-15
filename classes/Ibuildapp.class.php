<?php

/**
 * @uses IbuildappApiClient
 * @uses IbuildappApp
 */
class Ibuildapp
{
    const OPTION_PREFIX = 'ibuildapp_';
    const OPTION_APP_PREFIX = 'application_';
    const LOGIN_ENDPOINT = '/login.php';

    /**
     * Options with default values
     */
    protected static $options_before = array();
    protected static $options = array(
        'version'              => IBUILDAPP_VERSION,
        'site_url'             => 'http://ibuildapp.com',
        'api_url'              => 'http://public-api.ibuildapp.com',
        'user_api_id'          => '',
        'user_api_key'         => '',
        'user_info'            => array(
                                      'id' => 0,
                                      'login' => ''
                                  ),
        'applications_counter' => 0,
        'applications'         => array(),
        'jsonp_tokens'         => array(),
        'jsonp_tokens_limit'   => 10
    );

    /**
     * @var IbuildappApp[]
     */
    protected static $applications = array();

    public static function init()
    {
        self::versionUpdate();

        foreach (self::$options as $k => $v) {
            if (false !== ($option = get_option(Ibuildapp::OPTION_PREFIX . $k))) {
                self::$options[$k] = $option;
            }
            self::$options_before[$k] = self::$options[$k];
        }

        self::afterInit();
        self::loadApps();
    }

    /*
     * Options management
     */

    protected static function afterInit()
    {
        IbuildappApiClient::setApiUrl(self::$options['api_url']);
    }

    public function getOption($name) {
        if (!isset(self::$options[$name])) {
            return null;
        }

        return self::$options[$name];
    }

    public function setOption($name, $value) {
        if (!isset(self::$options[$name])) {
            return false;
        }

        self::$options[$name] = $value;

        return true;
    }

    public static function saveOptions()
    {
        foreach (self::$options as $k => $v) {
            if (self::$options_before[$k] !== $v) {
                update_option(Ibuildapp::OPTION_PREFIX . $k, $v);
                self::$options_before[$k] = $v;
            }
        }

        self::afterInit();
    }

    public static function getUserInfo($key = null)
    {
        if (is_null($key)) {
            return self::$options['user_info'];
        } elseif (isset(self::$options['user_info'][$key])) {
            return self::$options['user_info'][$key];
        }

        return null;
    }

    public static function setUserInfo($keyOrInfo, $value = null)
    {
        if (is_array($keyOrInfo)) {
            foreach ($keyOrInfo as $k => $v) {
                self::setUserInfo($k, $v);
            }
        } elseif (is_string($keyOrInfo)) {
            if (!isset(self::$options['user_info'][$keyOrInfo])) {
                return false;
            }

            self::$options['user_info'][$keyOrInfo] = $value;
        } else {
            return false;
        }

        return true;
    }

    public static function hasKeys()
    {
        return !empty(self::$options['user_api_id']) && !empty(self::$options['user_api_key']);
    }

    public static function authHeadersArray()
    {
        return array(
            'X-API-Id' => Ibuildapp::getOption('user_api_id'),
            'X-API-Key' => Ibuildapp::getOption('user_api_key')
        );
    }

    /*
     * Applications management
     */

    public static function loadApps()
    {
        foreach (self::$options['applications'] as $appId) {
            self::loadApp($appId);
        }
    }

    public static function loadApp($appId)
    {
        if (false !== ($appData = get_option(Ibuildapp::OPTION_PREFIX . Ibuildapp::OPTION_APP_PREFIX . $appId))) {
            self::$applications[$appId] = new IbuildappApp($appId, $appData);

            return true;
        }

        return false;
    }

    public static function saveApp($appId)
    {
        if (!isset(self::$applications[$appId])) {
            return false;
        }

        update_option(Ibuildapp::OPTION_PREFIX . Ibuildapp::OPTION_APP_PREFIX . $appId, self::$applications[$appId]->getStorableData());

        return true;
    }

    public static function deleteApp($appId)
    {
        if (!isset(self::$applications[$appId])) {
            return false;
        }

        unset(self::$options['applications'][array_search($appId, self::$options['applications'])]);
        unset(self::$applications[$appId]);
        delete_option(Ibuildapp::OPTION_PREFIX . Ibuildapp::OPTION_APP_PREFIX . $appId);

        self::saveOptions();

        return true;
    }

    public static function createApp()
    {
        $appId = self::$options['applications_counter']++;
        self::$options['applications'][] = $appId;
        self::$applications[$appId] = new IbuildappApp($appId);

        self::saveApp($appId);
        self::saveOptions();
    }

    public static function hasApps()
    {
        return !empty(self::$applications);
    }

    public static function getApp($appId = null)
    {
        if (empty(self::$applications)) {
            return null;
        }

        if (is_null($appId)) {
            // the first one
            $appId = min(self::$options['applications']);
        } elseif (!isset(self::$applications[$appId])) {
            return null;
        }

        return self::$applications[$appId];
    }

    /*
     * JSONP tokens
     */
    public static function generateJsonpToken()
    {
        if (!is_array(self::$options['jsonp_tokens'])) {
            self::$options['jsonp_tokens'] = array();
        }

        $token = uniqid();

        if (sizeof(self::$options['jsonp_tokens']) >= self::$options['jsonp_tokens_limit']) {
            self::$options['jsonp_tokens'] = array_slice(self::$options['jsonp_tokens'], -1 * (self::$options['jsonp_tokens_limit'] - 1));
        }
        self::$options['jsonp_tokens'][] = $token;
        self::saveOptions();

        return $token;
    }

    public static function validateJsonpToken($token)
    {
        if (!is_array(self::$options['jsonp_tokens'])) {
            return false;
        }
        return in_array($token, self::$options['jsonp_tokens']);
    }

    /*
     * Migrations
     */

    protected static function versionUpdate()
    {
        $dbVersion = get_option(Ibuildapp::OPTION_PREFIX . 'version');

        /* On first install */
        if (false === $dbVersion) {
            update_option(Ibuildapp::OPTION_PREFIX . 'version', IBUILDAPP_VERSION);

            return false;
        }

        $comparison = version_compare($dbVersion, IBUILDAPP_VERSION);
        /* No changes needed */
        if (0 == $comparison) {
            return false;
        }
        /* Downgrade ?! */
        elseif (1 == $comparison) {
        }
        /* Let's update data! */
        else {
        }

        update_option(Ibuildapp::OPTION_PREFIX . 'version', IBUILDAPP_VERSION);

        return true;
    }
}
