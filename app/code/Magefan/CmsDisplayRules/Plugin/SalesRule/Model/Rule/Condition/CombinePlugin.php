<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Plugin\SalesRule\Model\Rule\Condition;

use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\SalesRule\Model\RuleFactory as SaleRuleFactory;
use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\CatalogRule\Model\Rule\Condition\ProductFactory;
use Magento\Framework\App\RequestInterface;

/**
 * Class CombinePlugin
 */
class CombinePlugin
{
    /**
     * @var SaleRuleFactory
     */
    protected $saleRuleFactory;

    /**
     * @var Address
     */
    protected $ruleAddress;

    /**
     * @var ProductFactory
     */
    protected $ruleProduct;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * CombinePlugin constructor.
     * @param SaleRuleFactory $saleRuleFactory
     * @param Address $ruleAddress
     * @param ProductFactory $ruleProduct
     * @param RequestInterface $request
     */
    public function __construct(
        SaleRuleFactory $saleRuleFactory,
        Address $ruleAddress,
        ProductFactory $ruleProduct,
        RequestInterface $request
    ) {
        $this->saleRuleFactory = $saleRuleFactory;
        $this->ruleAddress = $ruleAddress;
        $this->ruleProduct = $ruleProduct;
        $this->request = $request;
    }

    /**
     * @param Combine $subject
     * @param $result
     * @return array
     */
    public function afterGetNewChildSelectOptions(Combine $subject, $result)
    {
        if ($this->request->getFullActionName() == 'cms_page_edit'
            || $this->request->getFullActionName() == 'cms_block_edit'
            || $this->request->getFullActionName() == 'sales_rule_promo_quote_newConditionHtml'
        ) {
            $addressAttributes = $this->ruleAddress->loadAttributeOptions()->getAttributeOption();
            $attributesAddress = [];
            foreach ($addressAttributes as $code => $label) {
                $attributesAddress[] = [
                    'value' => 'Magento\SalesRule\Model\Rule\Condition\Address|' . $code,
                    'label' => $label,
                ];
            }
            $productAttributes = $this->ruleProduct->create()->loadAttributeOptions()->getAttributeOption();
            $attributesProduct = [];
            foreach ($productAttributes as $code => $label) {
                $attributesProduct[] = [
                    'value' => 'Magento\CatalogRule\Model\Rule\Condition\Product|' . $code,
                    'label' => $label,
                ];
            }
            $conditions =[];
            $conditions = array_merge_recursive(
                $conditions,
                [
                    [
                        'value' => \Magento\SalesRule\Model\Rule\Condition\Product\Found::class,
                        'label' => __('Product attribute combination'),
                    ],
                    [
                        'value' => \Magento\SalesRule\Model\Rule\Condition\Product\Subselect::class,
                        'label' => __('Products subselection')
                    ],
                    [
                        'value' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
                        'label' => __('Conditions combination')
                    ],
                    ['label' => __('Cart Attribute'), 'value' => $attributesAddress],
                    ['label' => __('Product Attribute'), 'value' => $attributesProduct]
                ]
            );
            $result = $conditions;
        }
        return $result;
    }
}
