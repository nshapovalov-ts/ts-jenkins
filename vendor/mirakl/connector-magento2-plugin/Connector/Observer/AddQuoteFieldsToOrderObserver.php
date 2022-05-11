<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddQuoteFieldsToOrderObserver implements ObserverInterface
{
    /**
     * @var array
     */
    private $copyQuoteFields = [
        'mirakl_shipping_zone',
        'mirakl_base_shipping_fee',
        'mirakl_shipping_fee',
        'mirakl_is_offer_incl_tax',
        'mirakl_is_shipping_incl_tax',
        'mirakl_base_shipping_tax_amount',
        'mirakl_shipping_tax_amount',
        'mirakl_base_custom_shipping_tax_amount',
        'mirakl_custom_shipping_tax_amount'
    ];

    /**
     * @var array
     */
    private $copyQuoteItemFields = [
        'free_shipping',
        'mirakl_offer_id',
        'mirakl_shop_id',
        'mirakl_shop_name',
        'mirakl_leadtime_to_ship',
        'mirakl_shipping_type',
        'mirakl_shipping_type_label',
        'mirakl_base_shipping_fee',
        'mirakl_shipping_fee',
        'mirakl_shipping_tax_percent',
        'mirakl_base_shipping_tax_amount',
        'mirakl_shipping_tax_amount',
        'mirakl_shipping_tax_applied',
        'mirakl_custom_tax_applied',
        'mirakl_base_custom_shipping_tax_amount',
        'mirakl_custom_shipping_tax_amount',
    ];

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /**
         * @var \Magento\Quote\Model\Quote $quote
         * @var \Magento\Quote\Model\Quote\Item $quoteItem
         * @var \Magento\Sales\Model\Order $order
         * @var \Magento\Sales\Model\Order\Item $orderItem
         */
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        // Copy quote fields to order
        foreach ($this->copyQuoteFields as $field) {
            $order->setData($field, $quote->getData($field));
        }

        // Copy quote items fields to order items
        foreach ($order->getItems() as $orderItem) {
            $quoteItem = $quote->getItemById($orderItem->getQuoteItemId());
            if ($quoteItem && $quoteItem->getMiraklOfferId()) {
                foreach ($this->copyQuoteItemFields as $field) {
                    $orderItem->setData($field, $quoteItem->getData($field));
                }
            }
        }
    }
}