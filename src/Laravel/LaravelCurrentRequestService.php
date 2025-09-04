<?php

declare(strict_types=1);

namespace OpenIDConnect\Laravel;

use OpenIDConnect\Interfaces\CurrentRequestServiceInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Slim\Psr7\Factory\UriFactory;

class LaravelCurrentRequestService implements CurrentRequestServiceInterface
{
    public function getRequest(): ServerRequestInterface
    {
        // Create Slim PSR-17 factories
        $psrHttpFactory = new PsrHttpFactory(
            new ServerRequestFactory(),
            new UriFactory(),
            new UploadedFileFactory(),
            new StreamFactory()
        );

        // Convert Laravel request to PSR-7
        return $psrHttpFactory->createRequest(request());
    }
}
