<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\ActivitySmith;
use ActivitySmith\Devices;
use ActivitySmith\Notifications;
use ActivitySmith\Generated\Api\DevicesApi;
use ActivitySmith\Generated\Api\PushNotificationsApi;
use ActivitySmith\Generated\Model\AccountDeviceListResponse;
use ActivitySmith\Generated\Model\PushNotificationDismissResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class DevicesAndDismissTest extends TestCase
{
    private const NOTIFICATION_ID = 'a8d2c9a4-3a1e-4f6b-9c2d-7e5f1b0a6c34';

    private const SEND_RESPONSE = '{"success":true,"notification_id":"a8d2c9a4-3a1e-4f6b-9c2d-7e5f1b0a6c34","devices_notified":1,"users_notified":1,"timestamp":"2026-11-07T09:00:00.000Z"}';

    private const DISMISS_RESPONSE = '{"success":true,"notification_id":"a8d2c9a4-3a1e-4f6b-9c2d-7e5f1b0a6c34","dismissed_at":"2026-11-07T09:05:00.000Z"}';

    private const DEVICES_RESPONSE = '{"devices":[{"user_email":"you@example.com","device_id":"4F1C2A8E-7B3D-4E21-9A6C-1D2E3F4A5B6C","device_type":"ios","is_active":true,"token_status":{"push":true,"start_live_activity":true},"app_version":"1.17.0","device_name":"iPhone","system_name":"iOS","system_version":"27.0","created_at":"2026-10-01T09:00:00.000Z","updated_at":"2026-10-26T12:00:00.000Z"}],"meta":{"total":1,"limit":100,"offset":0,"has_more":false,"next_offset":null,"include_inactive":false}}';

    private const METRIC_RESPONSE = '{"metric":{"public_id":"m1","key":"mrr","label":"MRR","format":"currency","currency_code":"USD","unit":null,"unit_spacing":"none","latest_value":12450,"latest_value_at":"2026-11-07T09:00:00Z","created_at":"2026-11-07T09:00:00Z","updated_at":"2026-11-07T09:00:00Z"}}';

    private const METRIC_VALUE_RESPONSE = '{"success":true,"metric":{"key":"mrr","value":12450,"timestamp":"2026-11-07T09:00:00.000Z"}}';

    /**
     * @param array<int,Response> $responses
     * @param array<int,array<string,mixed>> $history
     */
    private static function mockClient(array $responses, array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        return new Client(['handler' => $stack]);
    }

    private static function json(string $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], $body);
    }

    /**
     * @return array<string,string>
     */
    private static function query(\Psr\Http\Message\RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);

        return $query;
    }

    public function testDismissSendsPostAndReturnsParsedBody(): void
    {
        foreach (['dismiss', 'dismissPushNotification'] as $method) {
            $history = [];
            $notifications = new Notifications(new PushNotificationsApi(
                self::mockClient([self::json(self::DISMISS_RESPONSE)], $history)
            ));

            $response = $notifications->{$method}(self::NOTIFICATION_ID);

            $this->assertCount(1, $history);
            $request = $history[0]['request'];
            $this->assertSame('POST', $request->getMethod());
            $this->assertStringEndsWith(
                '/push-notification/' . self::NOTIFICATION_ID . '/dismiss',
                $request->getUri()->getPath()
            );
            $this->assertSame('', $request->getUri()->getQuery());
            $this->assertInstanceOf(PushNotificationDismissResponse::class, $response);
            $this->assertTrue($response->getSuccess());
            $this->assertSame(self::NOTIFICATION_ID, $response->getNotificationId());
            $this->assertSame('2026-11-07T09:05:00+00:00', $response->getDismissedAt()->format(DATE_ATOM));
        }
    }

    public function testDevicesListSendsGetWithoutQuery(): void
    {
        foreach (['list', 'listAccountDevices'] as $method) {
            $history = [];
            $devices = new Devices(new DevicesApi(
                self::mockClient([self::json(self::DEVICES_RESPONSE)], $history)
            ));

            $response = $devices->{$method}();

            $this->assertCount(1, $history);
            $request = $history[0]['request'];
            $this->assertSame('GET', $request->getMethod());
            $this->assertStringEndsWith('/account/devices', $request->getUri()->getPath());
            $this->assertSame('', $request->getUri()->getQuery());
            $this->assertInstanceOf(AccountDeviceListResponse::class, $response);
            $this->assertCount(1, $response->getDevices());
            $this->assertSame('iPhone', $response->getDevices()[0]->getDeviceName());
            $this->assertSame(1, $response->getMeta()->getTotal());
        }
    }

    public function testDevicesListSendsQueryParameters(): void
    {
        foreach (['list', 'listAccountDevices'] as $method) {
            $history = [];
            $devices = new Devices(new DevicesApi(
                self::mockClient([
                    self::json(self::DEVICES_RESPONSE),
                    self::json(self::DEVICES_RESPONSE),
                ], $history)
            ));

            $devices->{$method}(limit: 10, includeInactive: true);
            $devices->{$method}(offset: 20, includeInactive: false);

            $this->assertSame('GET', $history[0]['request']->getMethod());
            $this->assertSame(
                ['limit' => '10', 'include_inactive' => 'true'],
                self::query($history[0]['request'])
            );
            $this->assertSame(
                ['offset' => '20', 'include_inactive' => 'false'],
                self::query($history[1]['request'])
            );
        }
    }

    public function testClientWiresDevicesResource(): void
    {
        $client = new ActivitySmith('test');

        $this->assertInstanceOf(Devices::class, $client->devices);
    }

    public function testDocExamplesRunAgainstMockedClient(): void
    {
        $history = [];
        $http = self::mockClient([
            self::json(self::SEND_RESPONSE),
            self::json(self::DISMISS_RESPONSE),
            self::json(self::DEVICES_RESPONSE),
            self::json(self::DEVICES_RESPONSE),
            self::json(self::METRIC_RESPONSE, 201),
            self::json(self::METRIC_VALUE_RESPONSE),
        ], $history);

        $activitysmith = new ActivitySmith('test-api-key');
        foreach (['notifications', 'devices', 'metrics'] as $resource) {
            $api = (new \ReflectionProperty($activitysmith->{$resource}, 'api'))->getValue($activitysmith->{$resource});
            (new \ReflectionProperty($api, 'client'))->setValue($api, $http);
        }

        ob_start();
        try {
            // Doc examples, verbatim.
            $sent = $activitysmith->notifications->send(title: '🙋 Agent needs your input', message: 'Approve the database migration?');
            $activitysmith->notifications->dismiss($sent->getNotificationId());
            $result = $activitysmith->devices->list();
            foreach ($result->getDevices() as $device) { echo $device->getDeviceName() . ' ' . $device->getSystemName() . PHP_EOL; }
            $activitysmith->devices->list(includeInactive: true);
            $activitysmith->metrics->create('mrr', label: 'MRR', format: 'currency', currencyCode: 'USD');
            $activitysmith->metrics->update('mrr', 12450);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('iPhone iOS' . PHP_EOL, $output);
        $this->assertCount(6, $history);

        $requests = array_map(static fn (array $entry) => $entry['request'], $history);
        foreach ($requests as $request) {
            $this->assertSame('Bearer test-api-key', $request->getHeaderLine('Authorization'));
        }

        [$send, $dismiss, $list, $listInactive, $create, $update] = $requests;

        $this->assertSame('POST', $send->getMethod());
        $this->assertStringEndsWith('/push-notification', $send->getUri()->getPath());
        $this->assertSame(
            ['title' => '🙋 Agent needs your input', 'message' => 'Approve the database migration?'],
            json_decode((string) $send->getBody(), true)
        );

        $this->assertSame('POST', $dismiss->getMethod());
        $this->assertStringEndsWith('/push-notification/' . self::NOTIFICATION_ID . '/dismiss', $dismiss->getUri()->getPath());

        $this->assertSame('GET', $list->getMethod());
        $this->assertStringEndsWith('/account/devices', $list->getUri()->getPath());
        $this->assertSame('', $list->getUri()->getQuery());

        $this->assertSame('GET', $listInactive->getMethod());
        $this->assertSame(['include_inactive' => 'true'], self::query($listInactive));

        $this->assertSame('PUT', $create->getMethod());
        $this->assertStringEndsWith('/metrics/mrr', $create->getUri()->getPath());
        $this->assertEquals(
            ['label' => 'MRR', 'format' => 'currency', 'currency_code' => 'USD'],
            json_decode((string) $create->getBody(), true)
        );

        $this->assertSame('POST', $update->getMethod());
        $this->assertStringEndsWith('/metrics/mrr/value', $update->getUri()->getPath());
        $this->assertSame(['value' => 12450], json_decode((string) $update->getBody(), true));
    }
}
