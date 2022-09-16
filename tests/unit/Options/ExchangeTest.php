<?php

namespace RabbitMqModule\Options;

use InvalidArgumentException;

class ExchangeTest extends \PHPUnit\Framework\TestCase
{
    public function testOptions(): void
    {
        $configuration = [
            'name' => 'test-name',
            'type' => 'type-name',
            'passive' => true,
            'durable' => true,
            'auto_delete' => false,
            'internal' => true,
            'no_wait' => true,
            'ticket' => 1,
            'declare' => true,
            'arguments' => [
                'argument1' => 'value1',
            ],
            'exchange_binds' => [
                [
                    'exchange' => ['name' => 'foo'],
                    'routing_keys' => ['routing.1', 'routing.2'],
                ],
            ],
        ];
        $options = new Exchange();
        $options->setFromArray($configuration);

        static::assertEquals($configuration['name'], $options->getName());
        static::assertEquals($configuration['type'], $options->getType());
        static::assertEquals($configuration['passive'], $options->isPassive());
        static::assertEquals($configuration['durable'], $options->isDurable());
        static::assertEquals($configuration['auto_delete'], $options->isAutoDelete());
        static::assertEquals($configuration['internal'], $options->isInternal());
        static::assertEquals($configuration['ticket'], $options->getTicket());
        static::assertEquals($configuration['declare'], $options->isDeclare());
        static::assertEquals($configuration['arguments'], $options->getArguments());

        $binds = $options->getExchangeBinds();
        static::assertCount(1, $binds);

        foreach ($binds as $bind) {
            static::assertInstanceOf('RabbitMqModule\\Options\\Exchange', $bind->getExchange());
            static::assertCount(2, $bind->getRoutingKeys());
            static::assertEquals(['routing.1', 'routing.2'], $bind->getRoutingKeys());
        }
    }

    public function testSetExchangeBindInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new Exchange();
        $options->addExchangeBind('');
    }
}
