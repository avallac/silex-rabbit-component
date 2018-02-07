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
        if ($param instanceof \stdClass) {
            $this->data = (object)$param;
        } elseif (is_array($param)) {
            $this->data = (object)json_decode(json_encode($param));
        } else {
            $this->data = (object)json_decode($param);
        }
    }

    public function getData() : \stdClass
    {
        return $this->data;
    }

    public function __toString() : string
    {
        return (string)json_encode($this->data);
    }

    /**
     * @param string $name
     * @throws |AVAllAC\RabbitComponent|CantFindFieldInMessageViolationException
     * @return mixed
     */
    public function get(string $name)
    {
        if ($this->exists($name)) {
            return $this->data->{$name};
        } else {
            throw new CantFindFieldInMessageViolationException($name);
        }
    }

    public function exists($name) : bool
    {
        if (property_exists($this->data, $name)) {
            return true;
        } else {
            return false;
        }
    }
}