<?php


namespace tp5er\Tests;


use PHPUnit\Framework\TestCase;
use tp5er\Arr;

class ArrTest extends TestCase
{
    public function testGet()
    {
        $array = [
            'foo' => [
                'bar' => 'test',
            ]
        ];
        $this->assertSame('test', Arr::get($array,'foo.bar'));
        $this->assertSame('test', Arr::dataGet($array,'foo.bar'));
    }
}