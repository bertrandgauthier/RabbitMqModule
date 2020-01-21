<?php

declare(strict_types=1);

namespace RabbitMqModule\Controller;

use Laminas\Console\ColorInterface;

/**
 * Class StdInProducerController.
 */
class StdInProducerController extends AbstractConsoleController
{
    public function indexAction()
    {
        /** @var \Laminas\Console\Response $response */
        $response = $this->getResponse();

        $producerName = $this->params('name');
        $route = (string) $this->params('route', '');
        $msg = (string) $this->params('msg');

        $serviceName = sprintf('rabbitmq.producer.%s', $producerName);

        if (! $this->container->has($serviceName)) {
            $this->getConsole()->writeLine(
                sprintf('No producer with name "%s" found', $producerName),
                ColorInterface::RED
            );
            $response->setErrorLevel(1);

            return $response;
        }

        /** @var \RabbitMqModule\Producer $producer */
        $producer = $this->container->get($serviceName);
        $producer->publish($msg, $route);

        return $response;
    }
}
