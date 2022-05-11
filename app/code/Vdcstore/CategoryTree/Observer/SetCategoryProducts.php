<?php

/**
 * Vdcstore_CategoryTree
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);
namespace Vdcstore\CategoryTree\Observer;

use Exception;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SetCategoryProducts
 */
class SetCategoryProducts implements ObserverInterface
{
    /**
     * @var \Vdcstore\CategoryTree\Model\Category
     */
    private $categoryModel;
    /**
     * @var \Mirakl\Mci\Helper\Config
     */
    private $miraklConfigHelper;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * SetCategoryProducts constructor.
     * @param \Vdcstore\CategoryTree\Model\Category $categoryModel
     * @param \Mirakl\Mci\Helper\Config $miraklConfigHelper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Vdcstore\CategoryTree\Model\Category $categoryModel,
        \Mirakl\Mci\Helper\Config $miraklConfigHelper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->categoryModel = $categoryModel;
        $this->miraklConfigHelper = $miraklConfigHelper;
        $this->request = $request;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     * @throws Exception
     */
    public function execute(EventObserver $observer): SetCategoryProducts
    {
        $category = $observer->getCategory();
        if (!$this->request->getParam('child_categories')) {
            $category->setData('child_categories', "");
        }
        if (!$this->request->getParam('mapped_category')) {
            $category->setData('mapped_category', "");
        }
        return $this;
    }
}
