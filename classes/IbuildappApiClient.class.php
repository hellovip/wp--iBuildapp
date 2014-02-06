<?php

class IbuildappApiClient
{
    const SYSTEM_STATUS_OK = 'ok';

    const SYSTEM_VALIDATE_ERRORCODE_OK = '';
    const SYSTEM_VALIDATE_ERRORCODE_OUTDATED = 'outdated';

    const PARSER_STATE_CREATED = 'created';
    const PARSER_STATE_INPROGRESS = 'in-progress';
    const PARSER_STATE_DONE = 'done';

    protected static $apiUrl = '';

    public static function setApiUrl($apiUrl)
    {
        self::$apiUrl = $apiUrl;
    }

    protected static function get($endpoint, $body = array(), $headers = array())
    {
        $endpoint .= '?' . build_query($body);
        return self::call('GET', $endpoint, array(), $headers);
    }

    protected static function post($endpoint, $body = array(), $headers = array())
    {
        return self::call('POST', $endpoint, $body, $headers);
    }

    protected static function call($method, $endpoint, $body = array(), $headers = array())
    {
        if (!class_exists('WP_Http')) {
            include_once(ABSPATH . WPINC. '/class-http.php');
        }
        $request = new WP_Http();

        $result = $request->request(
            self::$apiUrl . $endpoint,
            array(
                'method' => $method,
                'timeout' => 30,
                'body' => $body,
                'headers' => $headers
            )
        );

        if (is_wp_error($result)) {
            throw new Exception(sprintf(
                __('An error occured during API call: %s', 'ibuildapp'),
                $result->get_error_message()
            ));
        }

        $response = json_decode($result['body'], true);

        if (200 != $result['response']['code']) {
            throw new Exception(sprintf(
                __('An error occured during API call: %s', 'ibuildapp'),
                sprintf(
                    '[%d] %s',
                    $result['response']['code'],
                    (isset($response['error']) ? $response['error'] : $result['response']['message'])
                )
            ));
        }

        if (!is_array($response)) {
            throw new Exception(sprintf(
                __('An error occured during API call: %s', 'ibuildapp'),
                __('Bad response format', 'ibuildapp')
            ));
        }

        return $response['result'];
    }

    /**
     * @throws Exception
     */
    public static function system_status()
    {
        $response = self::get(
            '/v1/system/status'
        );

        return array(
            'status' => $response['status']
        );
    }

    /**
     * @throws Exception
     */
    public static function system_validatePlugin()
    {
        $response = self::post(
            '/v1/system/validatePlugin',
            array(
                'type' => IBUILDAPP_PLUGIN_TYPE,
                'version' => IBUILDAPP_VERSION
            )
        );

        return array(
            'valid' => $response['valid'],
            'errorCode' => $response['errorCode']
        );
    }

    /**
     * @throws Exception
     */
    public static function user_getApiKey($id, $token)
    {
        $response = self::post(
            '/v1/user/getApiKey',
            array(
                'id' => $id,
                'token' => $token
            )
        );

        return array(
            'id' => $response['id'],
            'key' => $response['apiKey']
        );
    }

    /**
     * @throws Exception
     */
    public static function user_login()
    {
        $response = self::get(
            '/v1/user/login',
            array(),
            Ibuildapp::authHeadersArray()
        );

        return array(
            'id' => $response['id'],
            'login' => $response['login'],
        );
    }

    /**
     * @throws Exception
     */
    public static function parser_parse($url)
    {
        $response = self::post(
            '/v1/parser/parse',
            array(
                'url' => $url
            ),
            Ibuildapp::authHeadersArray()
        );

        return array(
            'parserId' => $response['parserId'],
            'state' => $response['state'],
            'data' => $response['data']
        );
    }

    /**
     * @throws Exception
     */
    public static function parser_getParsedData($parserId)
    {
        $response = self::get(
            '/v1/parser/getParsedData',
            array(
                'parserId' => $parserId
            ),
            Ibuildapp::authHeadersArray()
        );

        return array(
            'parserId' => $response['parserId'],
            'state' => $response['state'],
            'data' => $response['data']
        );
    }

    /**
     * @throws Exception
     */
    public static function parser_getTemplatesList()
    {
        $response = self::post(
            '/v1/parser/getTemplatesList',
            array(
            ),
            Ibuildapp::authHeadersArray()
        );

        return array(
            'templates' => $response['templates']
        );
    }

    /**
     * @throws Exception
     */
    public static function parser_createApp($parserId, $templateId)
    {
        $response = self::post(
            '/v1/parser/createApp',
            array(
                'parserId' => $parserId,
                'templateId' => $templateId
            ),
            Ibuildapp::authHeadersArray()
        );

        return array(
            'id' => $response['id'],
            'key' => $response['key']
        );
    }

    /**
     * @throws Exception
     */
    public static function app_getInfo($app)
    {
        $response = self::get(
            '/v1/app/getInfo',
            array(),
            $app->authHeadersArray()
        );

        return array(
            'id' => $response['id'],
            'name' => $response['appName'],
            'builds' => $response['buildInfo']
        );
    }

    /**
     * @throws Exception
     */
    public static function app_delete($app)
    {
        $response = self::post(
            '/v1/app/delete',
            array(),
            $app->authHeadersArray()
        );

        return array(
            'id' => $response['id'],
            'status' => $response['status']
        );
    }

    /**
     * @throws Exception
     */
    public static function publishing_buildAndroid($app)
    {
        $response = self::post(
            '/v1/publishing/buildAndroid',
            array(),
            $app->authHeadersArray()
        );

        return $response;
    }
}
