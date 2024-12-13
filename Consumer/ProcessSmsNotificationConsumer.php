<?php

namespace App\Integration\AliExpress\Consumer;

use App\Consumer\BaseConsumer;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ProcessSmsNotificationConsumer extends BaseConsumer
{
    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        #[Autowire('%commonBaseConsumerMaxAttempts%')] int $commonBaseConsumerMaxAttempts,
        private readonly ProducerInterface $processSmsNotificationProducer,
    ) {
        parent::__construct($em, $logger, $commonBaseConsumerMaxAttempts);
    }

    protected function process(array $data): void
    {
        // реализуйте тут логику

        // в массиве $data будет лежать то, что было передано в сообщении

        // здесь вы можете выбросить исключение и за счет
        // логики в базовом контроллере вы сможете
        // перепоставить задачку в очередь снова
    }

    protected function getDelayedProducer(): ProducerInterface
    {
        return $this->processSmsNotificationProducer;
    }
}
