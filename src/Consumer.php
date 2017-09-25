<?php

namespace AVAllAC\RabbitComponent;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class Consumer
{
    protected $app;
    protected $receivedBreak;
    protected $socket;
    /** @var AMQPChannel $channel */
    protected $channel;

    public function __construct($app)
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

    protected function initSignalHandlers()
    {
        pcntl_signal(SIGTERM, function () {
            $this->receivedBreak = 1;
        });
        pcntl_signal(SIGINT, function () {
            $this->receivedBreak = 1;
        });
    }

    public function initConsume($qName, MessageManager $manager)
    {
        $fnCallback = function ($rabbitMessage) use ($manager) {
            $code = $manager->handle($rabbitMessage);
            if ($code !== MessageManager::NO_ACK_MESSAGE) {
                $this->channel->basic_ack($rabbitMessage->delivery_info['delivery_tag']);
            }
        };
        $this->channel->basic_consume($qName, '', false, false, false, false, $fnCallback);

    }

    public function run()
    {
        $this->initSignalHandlers();
        if (sizeof($this->channel->getMethodQueue())) {
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
