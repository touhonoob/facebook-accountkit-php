<?php

namespace Ingresse\Accountkit;

use Ingresse\Accountkit\Config;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use Ingresse\Accountkit\Exception\VerifyException;
use Ingresse\Accountkit\Exception\RequestException;
use Ingresse\Accountkit\Exception\ResponseFormatException;
use Ingresse\Accountkit\Exception\ResponseFieldException;
use Ingresse\Accountkit\Exception\UnexpectedException;
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

        $response = $this->call(
            $this->config->getUrlToken(),
            [
                'query' => [
                    'grant_type'   => 'authorization_code',
                    'code'         => $requestCode,
                    'access_token' => $appAccessToken,
                ]
            ]
        );

        $authResponse = $this->convertResponse($response);

        if (!isset($authResponse['access_token'])) {
            throw new ResponseFieldException('access_token');
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

        $response = $this->call(
            $this->config->getUrlUser(),
            [
                'query' => [
                    'appsecret_proof' => $hash,
                    'access_token'    => $authAccessToken,
                ]
            ]
        );

        $userResponse = $this->convertResponse($response);

        if (!isset($userResponse['phone']['number'])) {
            throw new ResponseFieldException('phone number');
        }

        $this->userPhone = $userResponse['phone'];
    }

    private function call($url, $params)
    {
        try {
            return $this->guzzle->request('GET', $url, $params);
        } catch (GuzzleClientException $e) {
            throw new VerifyException($e);
        } catch (GuzzleServerException $e) {
            throw new RequestException($e);
        } catch (Exception $e) {
            throw new UnexpectedException($e);
        }
    }

    /**
     * @param  GuzzleHttp\Psr7\Response $response
     * @return array
     */
    private function convertResponse($response)
    {
        $responseHeader = $response->getHeaderLine('content-type');

        if (false == preg_match('/application\/json/', $responseHeader)) {
            throw new ResponseFormatException;
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->userPhone['national_number'];
    }

    /**
     * @return string
     */
    public function getDDI()
    {
        return $this->userPhone['country_prefix'];
    }

    /**
     * @return string
     */
    public function getFullPhonenumber()
    {
        return $this->userPhone['number'];
    }

    /**
     * @param GuzzleHttp\Client
     */
    public function setGuzzle(GuzzleClient $guzzle)
    {
        $this->guzzle = $guzzle;
    }
}
