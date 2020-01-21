<?php

declare(strict_types=1);

namespace RabbitMqModule\Controller;

use Laminas\Console\ColorInterface;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use RabbitMqModule\Consumer;

/**
 * Class ConsumerController.
 */
class ConsumerController extends AbstractConsoleController
{
    /**
     * @var Consumer
     */
    protected $consumer;

    public function indexAction()
    {
        /** @var \Laminas\Console\Response $response */
        $response = $this->getResponse();

        $this->getConsole()->writeLine(sprintf('Starting consumer %s', $this->params('name')));

        $withoutSignals = $this->params('without-signals') || $this->params('w');

        $serviceName = sprintf('rabbitmq.consumer.%s', $this->params('name'));

        if (! $this->container->has($serviceName)) {
            $this->getConsole()->writeLine(
                sprintf('No consumer with name "%s" found', $this->params('name')),
                ColorInterface::RED
            );
            $response->setErrorLevel(1);

            return $response;
        }

        /* @var \RabbitMqModule\Consumer $consumer */
        $this->consumer = $this->container->get($serviceName);
        $this->consumer->setSignalsEnabled(! $withoutSignals);

        if ($withoutSignals) {
            define('AMQP_WITHOUT_SIGNALS', true);
        }

        // @codeCoverageIgnoreStart
        if (! $withoutSignals && \extension_loaded('pcntl')) {
            if (! \function_exists('pcntl_signal')) {
                throw new \BadFunctionCallException(
                    'Function \'pcntl_signal\' is referenced in the php.ini \'disable_functions\' and can\'t be called.'
                );
            }

            \pcntl_signal(SIGTERM, [$this, 'stopConsumer']);
            \pcntl_signal(SIGINT, [$this, 'stopConsumer']);
        }
        // @codeCoverageIgnoreEnd

        $this->consumer->consume();

        return $response;
    }

    /**
     * List available consumers.
     */
    public function listAction()
    {
        /** @var array $config */
        $config = $this->container->get('Configuration');

        if (! array_key_exists('rabbitmq', $config) || ! array_key_exists('consumer', $config['rabbitmq'])) {
            return 'No \'rabbitmq.consumer\' configuration key found!';
        }

        $consumers = $config['rabbitmq']['consumer'];

        if (! is_array($consumers) || count($consumers) === 0) {
            return 'No consumers defined!';
        }

        foreach ($consumers as $name => $configuration) {
            $description = array_key_exists('description', $configuration) ? (string) $configuration['description'] : '';
            $this->getConsole()->writeLine(sprintf(
                '- %s: %s',
                $this->getConsole()->colorize($name, ColorInterface::LIGHT_GREEN),
                $this->getConsole()->colorize($description, ColorInterface::LIGHT_YELLOW)
            ));
        }
    }

    /**
     * Stop consumer.
     */
    public function stopConsumer(): void
    {
        if ($this->consumer instanceof Consumer) {
            $this->consumer->forceStopConsumer();
            try {
                $this->consumer->stopConsuming();
            } catch (AMQPTimeoutException $e) {
                // ignore
            }
        }
        $this->callExit(0);
    }

    /**
     * @param Consumer $consumer
     *
     * @return $this
     */
    public function setConsumer(Consumer $consumer)
    {
        $this->consumer = $consumer;

        return $this;
    }

    /**
     * @param int $code
     * @codeCoverageIgnore
     */
    protected function callExit($code)
    {
        exit($code);
    }
}
