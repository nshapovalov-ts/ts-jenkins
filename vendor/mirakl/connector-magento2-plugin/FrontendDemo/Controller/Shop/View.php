<?php
namespace Mirakl\FrontendDemo\Controller\Shop;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ShopFactory
     */
    protected $shopFactory;

    /**
     * @var ShopResourceFactory
     */
    protected $shopResourceFactory;

    /**
     * @param   Context             $context
     * @param   Registry            $registry
     * @param   ShopFactory         $shopFactory
     * @param   ShopResourceFactory $shopResourceFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ShopFactory $shopFactory,
        ShopResourceFactory $shopResourceFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry = $registry;
        $this->shopFactory = $shopFactory;
        $this->shopResourceFactory = $shopResourceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $shop = $this->shopFactory->create();
        $this->shopResourceFactory->create()->load($shop, $this->getRequest()->getParam('id'));
        if (!$shop->getId()) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Could not find shop.'));
        }

        $this->coreRegistry->register('mirakl_shop', $shop);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $resultPage;
    }
}
