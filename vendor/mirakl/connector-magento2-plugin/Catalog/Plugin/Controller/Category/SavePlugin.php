<?php
namespace Mirakl\Catalog\Plugin\Controller\Category;

use Magento\Catalog\Controller\Adminhtml\Category\Save;

class SavePlugin
{
    /**
     * @param   Save    $subject
     */
    public function beforeExecute(Save $subject)
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $subject->getRequest();
        if ($request->getParam('entity_id') && !$request->getParam('id')) {
            /**
             * Fix a Magento bug that do not provide 'id' parameter in request that makes category not being loaded in:
             * @see \Magento\Catalog\Controller\Adminhtml\Category::_initCategory
             * and thus getOrigData() was returning null in:
             * @see \Mirakl\Catalog\Observer\Category\SaveAfter::execute
             */
            $request->setParam('id', $request->getParam('entity_id'));
        }
    }
}