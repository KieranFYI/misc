<?php

namespace KieranFYI\Tests\Misc\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use KieranFYI\Misc\Traits\ResponseCacheable;
use KieranFYI\Tests\Misc\Models\TestModel;
use Throwable;

class TestController extends Controller
{
    use ResponseCacheable;
    use AuthorizesRequests;

    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['cacheable']);
        $this->authorizeResource(TestModel::class, 'model');
    }

    /**
     * @return Response
     * @throws Throwable
     */
    public function testDefault(): Response
    {
        $this->cached();
        return response(status: 200);
    }

    /**
     * @param TestModel $model
     * @return Response
     * @throws Throwable
     */
    public function testSignature(TestModel $model): Response
    {
        $this->cached();
        return response(status: 200);
    }

    /**
     * @return Response
     * @throws Throwable
     */
    public function testMiddleware(): Response
    {
        $this->cached();
        return response(status: 200);
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'testMiddleware' => 'viewAny',
        ];
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * @return array
     */
    protected function resourceMethodsWithoutModels()
    {
        return ['testMiddleware'];
    }
}