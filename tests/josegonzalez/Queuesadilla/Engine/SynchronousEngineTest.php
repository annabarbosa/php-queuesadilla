<?php

namespace josegonzalez\Queuesadilla\Engine;

use DateTime;
use DateInterval;
use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\Engine\SynchronousEngine;
use josegonzalez\Queuesadilla\Worker\SequentialWorker;
use josegonzalez\Queuesadilla\Worker\TestWorker;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

class SynchronousEngineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->url = getenv('SYNCHRONOUS_URL');
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\SynchronousEngine';
        $this->Engine = $this->mockEngine(['getWorker']);
        $this->Engine->expects($this->any())
                ->method('getWorker')
                ->will($this->returnValue(new TestWorker($this->Engine)));
        $this->Fixtures = new FixtureData;
    }

    public function tearDown()
    {
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::__construct
     */
    public function testConstruct()
    {
        $Engine = new SynchronousEngine($this->Logger, []);
        $this->assertNotNull($Engine->connection());

        $Engine = new SynchronousEngine($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new SynchronousEngine($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::connect
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     */
    public function testGetJobClass()
    {
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\Base', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::acknowledge
     */
    public function testAcknowledge()
    {
        $Engine = $this->mockEngine();
        $this->assertFalse($Engine->acknowledge(null));
        $this->assertFalse($Engine->acknowledge(false));
        $this->assertFalse($Engine->acknowledge(1));
        $this->assertFalse($Engine->acknowledge('string'));
        $this->assertFalse($Engine->acknowledge(['key' => 'value']));
        $this->assertFalse($Engine->acknowledge($this->Fixtures->default['first']));

        $this->assertTrue($Engine->push($this->Fixtures->default['first']));
        $this->assertTrue($Engine->push($this->Fixtures->other['third']));
        $this->assertFalse($Engine->acknowledge($this->Fixtures->default['first']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::reject
     */
    public function testReject()
    {
        $Engine = $this->mockEngine();
        $this->assertFalse($Engine->reject(null));
        $this->assertFalse($Engine->reject(false));
        $this->assertFalse($Engine->reject(1));
        $this->assertFalse($Engine->reject('string'));
        $this->assertFalse($Engine->reject(['key' => 'value']));
        $this->assertFalse($Engine->reject($this->Fixtures->default['first']));

        $this->assertTrue($Engine->push($this->Fixtures->default['first']));
        $this->assertTrue($Engine->push($this->Fixtures->other['third']));
        $this->assertFalse($Engine->reject($this->Fixtures->default['first']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::push
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::shouldDelay
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::shouldExpire
     */
    public function testPush()
    {
        $Engine = $this->mockEngine();
        $this->assertTrue($Engine->push($this->Fixtures->default['first'], 'default'));
        $this->assertTrue($Engine->push($this->Fixtures->default['second'], [
            'delay' => 30,
        ]));
        $this->assertTrue($Engine->push($this->Fixtures->other['third'], [
            'expires_in' => 1,
        ]));
        $this->assertTrue($Engine->push($this->Fixtures->default['fourth'], 'default'));

        sleep(2);

        $pop1 = $this->Engine->pop();
        $pop2 = $this->Engine->pop();
        $pop3 = $this->Engine->pop();
        $pop4 = $this->Engine->pop();

        $this->assertNull($pop1);
        $this->assertNull($pop2);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\SynchronousEngine::getWorker
     */
    public function testGetWorker()
    {
        $Engine = new SynchronousEngine;
        $this->assertInstanceOf(
            '\josegonzalez\Queuesadilla\Worker\SequentialWorker',
            $this->protectedMethodCall($Engine, 'getWorker')
        );
    }

    protected function protectedMethodCall(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    protected function mockEngine($methods = null, $config = null)
    {
        if ($config === null) {
            $config = $this->config;
        }
        return $this->getMock($this->engineClass, $methods, [$this->Logger, $config]);
    }
}
