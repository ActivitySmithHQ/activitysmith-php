<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\LiveActivities;
use ActivitySmith\Notifications;
use ActivitySmith\Generated\Api\LiveActivitiesApi;
use ActivitySmith\Generated\Api\PushNotificationsApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class SoundTest extends TestCase
{
    private const RESPONSE = '{"success":true,"activity_id":"activity-1","timestamp":"2026-11-18T00:00:00Z"}';

    /**
     * @param array<int,array<string,mixed>> $history
     */
    private function client(array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler([new Response(200, ['Content-Type' => 'application/json'], self::RESPONSE)]));
        $stack->push(Middleware::history($history));

        return new Client(['handler' => $stack]);
    }

    /**
     * @param array<int,array<string,mixed>> $history
     * @return array<string,mixed>
     */
    private function body(array $history): array
    {
        $this->assertCount(1, $history);

        return json_decode((string) $history[0]['request']->getBody(), true);
    }

    public function testPushNotificationSendSerializesSound(): void
    {
        foreach (['chime', 'default', 'none', null] as $sound) {
            foreach (['send', 'sendPushNotification'] as $method) {
                foreach (['named', 'array'] as $form) {
                    $history = [];
                    $notifications = new Notifications(new PushNotificationsApi($this->client($history)));
                    if ($form === 'named') {
                        $notifications->$method(title: 'Build finished', sound: $sound);
                    } else {
                        $fields = ['title' => 'Build finished'];
                        if ($sound !== null) $fields['sound'] = $sound;
                        $notifications->$method($fields);
                    }
                    $body = $this->body($history);
                    $this->assertSame($sound !== null, array_key_exists('sound', $body), "$method $form");
                    if ($sound !== null) $this->assertSame($sound, $body['sound']);
                    $this->assertSame('Build finished', $body['title']);
                }
            }
        }
    }

    public function testLiveActivityRequestsSerializeTopLevelSound(): void
    {
        $state = ['title' => 'Deploy', 'type' => 'segmented_progress', 'number_of_steps' => 3, 'current_step' => 2];
        $calls = [
            'start' => [
                'named' => fn (LiveActivities $r, ?string $s) => $r->start(contentState: $state, sound: $s),
                'array' => fn (LiveActivities $r, ?string $s) => $r->start($this->withSound(['content_state' => $state], $s)),
                'alias' => fn (LiveActivities $r, ?string $s) => $r->startLiveActivity($this->withSound(['content_state' => $state], $s)),
            ],
            'update' => [
                'named' => fn (LiveActivities $r, ?string $s) => $r->update(activityId: 'activity-1', contentState: $state, sound: $s),
                'array' => fn (LiveActivities $r, ?string $s) => $r->update($this->withSound(['activity_id' => 'activity-1', 'content_state' => $state], $s)),
                'alias' => fn (LiveActivities $r, ?string $s) => $r->updateLiveActivity($this->withSound(['activity_id' => 'activity-1', 'content_state' => $state], $s)),
            ],
            'end' => [
                'named' => fn (LiveActivities $r, ?string $s) => $r->end(activityId: 'activity-1', contentState: $state, sound: $s),
                'array' => fn (LiveActivities $r, ?string $s) => $r->end($this->withSound(['activity_id' => 'activity-1', 'content_state' => $state], $s)),
                'alias' => fn (LiveActivities $r, ?string $s) => $r->endLiveActivity($this->withSound(['activity_id' => 'activity-1', 'content_state' => $state], $s)),
            ],
            'stream' => [
                'named' => fn (LiveActivities $r, ?string $s) => $r->stream('deploy', contentState: $state, sound: $s),
                'array' => fn (LiveActivities $r, ?string $s) => $r->stream('deploy', $this->withSound(['content_state' => $state], $s)),
                'alias' => fn (LiveActivities $r, ?string $s) => $r->reconcileLiveActivityStream('deploy', $this->withSound(['content_state' => $state], $s)),
            ],
            'endStream' => [
                'named' => fn (LiveActivities $r, ?string $s) => $r->endStream('deploy', contentState: $state, sound: $s),
                'array' => fn (LiveActivities $r, ?string $s) => $r->endStream('deploy', $this->withSound(['content_state' => $state], $s)),
                'alias' => fn (LiveActivities $r, ?string $s) => $r->endLiveActivityStream('deploy', $this->withSound(['content_state' => $state], $s)),
            ],
        ];

        foreach (['bell', 'default', 'none', null] as $sound) {
            foreach ($calls as $method => $forms) {
                foreach ($forms as $form => $call) {
                    $history = [];
                    $call(new LiveActivities(new LiveActivitiesApi($this->client($history))), $sound);
                    $body = $this->body($history);
                    $this->assertSame($sound !== null, array_key_exists('sound', $body), "$method $form");
                    if ($sound !== null) $this->assertSame($sound, $body['sound'], "$method $form");
                    $this->assertArrayNotHasKey('sound', $body['content_state'], "$method $form");
                    $this->assertSame('Deploy', $body['content_state']['title']);
                }
            }
        }
    }

    public function testSoundNamesOutsideTheBundledListPassThrough(): void
    {
        $history = [];
        (new Notifications(new PushNotificationsApi($this->client($history))))->send(title: 'Legacy', sound: 'custom.caf');
        $this->assertSame('custom.caf', $this->body($history)['sound']);
    }

    /**
     * @param array<string,mixed> $request
     * @return array<string,mixed>
     */
    private function withSound(array $request, ?string $sound): array
    {
        if ($sound !== null) $request['sound'] = $sound;

        return $request;
    }
}
