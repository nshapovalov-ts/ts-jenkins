<?php
namespace Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

use Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

class Save extends Zone
{
    /**
     * @return  void
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                /** @var \Mirakl\Core\Model\Shipping\Zone $model */
                $model = $this->_objectManager->create(\Mirakl\Core\Model\Shipping\Zone::class);
                /** @var \Mirakl\Core\Model\ResourceModel\Shipping\Zone $resource */
                $resource = $this->_objectManager->create(\Mirakl\Core\Model\ResourceModel\Shipping\Zone::class);
                $this->_eventManager->dispatch(
                    'adminhtml_controller_shipping_zone_prepare_save',
                    ['request' => $this->getRequest()]
                );
                $data = $this->getRequest()->getPostValue();

                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $resource->load($model, $id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong shipping zone is specified.'));
                    }
                }

                $session = $this->_objectManager->get('Magento\Backend\Model\Session');

                $validateResult = $model->getRule()->validateData(new \Magento\Framework\DataObject($data));
                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addErrorMessage($errorMessage);
                    }
                    $session->setPageData($data);
                    $this->_redirect('mirakl/*/edit', ['id' => $model->getId()]);
                    return;
                }

                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                if (isset($data['rule']['actions'])) {
                    $data['actions'] = $data['rule']['actions'];
                }
                unset($data['rule']);

                $model->addData($data);
                $model->setConditionsSerialized(serialize($data['conditions']));

                $session->setPageData($model->getData());

                $resource->save($model);

                $this->messageManager->addSuccessMessage(__('You saved the shipping zone.'));
                $session->setPageData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('mirakl/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('mirakl/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $id = (int) $this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('mirakl/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('mirakl/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while saving the shipping zone data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('mirakl/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('mirakl/*/');
    }
}
