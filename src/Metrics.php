<?php

declare(strict_types=1);

namespace ActivitySmith;

use ActivitySmith\Generated\Api\MetricsApi;

final class Metrics
{
    public function __construct(private MetricsApi $api)
    {
    }

    public function update(mixed $key, mixed $valueOrRequest, mixed $timestamp = null): mixed
    {
        return $this->api->updateMetricValue(
            $key,
            $this->normalizeValueRequest($valueOrRequest, $timestamp),
            MetricsApi::contentTypes['updateMetricValue'][0]
        );
    }

    // Backward-compatible generated-style alias.
    public function updateMetricValue(
        mixed $key,
        mixed $metricValueUpdateRequest,
        string $contentType = MetricsApi::contentTypes['updateMetricValue'][0]
    ): mixed {
        return $this->api->updateMetricValue(
            $key,
            $metricValueUpdateRequest,
            $contentType
        );
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->api->{$name}(...$arguments);
    }

    private function normalizeValueRequest(mixed $valueOrRequest, mixed $timestamp = null): mixed
    {
        if (is_array($valueOrRequest) && array_key_exists('value', $valueOrRequest)) {
            if ($timestamp !== null) {
                $valueOrRequest['timestamp'] = $timestamp;
            }

            return $valueOrRequest;
        }

        if (is_object($valueOrRequest) && $timestamp === null) {
            return $valueOrRequest;
        }

        $request = ['value' => $valueOrRequest];
        if ($timestamp !== null) {
            $request['timestamp'] = $timestamp;
        }

        return $request;
    }
}
