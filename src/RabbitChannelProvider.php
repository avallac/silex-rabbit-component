<?php

namespace AVAllAC\RabbitComponent;

use PhpAmqpLib\Connection\AbstractConnection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RabbitChannelProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['rabbitChannel'] = function () use ($app) {
            if ($app['amqp'] instanceof AbstractConnection) {
                return $app['amqp']->channel();
            } else {
                throw new \Exception('require AbstractConnection on "amqp"');
            }
        };
    }
}