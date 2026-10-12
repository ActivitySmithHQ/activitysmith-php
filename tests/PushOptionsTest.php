<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\Notifications;
use ActivitySmith\PushInterruptionLevel;
use ActivitySmith\Generated\Api\PushNotificationsApi;
use ActivitySmith\Generated\Model\PushNotificationRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class PushOptionsTest extends TestCase
{
    public function testSerializationAcrossNamedArrayAndModelInputs(): void
    {
        foreach ([null, ...PushInterruptionLevel::VALUES] as $level) {
            foreach (['send', 'sendPushNotification'] as $method) {
                foreach (['named', 'array', 'model'] as $form) {
                    foreach ([false, true] as $withIcon) {
                        $history = [];
                        $stack = HandlerStack::create(new MockHandler([new Response(200, ['Content-Type' => 'application/json'], '{"success":true,"timestamp":"2026-09-22T00:00:00Z"}')]));
                        $stack->push(Middleware::history($history));
                        $notifications = new Notifications(new PushNotificationsApi(new Client(['handler' => $stack])));
                        $fields = ['title' => 'GitHub', 'subtitle' => 'Build status', 'tags' => ['ci']];
                        $icon = $withIcon ? 'https://example.com/github.png' : null;
                        if ($icon !== null) $fields['icon'] = $icon;
                        if ($level !== null) {
                            $fields['interruption_level'] = $level;
                        }
                        if ($form === 'named') {
                            $notifications->$method(title: 'GitHub', subtitle: 'Build status', tags: ['ci'], channels: ['ops'], icon: $icon, interruptionLevel: $level);
                        } elseif ($form === 'model') {
                            $modelFields = [...$fields, 'target' => ['channels' => ['ops']]];
                            if ($level !== null) {
                                unset($modelFields['interruption_level']);
                                $modelFields['interruptionLevel'] = $level;
                            }
                            $notifications->$method(new PushNotificationRequest($modelFields));
                        } else {
                            $notifications->$method([...$fields, 'channels' => ['ops']]);
                        }
                        $body = json_decode((string) $history[0]['request']->getBody(), true);
                        $this->assertEquals([...$fields, 'target' => ['channels' => ['ops']]], $body);
                    }
                }
            }
        }
    }

    public function testReplaceSendsPutWithReplacementBody(): void
    {
        foreach (['named', 'array', 'alias'] as $form) {
            $history = [];
            $stack = HandlerStack::create(new MockHandler([new Response(200, ['Content-Type' => 'application/json'], '{"success":true,"notification_id":"pn1","devices_notified":1,"future_field":true,"timestamp":"2026-10-12T00:00:00Z"}')]));
            $stack->push(Middleware::history($history));
            $notifications = new Notifications(new PushNotificationsApi(new Client(['handler' => $stack])));
            $fields = ['title' => 'Answered from desk', 'tags' => [], 'metadata' => ['attempts' => 0, 'resolved' => false]];
            if ($form === 'named') {
                $response = $notifications->replace('pn1', title: 'Answered from desk', tags: [], metadata: ['attempts' => 0, 'resolved' => false]);
            } elseif ($form === 'array') {
                $response = $notifications->replace('pn1', $fields);
            } else {
                $response = $notifications->replacePushNotification('pn1', $fields);
            }
            $request = $history[0]['request'];
            $this->assertSame('PUT', $request->getMethod());
            $this->assertStringEndsWith('/push-notification/pn1', $request->getUri()->getPath());
            $this->assertEquals($fields, json_decode((string) $request->getBody(), true));
            $this->assertSame('pn1', $response->getNotificationId());
        }
    }

    public function testReplaceRejectsMediaWithActions(): void
    {
        $notifications = new Notifications(new PushNotificationsApi(new Client(['handler' => HandlerStack::create(new MockHandler([]))])));
        $this->expectExceptionMessage('ActivitySmith: media cannot be combined with actions');
        $notifications->replace('pn1', title: 'Done', media: 'https://cdn.activitysmith.com/voice.mp3', actions: [['title' => 'Open', 'type' => 'open_url', 'url' => 'https://example.com']]);
    }

    public function testInvalidLevelNeverReachesTransport(): void
    {
        $api = $this->getMockBuilder(PushNotificationsApi::class)->disableOriginalConstructor()->onlyMethods(['sendPushNotification'])->getMock();
        $api->expects($this->never())->method('sendPushNotification');
        $this->expectException(\InvalidArgumentException::class);
        (new Notifications($api))->send(title: 'Test', interruptionLevel: 'timeSensitive');
    }

    public function testCriticalLevelNeverReachesTransport(): void
    {
        $api = $this->getMockBuilder(PushNotificationsApi::class)->disableOriginalConstructor()->onlyMethods(['sendPushNotification'])->getMock();
        $api->expects($this->never())->method('sendPushNotification');
        $this->expectException(\InvalidArgumentException::class);
        (new Notifications($api))->send(title: 'Test', interruptionLevel: 'critical');
    }
}
