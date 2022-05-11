<?php
namespace Mirakl\Core\Controller\Adminhtml\Document\Type;

use Mirakl\Core\Controller\Adminhtml\Document\Type;

class Edit extends Type
{
    /**
     * @return  void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Mirakl\Core\Model\Document\Type $model */
        $model = $this->_objectManager->create(\Mirakl\Core\Model\Document\Type::class);
        /** @var \Mirakl\Core\Model\ResourceModel\Document\Type $resource */
        $resource = $this->_objectManager->create(\Mirakl\Core\Model\ResourceModel\Document\Type::class);

        if ($id) {
            $resource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This document type no longer exists.'));
                $this->_redirect('core/*');
                return;
            }
        }

        // Set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $this->_coreRegistry->register('current_document_type', $model);

        $title = $id
            ? __("Edit Document Type '%1'", $model->getLabel())
            : __('New Document Type');

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Document Type'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_view->getLayout()
            ->getBlock('document_type_edit')
            ->setData('action', $this->getUrl('mirakl/document_type/save'));

        $this->_addBreadcrumb($title, $title);
        $this->_view->renderLayout();
    }
}
