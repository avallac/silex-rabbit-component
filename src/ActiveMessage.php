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

    /**
     * @param MQMessage $message
     * @throws \Exception
     * @return bool
     */
    public function validate(MQMessage $message) : bool
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

    /**
     * @param array $params
     * @param int $prior
     * @param string $queue
     * @throws \Exception
     */
    public function send(array $params, int $prior = null, string $queue = null)
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
        $queue = $queue ?: $this->workQueue;
        if (strpos($queue, '@') !== false) {
            $q = explode('@', $queue);
            $channel->basic_publish($msg, $q[1], $q[0]);
        } else {
            $channel->basic_publish($msg, '', $queue);
        }
    }
}
