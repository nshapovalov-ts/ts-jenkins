<?php
namespace Mirakl\Connector\Controller\Adminhtml\Offer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

class View extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mirakl_Connector::offers';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id');

        /** @var \Mirakl\Connector\Model\Offer $model */
        $model = $this->_objectManager->create(\Mirakl\Connector\Model\Offer::class);
        /** @var \Mirakl\Connector\Model\ResourceModel\Offer $resource */
        $resource = $this->_objectManager->create(\Mirakl\Connector\Model\ResourceModel\Offer::class);
        $resource->load($model, $id);

        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('This offer no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('*/*/');
        }

        $this->coreRegistry->register('mirakl_offer', $model);

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Offer #%1', $model->getId()));
        $this->_view->renderLayout();
    }
}
