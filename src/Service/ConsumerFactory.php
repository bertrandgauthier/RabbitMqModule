<?php

namespace RabbitMqModule\Service;

use RabbitMqModule\Consumer;
use RabbitMqModule\ConsumerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use RabbitMqModule\Options\Consumer as Options;
use RuntimeException;

class ConsumerFactory extends AbstractFactory
{
    /**
     * Get the class name of the options associated with this factory.
     *
     * @return string
     */
    public function getOptionsClass()
    {
        return 'RabbitMqModule\\Options\\Consumer';
    }

    /**
     * Create service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var $options Options */
        $options = $this->getOptions($serviceLocator, 'consumer');

        return $this->createConsumer($serviceLocator, $options);
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param Options                 $options
     *
     * @throws RuntimeException
     *
     * @return Consumer
     */
    protected function createConsumer(ServiceLocatorInterface $serviceLocator, Options $options)
    {
        $callback = $options->getCallback();
        if ($callback instanceof ConsumerInterface) {
            $callback = [$callback, 'execute'];
        }
        if (!is_callable($callback)) {
            throw new RuntimeException('Invalid callback provided');
        }

        /** @var \PhpAmqpLib\Connection\AbstractConnection $connection */
        $connection = $serviceLocator->get(sprintf('rabbitmq.connection.%s', $options->getConnection()));
        $consumer = new Consumer($connection);
        $consumer->setQueueOptions($options->getQueue());
        $consumer->setExchangeOptions($options->getExchange());
        $consumer->setConsumerTag($options->getConsumerTag());
        $consumer->setAutoSetupFabricEnabled($options->isAutoSetupFabricEnabled());
        $consumer->setCallback($callback);

        if ($options->getQos()) {
            $consumer->setQosOptions(
                $options->getQos()->getPrefetchSize(),
                $options->getQos()->getPrefetchCount()
            );
        }

        return $consumer;
    }
}
