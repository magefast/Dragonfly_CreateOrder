<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Dragonfly\CreateOrder\Service;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Create
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var QuoteFactory
     */
    private $quote;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteFactory $quote
     * @param QuoteManagement $quoteManagement
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        StoreManagerInterface       $storeManager,
        CustomerFactory             $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        QuoteFactory                $quote,
        QuoteManagement             $quoteManagement,
        ProductRepositoryInterface  $productRepository
    )
    {
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->productRepository = $productRepository;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function createOrder($order)
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        $quote = $this->quote->create();
        $quote->setStore($store);

        /** @var Customer $customer */
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($order['email']);

        if ($customer->getEntityId()) {
            $customer = $this->customerRepository->getById($customer->getEntityId());
            $quote->setCustomer($customer)->assignCustomer($customer);
        } else {
            $quote->setCustomerEmail($order['email']);
            $quote->setCustomerFirstname($order['email']);
            $quote->setCustomerMiddlename($order['email']);
            $quote->setCustomerLastname($order['email']);
            $quote->setCustomerIsGuest(true);
        }

        $quote->setCurrency();

        foreach ($order['items'] as $item) {
            $product = $this->productRepository->get($item['sku']);
            $product->setPrice($item['price']);
            $quote->addProduct($product, intval($item['qty']));
        }

        $quote->getBillingAddress()->addData($order['shipping_address']);
        $quote->getShippingAddress()->addData($order['shipping_address']);
        // Collect Rates and Set Shipping & Payment Method
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($order['shipping_method']);
        $quote->setPaymentMethod($order['payment_method']);
        $quote->setInventoryProcessed(false);
        $quote->save();
        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => $order['payment_method']]);
        // Collect Totals & Save Quote
        $quote->collectTotals()->save();
        // Create Order From Quote
        $orderData = $this->quoteManagement->submit($quote);
        $orderData->setEmailSent(0);
        $increment_id = $orderData->getRealOrderId();
        if ($orderData->getEntityId()) {
            $result['order_id'] = $orderData->getRealOrderId();
        } else {
            $result = ['error' => 1, 'msg' => 'Your custom message'];
        }
        return $result;
    }
}