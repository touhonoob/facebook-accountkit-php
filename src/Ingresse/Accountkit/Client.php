<?php

namespace Ingresse\Accountkit;

use Ingresse\Accountkit\Config;
use GuzzleHttp\Client as GuzzleClient;
use Exception;

class Client
{
    /**
     * @var Ingresse\Accountkit\Config
     */
    private $config;
    /**
     * @var GuzzleHttp\Client
     */
    private $guzzle;
    /**
     * @var string
     */
    private $userPhone;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->guzzle = new GuzzleClient;
    }

    /**
     * @param  string $requestCode
     * @return boolean
     */
    public function validate($requestCode)
    {
        $this->callUserData($this->callAccessToken($requestCode));
        return true;
    }

    /**
     * @param  string $requestCode
     * @return string
     * @throws Exception
     */
    public function callAccessToken($requestCode)
    {
        $appId           = $this->config->getAppId();
        $appSecret       = $this->config->getAppSecret();
        $appAccessToken  = sprintf('AA|%s|%s', $appId, $appSecret);
        $response        = $this->guzzle->request(
            'GET',
            $this->config->getUrlToken(),
            [
                'query' => [
                    'grant_type'   => 'authorization_code',
                    'code'         => $requestCode,
                    'access_token' => $appAccessToken,
                ]
            ]
        );

        if (200 != $response->getStatusCode()) {
            throw new Exception("Error on Status Code Request Token", 1);
        }

        $responseHeader = $response->getHeaderLine('content-type');

        if (false == preg_match('/application\/json/', $responseHeader)) {
            throw new Exception("Error on content type Request Token", 2);
        }

        $authResponse = json_decode($response->getBody(), true);

        if (!isset($authResponse['access_token'])) {
            throw new Exception("Error on token code Request Token", 3);
        }

        return $authResponse['access_token'];
    }

    /**
     * @param  string $authAccessToken
     * @return string
     * @throws Exception
     */
    public function callUserData($authAccessToken)
    {
        $hash = hash_hmac(
            'sha256',
            $authAccessToken,
            $this->config->getAppSecret()
        );

        $response = $this->guzzle->request(
            'GET',
            $this->config->getUrlUser(),
            [
                'query' => [
                    'appsecret_proof' => $hash,
                    'access_token'    => $authAccessToken,
                ]
            ]
        );

        if (200 != $response->getStatusCode()) {
            throw new Exception("Error on Status Code Request User", 1);
        }

        $responseHeader = $response->getHeaderLine('content-type');

        if (false == preg_match('/application\/json/', $responseHeader)) {
            throw new Exception("Error on Content Type Request User", 2);
        }

        $userResponse    = json_decode($response->getBody(), true);

        if (!isset($userResponse['phone']['number'])) {
            throw new Exception("Error on Phone Request User", 3);
        }

        $this->userPhone = $userResponse['phone']['number'];

    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->userPhone;
    }

    /**
     * @param GuzzleHttp\Client
     */
    public function setGuzzle(GuzzleClient $guzzle)
    {
        $this->guzzle = $guzzle;
    }
}
