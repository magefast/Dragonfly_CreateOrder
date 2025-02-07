<?php

namespace Dragonfly\CreateOrder\Console\Command;

use Dragonfly\CreateOrder\Service\Create;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Command
{
    /**
     * @var Create
     */
    private $createOrder;

    /**
     * @var State
     */
    private $state;

    /**
     * @param Create $createOrder
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        Create $createOrder,
        State  $state,
        string $name = null
    )
    {
        parent::__construct($name);
        $this->createOrder = $createOrder;
        $this->state = $state;
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('import:orders:test');
        $this->setDescription('Import Orders Test');
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $result = $this->createOrder->createOrder($this->orderData());

        var_dump($result);
    }

    /**
     * @return array
     */
    private function orderData()
    {
        return [
            'currency_id' => 'UAH',
            'email' => 'test@test.com',
            'shipping_address' => [
                'firstname' => 'test',
                'lastname' => 'test',
                'street' => 'xxxxxx',
                'city' => 'xxxxxxx',
                'country_id' => 'UA',
                'region' => 'xxxxx',
                'postcode' => '85001',
                'telephone' => '52556542',
                'fax' => '3242322556',
                'save_in_address_book' => 1
            ],
            'shipping_method' => 'flatrate_flatrate',
            'payment_method'=>'checkmo',
            'items' => [
                ['sku' => '1', 'qty' => 1, 'price'=>9],
                ['sku' => '2', 'qty' => 2, 'price'=>4]
            ]
        ];
    }
}
