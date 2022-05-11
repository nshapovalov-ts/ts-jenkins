<?php
namespace Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

use Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

class Delete extends Zone
{
    /**
     * Delete shipping zone action
     *
     * @return  void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                /** @var \Mirakl\Core\Model\Shipping\Zone $model */
                $model = $this->_objectManager->create(\Mirakl\Core\Model\Shipping\Zone::class);
                /** @var \Mirakl\Core\Model\ResourceModel\Shipping\Zone $resource */
                $resource = $this->_objectManager->create(\Mirakl\Core\Model\ResourceModel\Shipping\Zone::class);
                $resource->load($model, $id);
                $resource->delete($model);
                $this->messageManager->addSuccessMessage(__('You deleted the shipping zone.'));
                $this->_redirect('mirakl/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('We cannot delete the shipping zone right now. Please review the log and try again.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('mirakl/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addErrorMessage(__('We cannot find a shipping zone to delete.'));
        $this->_redirect('mirakl/*/');
    }
}
