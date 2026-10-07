<?php

declare(strict_types=1);

namespace ActivitySmith;

final class PushInterruptionLevel
{
    public const PASSIVE = 'passive';
    public const ACTIVE = 'active';
    public const TIME_SENSITIVE = 'time-sensitive';
    public const VALUES = [self::PASSIVE, self::ACTIVE, self::TIME_SENSITIVE];
}
