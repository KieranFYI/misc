<?php

namespace KieranFYI\Tests\Misc\Unit\Traits;

use Exception;
use Illuminate\Support\Facades\Http;
use KieranFYI\Tests\Misc\Helpers\HTTPRequestTraitClass;
use KieranFYI\Tests\Misc\TestCase;

class HTTPRequestTraitTest extends TestCase
{

    private HTTPRequestTraitClass $client;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->client = new HTTPRequestTraitClass();
    }

    /**
     * @throws Exception
     */
    public function testGet()
    {
        $response = $this->client->get('/');
        $this->assertTrue($response->successful());
    }

    /**
     * @throws Exception
     */
    public function testPost()
    {
        $response = $this->client->post('/');
        $this->assertTrue($response->successful());
    }

    /**
     * @throws Exception
     */
    public function testPostWithParams()
    {
        $response = $this->client->post('/', ['param1' => 'testPostWithParams']);
        $this->assertTrue($response->successful());
    }

    /**
     * @throws Exception
     */
    public function testPostWithParamsWithoutJson()
    {
        $response = $this->client->post('/', ['param1' => 'testPostWithParamsWithoutJson'], false);
        $this->assertTrue($response->successful());
    }

    /**
     * @throws Exception
     */
    public function testPostWithoutJson()
    {
        $response = $this->client->post('/', json: false);
        $this->assertTrue($response->successful());
    }

    /**
     * @throws Exception
     */
    public function testDownload()
    {
        $file = './tests/HTTPRequestTraitTest_Download';
        if (file_exists($file)) {
            unlink($file);
        }
        $response = $this->client->download('/', $file);
        $this->assertTrue($response->successful());
        $this->assertFileExists($file);
        unlink($file);
    }

}