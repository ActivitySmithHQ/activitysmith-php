# ActivitySmith PHP SDK

The ActivitySmith PHP SDK provides convenient access to the ActivitySmith API from PHP applications.

## Documentation

See [API reference](https://activitysmith.com/docs/api-reference/introduction).

## Installation

```sh
composer require activitysmith/activitysmith
```

## Usage

```php
<?php

declare(strict_types=1);

use ActivitySmith\ActivitySmith;

$client = new ActivitySmith($_ENV['ACTIVITYSMITH_API_KEY']);

$client->notifications->sendPushNotification([
    'push_notification_request' => [
        'title' => 'Build Failed',
        'message' => 'CI pipeline failed on main branch',
    ],
]);

$client->liveActivities->startLiveActivity([
    'live_activity_start_request' => [
        'content_state' => [
            'title' => 'Deploy',
            'number_of_steps' => 4,
            'current_step' => 1,
            'type' => 'segmented_progress',
        ],
    ],
]);
```

## API Surface

- `$client->notifications`
- `$client->liveActivities`

## Requirements

- PHP 8.1+

## License

MIT
