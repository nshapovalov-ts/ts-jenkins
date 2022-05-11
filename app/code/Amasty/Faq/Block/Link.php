<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Block;

class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \Amasty\Faq\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\Faq\Model\ResourceModel\Category\Collection
     */
    private $collection;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Amasty\Faq\Model\ConfigProvider $configProvider,
        \Amasty\Faq\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->configProvider = $configProvider;
        $this->collection = $collectionFactory->create();
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if (!$this->configProvider->isEnabled()) {
            return '';
        }

        return parent::toHtml();
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if (!$this->hasData('path')) {
            if ($this->configProvider->isUseFaqCmsHomePage()) {
                $this->setData('path', $this->configProvider->getUrlKey());
            } else {
                $this->setData('path', $this->collection->getFirstCategoryUrl());
            }
        }

        return $this->getData('path');
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->configProvider->getLabel();
    }
}
