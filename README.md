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
$response = $client->notifications->send([
    'title' => 'Build Failed',
    'message' => 'CI pipeline failed on main branch',
]);
```

### Start a Live Activity

```php
$start = $client->liveActivities->start([
    'content_state' => [
        'title' => 'Deploy',
        'number_of_steps' => 4,
        'current_step' => 1,
        'type' => 'segmented_progress',
    ],
]);

$activityId = $start->getActivityId();
```

### Update a Live Activity

```php
$update = $client->liveActivities->update([
    'activity_id' => $activityId,
    'content_state' => [
        'title' => 'Deploy',
        'current_step' => 3,
    ],
]);
```

### End a Live Activity

```php
$end = $client->liveActivities->end([
    'activity_id' => $activityId,
    'content_state' => [
        'title' => 'Deploy Complete',
        'current_step' => 4,
        'auto_dismiss_minutes' => 3,
    ],
]);
```

## Error Handling

```php
try {
    $client->notifications->send([
        'title' => 'Build Failed',
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
