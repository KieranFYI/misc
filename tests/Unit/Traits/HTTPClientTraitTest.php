<?php

namespace KieranFYI\Tests\Misc\Unit\Traits;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use KieranFYI\Misc\Traits\HTTPClientTrait;
use KieranFYI\Tests\Misc\TestCase;

class HTTPClientTraitTest extends TestCase
{
    use HTTPClientTrait;

    public function testUserAgent()
    {
        $userAgent = 'Custom User Agent';
        $this->assertEquals($this, $this->userAgent($userAgent));
        $this->assertEquals($userAgent, $this->userAgent);
    }

    public function testTimeout()
    {
        $timeout = rand(1, 50);
        $this->assertEquals($this, $this->timeout($timeout));
        $this->assertEquals($timeout, $this->timeout);
    }

    public function testNotAuthed()
    {
        $this->assertFalse($this->isAuthed());
    }

    public function testAuth()
    {
        $auth = 'My Auth String';
        $this->assertEquals($this, $this->auth($auth));
        $this->assertEquals($auth, $this->auth);
        $this->assertTrue($this->isAuthed());
    }

    /**
     * @throws Exception
     */
    public function testClient()
    {
        $client = $this->client();
        $options = $client->getOptions();
        $this->assertInstanceOf(PendingRequest::class, $client);
        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('Accept', $options['headers']);
        $this->assertEquals('application/json', $options['headers']['Accept']);
    }

    /**
     * @throws Exception
     * @depends testClient
     */
    public function testClientWithoutJson()
    {
        $client = $this->client(false);
        $options = $client->getOptions();
        $this->assertInstanceOf(PendingRequest::class, $client);
        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayNotHasKey('Accept', $options['headers']);
    }

    /**
     * @throws Exception
     * @depends testUserAgent
     */
    public function testClientWithUserAgent()
    {
        $userAgent = 'Custom User Agent';
        $client = $this->userAgent($userAgent)
            ->client();
        $options = $client->getOptions();
        $this->assertInstanceOf(PendingRequest::class, $client);
        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('User-Agent', $options['headers']);
        $this->assertEquals($userAgent, $options['headers']['User-Agent']);
    }

    /**
     * @throws Exception
     * @depends testUserAgent
     */
    public function testClientWithAuth()
    {
        $auth = 'My Auth String';
        $client = $this->auth($auth)
            ->client();
        $options = $client->getOptions();
        $this->assertInstanceOf(PendingRequest::class, $client);
        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('Authorization', $options['headers']);
        $this->assertEquals($auth, $options['headers']['Authorization']);
    }
}