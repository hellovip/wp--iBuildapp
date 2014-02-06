<?php

/**
 * @uses IbuildappApiClient
 */
class IbuildappApp
{
    const STATE_JUST_CREATED = 'STATE_JUST_CREATED';
    const STATE_PARSING = 'STATE_PARSING';
    const STATE_PARSED = 'STATE_PARSED';
    const STATE_CREATED = 'STATE_CREATED';

    /**
     * ID of this app in WP
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $state = self::STATE_JUST_CREATED;

    /**
     * @var integer
     */
    protected $parserId;

    /**
     * @var array
     */
    protected $parsedData = array();

    /**
     * iBuildApp app ID
     * @var integer
     */
    protected $appId;

    /**
     * @var string
     */
    protected $apiKey;

    public function __construct($id, $data = array())
    {
        $this->id = $id;

        if (isset($data['state'])) {
            $this->state = $data['state'];
        }
        if (isset($data['parserId'])) {
            $this->parserId = $data['parserId'];
        }
        if (isset($data['parsedData'])) {
            $this->parsedData = $data['parsedData'];
        }
        if (isset($data['appId'])) {
            $this->appId = $data['appId'];
        }
        if (isset($data['apiKey'])) {
            $this->apiKey = $data['apiKey'];
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getState()
    {
        return $this->state;
    }

    public function isJustCreated()
    {
        return self::STATE_JUST_CREATED === $this->state;
    }

    public function isParsing()
    {
        return self::STATE_PARSING === $this->state;
    }

    public function isParsed()
    {
        return self::STATE_PARSED === $this->state;
    }

    public function isCreated()
    {
        return self::STATE_CREATED === $this->state;
    }

    public function getParsedData()
    {
        return $this->parsedData;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @throws Exception
     */
    public function parse($siteUrl)
    {
        $response = IbuildappApiClient::parser_parse($siteUrl);

        $this->parserId = $response['parserId'];
        $this->state = self::STATE_PARSING;

        $this->save();

        return $response['state'];
    }

    /**
     * @throws Exception
     */
    public function checkIfParsed()
    {
        $response = IbuildappApiClient::parser_getParsedData($this->parserId);

        $this->parsedData = $response['data'];
        if (IbuildappApiClient::PARSER_STATE_DONE == $response['state']) {
            $this->state = self::STATE_PARSED;
        }
        $this->save();

        return $response['state'];
    }

    /**
     * @throws Exception
     */
    public function create($templateId)
    {
        $response = IbuildappApiClient::parser_createApp($this->parserId, $templateId);

        $this->parsedData = array();
        $this->appId = $response['id'];
        $this->apiKey = $response['key'];
        $this->state = self::STATE_CREATED;

        $this->save();

        return $response;
    }

    public function save()
    {
        Ibuildapp::saveApp($this->id);
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        if (!$this->isCreated()) {
            return false;
        }

        $response = IbuildappApiClient::app_delete($this);

        Ibuildapp::deleteApp($this->getId());

        return true;
    }

    /**
     *
     */
    public function deleteLocal()
    {
        Ibuildapp::deleteApp($this->getId());

        return true;
    }

    public function authHeadersArray()
    {
        return array_merge(
            Ibuildapp::authHeadersArray(),
            array(
                'X-API-App-Token' => $this->apiKey
            )
        );
    }

    public function getStorableData()
    {
        return array(
            'state' => $this->state,
            'parserId' => $this->parserId,
            'parsedData' => $this->parsedData,
            'appId' => $this->appId,
            'apiKey' => $this->apiKey
        );
    }
}
