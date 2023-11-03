<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Aura\Router\Generator as AuraGenerator;
use Aura\Router\Map as AuraMap;
use Aura\Router\Matcher as AuraMatcher;
use Aura\Router\Route as AuraRoute;
use Aura\Router\RouterContainer as AuraRouterContainer;
use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\AuraRouter;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;

class AuraRouterTest extends TestCase
{
    /** @var AuraRouterContainer&MockObject */
    private AuraRouterContainer $auraRouterContainer;

    /** @var AuraMap&MockObject */
    private AuraMap $auraMap;

    /** @var AuraMatcher&MockObject */
    private AuraMatcher $auraMatcher;

    /** @var AuraGenerator&MockObject */
    private AuraGenerator $auraGenerator;
    /** @var MiddlewareInterface&MockObject */
    private MiddlewareInterface $middleware;

    protected function setUp(): void
    {
        $this->auraRouterContainer = $this->createMock(AuraRouterContainer::class);
        $this->auraMap             = $this->createMock(AuraMap::class);
        $this->auraMatcher         = $this->createMock(AuraMatcher::class);
        $this->auraGenerator       = $this->createMock(AuraGenerator::class);

        $this->auraRouterContainer->method('getMap')->willReturn($this->auraMap);
        $this->auraRouterContainer->method('getMatcher')->willReturn($this->auraMatcher);
        $this->auraRouterContainer->method('getGenerator')->willReturn($this->auraGenerator);

        $this->middleware = $this->createMock(MiddlewareInterface::class);
    }

    private function getRouter(): AuraRouter
    {
        return new AuraRouter($this->auraRouterContainer);
    }

    private function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    public function testAddingRouteAggregatesRoute(): AuraRouter
    {
        $route  = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $router = $this->getRouter();
        $router->addRoute($route);

        $reflectionClass = new ReflectionClass($router);

        $property = $reflectionClass->getProperty('routesToInject');
        $property->setAccessible(true);

        $this->assertContains($route, $property->getValue($router));

        return $router;
    }

    /**
     * @depends testAddingRouteAggregatesRoute
     */
    public function testMatchingInjectsRouteIntoAuraRouter(): void
    {
        $route  = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $router = $this->getRouter();
        $router->addRoute($route);

        $auraRoute = new AuraRoute();
        $auraRoute->name($route->getName());
        $auraRoute->path($route->getPath());
        $auraRoute->handler($route->getMiddleware());
        $auraRoute->allows($route->getAllowedMethods());

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_GET);
        $request->method('getServerParams')->willReturn([]);

        $this->auraMap->expects(self::once())->method('addRoute')->with($auraRoute);
        $this->auraMatcher->expects(self::once())->method('match')->with($request)->willReturn(false);
        $this->auraMatcher->expects(self::once())->method('getFailedRoute')->willReturn(null);

        $router->match($request);
    }

    /**
     * @depends testAddingRouteAggregatesRoute
     */
    public function testUriGenerationInjectsRouteIntoAuraRouter(): void
    {
        $route  = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $router = $this->getRouter();
        $router->addRoute($route);

        $auraRoute = new AuraRoute();
        $auraRoute->name($route->getName());
        $auraRoute->path($route->getPath());
        $auraRoute->handler($route->getMiddleware());
        $auraRoute->allows($route->getAllowedMethods());

        $this->auraMap->expects(self::once())->method('addRoute')->with($auraRoute);
        $this->auraGenerator->expects(self::once())->method('generateRaw')->with('foo', [])->willReturn('/foo');

        $this->assertEquals('/foo', $router->generateUri('foo'));
    }

    public function testCanSpecifyAuraRouteTokensViaRouteOptions(): void
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $route->setOptions(['tokens' => ['foo' => 'bar']]);

        $auraRoute = new AuraRoute();
        $auraRoute->name('/foo^GET');
        $auraRoute->path('/foo');
        $auraRoute->handler($route->getMiddleware());
        $auraRoute->allows($route->getAllowedMethods());
        $auraRoute->tokens($route->getOptions()['tokens']);

        $this->auraMap->expects(self::once())->method('addRoute')->with($auraRoute);
        // Injection happens when match() or generateUri() are called
        $this->auraGenerator->expects(self::once())->method('generateRaw')->with('foo', [])->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);
        $router->generateUri('foo');
    }

    public function testCanSpecifyAuraRouteValuesViaRouteOptions(): void
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $route->setOptions(['values' => ['foo' => 'bar']]);

        $auraRoute = new AuraRoute();
        $auraRoute->name($route->getName());
        $auraRoute->path($route->getPath());
        $auraRoute->handler($route->getMiddleware());
        $auraRoute->allows($route->getAllowedMethods());
        $auraRoute->defaults($route->getOptions()['values']);

        $this->auraMap->expects(self::once())->method('addRoute')->with($auraRoute);
        // Injection happens when match() or generateUri() are called
        $this->auraGenerator->expects(self::once())->method('generateRaw')->with('foo', [])->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);
        $router->generateUri('foo');
    }

    public function testCanSpecifyAuraRouteWildcardViaRouteOptions(): void
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $route->setOptions(['wildcard' => 'card']);

        $auraRoute = new AuraRoute();
        $auraRoute->name($route->getName());
        $auraRoute->path($route->getPath());
        $auraRoute->handler($route->getMiddleware());
        $auraRoute->allows($route->getAllowedMethods());
        $auraRoute->wildcard($route->getOptions()['wildcard']);

        $this->auraMap->expects(self::once())->method('addRoute')->with($auraRoute);

        // Injection happens when match() or generateUri() are called
        $this->auraGenerator->expects(self::once())->method('generateRaw')->with('foo', [])->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);
        $router->generateUri('foo');
    }

    public function testMatchingRouteShouldReturnSuccessfulRouteResult(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_GET);
        $request->method('getServerParams')->willReturn([]);

        $auraRoute = new AuraRoute();
        $auraRoute->name('/foo');
        $auraRoute->path('/foo');
        $auraRoute->handler('foo');
        $auraRoute->allows([RequestMethod::METHOD_GET]);
        $auraRoute->attributes([
            'action' => 'foo',
            'bar'    => 'baz',
        ]);

        $this->auraMatcher->expects(self::once())->method('match')->with($request)->willReturn($auraRoute);

        $middleware = $this->getMiddleware();
        $router     = $this->getRouter();
        $router->addRoute(new Route('/foo', $middleware, [RequestMethod::METHOD_GET], '/foo'));
        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame('/foo', $result->getMatchedRouteName());
        $this->assertSame($middleware, $result->getMatchedRoute()->getMiddleware());
        $this->assertSame([
            'action' => 'foo',
            'bar'    => 'baz',
        ], $result->getMatchedParams());
    }

    public function testMatchFailureDueToHttpMethodReturnsRouteResultWithAllowedMethods(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_GET);
        $request->method('getServerParams')->willReturn([]);

        $this->auraMatcher->expects(self::once())->method('match')->with($request)->willReturn(false);

        $auraRoute = new AuraRoute();
        $auraRoute->allows([RequestMethod::METHOD_POST]);

        $this->auraMatcher->method('getFailedRoute')->willReturn($auraRoute);

        $router = $this->getRouter();
        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame([RequestMethod::METHOD_POST], $result->getAllowedMethods());
    }

    /**
     * @group failure
     */
    public function testMatchFailureNotDueToHttpMethodReturnsGenericRouteFailureResult(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/bar');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_PUT);
        $request->method('getServerParams')->willReturn([]);

        $this->auraMatcher->expects(self::once())->method('match')->with($request)->willReturn(false);

        $auraRoute = new AuraRoute();

        $this->auraMatcher->method('getFailedRoute')->willReturn($auraRoute);

        $router = $this->getRouter();
        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
        $this->assertSame(Route::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }

    /**
     * @group 53
     */
    public function testCanGenerateUriFromRoutes(): void
    {
        $router = new AuraRouter();
        $route1 = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_POST], 'foo-create');
        $route2 = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'foo-list');
        $route3 = new Route('/foo/{id}', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'foo');
        $route4 = new Route('/bar/{baz}', $this->getMiddleware(), Route::HTTP_METHOD_ANY, 'bar');

        $router->addRoute($route1);
        $router->addRoute($route2);
        $router->addRoute($route3);
        $router->addRoute($route4);

        $this->assertEquals('/foo', $router->generateUri('foo-create'));
        $this->assertEquals('/foo', $router->generateUri('foo-list'));
        $this->assertEquals('/foo/bar', $router->generateUri('foo', ['id' => 'bar']));
        $this->assertEquals('/bar/BAZ', $router->generateUri('bar', ['baz' => 'BAZ']));
    }

    /**
     * @group failure
     * @group 85
     */
    public function testReturns404ResultIfAuraReturnsNullForFailedRoute(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/bar');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_PUT);
        $request->method('getServerParams')->willReturn([]);

        $this->auraMatcher->method('match')->with($request)->willReturn(false);
        $this->auraMatcher->method('getFailedRoute')->willReturn(null);

        $router = $this->getRouter();
        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
        $this->assertSame(Route::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }

    /**
     * @group 149
     */
    public function testGeneratedUriIsNotEncoded(): void
    {
        $router = new AuraRouter();
        $route  = new Route('/foo/{id}', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'foo');

        $router->addRoute($route);

        $this->assertEquals(
            '/foo/bar is not encoded',
            $router->generateUri('foo', ['id' => 'bar is not encoded'])
        );
    }

    public function testSuccessfulRouteResultComposesMatchedRoute(): void
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_GET);
        $request->method('getServerParams')->willReturn([]);

        $router = new AuraRouter();
        $router->addRoute($route);

        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());

        $matched = $result->getMatchedRoute();
        $this->assertSame($route, $matched);
    }

    /** @return array<string, array{0: string}> */
    public static function implicitMethods(): array
    {
        return [
            'head'    => [RequestMethod::METHOD_HEAD],
            'options' => [RequestMethod::METHOD_OPTIONS],
        ];
    }

    /**
     * @dataProvider implicitMethods
     */
    public function testHeadAndOptionsAlwaysResultInRoutingSuccessIfPathMatches(string $method): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn($method);
        $request->method('getServerParams')->willReturn([]);

        // Not mocking the router container or Aura\Route; this particular test
        // is testing how the parts integrate.
        $router = new AuraRouter();
        $route  = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_POST]);
        $router->addRoute($route);

        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->getMatchedRoute());
        $this->assertSame([RequestMethod::METHOD_POST], $result->getAllowedMethods());
    }

    public function testMethodFailureWhenMultipleRoutesUseSamePathShouldResultIn405ListingAllAllowedMethods(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_PATCH);
        $request->method('getServerParams')->willReturn([]);

        // Not mocking the router container or Aura\Route; this particular test
        // is testing how the parts integrate.
        $router = new AuraRouter();
        $router->addRoute(new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]));
        $router->addRoute(new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_POST]));

        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertEquals([RequestMethod::METHOD_GET, RequestMethod::METHOD_POST], $result->getAllowedMethods());
    }

    public function testFailureToMatchSubPathWhenRootPathRouteIsPresentShouldResultIn405(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_GET);
        $request->method('getServerParams')->willReturn([]);

        // Not mocking the router container or Aura\Route; this particular test
        // is testing how the parts integrate.
        $router = new AuraRouter();
        $router->addRoute(new Route('/', $this->getMiddleware(), [RequestMethod::METHOD_GET]));
        $router->addRoute(new Route('/bar', $this->getMiddleware(), [RequestMethod::METHOD_GET]));

        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }

    /** @return list<array{0: string}> */
    public static function allHttpMethods(): array
    {
        return [
            [RequestMethod::METHOD_GET],
            [RequestMethod::METHOD_POST],
            [RequestMethod::METHOD_PUT],
            [RequestMethod::METHOD_PATCH],
            [RequestMethod::METHOD_DELETE],
            [RequestMethod::METHOD_HEAD],
            [RequestMethod::METHOD_OPTIONS],
        ];
    }

    /**
     * @dataProvider allHttpMethods
     */
    public function testWhenRouteAllowsAnyHttpMethodRouterShouldResultInSuccess(string $method): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn($method);
        $request->method('getServerParams')->willReturn([]);

        // Not mocking the router container or Aura\Route; this particular test
        // is testing how the parts integrate.
        $router = new AuraRouter();
        $router->addRoute(new Route('/foo', $this->getMiddleware(), Route::HTTP_METHOD_ANY));

        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess(), 'Routing failed, but should have succeeded');
    }

    public function testFailedRoutingDueToUnknownCausesResultsInFailureRouteNotDueToMethod(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/bar');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_GET);
        $request->method('getServerParams')->willReturn([]);

        // Not mocking the router container or Aura\Route; this particular test
        // is testing how the parts integrate.
        $router = new AuraRouter();
        $router->addRoute(new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_TRACE]));

        $result = $router->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure(), 'Routing did not fail, but should have');
        $this->assertFalse($result->isMethodFailure());
    }

    public function testReturnsRouteFailureForRouteInjectedManuallyIntoBaseRouterButNotRouterBridge(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn(RequestMethod::METHOD_GET);
        $request->method('getServerParams')->willReturn([]);

        $auraRoute = new AuraRoute();
        $auraRoute->name('/foo');
        $auraRoute->path('/foo');
        $auraRoute->handler('foo');
        $auraRoute->allows([RequestMethod::METHOD_GET]);
        $auraRoute->attributes([
            'action' => 'foo',
            'bar'    => 'baz',
        ]);

        $this->auraMatcher->expects(self::once())->method('match')->with($request)->willReturn($auraRoute);

        $router = $this->getRouter();

        $result = $router->match($request);

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }
}
