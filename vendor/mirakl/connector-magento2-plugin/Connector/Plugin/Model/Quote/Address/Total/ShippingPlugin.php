<?php
namespace Mirakl\Connector\Plugin\Model\Quote\Address\Total;

use Magento\Catalog\Model\Product;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\Shipping;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;

class ShippingPlugin
{
    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var QuoteSynchronizer
     */
    protected $quoteSynchronizer;

    /**
     * @param   QuoteHelper         $quoteHelper
     * @param   ConnectorConfig     $connectorConfig
     * @param   QuoteSynchronizer   $quoteSynchronizer
     */
    public function __construct(
        QuoteHelper $quoteHelper,
        ConnectorConfig $connectorConfig,
        QuoteSynchronizer $quoteSynchronizer
    ) {
        $this->quoteHelper = $quoteHelper;
        $this->connectorConfig = $connectorConfig;
        $this->quoteSynchronizer = $quoteSynchronizer;
    }

    /**
     * @param   Shipping                    $subject
     * @param   \Closure                    $proceed
     * @param   Quote                       $quote
     * @param   ShippingAssignmentInterface $shippingAssignment
     * @param   Total                       $total
     * @return  Shipping
     */
    public function aroundCollect(
        Shipping $subject,
        \Closure $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $proceed($quote, $shippingAssignment, $total);

        if (!$this->quoteHelper->isMiraklQuote($quote)) {
            return $subject;
        }

        /** @var Address $shippingAddress */
        $shippingAddress = $shippingAssignment->getShipping()->getAddress();

        // Collect Mirakl shipping fees
        $this->collectShippingFees($shippingAddress);

        return $subject;
    }

    /**
     * @param   Address $address
     * @return  $this
     */
    private function collectShippingFees(Address $address)
    {
        if ($address->getAddressType() == Address::ADDRESS_TYPE_BILLING) {
            return $this;
        }

        $quote = $address->getQuote();
        $items = $quote->getAllVisibleItems();

        $shippingFeeBaseTotal       = 0;
        $shippingFeeTotal           = 0;
        $shippingTaxBaseTotal       = 0;
        $shippingTaxTotal           = 0;
        $customShippingTaxBaseTotal = 0;
        $customShippingTaxTotal     = 0;

        /** @var QuoteItem $item */
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item->getProduct();
            if ($product->isVirtual() || $item->getParentItem() || !$item->getMiraklShopId()) {
                continue;
            }
            $shippingFeeBaseTotal       += $item->getMiraklBaseShippingFee();
            $shippingFeeTotal           += $item->getMiraklShippingFee();
            $shippingTaxBaseTotal       += $item->getMiraklBaseShippingTaxAmount();
            $shippingTaxTotal           += $item->getMiraklShippingTaxAmount();
            $customShippingTaxBaseTotal += $item->getMiraklBaseCustomShippingTaxAmount();
            $customShippingTaxTotal     += $item->getMiraklCustomShippingTaxAmount();
        }

        $zone = $this->quoteSynchronizer->getQuoteShippingZone($quote);
        $quote->setMiraklShippingZone($zone)
            ->setMiraklBaseShippingFee($shippingFeeBaseTotal)
            ->setMiraklShippingFee($shippingFeeTotal)
            ->setMiraklBaseShippingTaxAmount($shippingTaxBaseTotal)
            ->setMiraklShippingTaxAmount($shippingTaxTotal)
            ->setMiraklBaseCustomShippingTaxAmount($customShippingTaxBaseTotal)
            ->setMiraklCustomShippingTaxAmount($customShippingTaxTotal);

        return $this;
    }
}
