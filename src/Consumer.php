<?php

namespace AVAllAC\RabbitComponent;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Silex\Application;

class Consumer
{
    protected $app;
    protected $receivedBreak;
    protected $socket;
    /** @var AMQPChannel $channel */
    protected $channel;
    protected $preHandler = null;
    protected $postHandler = null;

    /**
     * Consumer constructor.
     * @param Application $app
     * @throws \Exception
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        if ($this->app['amqp'] instanceof AbstractConnection) {
            $this->socket = $this->app['amqp']->getSocket();
        } else {
            throw new \Exception('require AbstractConnection on "amqp"');
        }
        if ($this->app['rabbitChannel'] instanceof AMQPChannel) {
            $this->channel = $this->app['rabbitChannel'];
        } else {
            throw new \Exception('require AMQPChannel on "rabbitChannel"');
        }
    }

    public function setPreHandler($preHandler)
    {
        $this->preHandler = $preHandler;
    }

    public function setPostHandler($postHandler)
    {
        $this->postHandler = $postHandler;
    }

    public function stop()
    {
        $this->receivedBreak = 1;
    }

    protected function initSignalHandlers()
    {
        pcntl_signal(SIGTERM, function () {
            $this->receivedBreak = 1;
        });
        pcntl_signal(SIGINT, function () {
            $this->receivedBreak = 1;
        });
    }

    public function initConsume($qName, MessageManager $manager, $catchExceptions = true)
    {
        $fnCallback = function (AMQPMessage $rabbitMessage) use ($manager, $qName, $catchExceptions) {
            try {
                $begin = microtime(true);
                if (is_callable($this->preHandler)) {
                    call_user_func($this->preHandler, $rabbitMessage);
                }
                $code = $manager->handle($rabbitMessage);
                if ($code !== MessageManager::NO_ACK_MESSAGE) {
                    $this->channel->basic_ack($rabbitMessage->delivery_info['delivery_tag']);
                }
                if (is_callable($this->postHandler)) {
                    call_user_func($this->postHandler, $rabbitMessage, microtime(true) - $begin);
                }

            } catch (\Exception $e) {
                if (!$catchExceptions) {
                    throw $e;
                }
                $property = $rabbitMessage->get_properties();
                $deliveryTag = $rabbitMessage->delivery_info['delivery_tag'];
                if (isset($property['priority'])) {
                    $rabbitMessage->set('priority', max($property['priority'] - 1, 1));
                }
                $this->channel->basic_publish($rabbitMessage, '', $qName);
                $this->channel->basic_ack($deliveryTag);
            }
        };
        $this->channel->basic_consume($qName, '', false, false, false, false, $fnCallback);

    }

    public function run()
    {
        $this->initSignalHandlers();
        while (sizeof($this->channel->getMethodQueue())) {
            $this->channel->wait();
        }
        while (count($this->channel->callbacks) && !$this->receivedBreak) {
            $read = [$this->socket];
            $write = null;
            $except = null;
            $changeStreamsCount = @stream_select($read, $write, $except, 60);
            pcntl_signal_dispatch();
            if (!$this->receivedBreak) {
                if ($changeStreamsCount === false) {
                    throw new \RuntimeException();
                } elseif ($changeStreamsCount > 0) {
                    $this->channel->wait();
                }
            }
            pcntl_signal_dispatch();
        }
    }
}
