<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\ActivitySmith;
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testClientConstructsWhenGeneratedCodeIsPresent(): void
    {
        if (!class_exists('ActivitySmith\\Generated\\Configuration')) {
            $this->markTestSkipped('Generated OpenAPI client is not present yet.');
        }

        $client = new ActivitySmith('test-api-key');

        $this->assertNotNull($client->notifications);
        $this->assertNotNull($client->liveActivities);
    }
}
