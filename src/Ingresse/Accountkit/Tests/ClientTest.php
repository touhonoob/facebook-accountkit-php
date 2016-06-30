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
        $this->appId       = '1234567890';
        $this->appSecret   = 'secret-test-123';
        $this->requestCode = 'AQCaNAXimMpqr2cQoPsrcbmbaDDtjwu5nV';

        $this->guzzle      = $this->getMock('GuzzleHttp\Client', ['request']);

        $config = new Config([
            'app_id'     => $this->appId,
            'app_secret' => $this->appSecret
        ]);

        $this->client = new Client($config);
        $this->client->setGuzzle($this->guzzle);
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
            json_encode([
                "phone" => [
                    "number"          => "+551190908080",
                    "country_prefix"  => "55",
                    "national_number" => "1190908080",
                ]
            ])
        );

        $this->addRequestCallToken($tokenResponse);
        $this->addRequestCallUSer($userResponse);

        $response = $this->client->validate($this->requestCode);

        $this->assertTrue($response);
        $this->assertEquals('55', $this->client->getDDI());
        $this->assertEquals('1190908080', $this->client->getPhone());
        $this->assertEquals('+551190908080', $this->client->getFullPhonenumber());
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Ingresse\Accountkit\Exception\VerifyException
     * @expectedExceptionMessage Accountkit Validation Unsuccessful
     */
    public function testValidateThrowsExceptionForResponseWithWrongStatusCode()
    {
        $clientException = new \GuzzleHttp\Exception\ClientException(
            'Message from accountkit server invalidating verifying',
            $this->getMock('Psr\Http\Message\RequestInterface'),
            $this->getMock('Psr\Http\Message\ResponseInterface')
        );

        $this->addRequestCallToken($clientException, 'throwException');
        $this->client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Ingresse\Accountkit\Exception\RequestException
     * @expectedExceptionMessage Request was not done properly
     */
    public function testValidateThrowsExceptionForConnectionError()
    {
        $clientException = new \GuzzleHttp\Exception\ServerException(
            'Message from accountkit server invalidating verifying',
            $this->getMock('Psr\Http\Message\RequestInterface'),
            $this->getMock('Psr\Http\Message\ResponseInterface')
        );

        $this->addRequestCallToken($clientException, 'throwException');
        $this->client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Ingresse\Accountkit\Exception\UnexpectedException
     * @expectedExceptionMessage Unexpected Error into Accountkit Request
     */
    public function testValidateThrowsExceptionForUnexpectedError()
    {
        $clientException = new \Exception('Exception error message');
        $this->addRequestCallToken($clientException, 'throwException');
        $this->client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Ingresse\Accountkit\Exception\ResponseFormatException
     * @expectedExceptionMessage Unexpected Response Format
     */
    public function testValidateThrowsExceptionForResponseContentType()
    {
        $tokenResponse = new Response(200, ['content-type' => 'charset=UTF-8']);
        $this->addRequestCallToken($tokenResponse);
        $this->client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Ingresse\Accountkit\Exception\ResponseFieldException
     * @expectedExceptionMessage Response Field Not Found - access_token
     */
    public function testValidateThrowsExceptionForTokenResponseBody()
    {
        $tokenResponse = new Response(
            200,
            ['content-type' => 'application/json; charset=UTF-8'],
            json_encode(['wrong_key' => 'abc-123'])
        );

        $this->addRequestCallToken($tokenResponse);
        $this->client->validate($this->requestCode);
    }

    /**
     * @covers Ingresse\Accountkit\Client
     * @expectedException Ingresse\Accountkit\Exception\ResponseFieldException
     * @expectedExceptionMessage Response Field Not Found - phone
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

        $this->addRequestCallToken($tokenResponse);
        $this->addRequestCallUSer($userResponse);
        $this->client->validate($this->requestCode);
    }

    private function addRequestCallToken($response, $responseType = 'returnValue')
    {
        $this->guzzle
            ->expects($this->at(0))
            ->method('request')
            ->with(
                'GET',
                Config::ACCES_TOKEN_URL,
                [
                    'query' => [
                        'grant_type'   => 'authorization_code',
                        'code'         => $this->requestCode,
                        'access_token' => sprintf(
                            'AA|%s|%s',
                            $this->appId,
                            $this->appSecret
                        ),
                    ]
                ]
            )
            ->will($this->$responseType($response));
    }

    private function addRequestCallUSer($response, $responseType = 'returnValue')
    {
        $this
            ->guzzle
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
            ->will($this->$responseType($response));
    }
}
