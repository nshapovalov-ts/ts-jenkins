<?php
namespace Mirakl\Core\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;

class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @param   EavSetupFactory     $eavSetupFactory
     * @param   QuoteSetupFactory   $quoteSetupFactory
     * @param   SalesSetupFactory   $salesSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installProductAttributes($setup);
        $this->installQuoteAttributes($setup);
        $this->installSalesAttributes($setup);
    }

    /**
     * Create custom product attributes
     *
     * @param   ModuleDataSetupInterface    $setup
     * @return  $this
     */
    private function installProductAttributes(ModuleDataSetupInterface $setup)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /***
         * Create aattribute to stores offer's shop and  offer states 
         */
        $eavSetup->removeAttribute(Product::ENTITY, 'mirakl_shop_ids');
        $eavSetup->addAttribute(
            Product::ENTITY,
            'mirakl_shop_ids',
            [
                'group'                     => 'Mirakl Marketplace',
                'type'                      => 'varchar',
                'backend'                   => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'label'                     => 'Shops',
                'input'                     => 'multiselect',
                'visible'                   => true,
                'required'                  => false,
                'user_defined'              => true,
                'searchable'                => true,
                'filterable'                => true,
                'comparable'                => false,
                'visible_on_front'          => false,
                'used_in_product_listing'   => true,
                'unique'                    => false,
                'apply_to'                  => 'simple',
                'note'                      => 'Selected shops are associated with the product. This field is automatically filled.',
                'is_configurable'           => false,
            ]
        );

        $eavSetup->removeAttribute(Product::ENTITY, 'mirakl_offer_state_ids');
        $eavSetup->addAttribute(Product::ENTITY, 'mirakl_offer_state_ids', [
            'group'                     => 'Mirakl Marketplace',
            'type'                      => 'varchar',
            'backend'                   => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            'label'                     => 'Offer Conditions',
            'input'                     => 'multiselect',
            'visible'                   => true,
            'required'                  => false,
            'user_defined'              => true,
            'searchable'                => true,
            'filterable'                => true,
            'comparable'                => false,
            'visible_on_front'          => false,
            'used_in_product_listing'   => true,
            'unique'                    => false,
            'apply_to'                  => 'simple',
            'note'                      => 'Selected offer conditions are associated with the product. This field is automatically filled.',
            'is_configurable'           => false,
        ]);

        return $this;
    }

    /**
     * Create quote additional attributes
     *
     * @param   ModuleDataSetupInterface    $setup
     * @return  $this
     */
    private function installQuoteAttributes(ModuleDataSetupInterface $setup)
    {
        /** @var \Magento\Quote\Setup\QuoteSetup $quoteSetup */
        $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);

        $attributes = [
            'quote' => [
                'mirakl_shipping_zone'       => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_base_shipping_fee'   => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_fee'        => ['type' => Table::TYPE_DECIMAL],
            ],
            'quote_item' => [
                'mirakl_offer_id'            => ['type' => Table::TYPE_INTEGER],
                'mirakl_shop_id'             => ['type' => Table::TYPE_INTEGER],
                'mirakl_shop_name'           => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_leadtime_to_ship'    => ['type' => Table::TYPE_INTEGER],
                'mirakl_shipping_type'       => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_shipping_type_label' => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_base_shipping_fee'   => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_fee'        => ['type' => Table::TYPE_DECIMAL],
            ],
        ];

        foreach ($attributes as $entityTypeId => $attrParams) {
            foreach ($attrParams as $code => $params) {
                $params['visible'] = false;
                $quoteSetup->addAttribute($entityTypeId, $code, $params);
            }
        }

        return $this;
    }

    /**
     * Create sales additional attributes
     *
     * @param   ModuleDataSetupInterface    $setup
     * @return  $this
     */
    private function installSalesAttributes(ModuleDataSetupInterface $setup)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        $attributes = [
            'order' => [
                'mirakl_shipping_zone'       => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_base_shipping_fee'   => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_fee'        => ['type' => Table::TYPE_DECIMAL],
                'mirakl_sent'                => ['type' => Table::TYPE_BOOLEAN, 'default' => 0],
            ],
            'order_item' => [
                'mirakl_offer_id'            => ['type' => Table::TYPE_INTEGER],
                'mirakl_shop_id'             => ['type' => Table::TYPE_INTEGER],
                'mirakl_shop_name'           => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_leadtime_to_ship'    => ['type' => Table::TYPE_INTEGER],
                'mirakl_shipping_type'       => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_shipping_type_label' => ['type' => Table::TYPE_TEXT, 'size' => 255],
                'mirakl_base_shipping_fee'   => ['type' => Table::TYPE_DECIMAL],
                'mirakl_shipping_fee'        => ['type' => Table::TYPE_DECIMAL],
            ],
        ];

        foreach ($attributes as $entityTypeId => $attrParams) {
            foreach ($attrParams as $code => $params) {
                $params['visible'] = false;
                $salesSetup->addAttribute($entityTypeId, $code, $params);
            }
        }
        
        return $this;
    }
}
