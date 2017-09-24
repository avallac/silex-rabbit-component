<?php

namespace AVAllAC\RabbitComponent;

use AVAllAC\JsonValidatorComponent\JsonValidatorService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

abstract class ActiveMessage extends AbstractMessage
{
    protected $workQueue = null;
    protected $prior = 1;
    protected $app;
    protected $jsonSchema;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function validate(MQMessage $message)
    {
        if ($this->app['jsonValidator'] instanceof JsonValidatorService) {
            /** @var JsonValidatorService $validator */
            $validator = $this->app['jsonValidator'];
        } else {
            throw new \Exception('require JsonValidatorService on "jsonValidator"');
        }
        if (!$validator->isValid($message->getData(), json_decode($this->jsonSchema))) {
            return false;
        }
        return true;
    }

    public function send(array $params, $prior = null)
    {
        if ($this->app['rabbitChannel'] instanceof AMQPChannel) {
            /** @var AMQPChannel $channel */
            $channel = $this->app['rabbitChannel'];
        } else {
            throw new \Exception('require AMQPChannel on "rabbitChannel"');
        }
        $outM = $this->create($params);
        $prior = $prior === null ? $this->prior : $prior;
        $msg = new AMQPMessage($outM, ['priority' => $prior]);
        if (strpos($this->workQueue, '@') !== false) {
            $q = explode('@', $this->workQueue);
            $channel->basic_publish($msg, $q[1], $q[0]);
        } else {
            $channel->basic_publish($msg, '', $this->workQueue);
        }
    }
}
