<?php

declare(strict_types=1);

namespace LogTide\Slim;

use LogTide\LogtideSdk;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorRendererInterface;

class LogtideErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly bool $displayErrorDetails = false,
        private readonly ?ErrorRendererInterface $errorRenderer = null,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $hub = LogtideSdk::getCurrentHub();
            $hub->captureException($e);

            return $this->renderError($request, $e);
        }
    }

    private function renderError(ServerRequestInterface $request, \Throwable $exception): ResponseInterface
    {
        $statusCode = $this->resolveStatusCode($exception);
        $response = $this->responseFactory->createResponse($statusCode);

        if ($this->errorRenderer !== null) {
            $body = $this->errorRenderer->__invoke($exception, $this->displayErrorDetails);
            $response->getBody()->write($body);
            return $response->withHeader('Content-Type', 'text/html');
        }

        $output = $this->renderDefaultError($exception);
        $response->getBody()->write($output);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private function resolveStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getCode();
        }

        return 500;
    }

    private function renderDefaultError(\Throwable $exception): string
    {
        $title = $this->displayErrorDetails
            ? get_class($exception) . ': ' . $exception->getMessage()
            : 'Internal Server Error';

        $html = "<html><head><title>{$title}</title></head><body><h1>{$title}</h1>";

        if ($this->displayErrorDetails) {
            $html .= '<pre>' . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        $html .= '</body></html>';

        return $html;
    }
}
