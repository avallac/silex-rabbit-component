<?php

namespace AVAllAC\RabbitComponent;

class MQMessage
{
    private $data;
    protected $priority = null;
    protected $deliveryTag = null;

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setDeliveryTag($tag)
    {
        $this->deliveryTag = $tag;
    }

    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    public function __construct($param)
    {
        if (is_array($param)) {
            $this->data = $param;
        } else {
            $this->data = json_decode($param, true);
        }
    }

    public function getData()
    {
        return (object)$this->data;
    }

    public function __toString()
    {
        return (string)json_encode($this->data);
    }

    public function get($name)
    {
        if ($this->exists($name)) {
            return $this->data[$name];
        } else {
            throw new CantFindFieldInMessageViolationException($name);
        }
    }

    public function exists($name)
    {
        if (array_key_exists($name, $this->data)) {
            return true;
        } else {
            return false;
        }
    }
}