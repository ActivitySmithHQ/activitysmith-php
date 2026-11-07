<?php

declare(strict_types=1);

namespace ActivitySmith;

use ActivitySmith\Generated\Api\DevicesApi;

final class Devices
{
    public function __construct(private DevicesApi $api)
    {
    }

    /**
     * Lists the iPhones and iPads paired to your account.
     * Omitted parameters use the API defaults: limit 100, offset 0, active devices only.
     */
    public function list(?int $limit = null, ?int $offset = null, ?bool $includeInactive = null): mixed
    {
        return $this->api->listAccountDevices(
            $limit,
            $offset,
            self::booleanQueryValue($includeInactive)
        );
    }

    // Generated-style alias.
    public function listAccountDevices(
        ?int $limit = null,
        ?int $offset = null,
        ?bool $includeInactive = null,
        string $contentType = DevicesApi::contentTypes['listAccountDevices'][0]
    ): mixed {
        return $this->api->listAccountDevices(
            $limit,
            $offset,
            self::booleanQueryValue($includeInactive),
            $contentType
        );
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->api->{$name}(...$arguments);
    }

    // The API reads include_inactive as the literal string "true". The generated
    // client sends booleans as 1/0 by default, so pass the string form directly.
    private static function booleanQueryValue(?bool $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value ? 'true' : 'false';
    }
}
