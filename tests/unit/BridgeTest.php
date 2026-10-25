<?php namespace Renick\Matomo\Tests\Unit;

use MatomoTracker;
use Renick\Matomo\Classes\MatomoBridge;

class BridgeTest extends MatomoTestCase
{
    private array $server;

    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    public function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    private function bridgeWith(MatomoTracker $tracker): MatomoBridge
    {
        $bridge = new MatomoBridge('https://matomo.example.invalid/', 'dummy-token', 7);
        $prop = new \ReflectionProperty($bridge, 'tracker');
        $prop->setAccessible(true);
        $prop->setValue($bridge, $tracker);

        return $bridge;
    }

    private function trackerMock(): MatomoTracker
    {
        return $this->createMock(MatomoTracker::class);
    }

    public function testConstructorConfiguresTheTracker(): void
    {
        $bridge = new MatomoBridge('https://matomo.example.invalid/', 'dummy-token', 7);
        $tracker = $this->getProtectedProperty($bridge, 'tracker');

        $this->assertInstanceOf(MatomoTracker::class, $tracker);
        $this->assertSame(7, $this->getProtectedProperty($tracker, 'idSite'));
        $this->assertSame('https://matomo.example.invalid/', $this->getProtectedProperty($tracker, 'apiUrl'));
        $this->assertSame('dummy-token', $this->getProtectedProperty($tracker, 'token_auth'));
        $this->assertFalse($this->getProtectedProperty($tracker, 'sendImageResponse'), 'image response is disabled');

        if (method_exists($tracker, 'setExceptionsEnabled')) {
            $this->assertFalse($tracker->exceptionsEnabled, 'tracking failures must not break the page (tracker 4.x)');
        }
    }

    public function testTrackingSendsAPageViewWithTheGivenTitle(): void
    {
        $tracker = $this->trackerMock();
        $tracker->expects($this->once())->method('doTrackPageView')->with('My title');

        $this->bridgeWith($tracker)->useTracking('My title');
    }

    public function testTrackingWithoutTitleSendsAnEmptyTitle(): void
    {
        $tracker = $this->trackerMock();
        $tracker->expects($this->once())->method('doTrackPageView')->with('');

        $this->bridgeWith($tracker)->useTracking();
    }

    public function testClientIpIsTakenFromRemoteAddr(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        $tracker = $this->trackerMock();
        $tracker->expects($this->once())->method('setIp')->with('198.51.100.7');

        $this->bridgeWith($tracker)->useTracking();
    }

    public function testForwardedForHeaderWinsOverRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9';
        $tracker = $this->trackerMock();
        $tracker->expects($this->once())->method('setIp')->with('203.0.113.9');

        $this->bridgeWith($tracker)->useTracking();
    }

    public function testIpv6IsAccepted(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '2001:db8::1';
        $tracker = $this->trackerMock();
        $tracker->expects($this->once())->method('setIp')->with('2001:db8::1');

        $this->bridgeWith($tracker)->useTracking();
    }

    /** @dataProvider invalidIps */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidIps')]
    public function testInvalidIpsAreNotForwarded(?string $forwarded, ?string $remote): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);
        $forwarded !== null && $_SERVER['HTTP_X_FORWARDED_FOR'] = $forwarded;
        $remote !== null && $_SERVER['REMOTE_ADDR'] = $remote;
        $tracker = $this->trackerMock();
        $remote === null ? $tracker->expects($this->never())->method('setIp') : $tracker->expects($this->once())->method('setIp')->with($remote);
        $tracker->expects($this->once())->method('doTrackPageView');

        $this->bridgeWith($tracker)->useTracking();
    }

    public function testForwardedChainUsesFirstValidIp(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9, 10.0.0.1';
        $tracker = $this->trackerMock();
        $tracker->expects($this->once())->method('setIp')->with('203.0.113.9');

        $this->bridgeWith($tracker)->useTracking();
    }

    public static function invalidIps(): array
    {
        return [
            'garbage header' => ['not-an-ip', '198.51.100.7'],
            'no ip at all' => [null, null],
            'script in header' => ['<script>', null],
            
        ];
    }

    public function testNoUserIdWithoutAuthenticatedUser(): void
    {
        $tracker = $this->trackerMock();
        $tracker->expects($this->never())->method('setUserId');

        $this->bridgeWith($tracker)->useTracking();
    }

    public function testUserIdIsTheEmailOfTheAuthenticatedUser(): void
    {
        $user = \Backend\Models\User::create([
            'login' => 'tester', 'email' => 'tester@example.invalid', 'first_name' => 'T', 'last_name' => 'T',
            'password' => 'Secret-123!', 'password_confirmation' => 'Secret-123!',
        ]);
        $this->app['auth']->setUser($user);
        $tracker = $this->trackerMock();
        $tracker->expects($this->once())->method('setUserId')->with('tester@example.invalid');

        $this->bridgeWith($tracker)->useTracking();
    }
}
