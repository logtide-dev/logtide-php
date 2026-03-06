<?php

declare(strict_types=1);

namespace LogTide\Slim\Tests\Unit;

use LogTide\Client;
use LogTide\LogtideSdk;
use LogTide\Options;
use LogTide\Slim\LogtideErrorMiddleware;
use LogTide\State\Hub;
use LogTide\Transport\NullTransport;
use LogTide\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class LogtideErrorMiddlewareTest extends TestCase
{
    private function createSpyTransport(): TransportInterface
    {
        return new class implements TransportInterface {
            public array $sentLogs = [];
            public function sendLogs(array $events): void { $this->sentLogs = array_merge($this->sentLogs, $events); }
            public function sendSpans(array $spans): void {}
            public function flush(): void {}
            public function close(): void {}
        };
    }

    protected function tearDown(): void
    {
        LogtideSdk::reset();
    }

    private function setupSdk(?TransportInterface $transport = null): void
    {
        $client = new Client(
            Options::fromArray(['default_integrations' => false]),
            $transport ?? new NullTransport(),
        );
        LogtideSdk::setCurrentHub(new Hub($client));
    }

    public function testPassesThroughOnSuccess(): void
    {
        $this->setupSdk();
        $responseFactory = new ResponseFactory();
        $middleware = new LogtideErrorMiddleware($responseFactory);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse(200);
            }
        };

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCapturesExceptionAndReturns500(): void
    {
        $transport = $this->createSpyTransport();
        $this->setupSdk($transport);
        $responseFactory = new ResponseFactory();
        $middleware = new LogtideErrorMiddleware($responseFactory);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('test error');
            }
        };

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/fail');
        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertNotEmpty($transport->sentLogs);
    }

    public function testRendersDefaultError(): void
    {
        $this->setupSdk();
        $responseFactory = new ResponseFactory();
        $middleware = new LogtideErrorMiddleware($responseFactory);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('boom');
            }
        };

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $middleware->process($request, $handler);

        $body = (string) $response->getBody();
        $this->assertStringContainsString('Internal Server Error', $body);
    }

    public function testRendersDetailedErrorWhenEnabled(): void
    {
        $this->setupSdk();
        $responseFactory = new ResponseFactory();
        $middleware = new LogtideErrorMiddleware($responseFactory, displayErrorDetails: true);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('detailed error');
            }
        };

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $middleware->process($request, $handler);

        $body = (string) $response->getBody();
        $this->assertStringContainsString('detailed error', $body);
        $this->assertStringContainsString('RuntimeException', $body);
    }

    public function testResponseContentTypeIsHtml(): void
    {
        $this->setupSdk();
        $responseFactory = new ResponseFactory();
        $middleware = new LogtideErrorMiddleware($responseFactory);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('error');
            }
        };

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $middleware->process($request, $handler);

        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
    }
}
