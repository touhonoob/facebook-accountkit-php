<?php

namespace Ingresse\Accountkit;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\Psr7\Response;
use Ingresse\Accountkit\Exception\RequestException;
use Ingresse\Accountkit\Exception\ResponseFieldException;
use Ingresse\Accountkit\Exception\ResponseFormatException;
use Ingresse\Accountkit\Exception\UnexpectedException;
use Ingresse\Accountkit\Exception\VerifyException;

class Client
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var \GuzzleHttp\Client
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
     * @throws RequestException
     * @throws ResponseFieldException
     * @throws VerifyException
     * @throws UnexpectedException
     */
    public function validate($requestCode)
    {
        $this->callUserData($this->callAccessToken($requestCode));
        return true;
    }

    /**
     * @param  string $requestCode
     * @return string
     * @throws RequestException
     * @throws ResponseFieldException
     * @throws ResponseFormatException
     * @throws VerifyException
     * @throws UnexpectedException
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
     * @throws RequestException
     * @throws ResponseFieldException
     * @throws ResponseFormatException
     * @throws VerifyException
     * @throws UnexpectedException
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

    /**
     * @param string $url
     * @param array $params
     * @return Response
     * @throws VerifyException
     * @throws RequestException
     * @throws UnexpectedException
     */
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
     * @param  Response $response
     * @return array
     * @throws ResponseFormatException
     */
    private function convertResponse(Response $response)
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
     * @param \GuzzleHttp\Client
     */
    public function setGuzzle(GuzzleClient $guzzle)
    {
        $this->guzzle = $guzzle;
    }
}
