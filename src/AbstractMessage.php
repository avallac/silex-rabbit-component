<?php

namespace AVAllAC\RabbitComponent;

abstract class AbstractMessage
{
    protected $require = [];

    public function register()
    {
        return $this->getCommand();
    }

    public function create(array $params)
    {
        $params['command'] = $this->getCommand();
        $outM = new MQMessage($params);
        if (!$this->validate($outM)) {
            throw new NotValidMessageViolationException(get_class($this));
        }
        return $outM;
    }

    public function getCommand()
    {
        return get_class($this);
    }

    abstract public function handle(MQMessage $message);
    abstract public function validate(MQMessage $message);
}
