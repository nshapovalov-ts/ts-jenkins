<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Model\Rule\Condition;

use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use Mirakl\Core\Model\ResourceModel\Shop as ShopResourceModel;
use Mirakl\Core\Model\ShopFactory as ShopFactory;

/**
 * Class AuPost
 */
class AuPost extends AbstractCondition
{
    /** @var string */
    public const IS_AU_POST_PRODUCT = 'is_au_post_product';

    /** @var \Magento\Config\Model\Config\Source\Yesno */
    private $sourceYesno;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop */
    private $shopResourceModel;

    /** @var \Mirakl\Core\Model\ShopFactory */
    private $shopFactory;

    /**
     * AuPost constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesno
     * @param \Mirakl\Core\Model\ResourceModel\Shop $shopResourceModel
     * @param \Mirakl\Core\Model\ShopFactory $shopFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Yesno $sourceYesno,
        ShopResourceModel $shopResourceModel,
        ShopFactory $shopFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sourceYesno = $sourceYesno;
        $this->shopResourceModel = $shopResourceModel;
        $this->shopFactory = $shopFactory;
    }

    /**
     * Validate Condition
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Quote\Model\Quote\Item $model
     * @return bool
     */
    public function validate(AbstractModel $model)
    {
        $model->setData(self::IS_AU_POST_PRODUCT, $this->checkShopIsAuPost((int) $model->getMiraklShopId()));

        return parent::validate($model);
    }

    /**
     * Load attribute options
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption([
            self::IS_AU_POST_PRODUCT => __('Is AU Post Product')
        ]);

        return $this;
    }

    /**
     * Get value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Get input type
     *
     * @return string
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * Get value select options
     *
     * @return array|mixed
     */
    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            $this->setData(
                'value_select_options',
                $this->sourceYesno->toOptionArray()
            );
        }

        return $this->getData('value_select_options');
    }

    /**
     * Check if Shop is AU Post Seller
     *
     * @param int|null $shopId
     * @return bool
     */
    public function checkShopIsAuPost(?int $shopId): bool
    {
        $result = false;
        if ($shopId) {
            $shop = $this->shopFactory->create();
            $this->shopResourceModel->load($shop, $shopId);
            $result = (bool) $shop->getAuPostSeller();
        }

        return $result;
    }
}
