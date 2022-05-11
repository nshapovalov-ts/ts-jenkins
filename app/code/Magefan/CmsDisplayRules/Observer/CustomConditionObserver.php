<?php

namespace Magefan\CmsDisplayRules\Observer;


class CustomConditionObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\CatalogRule\Model\Rule\Condition\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * CustomerConditionObserver constructor.
     * @param \Magento\CatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\CatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->request = $request;
        $this->_productFactory = $conditionFactory;
    }

    /**
     * Execute observer.
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->request->getFullActionName() == 'cms_block_edit' || $this->request->getFullActionName() == 'cms_page_edit') {
            $additional = $observer->getAdditional();
            $conditions = (array)$additional->getConditions();
            $productAttributes = $this->_productFactory->create()->loadAttributeOptions()->getAttributeOption();
            $attributes = [];
            foreach ($productAttributes as $code => $label) {
                $attributes[] = [
                    'value' => 'Magento\CatalogRule\Model\Rule\Condition\Product|' . $code,
                    'label' => $label,
                ];
            }


            $conditions = array_merge_recursive($conditions, [
                [
                    'value' => \Magento\CatalogRule\Model\Rule\Condition\Combine::class,
                    'label' => __('Conditions Combination'),
                ],
                ['label' => __('Product Attribute'), 'value' => $attributes]
            ]);

            $additional->setConditions($conditions);
            return $this;
        }
    }
}
