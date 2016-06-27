<?php

namespace Ingresse\Accountkit\Tests;

use Ingresse\Accountkit\Client;
use Ingresse\Accountkit\Config;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;

class ClientTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->requestCode = 'AQCaNAXimMpqr2cQoPsrcbmbaDDtjwu5nV';
    }

    /**
     * @covers Ingresse\Accountkit\Client
     */
    public function testValidateTokenWithSuccess()
    {
        $tokenResponse = new Response(
            200,
            ['content-type' => 'application/json; charset=UTF-8'],
            json_encode(['access_token' => 'abc-123'])
        );
        $userResponse  = new Response(
            200,
            ['content-type' => 'application/json'],
            json_encode(["phone" => ["number" => "+551190908080"]])
        );

        $client   = $this->getClient($tokenResponse, $userResponse);
        $response = $client->validate($this->requestCode);

        $this->assertTrue($response);
        $this->assertEquals('+551190908080', $client->getPhone());
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Exception
     */
    public function testValidateThrowsExceptionForTokenResponseStatusCode()
    {
        $tokenResponse = new Response(400);
        $client        = $this->getClient($tokenResponse);
        $response      = $client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Exception
     */
    public function testValidateThrowsExceptionForTokenResponseContentType()
    {
        $tokenResponse = new Response(200, ['content-type' => 'charset=UTF-8']);
        $client        = $this->getClient($tokenResponse);
        $response      = $client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Exception
     */
    public function testValidateThrowsExceptionForTokenResponseBody()
    {
        $tokenResponse = new Response(
            200,
            ['content-type' => 'application/json; charset=UTF-8'],
            json_encode(['wrong_key' => 'abc-123'])
        );

        $client   = $this->getClient($tokenResponse);
        $response = $client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Exception
     */
    public function testValidateThrowsExceptionForUserResponseStatusCode()
    {
        $tokenResponse = new Response(
            200,
            ['content-type' => 'application/json; charset=UTF-8'],
            json_encode(['access_token' => 'abc-123'])
        );
        $userResponse  = new Response(400);

        $client   = $this->getClient($tokenResponse, $userResponse);
        $response = $client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Exception
     */
    public function testValidateThrowsExceptionForUserResponseContentType()
    {
        $tokenResponse = new Response(
            200,
            ['content-type' => 'application/json; charset=UTF-8'],
            json_encode(['access_token' => 'abc-123'])
        );
        $userResponse  = new Response(
            200,
            ['content-type' => 'json'],
            json_encode(["phone" => ["number" => "+551190908080"]])
        );

        $client   = $this->getClient($tokenResponse, $userResponse);
        $response = $client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Exception
     */
    public function testValidateThrowsExceptionForUserResponseBody()
    {
        $tokenResponse = new Response(
            200,
            ['content-type' => 'application/json; charset=UTF-8'],
            json_encode(['access_token' => 'abc-123'])
        );
        $userResponse  = new Response(
            200,
            ['content-type' => 'application/json'],
            json_encode(["phone" => ''])
        );

        $client   = $this->getClient($tokenResponse, $userResponse);
        $response = $client->validate($this->requestCode);
    }

    /**
     * @param  string $requestCode
     * @param  GuzzleHttp\Psr7\Response $tokenResponse
     * @param  GuzzleHttp\Psr7\Response $userResponse
     * @return GuzzleHttp\Client
     */
    public function getClient($tokenResponse, $userResponse = null)
    {
        $appId         = '1234567890';
        $appSecret     = 'secret-test-123';

        $guzzle = $this->getMock('GuzzleHttp\Client', ['request']);
        $guzzle
            ->expects($this->at(0))
            ->method('request')
            ->with(
                'GET',
                Config::ACCES_TOKEN_URL,
                [
                    'query' => [
                        'grant_type'   => 'authorization_code',
                        'code'         => $this->requestCode,
                        'access_token' => sprintf('AA|%s|%s', $appId, $appSecret),
                    ]
                ]
            )
            ->will($this->returnValue($tokenResponse));

        if (null != $userResponse) {
            $guzzle
                ->expects($this->at(1))
                ->method('request')
                ->with(
                    'GET',
                    Config::USER_URL,
                    [
                        'query' => [
                            'appsecret_proof' => hash_hmac(
                                'sha256',
                                'abc-123',
                                'secret-test-123'
                            ),
                            'access_token'    => 'abc-123',
                        ]
                    ]
                )
                ->will($this->returnValue($userResponse));
        }

        $config = new Config(['app_id' => $appId, 'app_secret' => $appSecret]);
        $client = new Client($config);
        $client->setGuzzle($guzzle);
        return $client;
    }
}
