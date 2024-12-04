<?php

declare(strict_types=1);

namespace Tempest\Router\Tests\Routing\Construction;

use PHPUnit\Framework\TestCase;
use Tempest\Http\Method;
use Tempest\Router\Delete;
use Tempest\Router\Patch;
use Tempest\Router\Put;
use Tempest\Router\Route;
use Tempest\Router\RouteConfig;
use Tempest\Router\Routing\Construction\RouteConfigurator;
use Tempest\Router\Routing\Matching\MatchingRegex;

/**
 * @internal
 */
final class RouteConfiguratorTest extends TestCase
{
    private RouteConfigurator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RouteConfigurator();
    }

    public function test_empty(): void
    {
        $this->assertEquals(new RouteConfig(), $this->subject->toRouteConfig());
    }

    public function test_adding_static_routes(): void
    {
        $routes = [
            new Route('/1', Method::GET),
            new Route('/2', Method::POST),
            new Route('/3', Method::GET),
            new Delete('/4'),
            new Put('/5'),
            new Patch('/6'),
        ];

        foreach ($routes as $route) {
            $this->subject->addRoute($route);
        }

        $config = $this->subject->toRouteConfig();

        $this->assertEquals([
            'GET' => [
                '/1' => $routes[0],
                '/1/' => $routes[0],
                '/3' => $routes[2],
                '/3/' => $routes[2],
            ],
            'POST' => [
                '/2' => $routes[1],
                '/2/' => $routes[1],
            ],
            'DELETE' => [
                '/4' => $routes[3],
                '/4/' => $routes[3],
            ],
            'PUT' => [
                '/5' => $routes[4],
                '/5/' => $routes[4],
            ],
            'PATCH' => [
                '/6' => $routes[5],
                '/6/' => $routes[5],
            ],
        ], $config->staticRoutes);
        $this->assertEquals([], $config->dynamicRoutes);
        $this->assertEquals([], $config->matchingRegexes);
    }

    public function test_adding_dynamic_routes(): void
    {
        $routes = [
            new Route('/dynamic/{id}', Method::GET),
            new Route('/dynamic/{id}', Method::PATCH),
            new Route('/dynamic/{id}/view', Method::GET),
            new Route('/dynamic/{id}/{tag}/{name}/{id}', Method::GET),
            new Delete('/dynamic/{id}'),
            new Put('/dynamic/{id}'),
        ];

        foreach ($routes as $route) {
            $this->subject->addRoute($route);
        }

        $config = $this->subject->toRouteConfig();

        $this->assertEquals([], $config->staticRoutes);
        $this->assertEquals([
            'GET' => [
                'b' => $routes[0],
                'd' => $routes[2],
                'e' => $routes[3],
            ],
            'DELETE' => [
                'f' => $routes[4],
            ],
            'PUT' => [
                'g' => $routes[5],
            ],
            'PATCH' => [
                'c' => $routes[1],
            ],
        ], $config->dynamicRoutes);

        $this->assertEquals([
            'GET' => new MatchingRegex([
                '#^(?|/dynamic(?|/([^/]++)(?|\/?$(*MARK:b)|/view\/?$(*MARK:d)|/([^/]++)(?|/([^/]++)(?|/([^/]++)\/?$(*MARK:e))))))#',
            ]),
            'DELETE' => new MatchingRegex([
                '#^(?|/dynamic(?|/([^/]++)\/?$(*MARK:f)))#',
            ]),
            'PUT' => new MatchingRegex([
                '#^(?|/dynamic(?|/([^/]++)\/?$(*MARK:g)))#',
            ]),
            'PATCH' => new MatchingRegex([
                '#^(?|/dynamic(?|/([^/]++)\/?$(*MARK:c)))#',
            ]),
        ], $config->matchingRegexes);
    }
}
