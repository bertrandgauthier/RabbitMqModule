<?php

namespace RabbitMqModule;

use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqModule\Options\Exchange as ExchangeOptions;
use RabbitMqModule\Options\Queue as QueueOptions;

class ProducerTest extends \PHPUnit\Framework\TestCase
{
    public function testProperties()
    {
        $connection = $this->getMockBuilder('PhpAmqpLib\\Connection\\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $producer = new Producer($connection);

        static::assertSame($connection, $producer->getConnection());
        static::assertEquals('text/plain', $producer->getContentType());
        static::assertEquals(2, $producer->getDeliveryMode());

        $queueOptions = new QueueOptions();
        $exchangeOptions = new ExchangeOptions();

        $producer->setDeliveryMode(-1);
        $producer->setContentType('foo');
        $producer->setQueueOptions($queueOptions);
        $producer->setExchangeOptions($exchangeOptions);
        $producer->setAutoSetupFabricEnabled(false);

        static::assertSame($connection, $producer->getConnection());
        static::assertEquals('foo', $producer->getContentType());
        static::assertEquals(-1, $producer->getDeliveryMode());
        static::assertSame($queueOptions, $producer->getQueueOptions());
        static::assertSame($exchangeOptions, $producer->getExchangeOptions());
        static::assertFalse($producer->isAutoSetupFabricEnabled());
    }

    public function testSetupFabric()
    {
        $connection = $this->getMockBuilder('PhpAmqpLib\\Connection\\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $channel = $this->getMockBuilder('PhpAmqpLib\\Channel\\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $queueOptions = new QueueOptions();
        $queueOptions->setName('foo');
        $exchangeOptions = new ExchangeOptions();

        $producer = new Producer($connection, $channel);
        $producer->setQueueOptions($queueOptions);
        $producer->setExchangeOptions($exchangeOptions);

        $channel->expects(static::once())
            ->method('exchange_declare');
        $channel->expects(static::once())
            ->method('queue_declare');

        $producer->setupFabric();
    }

    public function testPublish()
    {
        $connection = $this->getMockBuilder('PhpAmqpLib\\Connection\\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $channel = $this->getMockBuilder('PhpAmqpLib\\Channel\\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $queueOptions = new QueueOptions();
        $queueOptions->setName('foo');
        $exchangeOptions = new ExchangeOptions();
        $exchangeOptions->setName('foo');

        $producer = new Producer($connection, $channel);
        $producer->setQueueOptions($queueOptions);
        $producer->setExchangeOptions($exchangeOptions);

        $channel->expects(static::once())
            ->method('exchange_declare');
        $channel->expects(static::once())
            ->method('queue_declare');

        $channel->expects(static::once())
            ->method('basic_publish')
            ->with(static::callback(
                function ($subject) {
                    return $subject instanceof AMQPMessage
                    && $subject->body === 'test-body'
                    && $subject->get_properties() === [
                        'content_type' => 'foo/bar',
                        'delivery_mode' => 2,
                    ];
                }
            ), 'foo', 'test-key');

        $producer->publish('test-body', 'test-key', ['content_type' => 'foo/bar']);
    }
}
