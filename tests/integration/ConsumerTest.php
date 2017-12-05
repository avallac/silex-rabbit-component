<?php

namespace AVAllAC\RabbitComponent;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use Silex\Application;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorHandler()
    {
        $app = new Application();
        $app['amqp'] = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
        $app->register(new RabbitChannelProvider());
        $consumer = new Consumer($app);

        $testMessageClass = new class($app, $this, $consumer) extends ActiveMessage
        {
            protected $parent;
            protected $state = 0;
            protected $consumer;

            public function __construct($app, $parent, Consumer $consumer)
            {
                $this->parent = $parent;
                $this->consumer = $consumer;
                parent::__construct($app);
            }

            public function handle(MQMessage $msg)
            {
                $this->state++;
                if ($this->state === 1) {
                    $this->parent->assertEquals('a', $msg->get('param'));
                    $this->parent->assertEquals(100, $msg->getPriority());
                    throw new \Exception();
                } elseif ($this->state === 2) {
                    $this->parent->assertEquals('b', $msg->get('param'));
                    $this->parent->assertEquals(100, $msg->getPriority());
                    throw new \Exception();
                } elseif ($this->state === 3) {
                    $this->parent->assertEquals('a', $msg->get('param'));
                    $this->parent->assertEquals(99, $msg->getPriority());
                } elseif ($this->state === 4) {
                    $this->parent->assertEquals('b', $msg->get('param'));
                    $this->parent->assertEquals(99, $msg->getPriority());
                    throw new \Exception();
                } elseif ($this->state === 5) {
                    $this->parent->assertEquals('b', $msg->get('param'));
                    $this->parent->assertEquals(98, $msg->getPriority());
                    throw new \Exception();
                } else {
                    $this->consumer->stop();
                }
            }

            public function validate(MQMessage $message)
            {
                return true;
            }
        };
        $manager = new MessageManager();
        $manager->bind($testMessageClass);
        $queue =  $app['rabbitChannel']->queue_declare()[0];
        $testMessageClass->send(['param' => 'a'], 100, $queue);
        $testMessageClass->send(['param' => 'b'], 100, $queue);
        $consumer->initConsume($queue, $manager);
        $consumer->run();

    }
}
