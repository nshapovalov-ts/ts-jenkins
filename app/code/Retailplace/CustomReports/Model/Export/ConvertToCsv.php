<?php
/**
 * Retailplace_CustomReports
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */
namespace Retailplace\CustomReports\Model\Export;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Zend_Db_Expr;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Url;
use Vdcstore\CategoryTree\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

/**
 * Class ConvertToCsv
 */
class ConvertToCsv
{
    /**
     * @var DirectoryList
     */
    protected $directory;

    /**
     * @var \Magento\Ui\Model\Export\MetadataProvider
     */
    protected $metadataProvider;

    /**
     * @var int|null
     */
    protected $pageSize = null;

    /**
     * @var Filter
     */
    protected $filter;
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
     * @var Url
     */
    private $urlBuilder;

    /**
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param \Magento\Ui\Model\Export\MetadataProvider $metadataProvider
     * @param ResourceConnection $resource
     * @param Collection $categoryCollection
     * @param Data $helper
     * @param Url $urlBuilder
     * @param int $pageSize
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        \Magento\Ui\Model\Export\MetadataProvider $metadataProvider,
        ResourceConnection $resource,
        Collection $categoryCollection,
        Data $helper,
        Url $urlBuilder,
        $pageSize = 200
    ) {
        $this->filter = $filter;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->metadataProvider = $metadataProvider;
        $this->pageSize = $pageSize;
        $this->_resource = $resource;
        $this->categoryCollection = $categoryCollection;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Returns CSV file
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCsvFile(): array
    {
        $component = $this->filter->getComponent();

        $name = md5(microtime());
        $file = 'export/' . $component->getName() . $name . '.csv';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();
        $fields = $this->metadataProvider->getFields($component);
        $options = $this->metadataProvider->getOptions();
        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $headers = $this->metadataProvider->getHeaders($component);
        if (in_array('product_frontend_url', $fields)) {
            $fields[] = 'product_frontend_url';
            $headers[] = 'Product Frontend Url';
        }

        $stream->writeCsv($headers);
        $i = 1;
        $searchCriteria = $dataProvider->getSearchCriteria()
            ->setCurrentPage($i)
            ->setPageSize($this->pageSize);
        $totalCount = (int) $dataProvider->getSearchResult()->getTotalCount();

        $connection = $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->categoryCollection
            ->addAttributeToSelect('name', 'left')
            ->addFieldToFilter('path', ['like' => "1/{$this->helper->getMenuRoot()}/%"]);
        $allCategories = $connection->fetchAssoc(clone ($this->categoryCollection)->getSelect());
        $categories = $connection->fetchAssoc(clone ($this->categoryCollection)->addFieldToFilter('level', ['eq' => 4])->getSelect());
        $allCatIds = $this->categoryCollection->getAllIds();
        $categoryIds = [];

        while ($totalCount > 0) {
            $items = $dataProvider->getSearchResult()->getItems();
            $allProductIds = array_column($dataProvider->getData()['items'], 'product_id');
            if ($allProductIds) {
                $allProductIds = array_filter(array_unique($allProductIds));
                $categoryIdsSelect = $connection->select()
                    ->from(['ccp' => $this->_resource->getTableName('catalog_category_product')])
                    ->where('product_id in (?)', $allProductIds)
                    ->reset('columns')
                    ->columns(['product_id', 'categories' => new Zend_Db_Expr('GROUP_CONCAT(`category_id`)')])
                    ->group('product_id');
                $categoryIds = $connection->fetchPairs($categoryIdsSelect);
            }
            foreach ($items as $item) {
                $productCategories = $categoryIds[$item->getCustomAttribute('product_id')->getValue()] ?? "";
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
                        $item->setCustomAttribute('categories', implode("/", $categoryNames));
                    }
                }
                $item->setCustomAttribute('product_frontend_url', $this->urlBuilder->getDirectUrl($item->getCustomAttribute('url_key')->getValue() . ".html"));

                $this->metadataProvider->convertDate($item, $component->getName());
                $stream->writeCsv($this->getRowData($item, $fields, $options));
            }
            $searchCriteria->setCurrentPage(++$i);
            $totalCount = $totalCount - $this->pageSize;
        }
        $stream->unlock();
        $stream->close();

        return [
            'type'  => 'filename',
            'value' => $file,
            'rm'    => true  // can delete file after use
        ];
    }

    /**
     * Returns row data
     *
     * @param DocumentInterface $document
     * @param array $fields
     * @param array $options
     * @return array
     */
    public function getRowData(DocumentInterface $document, array $fields, array $options): array
    {
        $row = [];
        foreach ($fields as $column) {
            if (isset($options[$column])) {
                $key = $document->getCustomAttribute($column)->getValue();
                if (isset($options[$column][$key])) {
                    $row[] = $options[$column][$key];
                } else {
                    $row[] = '';
                }
            } else {
                $row[] = $document->getCustomAttribute($column)->getValue();
            }
        }
        return $row;
    }
}
