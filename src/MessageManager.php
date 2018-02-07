<?php

namespace AVAllAC\RabbitComponent;

use PhpAmqpLib\Message\AMQPMessage;

class MessageManager
{
    const NO_ACK_MESSAGE = 1;

    private $messages = [];

    public function bind(AbstractMessage $message)
    {
        $this->messages[$message->register()] = $message;
    }

    public function handle(AMQPMessage $message)
    {
        $msg = new MQMessage($message->body);
        $property = $message->get_properties();
        $delivery = $message->delivery_info;
        if (isset($property['priority'])) {
            $msg->setPriority($property['priority']);
        }
        if (isset($delivery['delivery_tag'])) {
            $msg->setDeliveryTag($delivery['delivery_tag']);
        }
        return $this->getMessage($msg->get('command'))->handle($msg);
    }

    public function getMessageKeys()
    {
        return array_keys($this->messages);
    }

    /**
     * @param $key
     * @return AbstractMessage|null
     * @throws |AVAllAC\RabbitComponent|CantFindMessageViolationException
     */
    public function getMessage($key)
    {
        if ($this->messageIsAllow($key)) {
            return $this->messages[$key];
        } else {
            throw new CantFindMessageViolationException($key);
        }

    }

    public function messageIsAllow($key)
    {
        if (isset($this->messages[$key])) {
            return true;
        } else {
            return false;
        }
    }
}
