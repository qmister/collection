<?php

namespace Qmister\Tests;

use PHPUnit\Framework\TestCase;
use tp5er\Collection;


class CollectionTest extends TestCase
{

    public function testGet()
    {
        $data   = new Collection(['foo', 'bar', 'baz']);
        $this->assertSame('bar', $data->get(1));
    }

    public function testFirstReturnsFirstItemInCollection()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertSame('foo', $c->first());
    }

    public function testFirstWithCallback()
    {
        $data   = new Collection(['foo', 'bar', 'baz']);
        $result = $data->first(function ($value) {
            return $value === 'bar';
        });
        $this->assertSame('bar', $result);
    }

    public function testFirstWithCallbackAndDefault()
    {
        $data   = new Collection(['foo', 'bar']);
        $result = $data->first(function ($value) {
            return $value === 'baz';
        }, 'default');
        $this->assertSame('default', $result);
    }

    public function testFirstWithDefaultAndWithoutCallback()
    {
        $data   = new Collection;
        $result = $data->first(null, 'default');
        $this->assertSame('default', $result);
    }
}