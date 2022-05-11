<?php
namespace Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

use Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

class Edit extends Zone
{
    /**
     * @return  void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Mirakl\Core\Model\Shipping\Zone $model */
        $model = $this->_objectManager->create(\Mirakl\Core\Model\Shipping\Zone::class);
        /** @var \Mirakl\Core\Model\ResourceModel\Shipping\Zone $resourceModel */
        $resourceModel = $this->_objectManager->create(\Mirakl\Core\Model\ResourceModel\Shipping\Zone::class);

        if ($id) {
            $resourceModel->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This shipping zone no longer exists.'));
                $this->_redirect('core/*');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $model->getRule()->getConditions()->setJsFormObject('rule_conditions_fieldset');

        $this->_coreRegistry->register('current_shipping_zone', $model);

        $title = $id
            ? __("Edit Shipping Zone '%1'", $model->getCode())
            : __('New Shipping Zone');

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shipping Zone'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_view->getLayout()
            ->getBlock('shipping_zone_edit')
            ->setData('action', $this->getUrl('mirakl/shipping_zone/save'));

        $this->_addBreadcrumb($title, $title);
        $this->_view->renderLayout();
    }
}
