<?php
/**
 * Retailplace_CustomReports
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */
namespace Retailplace\CustomReports\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Exception\LocalizedException;
use Vdcstore\CategoryTree\Helper\Data;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

/**
 *  Class Categories
 */
class Categories extends Column
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;
    /**
     * @var Collection
     */
    protected $categoryCollection;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Categories constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ResourceConnection $resource
     * @param Collection $categoryCollection
     * @param Data $helper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ResourceConnection $resource,
        Collection $categoryCollection,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_resource = $resource;
        $this->categoryCollection = $categoryCollection;
        $this->helper = $helper;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     * @throws LocalizedException
     */
    public function prepareDataSource(array $dataSource): array
    {
        $connection = $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->categoryCollection
            ->addAttributeToSelect('name', 'left')
            ->addFieldToFilter('path', ['like' => "1/{$this->helper->getMenuRoot()}/%"]);
        $allCategories = $connection->fetchAssoc(clone ($this->categoryCollection)->getSelect());
        $categories = $connection->fetchAssoc(clone ($this->categoryCollection)->addFieldToFilter('level', ['eq' => 4])->getSelect());

        $allCatIds = $this->categoryCollection->getAllIds();
        if (isset($dataSource['data']['items'])) {
            $productIds = array_column($dataSource['data']['items'], 'product_id');
            if ($productIds) {
                $productIds = array_filter(array_unique($productIds));
                $categoryIdsSelect = $connection->select()
                    ->from(['ccp' => $this->_resource->getTableName('catalog_category_product')])
                    ->where('product_id in (?)', $productIds)
                    ->reset('columns')
                    ->columns(['product_id', 'categories' => new \Zend_Db_Expr('GROUP_CONCAT(`category_id`)')])
                    ->group('product_id');
                $categoryIds = $connection->fetchPairs($categoryIdsSelect);
                foreach ($dataSource['data']['items'] as & $item) {
                    $name = $this->getData('name');
                    if (isset($item['product_id'])) {
                        $productCategories = $categoryIds[$item['product_id']] ?? "";
                        if ($productCategories) {
                            $productCategories = array_intersect(explode(",", $productCategories), $allCatIds);
                            if (isset(array_values($productCategories)[0])) {
                                $catId = array_values($productCategories)[0];
                                $path = $categories[$catId]['path'] ?? "";
                                $path = str_replace("1/{$this->helper->getMenuRoot()}/", "", $path);
                                $paths = explode("/", $path);
                                $categoryNames = [];
                                foreach ($paths as $categoryId) {
                                    if (isset($allCategories[$categoryId]['name'])) {
                                        $categoryNames[] = $allCategories[$categoryId]['name'] ?? "";
                                    }
                                }
                                $item[$name] = implode("/", $categoryNames);
                            }
                        }
                    }
                }
            }
        }
        return $dataSource;
    }
}
