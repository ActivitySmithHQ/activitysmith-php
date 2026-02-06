# ActivitySmith PHP SDK

The ActivitySmith PHP SDK provides convenient access to the ActivitySmith API from PHP applications.

## Documentation

See [API reference](https://activitysmith.com/docs/api-reference/introduction).

## Installation

```sh
composer require activitysmith/activitysmith
```

## Setup

```php
<?php

declare(strict_types=1);

use ActivitySmith\ActivitySmith;

$client = new ActivitySmith($_ENV['ACTIVITYSMITH_API_KEY']);
```

You can also override the API host:

```php
$client = new ActivitySmith(
    $_ENV['ACTIVITYSMITH_API_KEY'],
    'https://activitysmith.com/api'
);
```

## Usage

### Send a Push Notification

```php
$response = $client->notifications->sendPushNotification([
    'push_notification_request' => [
        'title' => 'Build Failed',
        'message' => 'CI pipeline failed on main branch',
    ],
]);
```

### Start a Live Activity

```php
$start = $client->liveActivities->startLiveActivity([
    'live_activity_start_request' => [
        'content_state' => [
            'title' => 'Deploy',
            'number_of_steps' => 4,
            'current_step' => 1,
            'type' => 'segmented_progress',
        ],
    ],
]);

$activityId = $start->getActivityId();
```

### Update a Live Activity

```php
$update = $client->liveActivities->updateLiveActivity([
    'live_activity_update_request' => [
        'activity_id' => $activityId,
        'content_state' => [
            'title' => 'Deploy',
            'current_step' => 3,
        ],
    ],
]);
```

### End a Live Activity

```php
$end = $client->liveActivities->endLiveActivity([
    'live_activity_end_request' => [
        'activity_id' => $activityId,
        'content_state' => [
            'title' => 'Deploy Complete',
            'current_step' => 4,
            'auto_dismiss_minutes' => 3,
        ],
    ],
]);
```

## Error Handling

```php
try {
    $client->notifications->sendPushNotification([
        'push_notification_request' => [
            'title' => 'Build Failed',
        ],
    ]);
} catch (Throwable $err) {
    echo 'Request failed: ' . $err->getMessage() . PHP_EOL;
}
```

## API Surface

- `$client->notifications`
- `$client->liveActivities`

## Requirements

- PHP 8.1+

## License

MIT
