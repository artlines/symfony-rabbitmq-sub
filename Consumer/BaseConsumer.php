<?php

namespace App\Consumer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

class BaseConsumer
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected LoggerInterface $logger,
        protected int $commonBaseConsumerMaxAttempts
    ) {
    }

    abstract protected function process(array $data): void;

    abstract protected function getDelayedProducer(): ProducerInterface;

    protected function getDelayWhenError(): int
    {
        return 1000 * 60 * 5;
    }

    public static function getDelayMessageHeaders(int $seconds = 0, int $minutes = 0, int $hours = 0): array
    {
        $time = 0;
        $time += 1000 * $seconds;
        $time += 1000 * 60 * $minutes;
        $time += 1000 * 60 * 60 * $hours;
        return ['x-delay' => $time];
    }

    public function execute(AMQPMessage $msg): void
    {
        $data = json_decode($msg->getBody(), true);

        try {
            $this->em->getConnection()->connect();
            $this->process($data);
        } catch (Throwable $e) {
            $this->catchExceptionAction($data, $e);
        }

        unset($data);

        $this->em->clear();
        $this->em->getConnection()->close();

        gc_collect_cycles();
        gc_mem_caches();
    }

    private function catchExceptionAction(array $data, Throwable $e): void
    {
        $log = sprintf(
            'Class = %s; Line = %s; ErrorMessage = %s;' . PHP_EOL . 'ErrorTrace: %s',
            $e->getFile(),
            $e->getLine(),
            $e->getMessage(),
            $e->getTraceAsString()
        );

        if (!isset($data['attempt'])) {
            $data['attempt'] = 1;
        }

        $log .= ' attempt=' . $data['attempt'];
        $data['attempt'] = $data['attempt'] + 1;

        if (!$e instanceof EntityManagerClosed) {
            $this->logger->warning($log);
        }

        if ($data['attempt'] < $this->commonBaseConsumerMaxAttempts) {
            $this->getDelayedProducer()->publish(msgBody: json_encode($data), headers: ['x-delay' => $this->getDelayWhenError()]);
        } else {
            $this->logger->critical($log . ' Attempts ended.');
        }
    }
}
