<?php

namespace RabbitMqModule\Options;

class RpcServerTest extends \PHPUnit\Framework\TestCase
{
    public function testSetSerializer()
    {
        $options = new RpcServer();

        $options->setSerializer('PhpSerialize');
        static::assertInstanceOf('Laminas\\Serializer\\Adapter\\AdapterInterface', $options->getSerializer());

        $options->setSerializer(null);
        static::assertNull($options->getSerializer());

        $options->setSerializer(['name' => 'PhpSerialize']);
        static::assertInstanceOf('Laminas\\Serializer\\Adapter\\AdapterInterface', $options->getSerializer());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetSerializerWithInvalidValue()
    {
        $options = new RpcServer();

        $options->setSerializer(true);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetSerializerWithEmptyArray()
    {
        $options = new RpcServer();

        $options->setSerializer([]);
    }
}
