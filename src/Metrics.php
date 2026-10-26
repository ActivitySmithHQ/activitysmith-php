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

    /**
     * Creates the metric, or updates the fields sent for an existing one.
     * Omitted fields keep their current values, so it is safe to call on every start.
     */
    public function create(
        string $key,
        mixed $definition = null,
        ?string $label = null,
        ?string $format = null,
        ?string $currencyCode = null,
        ?string $unit = null,
        ?string $unitSpacing = null
    ): mixed {
        $request = $definition;
        $fields = array_filter([
            'label' => $label,
            'format' => $format,
            'currency_code' => $currencyCode,
            'unit' => $unit,
            'unit_spacing' => $unitSpacing,
        ], static fn (mixed $value): bool => $value !== null);

        if ($fields !== []) {
            $request = [...(is_array($definition) ? $definition : []), ...$fields];
        }

        return $this->api->upsertMetric(
            $key,
            $request === [] ? null : $request,
            MetricsApi::contentTypes['upsertMetric'][0]
        );
    }

    // Backward-compatible generated-style aliases.
    public function upsertMetric(
        mixed $key,
        mixed $metricUpsertRequest = null,
        string $contentType = MetricsApi::contentTypes['upsertMetric'][0]
    ): mixed {
        return $this->api->upsertMetric($key, $metricUpsertRequest, $contentType);
    }

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
