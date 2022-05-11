<?php
namespace Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

use Magento\Rule\Model\Condition\AbstractCondition;
use Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

class NewConditionHtml extends Zone
{
    /**
     * @return  void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = $this->_objectManager->create($type)
            ->setId($id)
            ->setType($type)
            ->setRule($this->_objectManager->create('Magento\CatalogRule\Model\Rule'))
            ->setPrefix('conditions');

        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }

        $this->getResponse()->setBody($html);
    }
}
