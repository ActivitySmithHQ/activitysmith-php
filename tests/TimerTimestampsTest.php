<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\LiveActivities;
use ActivitySmith\LiveActivityContentState;
use ActivitySmith\Generated\Api\LiveActivitiesApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class TimerTimestampsTest extends TestCase
{
    private const RESPONSE = '{"success":true,"activity_id":"activity-1","timestamp":"2026-11-19T00:00:00Z"}';
    private const START_AT = '2026-11-19T10:00:00Z';
    private const END_AT = '2026-11-19T10:30:00Z';

    /**
     * @param array<int,array<string,mixed>> $history
     */
    private function liveActivities(array &$history): LiveActivities
    {
        $stack = HandlerStack::create(new MockHandler([new Response(200, ['Content-Type' => 'application/json'], self::RESPONSE)]));
        $stack->push(Middleware::history($history));

        return new LiveActivities(new LiveActivitiesApi(new Client(['handler' => $stack])));
    }

    /**
     * @param array<int,array<string,mixed>> $history
     * @return array<string,mixed>
     */
    private function contentState(array $history): array
    {
        $this->assertCount(1, $history);
        $body = json_decode((string) $history[0]['request']->getBody(), true);

        return $body['content_state'];
    }

    /**
     * @return array<string, callable(LiveActivities, mixed, mixed): mixed>
     */
    private function calls(): array
    {
        $timer = ['title' => 'Oven', 'type' => LiveActivities::TYPE_TIMER];

        return [
            'start' => fn (LiveActivities $r, mixed $s, mixed $e) => $r->start(contentState: $timer, timerStartAt: $s, timerEndAt: $e),
            'update' => fn (LiveActivities $r, mixed $s, mixed $e) => $r->update(activityId: 'activity-1', contentState: $timer, timerStartAt: $s, timerEndAt: $e),
            'stream' => fn (LiveActivities $r, mixed $s, mixed $e) => $r->stream('oven', contentState: $timer, timerStartAt: $s, timerEndAt: $e),
            'endStream' => fn (LiveActivities $r, mixed $s, mixed $e) => $r->endStream('oven', contentState: $timer, timerStartAt: $s, timerEndAt: $e),
        ];
    }

    public function testTimerTimestampsSerializeIntoContentState(): void
    {
        foreach ($this->calls() as $method => $call) {
            $history = [];
            $call($this->liveActivities($history), self::START_AT, self::END_AT);
            $state = $this->contentState($history);
            $this->assertSame(self::START_AT, $state['timer_start_at'], $method);
            $this->assertSame(self::END_AT, $state['timer_end_at'], $method);
            $this->assertArrayNotHasKey('duration_seconds', $state, $method);
            $this->assertSame('Oven', $state['title'], $method);
        }
    }

    public function testTimerTimestampsAreOmittedWhenUnset(): void
    {
        foreach ($this->calls() as $method => $call) {
            $history = [];
            $call($this->liveActivities($history), null, null);
            $state = $this->contentState($history);
            $this->assertArrayNotHasKey('timer_start_at', $state, $method);
            $this->assertArrayNotHasKey('timer_end_at', $state, $method);
        }
    }

    public function testDateTimeTimestampsSerializeAsRfc3339(): void
    {
        $start = new \DateTimeImmutable('2026-11-19T10:00:00+00:00');
        $end = new \DateTime('2026-11-19T17:30:00+07:00');

        foreach ($this->calls() as $method => $call) {
            $history = [];
            $call($this->liveActivities($history), $start, $end);
            $state = $this->contentState($history);
            $this->assertSame('2026-11-19T10:00:00+00:00', $state['timer_start_at'], $method);
            $this->assertSame('2026-11-19T17:30:00+07:00', $state['timer_end_at'], $method);
        }
    }

    public function testArrayRequestsPassTimerTimestampsThrough(): void
    {
        $history = [];
        $this->liveActivities($history)->stream('oven', [
            'content_state' => [
                'title' => 'Oven',
                'type' => LiveActivities::TYPE_TIMER,
                'timer_end_at' => self::END_AT,
            ],
        ]);
        $this->assertSame(self::END_AT, $this->contentState($history)['timer_end_at']);
    }

    public function testContentStateHelperAcceptsTimerTimestamps(): void
    {
        $state = LiveActivityContentState::make(
            title: 'Oven',
            type: LiveActivities::TYPE_TIMER,
            timerStartAt: new \DateTimeImmutable('2026-11-19T10:00:00+00:00'),
            timerEndAt: self::END_AT,
        );
        $this->assertSame('2026-11-19T10:00:00+00:00', $state['timer_start_at']);
        $this->assertSame(self::END_AT, $state['timer_end_at']);

        $plain = LiveActivityContentState::make(title: 'Oven', type: LiveActivities::TYPE_TIMER, durationSeconds: 60);
        $this->assertArrayNotHasKey('timer_start_at', $plain);
        $this->assertArrayNotHasKey('timer_end_at', $plain);
    }
}
