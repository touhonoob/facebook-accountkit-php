<?php

namespace Ingresse\Accountkit;

use OutOfBoundsException;

class Config
{
    const ACCES_TOKEN_URL = 'https://graph.accountkit.com/v1.0/access_token';
    const USER_URL        = 'https://graph.accountkit.com/v1.0/me';

    /**
     * @var string
     */
    private $appId;
    /**
     * @var string
     */
    private $appSecret;

    /**
     * @param array $params
     */
    public function __construct($params)
    {
        if (!isset($params['app_id']) || !isset($params['app_secret'])) {
            throw new OutOfBoundsException(
                'Config for AccountKit must be defined'
            );
        }

        $this->appSecret = $params['app_secret'];
        $this->appId     = $params['app_id'];
    }

    /**
     * @return string
     */
    public function getUrlToken()
    {
        return self::ACCES_TOKEN_URL;
    }

    /**
     * @return string
     */
    public function getUrlUser()
    {
        return self::USER_URL;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @return string
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }
}