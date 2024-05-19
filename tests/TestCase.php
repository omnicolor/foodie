<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionObject;
use Spatie\SlashCommand\Response;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function getAttachmentFromResponse(Response $response): array
    {
        return (new ReflectionObject($response))
            ->getProperty('attachments')
            ->getValue($response)[0]
            ->toArray();
    }
}
