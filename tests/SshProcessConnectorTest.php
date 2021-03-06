<?php

use PHPUnit\Framework\TestCase;
use Clue\React\SshProxy\SshProcessConnector;

class SshProcessConnectorTest extends TestCase
{
    public function testConstructorAcceptsUri()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new SshProcessConnector('host', $loop);

        $ref = new ReflectionProperty($connector, 'cmd');
        $ref->setAccessible(true);

        $this->assertEquals('exec ssh -vv -o BatchMode=yes \'host\'', $ref->getValue($connector));
    }

    public function testConstructorAcceptsUriWithDefaultPortWillNotBeAddedToCommand()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new SshProcessConnector('host:22', $loop);

        $ref = new ReflectionProperty($connector, 'cmd');
        $ref->setAccessible(true);

        $this->assertEquals('exec ssh -vv -o BatchMode=yes \'host\'', $ref->getValue($connector));
    }

    public function testConstructorAcceptsUriWithUserAndCustomPort()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new SshProcessConnector('user@host:2222', $loop);

        $ref = new ReflectionProperty($connector, 'cmd');
        $ref->setAccessible(true);

        $this->assertEquals('exec ssh -vv -o BatchMode=yes -p 2222 \'user@host\'', $ref->getValue($connector));
    }

    public function testConstructorAcceptsUriWithPasswordWillPrefixSshCommandWithSshpassAndWithoutBatchModeOption()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new SshProcessConnector('user:pass@host', $loop);

        $ref = new ReflectionProperty($connector, 'cmd');
        $ref->setAccessible(true);

        $this->assertEquals('exec sshpass -p \'pass\' ssh -vv \'user@host\'', $ref->getValue($connector));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsForInvalidUri()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        new SshProcessConnector('///', $loop);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsForInvalidUser()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        new SshProcessConnector('-invalid@host', $loop);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsForInvalidPass()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        new SshProcessConnector('user:-invalid@host', $loop);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsForInvalidHost()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        new SshProcessConnector('-host', $loop);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testConstructorAcceptsHostWithLeadingDashWhenPrefixedWithUser()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new SshProcessConnector('user@-host', $loop);
    }

    public function testConnectReturnsRejectedPromiseForInvalidUri()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new SshProcessConnector('host', $loop);

        $promise = $connector->connect('///');
        $promise->then(null, $this->expectCallableOnceWith($this->isInstanceOf('InvalidArgumentException')));
    }

    public function testConnectReturnsRejectedPromiseForInvalidHost()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new SshProcessConnector('host', $loop);

        $promise = $connector->connect('-host:80');
        $promise->then(null, $this->expectCallableOnceWith($this->isInstanceOf('InvalidArgumentException')));
    }

    protected function expectCallableOnceWith($value)
    {
        $mock = $this->createCallableMock();

        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($value);

        return $mock;
    }

    protected function createCallableMock()
    {
        return $this->getMockBuilder('stdClass')->setMethods(array('__invoke'))->getMock();
    }
}
