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

$activitysmith = new ActivitySmith($_ENV['ACTIVITYSMITH_API_KEY']);
```

## Usage

### Send a Push Notification

<p align="center">
  <img src="https://cdn.activitysmith.com/features/new-subscription-push-notification.png" alt="Push notification example" width="680" />
</p>

```php
$response = $activitysmith->notifications->send([
    'title' => 'New subscription 💸',
    'message' => 'Customer upgraded to Pro plan',
    'redirection' => 'https://crm.example.com/customers/cus_9f3a1d', // Optional
    'actions' => [ // Optional (max 4)
        [
            'title' => 'Open CRM Profile',
            'type' => 'open_url',
            'url' => 'https://crm.example.com/customers/cus_9f3a1d',
        ],
        [
            'title' => 'Start Onboarding Workflow',
            'type' => 'webhook',
            'url' => 'https://hooks.example.com/activitysmith/onboarding/start',
            'method' => 'POST',
            'body' => [
                'customer_id' => 'cus_9f3a1d',
                'plan' => 'pro',
            ],
        ],
    ],
    'channels' => ['sales', 'customer-success'], // Optional
]);

echo $response->getSuccess() ? 'true' : 'false';
echo PHP_EOL;
echo $response->getDevicesNotified();
```

### Start a Live Activity

<p align="center">
  <img src="https://cdn.activitysmith.com/features/start-live-activity.png" alt="Start live activity example" width="680" />
</p>

```php
$start = $activitysmith->liveActivities->start([
    'content_state' => [
        'title' => 'Nightly database backup',
        'subtitle' => 'create snapshot',
        'number_of_steps' => 3,
        'current_step' => 1,
        'type' => 'segmented_progress',
        'color' => 'yellow',
    ],
    'channels' => ['devs', 'ops'], // Optional
]);

$activityId = $start->getActivityId();
```

### Update a Live Activity

<p align="center">
  <img src="https://cdn.activitysmith.com/features/update-live-activity.png" alt="Update live activity example" width="680" />
</p>

```php
$update = $activitysmith->liveActivities->update([
    'activity_id' => $activityId,
    'content_state' => [
        'title' => 'Nightly database backup',
        'subtitle' => 'upload archive',
        'current_step' => 2,
    ],
]);

echo $update->getDevicesNotified();
```

### End a Live Activity

<p align="center">
  <img src="https://cdn.activitysmith.com/features/end-live-activity.png" alt="End live activity example" width="680" />
</p>

```php
$end = $activitysmith->liveActivities->end([
    'activity_id' => $activityId,
    'content_state' => [
        'title' => 'Nightly database backup',
        'subtitle' => 'verify restore',
        'current_step' => 3,
        'auto_dismiss_minutes' => 2,
    ],
]);

echo $end->getSuccess() ? 'true' : 'false';
```

## Error Handling

```php
try {
    $activitysmith->notifications->send([
        'title' => 'New subscription 💸',
    ]);
} catch (Throwable $err) {
    echo 'Request failed: ' . $err->getMessage() . PHP_EOL;
}
```

## API Surface

- `$activitysmith->notifications`
- `$activitysmith->liveActivities`

## Requirements

- PHP 8.1+

## License

MIT
