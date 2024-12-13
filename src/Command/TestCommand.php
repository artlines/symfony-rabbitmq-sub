<?php

namespace App\Command;

use App\Consumer\BaseConsumer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'test:test')]
class TestCommand extends Command
{
    public function __construct(
        private readonly ProducerInterface $processSmsNotificationProducer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processSmsNotificationProducer->publish(
            json_encode([
                'tel_number' => '+7 999 999 99 99',
                'sms_text' => 'some awesome text here',
            ]),
            headers: BaseConsumer::getDelayMessageHeaders()
        );
        return 0;
    }
}