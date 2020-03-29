<?php

namespace AVAllAC\RabbitComponent;

use PHPUnit\Framework\TestCase;

class MQMessageTest extends TestCase
{
    public function testInputArray()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertSame($m->get('p'), 1);
    }

    public function testInputJson()
    {
        $m = new MQMessage(json_encode(['p' => 1]));
        $this->assertSame($m->get('p'), 1);
    }

    public function testOutJson()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertSame((string)$m, '{"p":1}');
    }

    public function testGetOk()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertSame($m->get('p'), 1);
    }

    public function testGetBad()
    {
        $m = new MQMessage(['p' => 1]);
        $this->expectException(CantFindFieldInMessageViolationException::class);
        $this->assertSame($m->get('d'), null);
    }

    public function testSetAndGetPriority()
    {
        $m = new MQMessage(['p' => 1]);
        $m->setPriority(5);
        $this->assertSame(5, $m->getPriority());
    }

    public function testData()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertEquals((object)['p' => 1], $m->getData());
    }
}
