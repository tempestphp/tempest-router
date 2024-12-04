<?php

declare(strict_types=1);

namespace Tempest\Router\Stubs;

use Tempest\Router\IsRequest;
use Tempest\Router\Request;
use Tempest\Validation\Rules\Length;

final class RequestStub implements Request
{
    use IsRequest;

    #[Length(min: 10, max: 120)]
    public string $title;
}
