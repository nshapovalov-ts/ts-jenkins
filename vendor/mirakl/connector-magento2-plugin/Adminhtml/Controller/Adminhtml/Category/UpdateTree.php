<?php
namespace Mirakl\Adminhtml\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Core\Controller\Adminhtml\RedirectRefererTrait;
use Psr\Log\LoggerInterface;

class UpdateTree extends Action
{
    use RedirectRefererTrait;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryResourceFactory
     */
    protected $categoryResourceFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var ApiConfig
     */
    protected $apiConfigHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   Action\Context              $context
     * @param   CategoryFactory             $categoryFactory
     * @param   CategoryResourceFactory     $categoryResourceFactory
     * @param   CategoryCollectionFactory   $categoryCollectionFactory
     * @param   ApiConfig                   $apiConfigHelper
     * @param   LoggerInterface             $logger
     */
    public function __construct(
        Action\Context $context,
        CategoryFactory $categoryFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        ApiConfig $apiConfigHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->categoryFactory           = $categoryFactory;
        $this->categoryResourceFactory   = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->apiConfigHelper           = $apiConfigHelper;
        $this->logger                    = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $action = $this->getRequest()->getParam('action', false);

        if (!$categoryId || !in_array($action, ['enable', 'disable'])) {
            return $this->redirectReferer();
        }

        $action = $action == 'enable' ? 1 : 0;

        try {
            $this->apiConfigHelper->disable();

            /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('*')
                ->addAttributeToSelect('mirakl_sync', 'left')
                ->addFieldToFilter([
                    ['attribute' => 'path', 'like' => "%/$categoryId/%"],
                    ['attribute' => 'path', 'like' => "%/$categoryId"],
                ])
                ->addAttributeToFilter([
                    ['attribute' => 'mirakl_sync', 'neq' => $action],
                    ['attribute' => 'mirakl_sync', 'null' => true],
                ]);

            /** @var \Magento\Catalog\Model\Category $category */
            foreach ($collection as $category) {
                $category->setMiraklSync($action);
                $this->categoryResourceFactory->create()->save($category);
            }

            $this->apiConfigHelper->enable();

            $this->_eventManager->dispatch('mirakl_adminhtml_update_category_tree_after', [
                'category_id' => $categoryId,
                'categories' => $collection,
                'action' => $action,
            ]);

            $this->messageManager->addSuccessMessage(__('Category tree has been updated successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e->getMessage());
        }

        return $this->redirectReferer();
    }
}