<?php
namespace Mirakl\Connector\Plugin\Model\Quote\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Store\Model\Store;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsItemInterface;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Tax\Calculator as TaxCalculator;

class CommonTaxCollectorPlugin
{
    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var TaxCalculator
     */
    protected $taxCalculator;

    /**
     * @param ConnectorConfig $connectorConfig
     * @param QuoteHelper     $quoteHelper
     * @param TaxCalculator   $taxCalculator
     */
    public function __construct(
        ConnectorConfig $connectorConfig,
        QuoteHelper $quoteHelper,
        TaxCalculator $taxCalculator
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->quoteHelper     = $quoteHelper;
        $this->taxCalculator   = $taxCalculator;
    }

    /**
     * @param   CommonTaxCollector          $subject
     * @param   \Closure                    $proceed
     * @param   Quote                       $quote
     * @param   ShippingAssignmentInterface $shippingAssignment
     * @param   Total                       $total
     * @return  CommonTaxCollector
     */
    public function aroundCollect(
        CommonTaxCollector $subject,
        \Closure $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $proceed($quote, $shippingAssignment, $total);

        /** @var Address $shippingAddress */
        $shippingAddress = $shippingAssignment->getShipping()->getAddress();
        if ($shippingAddress->getAddressType() == Address::ADDRESS_TYPE_BILLING) {
            return $subject;
        }

        if (!$this->quoteHelper->isMiraklQuote($quote)) {
            return $subject;
        }

        $totalShippingAmount         = $total->getShippingAmount();
        $totalBaseShippingAmount     = $total->getBaseShippingAmount();
        $totalShippingInclTax        = $total->getShippingInclTax();
        $totalBaseShippingInclTax    = $total->getBaseShippingInclTax();
        $miraklShippingFee           = $quote->getMiraklShippingFee();
        $miraklBaseShippingFee       = $quote->getMiraklBaseShippingFee();
        $miraklShippingTaxAmount     = $quote->getMiraklShippingTaxAmount();
        $miraklBaseShippingTaxAmount = $quote->getMiraklBaseShippingTaxAmount();

        if ($quote->getMiraklIsShippingInclTax()) {
            // Shipping INCLUDING tax
            $total->setShippingAmount($totalShippingAmount + $miraklShippingFee - $miraklShippingTaxAmount);
            $total->setBaseShippingAmount($totalBaseShippingAmount + $miraklBaseShippingFee - $miraklBaseShippingTaxAmount);
            $total->setShippingInclTax($totalShippingInclTax + $miraklShippingFee);
            $total->setBaseShippingInclTax($totalBaseShippingInclTax + $miraklBaseShippingFee);
            $total->addTotalAmount('shipping', $miraklShippingFee - $miraklShippingTaxAmount);
            $total->addBaseTotalAmount('shipping', $miraklBaseShippingFee - $miraklBaseShippingTaxAmount);
        } else {
            // Shipping EXCLUDING tax
            $total->setShippingAmount($totalShippingAmount + $miraklShippingFee);
            $total->setBaseShippingAmount($totalBaseShippingAmount + $miraklBaseShippingFee);
            $total->setShippingInclTax($totalShippingInclTax + $miraklShippingFee + $miraklShippingTaxAmount);
            $total->setBaseShippingInclTax($totalBaseShippingInclTax + $miraklBaseShippingFee + $miraklBaseShippingTaxAmount);
            $total->addTotalAmount('shipping', $miraklShippingFee);
            $total->addBaseTotalAmount('shipping', $miraklBaseShippingFee);
        }

        $total->addTotalAmount('tax', $miraklShippingTaxAmount);
        $total->addBaseTotalAmount('tax', $miraklBaseShippingTaxAmount);

        return $subject;
    }

    /**
     * @param   CommonTaxCollector                  $subject
     * @param   \Closure                            $proceed
     * @param   QuoteDetailsItemInterfaceFactory    $itemDataObjectFactory
     * @param   Quote\Item\AbstractItem             $item
     * @param   bool                                $priceIncludesTax
     * @param   bool                                $useBaseCurrency
     * @param   string                              $parentCode
     * @return  mixed
     */
    public function aroundMapItem(
        CommonTaxCollector $subject,
        \Closure $proceed,
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        Quote\Item\AbstractItem $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {
        $product = $item->getProduct();
        if ($product && $product->getCustomOption('mirakl_offer')) {
            $priceIncludesTax = $this->connectorConfig->getOffersIncludeTax($item->getStore());
        }

        return $proceed($itemDataObjectFactory, $item, $priceIncludesTax, $useBaseCurrency, $parentCode);
    }

    /**
     * @param   CommonTaxCollector      $subject
     * @param   \Closure                $proceed
     * @param   Quote\Item\AbstractItem $quoteItem
     * @param   TaxDetailsItemInterface $itemTaxDetails
     * @param   TaxDetailsItemInterface $baseItemTaxDetails
     * @param   Store                   $store
     * @return  CommonTaxCollector
     */
    public function aroundUpdateItemTaxInfo(
        CommonTaxCollector $subject,
        \Closure $proceed,
        $quoteItem,
        $itemTaxDetails,
        $baseItemTaxDetails,
        $store
    ) {
        if ($miraklTaxesApplied = $quoteItem->getMiraklCustomTaxApplied()) {
            $miraklTaxes = unserialize($miraklTaxesApplied);
            if (!empty($miraklTaxes['taxes'])) {
                $this->taxCalculator->addMiraklTaxesToTaxItems($itemTaxDetails, $baseItemTaxDetails, $miraklTaxes['taxes']);
            }
        }

        return $proceed($quoteItem, $itemTaxDetails, $baseItemTaxDetails, $store);
    }
}
