<?php

namespace Tests\Unit;

use App\Core\Router;
use App\Core\Route;
use Tests\TestCase;

/**
 * Test unitaire pour la classe Router
 */
class RouterTest extends TestCase
{
    /**
     * @var Router
     */
    private Router $router;

    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
    }
    
    /**
     * Teste la création d'une route GET
     */
    public function testCanCreateGetRoute(): void
    {
        $route = $this->router->get('/test', 'TestController@index');
        
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('GET', $this->getRouteMethod($route));
        $this->assertEquals('/test', $this->getRoutePath($route));
        $this->assertEquals('TestController@index', $this->getRouteHandler($route));
    }
    
    /**
     * Teste la création d'une route POST
     */
    public function testCanCreatePostRoute(): void
    {
        $route = $this->router->post('/test', 'TestController@store');
        
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('POST', $this->getRouteMethod($route));
        $this->assertEquals('/test', $this->getRoutePath($route));
        $this->assertEquals('TestController@store', $this->getRouteHandler($route));
    }
    
    /**
     * Teste la création d'une route PUT
     */
    public function testCanCreatePutRoute(): void
    {
        $route = $this->router->put('/test/1', 'TestController@update');
        
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('PUT', $this->getRouteMethod($route));
        $this->assertEquals('/test/1', $this->getRoutePath($route));
        $this->assertEquals('TestController@update', $this->getRouteHandler($route));
    }
    
    /**
     * Teste la création d'une route DELETE
     */
    public function testCanCreateDeleteRoute(): void
    {
        $route = $this->router->delete('/test/1', 'TestController@destroy');
        
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('DELETE', $this->getRouteMethod($route));
        $this->assertEquals('/test/1', $this->getRoutePath($route));
        $this->assertEquals('TestController@destroy', $this->getRouteHandler($route));
    }
    
    /**
     * Teste l'ajout d'un middleware à une route
     */
    public function testCanAddMiddlewareToRoute(): void
    {
        $route = $this->router->get('/admin', 'AdminController@index')
                            ->middleware('auth');
        
        $this->assertContains('auth', $route->getMiddlewares());
    }
    
    /**
     * Teste la correspondance d'une route simple
     */
    public function testCanMatchSimpleRoute(): void
    {
        $this->router->get('/users', 'UserController@index');
        
        $result = $this->invokeRouterMethod('findRoute', ['GET', '/users', &$params]);
        
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('UserController@index', $result->getHandler());
        $this->assertEmpty($params);
    }
    
    /**
     * Teste la correspondance d'une route avec paramètres
     */
    public function testCanMatchRouteWithParameters(): void
    {
        $this->router->get('/users/{id}', 'UserController@show');
        
        $result = $this->invokeRouterMethod('findRoute', ['GET', '/users/123', &$params]);
        
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('UserController@show', $result->getHandler());
        $this->assertEquals(['id' => '123'], $params);
    }
    
    /**
     * Teste la correspondance d'une route avec plusieurs paramètres
     */
    public function testCanMatchRouteWithMultipleParameters(): void
    {
        $this->router->get('/users/{id}/posts/{post_id}', 'UserController@showPost');
        
        $result = $this->invokeRouterMethod('findRoute', ['GET', '/users/123/posts/456', &$params]);
        
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('UserController@showPost', $result->getHandler());
        $this->assertEquals(['id' => '123', 'post_id' => '456'], $params);
    }
    
    /**
     * Teste qu'une route non correspondante retourne null
     */
    public function testNonMatchingRouteReturnsNull(): void
    {
        $this->router->get('/users', 'UserController@index');
        
        $result = $this->invokeRouterMethod('findRoute', ['GET', '/posts', &$params]);
        
        $this->assertNull($result);
    }
    
    /**
     * Teste qu'une méthode HTTP non correspondante retourne null
     */
    public function testNonMatchingMethodReturnsNull(): void
    {
        $this->router->get('/users', 'UserController@index');
        
        $result = $this->invokeRouterMethod('findRoute', ['POST', '/users', &$params]);
        
        $this->assertNull($result);
    }
    
    /**
     * Invoque une méthode privée du routeur par réflexion
     */
    private function invokeRouterMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(Router::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($this->router, $parameters);
    }
    
    /**
     * Obtient la méthode HTTP d'une route par réflexion
     */
    private function getRouteMethod(Route $route): string
    {
        $reflection = new \ReflectionClass(Route::class);
        $property = $reflection->getProperty('method');
        $property->setAccessible(true);
        
        return $property->getValue($route);
    }
    
    /**
     * Obtient le chemin d'une route par réflexion
     */
    private function getRoutePath(Route $route): string
    {
        $reflection = new \ReflectionClass(Route::class);
        $property = $reflection->getProperty('path');
        $property->setAccessible(true);
        
        return $property->getValue($route);
    }
    
    /**
     * Obtient le handler d'une route par réflexion
     */
    private function getRouteHandler(Route $route): string
    {
        $reflection = new \ReflectionClass(Route::class);
        $property = $reflection->getProperty('handler');
        $property->setAccessible(true);
        
        return $property->getValue($route);
    }
} 