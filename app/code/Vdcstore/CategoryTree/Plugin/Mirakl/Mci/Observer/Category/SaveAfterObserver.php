<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\CategoryTree\Plugin\Mirakl\Mci\Observer\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveAfterObserver
{
    /**
     * @var \Mirakl\Mci\Helper\Config
     */
	 
    private $miraklConfigHelper;

    /**
     * SaveAfterObserver constructor.
     * @param \Vdcstore\CategoryTree\Model\Category $categoryModel
     */
    public function __construct(
        \Mirakl\Mci\Helper\Config $miraklConfigHelper
    ) {
        $this->miraklConfigHelper = $miraklConfigHelper;
    }
	
    public function aroundExecute(
        \Mirakl\Mci\Observer\Category\SaveAfterObserver $subject,
        \Closure $proceed,
        $observer
    ) {
        $category = $observer->getEvent()->getCategory();
        if (strpos($category->getPath(),"1/{$this->miraklConfigHelper->getHierarchyRootCategoryId()}/") !== false) {
            //Your plugin code
            $result = $proceed($observer);
            return $result;
        }
        return $this;
    }
}
