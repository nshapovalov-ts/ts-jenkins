<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Math\Random;

/**
 * Class Block model
 */
class Block extends \Magento\Catalog\Model\AbstractModel
{
    /**
     * @deprecated
     * @var Random
     */
    protected $random;

    /**
     * Block constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Random $random
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Random $random,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $storeManager,
            $resource,
            $resourceCollection,
            $data
        );
        $this->random = $random;
    }

    public function _construct()
    {
        $this->_init(\Magefan\CmsDisplayRules\Model\ResourceModel\Block::class);
    }

    /**
     * @return mixed
     */
    public function getConditions()
    {
        return $this->getData('conditions');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateSecret()
    {
        $this->getResource()->generateSecret($this);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSecret()
    {
        return $this->getData('secret');
    }
}
