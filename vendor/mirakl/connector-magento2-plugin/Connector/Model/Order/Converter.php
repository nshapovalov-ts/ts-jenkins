<?php
namespace Mirakl\Connector\Model\Order;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Tax\ItemFactory as OrderItemTaxFactory;
use Mirakl\Connector\Helper\Config as CoreConfig;
use Mirakl\MMP\Common\Domain\Collection\Order\Tax\OrderTaxAmountCollection;
use Mirakl\MMP\Common\Domain\Order\CustomerBillingAddress;
use Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount;
use Mirakl\MMP\Front\Domain\Collection\Order\Create\CreateOrderOfferCollection;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrder;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrderOffer;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrderPaymentInfo;
use Mirakl\MMP\FrontOperator\Domain\Order\CustomerShippingAddress;
use Mirakl\MMP\FrontOperator\Domain\Order\OrderCustomer;

class Converter
{
    /**
     * @var CoreConfig
     */
    protected $coreConfig;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * @var OrderItemTaxFactory
     */
    protected $orderItemTaxFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var array
     */
    protected $orderItemsTaxes = [];

    /**
     * @param   CoreConfig              $coreConfig
     * @param   CountryFactory          $countryFactory
     * @param   OrderItemTaxFactory     $orderItemTaxFactory
     * @param   PriceCurrencyInterface  $priceCurrency
     * @param   EventManagerInterface   $eventManager
     */
    public function __construct(
        CoreConfig $coreConfig,
        CountryFactory $countryFactory,
        OrderItemTaxFactory $orderItemTaxFactory,
        PriceCurrencyInterface $priceCurrency,
        EventManagerInterface $eventManager
    ) {
        $this->coreConfig = $coreConfig;
        $this->countryFactory = $countryFactory;
        $this->orderItemTaxFactory = $orderItemTaxFactory;
        $this->priceCurrency = $priceCurrency;
        $this->eventManager = $eventManager;
    }

    /**
     * Transforms Magento order data into Mirakl format
     *
     * @param   Order   $order
     * @return  CreateOrder
     */
    public function convert(Order $order)
    {
        $this->eventManager->dispatch('mirakl_connector_convert_order_before', [
            'order' => $order
        ]);

        // Create order object to send to Mirakl
        $createOrder = new CreateOrder();
        $createOrder
            ->setCommercialId($order->getIncrementId())
            ->setShippingZoneCode($order->getMiraklShippingZone())
            ->setScored((bool) $order->getMiraklAutoScore());

        // Create customer to associate with the order
        $orderCustomer = new OrderCustomer();
        $orderCustomer
            ->setCustomerId($order->getCustomerId() ?: $order->getCustomerEmail())
            ->setEmail($order->getCustomerEmail())
            ->setFirstname($order->getCustomerFirstname() ?: __('Guest'))
            ->setLastname($order->getCustomerLastname() ?: __('Guest'))
            ->setLocale($this->coreConfig->getLocale($order->getStore()));

        // Create billing address to associate to the customer
        /** @var \Magento\Sales\Model\Order\Address $billingAddress */
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $country = $this->countryFactory->create()->loadByCode($billingAddress->getCountryId());

            $customerBillingAddress = new CustomerBillingAddress();
            $customerBillingAddress
                ->setFirstname($billingAddress->getFirstname())
                ->setLastname($billingAddress->getLastname())
                ->setCity($billingAddress->getCity())
                ->setCountry($country->getName())
                ->setCountryIsoCode($country->getData('iso3_code'))
                ->setStreet1($billingAddress->getStreetLine(1))
                ->setStreet2($billingAddress->getStreetLine(2))
                ->setZipCode($billingAddress->getPostcode())
                ->setPhone($billingAddress->getTelephone());

            if ($company = $billingAddress->getCompany()) {
                $customerBillingAddress->setCompany($company);
            }

            if ($region = $billingAddress->getRegion()) {
                $customerBillingAddress->setState($region);
            }

            // Assign billing address to the customer
            $orderCustomer->setBillingAddress($customerBillingAddress);
        }

        // Create shipping address to associate to the customer
        /** @var \Magento\Sales\Model\Order\Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $country = $this->countryFactory->create()->loadByCode($shippingAddress->getCountryId());

            $customerShippingAddress = new CustomerShippingAddress();
            $customerShippingAddress
                ->setFirstname($shippingAddress->getFirstname())
                ->setLastname($shippingAddress->getLastname())
                ->setCity($shippingAddress->getCity())
                ->setCountry($country->getName())
                ->setCountryIsoCode($country->getData('iso3_code'))
                ->setStreet1($shippingAddress->getStreetLine(1))
                ->setStreet2($shippingAddress->getStreetLine(2))
                ->setZipCode($shippingAddress->getPostcode())
                ->setPhone($shippingAddress->getTelephone());

            if ($company = $shippingAddress->getCompany()) {
                $customerShippingAddress->setCompany($company);
            }

            if ($region = $shippingAddress->getRegion()) {
                $customerShippingAddress->setState($region);
            }

            // Assign shipping address to the customer
            $orderCustomer->setShippingAddress($customerShippingAddress);
        }

        // Assign customer to the order to send
        $createOrder->setCustomer($orderCustomer);

        // Create offers to associate to the order
        $offerList = $this->createOffers($order);

        // Assign offers to the order to send
        $createOrder->setOffers($offerList);

        // Create payment information to associate to the order
        $payment = $order->getPayment();
        if ($payment) {
            $paymentInfo = new CreateOrderPaymentInfo();
            $paymentInfo->setPaymentType($payment->getMethod());

            // Assign payment information to the order to send
            $createOrder->setPaymentInfo($paymentInfo);
        }

        // Assign payment workflow used in configuration
        $createOrder->setPaymentWorkflow($this->coreConfig->getPaymentWorkflow());

        $this->eventManager->dispatch('mirakl_connector_convert_order_after', [
            'order'        => $order,
            'create_order' => $createOrder,
        ]);

        return $createOrder;
    }

    /**
     * Create offers associated to specified order
     *
     * @param   Order   $order
     * @return  CreateOrderOfferCollection
     */
    protected function createOffers(Order $order)
    {
        $offerList = new CreateOrderOfferCollection();

        foreach ($order->getAllItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            if (!$item->getMiraklOfferId()) {
                continue;
            }

            $offer = new CreateOrderOffer();
            $offer->setOfferId((int) $item->getMiraklOfferId())
                ->setOrderLineId((int) $item->getId())
                ->setQuantity((int) $item->getQtyOrdered())
                ->setShippingPrice((float) $item->getMiraklBaseShippingFee())
                ->setShippingTypeCode($item->getMiraklShippingType())
                ->setCurrencyIsoCode($order->getBaseCurrencyCode());

            if ($customTaxApplied = unserialize($item->getMiraklCustomTaxApplied())) {
                // Offer price excluding tax
                $offer->setPrice((float) $item->getBaseRowTotal())
                    ->setOfferPrice((float) $item->getBasePrice());

                // Add offer and shipping tax details to offer
                foreach (['taxes', 'shipping_taxes'] as $taxType) {
                    // Group taxes by type/code
                    $taxesByCode = [];
                    if (empty($customTaxApplied[$taxType])) {
                        continue;
                    }
                    foreach ($customTaxApplied[$taxType] as $tax) {
                        if (empty($customTaxApplied[$taxType])) {
                            continue;
                        }
                        $code = $tax['type'];
                        if (!isset($taxesByCode[$code])) {
                            $taxesByCode[$code] = 0;
                        }
                        $taxesByCode[$code] += $tax['base_amount'];
                    }
                    $taxes = new OrderTaxAmountCollection();
                    foreach ($taxesByCode as $code => $amount) {
                        $tax = new OrderTaxAmount($amount, $code);
                        $taxes->add($tax);
                    }
                    if ($taxes->count()) {
                        $offer->setData($taxType, $taxes);
                    }
                }
            } else {
                if ($order->getMiraklIsOfferInclTax()) {
                    // Offer price including tax
                    $offer->setPrice((float) $item->getBaseRowTotalInclTax())
                        ->setOfferPrice((float) $item->getBasePriceInclTax());
                } else {
                    // Offer price excluding tax
                    $offer->setPrice((float) $item->getBaseRowTotal())
                        ->setOfferPrice((float) $item->getBasePrice());

                    $orderItemsTaxes = $this->getOrderItemsTaxes($order);
                    $taxes = new OrderTaxAmountCollection();
                    foreach ($orderItemsTaxes as $orderItemTax) {
                        if ($orderItemTax['item_id'] != $item->getId() || $orderItemTax['taxable_item_type'] != 'product') {
                            continue;
                        }
                        $tax = new OrderTaxAmount($orderItemTax['real_base_amount'], $orderItemTax['code']);
                        $taxes->add($tax);
                    }
                    if ($taxes->count()) {
                        $offer->setTaxes($taxes);
                    }
                }

                if (!$order->getMiraklIsShippingInclTax()
                    && $item->getMiraklBaseShippingTaxAmount()
                    && $offer->getShippingPrice() > 0)
                {
                    // Shipping price excluding tax
                    $shippingTaxes = new OrderTaxAmountCollection();
                    $shippingTaxApplied = unserialize($item->getMiraklShippingTaxApplied());
                    if (is_array($shippingTaxApplied)) {
                        $shippingTaxInclTax = $offer->getShippingPrice();
                        foreach ($shippingTaxApplied as $shippingTaxInfo) {
                            foreach ($shippingTaxInfo['rates'] as $rateInfo) {
                                $shippingTaxAmount = $this->priceCurrency->round(
                                    $shippingTaxInclTax * $rateInfo['percent'] / 100
                                );
                                $shippingTax = new OrderTaxAmount($shippingTaxAmount, $rateInfo['code']);
                                $shippingTaxes->add($shippingTax);
                            }
                            $shippingTaxInclTax += $offer->getShippingPrice() * $shippingTaxInfo['percent'] / 100;
                        }
                    }
                    if ($shippingTaxes->count()) {
                        $offer->setShippingTaxes($shippingTaxes);
                    }
                }
            }

            $offerList->add($offer);
        }

        return $offerList;
    }

    /**
     * Returns order items taxes information
     *
     * @param   Order   $order
     * @return  array
     */
    protected function getOrderItemsTaxes(Order $order)
    {
        if (!isset($this->orderItemsTaxes[$order->getId()])) {
            $this->orderItemsTaxes[$order->getId()] = $this->orderItemTaxFactory->create()
                ->getTaxItemsByOrderId($order->getId());
        }

        return $this->orderItemsTaxes[$order->getId()];
    }
}
