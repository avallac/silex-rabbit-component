<?php

namespace AVAllAC\RabbitComponent;

use PHPUnit\Framework\TestCase;
use PhpAmqpLib\Message\AMQPMessage;

class MessageManagerTest extends TestCase
{
    /** @var  MessageManager */
    private $manager;

    public function setUp(): void
    {
        $this->manager = new MessageManager();
        $message = $this->createMock(AbstractMessage::class);
        $message->method('handle')->willReturn('test string');
        $message->method('register')->willReturn('test.test');
        $this->manager->bind($message);
    }

    public function testHandle()
    {
        $message = $this->createMock(AMQPMessage::class);
        $message->method('get_properties')->willReturn(['priority' => 1]);
        $message->body = (string)new MQMessage(['command' => 'test.test']);
        $return = $this->manager->handle($message);
        $this->assertSame('test string', $return);
    }

    public function testHandleException()
    {
        $this->expectException(CantFindMessageViolationException::class);
        $message = $this->createMock(AMQPMessage::class);
        $message->method('get_properties')->willReturn(['priority' => 1]);
        $message->body = (string)new MQMessage(['command' => 'bad.test']);
        $return = $this->manager->handle($message);
        $this->assertSame('test string', $return);
    }


    public function testGetMessageOk()
    {
        $this->assertInstanceOf(
            AbstractMessage::class,
            $this->manager->getMessage('test.test')
        );
    }

    public function testGetMessageBad()
    {
        $this->expectException(CantFindMessageViolationException::class);
        $this->assertSame(null, $this->manager->getMessage('bad.test'));
    }

    public function testGetMessageKeys()
    {
        $this->assertSame(['test.test'], $this->manager->getMessageKeys());
    }
}
