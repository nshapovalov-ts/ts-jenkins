<?php
/**
 * Retailplace_PdfCustomizer
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\PdfCustomizer\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Asset\Repository;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Stdlib\StringUtils;
use DateTime;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice as StripeInvoice;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Model\Order\Invoice as MagentoInvoice;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Invoice
 */
class Invoice extends Template
{
    /**
     * @type string
     */
    const DISPLAY_TEXT_FOOTER = 'sales_pdf/invoice/footer';

    /**
     * @type string
     */
    const EXCLUDE_DISCOUNT_RULE_IDS = 'sales_pdf/invoice/exclude_discount_rule_ids';

    /**
     * @var Repository
     */
    private $assetRepository;

    /**
     * @var Renderer
     */
    private $addressRenderer;

    /**
     * @var MagentoOrder
     */
    private $order;

    /**
     * @var StringUtils
     */
    private $string;

    /**
     * @var MagentoInvoice
     */
    private $invoice;

    /**
     * @var DataObject
     */
    private $totals;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var PricingHelper
     */
    private $priceHelper;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Template\Context $context
     * @param Repository $assetRepository
     * @param Renderer $addressRenderer
     * @param StringUtils $string
     * @param ObjectFactory $objectFactory
     * @param PricingHelper $priceHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Template\Context $context,
        Repository $assetRepository,
        Renderer $addressRenderer,
        StringUtils $string,
        ObjectFactory $objectFactory,
        PricingHelper $priceHelper,
        CustomerRepositoryInterface $customerRepository,
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->assetRepository = $assetRepository;
        $this->addressRenderer = $addressRenderer;
        $this->string = $string;
        $this->objectFactory = $objectFactory;
        $this->priceHelper = $priceHelper;
        $this->customerRepository = $customerRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get Image Content
     *
     * @param string $path
     * @return string
     * @throws LocalizedException
     */
    public function getImageContent(string $path): string
    {
        $asset = $this->assetRepository->createAsset('Retailplace_PdfCustomizer::' . $path);
        $img = $asset->getContent();
        return $this->getDataImageBase64($img);
    }

    /**
     * Get Svg Content
     *
     * @param string $path
     * @return string
     * @throws LocalizedException
     */
    public function getSvgContent(string $path): string
    {
        $asset = $this->assetRepository->createAsset('Retailplace_PdfCustomizer::' . $path);
        $img = $asset->getContent();
        return $this->getSvgImageBase64($img);
    }

    /**
     * Get Font Url
     *
     * @param string $path
     * @return string
     */
    public function getFontUrl(string $path): string
    {
        return $this->getViewFileUrl($path, [
            '_current'     => true,
            '_use_rewrite' => true,
            'area'         => 'frontend',
            'theme'        => 'Sm/market_child'
        ]);
    }

    /**
     * Get Data Image Base64
     *
     * @param string $img
     * @return string
     */
    private function getDataImageBase64(string $img): string
    {
        return "data:image;base64," . base64_encode($img);
    }

    /**
     * Get Svg Image Base64
     *
     * @param string $img
     * @return string
     */
    private function getSvgImageBase64(string $img): string
    {
        return "data:image/svg+xml;base64," . base64_encode($img);
    }

    /**
     * Format address
     *
     * @param string $address
     * @return array
     */
    protected function formatAddress(string $address): array
    {
        $return = [];
        foreach (explode('|', $address) as $str) {
            foreach ($this->string->split($str, 45, true, true) as $part) {
                if (empty($part)) {
                    continue;
                }
                $return[] = $part;
            }
        }
        return $return;
    }

    /**
     * Get Invoice Info
     *
     * @throws Exception
     */
    public function getInvoiceInfo(): array
    {
        $invoiceInfo = [];
        $invoiceInfo['id'] = $this->getInvoice()->getIncrementId();

        $date = $this->order->getPaymentDate() ?? $this->order->getCreatedAt();
        $invoiceInfo['date_due'] = (new DateTime($date))->format("M j, Y");
        $invoiceInfo['full_date_due'] = (new DateTime($date))->format("F j, Y");

        $invoiceDate = $this->invoice->getCreatedAt();
        $invoiceInfo['date_invoice'] = (new DateTime($invoiceDate))->format("M j, Y");

        return $invoiceInfo;
    }

    /**
     * Get Customer Name
     *
     * @return string
     */
    public function getCustomerName(): string
    {
        return $this->order->getCustomerName();
    }

    /**
     * Set Order
     *
     * @param MagentoOrder $order
     * @return $this
     */
    public function setOrder(MagentoOrder $order): Invoice
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Get Order
     *
     * @return null|MagentoOrder
     */
    public function getOrder(): ?MagentoOrder
    {
        return $this->order;
    }

    /**
     * Set Invoice
     *
     * @param MagentoInvoice $invoice
     * @return $this
     */
    public function setInvoice(MagentoInvoice $invoice): Invoice
    {
        $this->invoice = $invoice;
        return $this;
    }

    /**
     * Get Invoice
     *
     * @return null|MagentoInvoice
     */
    public function getInvoice(): ?MagentoInvoice
    {
        return $this->invoice;
    }

    /**
     * Get Sellers
     *
     * @return array
     */
    public function getSellers(): array
    {
        $sellers = [];

        $collection = $this->orderCollectionFactory->create();
        $connection = $collection->getConnection();

        $collection->addFieldToFilter('main_table.entity_id', ['eq' => $this->order->getId()]);
        $select = $collection->getSelect();
        $select->reset('columns');

        $select->joinInner(
            ['oi' => $connection->getTableName('sales_order_item')],
            'oi.order_id = main_table.entity_id AND oi.parent_item_id is null',
            [
                'shop_id'          => 'oi.mirakl_shop_id',
                'shop_name'        => 'oi.mirakl_shop_name',
                'name'             => 'oi.name',
                'sku'              => 'oi.sku',
                'price'            => 'oi.price_incl_tax',
                'tax'              => 'oi.tax_amount',
                'qty'              => 'ROUND(oi.qty_ordered)',
                'amount'           => 'oi.row_total_incl_tax',
                'shipping'         => 'oi.mirakl_shipping_fee',
                'shipping_tax'     => 'oi.mirakl_shipping_tax_amount',
                'discount'         => 'oi.discount_amount',
                'applied_rule_ids' => 'oi.applied_rule_ids'
            ]
        );

        $groupedCollection = [];

        foreach ($collection as $item) {
            $groupedCollection[$item->getShopId()][$item->getSku()] = $item;
        }

        foreach ($groupedCollection as $miraklShopId => $items) {
            $sellerItems = [];
            $subtotal = 0;
            $shipping = 0;
            $shippingTax = 0;
            $tax = 0;
            $discount = 0;

            foreach ($items as $item) {
                if (empty($sellers[$miraklShopId]['shop_name'])) {
                    $sellers[$miraklShopId]['shop_name'] = $item->getShopName();
                }
                $sellerItem = [];
                $sellerItem['name'] = $this->escapeHtml($item->getName());
                $sellerItem['sku'] = $this->escapeHtml($item->getSku());
                $sellerItem['price'] = $this->formatPrice($item->getPrice());
                $sellerItem['tax'] = $this->formatPrice($item->getTax());
                $sellerItem['qty'] = $this->escapeHtml($item->getQty());
                $sellerItem['item_total_amount'] = $this->formatPrice($item->getAmount());
                $sellerItem['discount'] = $this->getItemDiscount($item);

                $sellerItems[] = $sellerItem;

                $subtotal += $item->getAmount();
                $shipping += $item->getShipping();
                $tax += $item->getTax();
                $shippingTax += $item->getShippingTax();
                $discount += $item->getDiscount();
            }

            $sellers[$miraklShopId]['items'] = $sellerItems;
            $sellers[$miraklShopId]['subtotal'] = $this->formatPrice($subtotal);
            $sellers[$miraklShopId]['shipping'] = $this->formatPrice($shipping);
            $sellers[$miraklShopId]['shipping_tax'] = $this->formatPrice($shippingTax);
            $sellers[$miraklShopId]['tax'] = $this->formatPrice($tax + $shippingTax);
            $sellers[$miraklShopId]['discount'] = $this->formatPrice($discount, ($discount > 0 ? '-' : ''));
        }

        return $sellers;
    }

    /**
     * Get Item Discount
     *
     * @param $item
     * @return float|string
     */
    private function getItemDiscount($item)
    {
        $discount = $item->getDiscount();

        //check Approved AU post Seller
        if ($discount > 0) {
            $excludeDiscountRuleIds = $this->scopeConfig->getValue(self::EXCLUDE_DISCOUNT_RULE_IDS);
            $excludeDiscountRuleIds = explode(',', trim($excludeDiscountRuleIds));

            $matchCount = 0;
            $appliedRuleIds = $item->getAppliedRuleIds();
            if (!empty($appliedRuleIds)) {
                $ruleIds = explode(',', $appliedRuleIds);
                $ruleIds = array_unique($ruleIds);

                foreach ($ruleIds as $ruleId) {
                    if (in_array($ruleId, $excludeDiscountRuleIds)) {
                        $matchCount++;
                    }
                }

                if ($matchCount > 0 && $matchCount == count($ruleIds)) {
                    $discount = 0;
                }
            }
        }

        return $this->formatPrice($discount, $discount > 0 ? '-' : '');
    }

    /**
     * Get Refunds
     *
     * @return null|DataObject
     * @throws Exception
     */
    public function getRefunds(): ?DataObject
    {
        $creditmemoCollections = $this->getOrder()->getCreditmemosCollection();
        $refunds = [];
        $subtotalRefunded = 0;
        $shippingRefunded = 0;
        $totalRefunded = 0;
        $taxRefunded = 0;
        $shippingTaxRefunded = 0;

        foreach ($creditmemoCollections as $creditmemo) {
            //calculate totals
            $subtotalRefunded += $creditmemo->getSubtotalInclTax();
            $shippingRefunded += $creditmemo->getShippingInclTax();
            $taxRefunded += $creditmemo->getTaxAmount();
            $shippingTaxRefunded += $creditmemo->getShippingTaxAmount();
            $totalRefunded += $creditmemo->getGrandTotal();

            //get all items
            $date = $creditmemo->getCreatedAt();
            foreach ($creditmemo->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                $refunds[] = [
                    'product_id' => $item->getProductId(),
                    'name'       => $item->getName(),
                    'qty'        => $item->getQty(),
                    'price'      => $item->getRowTotalInclTax(),
                    'tax'        => $item->getTaxAmount(),
                    'date'       => $date,
                    'amount'     => $item->getRowTotalInclTax()
                ];
            }
        }

        if (empty($refunds)) {
            return null;
        }

        //totals
        return $this->objectFactory->create()->setData([
            'items'    => $this->getFormattedRefundItems($refunds),
            'subtotal' => $this->formatPrice($subtotalRefunded, '-'),
            'shipping' => $this->formatPrice($shippingRefunded, '-'),
            'tax'      => $this->formatPrice($taxRefunded, ($taxRefunded > 0 ? '-' : '')),
            'total'    => $this->formatPrice($totalRefunded, '-')
        ]);
    }

    /**
     * Get Formatted Refund Items
     *
     * @param array $refunds
     * @return array
     * @throws Exception
     */
    private function getFormattedRefundItems(array $refunds): array
    {
        $refundItems = [];

        foreach ($refunds as $refund) {
            $refundItems[] = $this->objectFactory->create()->setData([
                'product_id' => $refund['product_id'],
                'name'       => $refund['name'],
                'date'       => (new DateTime($refund['date']))->format("M j, Y"),
                'price'      => $this->formatPrice($refund['price']),
                'qty'        => round($refund['qty']),
                'tax'        => $this->formatPrice($refund['tax']),
                'amount'     => $this->formatPrice($refund['amount'])
            ]);
        }

        return $refundItems;
    }

    /**
     * Get Refunds Html
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function getRefundsHtml(): string
    {
        return $this->getLayout()->createBlock(Template::class)
            ->setTemplate('Retailplace_PdfCustomizer::refunds.phtml')
            ->setData('cache_lifetime', false)
            ->setData('refunds', $this->getRefunds())
            ->toHtml();
    }

    /**
     * Get Totals
     *
     * @return DataObject
     */
    public function getTotals(): DataObject
    {
        $shippingTax = $this->order->getShippingInclTax() - $this->order->getShippingAmount();

        $this->totals = $this->objectFactory->create()->setData([
            'subtotal'             => $this->formatPrice($this->order->getSubtotalInclTax()),
            'shipping'             => $this->formatPrice($this->order->getShippingInclTax()),
            'shipping_tax'         => $this->formatPrice(($shippingTax > 0) ? $shippingTax : 0),
            'tax'                  => $this->formatPrice($this->order->getTaxAmount()),
            'discount'             => $this->formatPrice($this->order->getDiscountAmount()),
            'discount_description' => $this->order->getDiscountDescription(),
            'total'                => $this->formatPrice($this->order->getGrandtotal()),
            'total_due'            => $this->getTotalDue(),
            'total_paid'           => $this->formatPrice(
                $this->order->getGrandTotal() - $this->order->getTotalRefunded()
            )
        ]);

        return $this->totals;
    }

    /**
     * Get Total Due
     *
     * @return float|string
     */
    public function getTotalDue()
    {
        $totalDue = 0;
        if (!$this->isPaid()) {
            $totalDue = $this->order->getGrandTotal() - $this->order->getTotalRefunded();
        }

        return $this->formatPrice($totalDue);
    }

    /**
     * Get Customer
     *
     * @return CustomerInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomer(): ?CustomerInterface
    {
        if ($this->customer === null && !empty($this->order)) {
            $customerId = $this->order->getCustomerId();
            $this->customer = $this->customerRepository->getById($customerId);
        }
        return $this->customer;
    }

    /**
     * Get Billing Address
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBillingAddress(): array
    {
        $address = $this->formatAddress($this->addressRenderer->format($this->order->getBillingAddress(), 'pdf'));
        $customer = $this->getCustomer();
        if (!empty($customer)) {
            $abn = $customer->getCustomAttribute('abn');
            if (!empty($abn)) {
                $address[] = "ABN " . str_replace(" ", "", $abn->getValue());
            }
        }

        return $address;
    }

    /**
     * Get Shipping Address
     *
     * @return array
     */
    public function getShippingAddress(): array
    {
        $shippingAddress = [];

        /* Shipping Address*/
        if (!$this->order->getIsVirtual()) {
            $shippingAddress = $this->formatAddress(
                $this->addressRenderer->format($this->order->getShippingAddress(), 'pdf')
            );
        }

        return $shippingAddress;
    }

    /**
     * Is Paid
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        $invoice = $this->getInvoice();

        $stripeInvoiceId = $invoice->getStripeInvoiceId();
        //if is stripe payment
        if (!empty($stripeInvoiceId)) {
            $stripeInvoicePaid = $invoice->getStripeInvoicePaid();
            if ($stripeInvoicePaid != StripeInvoice::STRIPE_INVOICE_PAID) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format Price
     *
     * @param float $price
     * @param string $prefix
     * @return float|string
     */
    public function formatPrice($price, string $prefix = "")
    {
        $formatPrice = $this->priceHelper->currency($price, true, false);

        if (!empty($prefix)) {
            return $prefix . $formatPrice;
        }

        return $formatPrice;
    }

    public function getFooterText()
    {
        return $this->scopeConfig->getValue(self::DISPLAY_TEXT_FOOTER);
    }
}
