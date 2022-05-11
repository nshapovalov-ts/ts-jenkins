<?php
namespace Mirakl\Core\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @param   QuoteSetupFactory   $quoteSetupFactory
     * @param   SalesSetupFactory   $salesSetupFactory
     */
    public function __construct(
        QuoteSetupFactory $quoteSetupFactory,
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addQuotePricesExclTaxFields($setup);
            $this->addSalesPricesExclTaxFields($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            // Add Mirakl custom taxes fields for Mirakl Tax Calulator feature
            $this->addMiraklCustomTaxesFields($setup);
        }

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            // Add column 'mirakl_is_shipping_incl_tax' on quote and order entities
            $attributes = ['quote' => ['mirakl_is_shipping_incl_tax' => ['type' => Table::TYPE_SMALLINT, 'default' => 1]]];
            $this->addQuoteAttributes($setup, $attributes);

            $attributes = ['order' => ['mirakl_is_shipping_incl_tax' => ['type' => Table::TYPE_SMALLINT, 'default' => 1]]];
            $this->addOrderAttributes($setup, $attributes);
        }

        $setup->endSetup();
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  $this
     */
    private function addMiraklCustomTaxesFields(ModuleDataSetupInterface $setup)
    {
        $attributes = [
            'quote' => [
                'mirakl_base_custom_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_custom_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
            ],
            'quote_item' => [
                'mirakl_custom_tax_applied'              => ['type' => Table::TYPE_TEXT],
                'mirakl_base_custom_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_custom_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
            ],
        ];

        $this->addQuoteAttributes($setup, $attributes);

        $attributes = [
            'order' => [
                'mirakl_base_custom_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_custom_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
            ],
            'order_item' => [
                'mirakl_custom_tax_applied'              => ['type' => Table::TYPE_TEXT],
                'mirakl_base_custom_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_custom_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
            ],
        ];

        $this->addOrderAttributes($setup, $attributes);

        return $this;
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @param   array                       $attributes
     * @return  $this
     */
    private function addQuoteAttributes(ModuleDataSetupInterface $setup, array $attributes)
    {
        $quoteSetup = $this->getQuoteSetup($setup);

        foreach ($attributes as $entityTypeId => $attrParams) {
            foreach ($attrParams as $code => $params) {
                $params['visible'] = false;
                $quoteSetup->addAttribute($entityTypeId, $code, $params);
            }
        }

        return $this;
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @param   array                       $attributes
     * @return  $this
     */
    private function addOrderAttributes(ModuleDataSetupInterface $setup, array $attributes)
    {
        $salesSetup = $this->getSalesSetup($setup);

        foreach ($attributes as $entityTypeId => $attrParams) {
            foreach ($attrParams as $code => $params) {
                $params['visible'] = false;
                $salesSetup->addAttribute($entityTypeId, $code, $params);
            }
        }

        return $this;
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  $this
     */
    private function addQuotePricesExclTaxFields(ModuleDataSetupInterface $setup)
    {
        $attributes = [
            'quote' => [
                'mirakl_is_offer_incl_tax'        => ['type' => Table::TYPE_SMALLINT, 'default' => 1],
                'mirakl_base_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
            ],
            'quote_item' => [
                'mirakl_shipping_tax_percent'     => ['type' => Table::TYPE_DECIMAL],
                'mirakl_base_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_tax_applied'     => ['type' => Table::TYPE_TEXT],
            ],
        ];

        return $this->addQuoteAttributes($setup, $attributes);
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  $this
     */
    private function addSalesPricesExclTaxFields(ModuleDataSetupInterface $setup)
    {
        $attributes = [
            'order' => [
                'mirakl_is_offer_incl_tax'        => ['type' => Table::TYPE_SMALLINT, 'default' => 1],
                'mirakl_base_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
            ],
            'order_item' => [
                'mirakl_shipping_tax_percent'     => ['type' => Table::TYPE_DECIMAL],
                'mirakl_base_shipping_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_tax_amount'      => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_tax_applied'     => ['type' => Table::TYPE_TEXT],
            ],
        ];

        return $this->addOrderAttributes($setup, $attributes);
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  \Magento\Quote\Setup\QuoteSetup
     */
    private function getQuoteSetup(ModuleDataSetupInterface $setup)
    {
        return $this->quoteSetupFactory->create(['setup' => $setup]);
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  \Magento\Sales\Setup\SalesSetup
     */
    private function getSalesSetup(ModuleDataSetupInterface $setup)
    {
        return $this->salesSetupFactory->create(['setup' => $setup]);
    }
}
