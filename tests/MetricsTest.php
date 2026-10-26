<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\Metrics;
use ActivitySmith\Generated\Api\MetricsApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class MetricsTest extends TestCase
{
    private const METRIC_RESPONSE = '{"metric":{"public_id":"m1","key":"mrr","label":"MRR","format":"currency","currency_code":"USD","unit":null,"unit_spacing":"none","latest_value":null,"latest_value_at":null,"created_at":"2026-10-26T09:00:00Z","updated_at":"2026-10-26T09:00:00Z"}}';

    public function testCreateSendsPutWithDefinition(): void
    {
        $expected = ['label' => 'MRR', 'format' => 'currency', 'currency_code' => 'USD'];

        foreach (['named', 'array', 'alias'] as $form) {
            $history = [];
            $stack = HandlerStack::create(new MockHandler([new Response(201, ['Content-Type' => 'application/json'], self::METRIC_RESPONSE)]));
            $stack->push(Middleware::history($history));
            $metrics = new Metrics(new MetricsApi(new Client(['handler' => $stack])));

            if ($form === 'named') {
                $response = $metrics->create('mrr', label: 'MRR', format: 'currency', currencyCode: 'USD');
            } elseif ($form === 'array') {
                $response = $metrics->create('mrr', $expected);
            } else {
                $response = $metrics->upsertMetric('mrr', $expected);
            }

            $request = $history[0]['request'];
            $this->assertSame('PUT', $request->getMethod());
            $this->assertStringEndsWith('/metrics/mrr', $request->getUri()->getPath());
            $this->assertEquals($expected, json_decode((string) $request->getBody(), true));
            $this->assertSame('USD', $response->getMetric()->getCurrencyCode());
            $this->assertNull($response->getMetric()->getUnit());
        }
    }

    public function testCreateWithDefaultsSendsNoDefinition(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(201, ['Content-Type' => 'application/json'], self::METRIC_RESPONSE)]));
        $stack->push(Middleware::history($history));
        $metrics = new Metrics(new MetricsApi(new Client(['handler' => $stack])));

        $metrics->create('deploy.success_rate');

        $request = $history[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertStringEndsWith('/metrics/deploy.success_rate', $request->getUri()->getPath());
        $this->assertSame('', (string) $request->getBody());
    }

    public function testCreateMergesNamedFieldsOverArray(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, ['Content-Type' => 'application/json'], self::METRIC_RESPONSE)]));
        $stack->push(Middleware::history($history));
        $metrics = new Metrics(new MetricsApi(new Client(['handler' => $stack])));

        $metrics->create('latency', ['format' => 'unit', 'unit' => 'ms'], unitSpacing: 'space');

        $this->assertEquals(
            ['format' => 'unit', 'unit' => 'ms', 'unit_spacing' => 'space'],
            json_decode((string) $history[0]['request']->getBody(), true)
        );
    }
}
