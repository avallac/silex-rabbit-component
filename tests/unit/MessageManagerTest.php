<?php

namespace AVAllAC\RabbitComponent;

class MessageManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  MessageManager */
    private $manager;

    public function setUp()
    {
        $this->manager = new MessageManager();
        $message = \Mockery::mock('\AVAllAC\RabbitComponent\AbstractMessage');
        $message->shouldReceive('handle')->andReturn('test string');
        $message->shouldReceive('register')->andReturn('test.test');
        $this->manager->bind($message);
    }

    public function testHandle()
    {
        $message = \Mockery::mock('\PhpAmqpLib\Message\AMQPMessage');
        $message->shouldReceive('get_properties')->andReturn(['priority' => 1]);
        $message->body = (string)new MQMessage(['command' => 'test.test']);
        $return = $this->manager->handle($message);
        $this->assertSame('test string', $return);
    }

    /**
     * @expectedException \AVAllAC\RabbitComponent\CantFindMessageViolationException
     */
    public function testHandleException()
    {
        $message = \Mockery::mock('\PhpAmqpLib\Message\AMQPMessage');
        $message->shouldReceive('get_properties')->andReturn(['priority' => 1]);
        $message->body = (string)new MQMessage(['command' => 'bad.test']);
        $return = $this->manager->handle($message);
        $this->assertSame('test string', $return);
    }


    public function testGetMessageOk()
    {
        $this->assertInstanceOf(
            '\AVAllAC\RabbitComponent\AbstractMessage',
            $this->manager->getMessage('test.test')
        );
    }

    /**
     * @expectedException \AVAllAC\RabbitComponent\CantFindMessageViolationException
     */
    public function testGetMessageBad()
    {
        $this->assertSame(null, $this->manager->getMessage('bad.test'));
    }

    public function testGetMessageKeys()
    {
        $this->assertSame(['test.test'], $this->manager->getMessageKeys());
    }
}
