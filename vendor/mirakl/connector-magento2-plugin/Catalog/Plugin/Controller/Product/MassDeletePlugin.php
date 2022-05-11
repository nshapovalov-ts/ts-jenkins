<?php
namespace Mirakl\Catalog\Plugin\Controller\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\Component\MassAction\Filter;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Catalog\Helper\Config as CatalogConfig;
use Mirakl\Catalog\Helper\Product as ProductHelper;

class MassDeletePlugin
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ApiConfig
     */
    protected $apiConfigHelper;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfigHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @param   RequestInterface            $request
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   ApiConfig                   $apiConfigHelper
     * @param   CatalogConfig               $catalogConfigHelper
     * @param   ProductHelper               $productHelper
     */
    public function __construct(
        RequestInterface $request,
        ProductCollectionFactory $productCollectionFactory,
        ApiConfig $apiConfigHelper,
        CatalogConfig $catalogConfigHelper,
        ProductHelper $productHelper
    ) {
        $this->request                  = $request;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->apiConfigHelper          = $apiConfigHelper;
        $this->catalogConfigHelper      = $catalogConfigHelper;
        $this->productHelper            = $productHelper;
    }

    /**
     * Handle mass product deletion in a plugin because we do not have any way to
     * retrieve products with mirakl_sync = true from the default dispatched event.
     *
     * @return  void
     */
    public function beforeExecute()
    {
        if ($this->apiConfigHelper->isEnabled() && $this->catalogConfigHelper->isSyncProducts()) {
            $productIds = $this->request->getParam(Filter::SELECTED_PARAM);
            if (is_array($productIds) && !empty($productIds)) {
                $collection = $this->productCollectionFactory->create();
                $collection->addIdFilter($productIds);
                $collection->addAttributeToFilter('mirakl_sync', 1);
                $this->productHelper->exportCollection($collection, 'delete');
            }
        }
    }
}