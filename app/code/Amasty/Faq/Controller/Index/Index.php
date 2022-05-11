<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Controller\Index;

use Amasty\Faq\Model\ConfigProvider;
use Amasty\Faq\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if ($this->configProvider->isUseFaqCmsHomePage()) {
            $this->_forward(
                'view',
                'page',
                'cms',
                [
                    'page_id' => $this->configProvider->getFaqCmsHomePage()
                ]
            );

            return;
        }

        $categories = $this->collectionFactory->create();
        $urlKey = $categories->getFirstCategoryUrl();
        if ($urlKey != $this->configProvider->getUrlKey()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            return $result->setPath($urlKey);
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
    }
}
