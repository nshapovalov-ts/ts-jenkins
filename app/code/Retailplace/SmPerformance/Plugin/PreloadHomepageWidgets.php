<?php

/**
 * Retailplace_SmPerformance
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SmPerformance\Plugin;

use Exception;
use Magefan\CmsDisplayRules\Model\Validator;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockRepository;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Retailplace\SmPerformance\Model\SmProductCollector;
use Sm\ListingTabs\Block\ListingTabs;
use Magento\Cms\Helper\Page;
use Zend_Db_ExprFactory;

/**
 * Class PreloadHomepageWidgets
 */
class PreloadHomepageWidgets
{
    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollectionFactory;

    /** @var \Retailplace\SmPerformance\Model\SmProductCollector */
    private $smProductCollector;

    /** @var \Magento\Catalog\Model\Config */
    private $catalogConfig;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;

    /** @var \Magefan\CmsDisplayRules\Model\Validator */
    private $cmsValidator;

    /** @var \Magefan\CmsDisplayRules\Model\BlockRepository */
    private $blockRulesRepository;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Magento\Cms\Api\PageRepositoryInterface */
    private $cmsPageRepository;

    /** @var \Zend_Db_ExprFactory */
    private $zendDbExprFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * PreloadHomepageWidgets Constructor
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\SmPerformance\Model\SmProductCollector $smProductCollector
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magefan\CmsDisplayRules\Model\Validator $cmsValidator
     * @param \Magefan\CmsDisplayRules\Model\BlockRepository $blockRulesRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Cms\Api\PageRepositoryInterface $cmsPageRepository
     * @param \Zend_Db_ExprFactory $zendDbExprFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SmProductCollector $smProductCollector,
        CollectionFactory $productCollectionFactory,
        CatalogConfig $catalogConfig,
        ResourceConnection $resource,
        Validator $cmsValidator,
        \Magefan\CmsDisplayRules\Model\BlockRepository $blockRulesRepository,
        ScopeConfigInterface $scopeConfig,
        PageRepositoryInterface $cmsPageRepository,
        Zend_Db_ExprFactory $zendDbExprFactory,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->smProductCollector = $smProductCollector;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogConfig = $catalogConfig;
        $this->resource = $resource;
        $this->cmsValidator = $cmsValidator;
        $this->blockRulesRepository = $blockRulesRepository;
        $this->scopeConfig = $scopeConfig;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->zendDbExprFactory = $zendDbExprFactory;
        $this->logger = $logger;
    }

    /**
     * Parse all Sm Blocks from Homepage before it's render and load all affected products within single request
     *
     * @param \Magento\Cms\Model\BlockRepository $blockRepository
     * @param \Magento\Cms\Model\Block $block
     * @return \Magento\Cms\Model\Block
     */
    public function afterGetById(BlockRepository $blockRepository, Block $block): Block
    {
        if ($this->isHomePage($block->getIdentifier()) && !$this->isBlockRestricted($block)) {
            $childBlocks = $this->getParametersFromString('block_id', $block->getContent());
            if ($childBlocks) {

                $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                $searchCriteria = $searchCriteriaBuilder
                    ->addFilter(BlockInterface::IDENTIFIER, $childBlocks, 'in')
                    ->create();
                $blocksList = $blockRepository->getList($searchCriteria);
                $content = '';
                foreach ($blocksList->getItems() as $childBlock) {
                    $content .= $childBlock->getContent();
                }

                $smBlocks = $this->getBlocksFromString(ListingTabs::class, $content);
                $categoryIds = [];
                foreach ($smBlocks as $smBlockContent) {
                    $categoryIds = array_merge($categoryIds, $this->parseCategoryIds($this->getParametersFromString('category_tabs', $smBlockContent, true)));
                }

                $categoryIds = array_unique(array_filter($categoryIds));

                $this->loadProducts($categoryIds);
            }
        }

        return $block;
    }

    /**
     * Check if Block restricted by Magefan Extension
     *
     * @param \Magento\Cms\Model\Block $block
     * @return bool
     */
    private function isBlockRestricted(Block $block): bool
    {
        $result = false;

        try {
            $cmsModel = $this->blockRulesRepository->getById($block->getId());
            $result = $this->cmsValidator->isRestricted($cmsModel);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    /**
     * Get Parameters from Widget string
     *
     * @param string|null $parameter
     * @param string|null $string
     * @param bool $single
     * @return array|string|null
     */
    private function getParametersFromString(?string $parameter, ?string $string, bool $single = false)
    {
        $result = null;

        if ($string && $parameter) {
            preg_match_all('/' . $parameter . '="([^"]+?)"/', $string, $matches);
            if (!empty($matches[1])) {
                $result = $matches[1];
                if ($single) {
                    $result = $result[0];
                }
            }
        }

        return $result;
    }

    /**
     * Get Widget string from content
     *
     * @param string $blockClass
     * @param string $string
     * @return array|null
     */
    private function getBlocksFromString(string $blockClass, string $string): ?array
    {
        $blockClass = str_replace('\\', '\\\\', $blockClass);
        preg_match_all('/{{block[^{}]*?class="' . $blockClass . '"[^{}]*?}}/', $string, $matches);

        $result = null;
        if (!empty($matches[0])) {
            $result = $matches[0];
        }

        return $result;
    }

    /**
     * Split Category Ids param
     *
     * @param string $categoryIdsString
     * @return array
     */
    private function parseCategoryIds(string $categoryIdsString): array
    {
        return preg_split('/[\s|,|;]/', $categoryIdsString, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Load affected Products
     *
     * @param array $categories
     */
    private function loadProducts(array $categories)
    {
        $resultData = [];
        $categories = $this->filterLoadedCategories($categories);
        if (count($categories)) {
            $productCollection = $this->getProductCollection($categories);
            $positionData = $this->getProductPositionData($productCollection->getAllIds());

            $items = $productCollection->getItems();

            foreach ($items as $item) {
                foreach ($item->getCategoryIds() as $categoryId) {
                    foreach ($positionData as $row) {
                        if ($row['product_id'] == $item->getId() && $row['category_id'] == $categoryId) {
                            $position = (int) $row['position'];
                            if (isset($resultData[$categoryId][$position])) {
                                $resultData[$categoryId][] = $item;
                            } else {
                                $resultData[$categoryId][$position] = $item;
                            }
                        }
                    }
                }
            }

            foreach ($resultData as &$categoryData) {
                ksort($categoryData);
            }

            $this->smProductCollector->setProducts($resultData);
        }
    }

    /**
     * Filter Categories to not load it twice
     *
     * @param array $categories
     * @return array
     */
    private function filterLoadedCategories(array $categories): array
    {
        $loadedData = $this->smProductCollector->getProducts();
        foreach ($categories as $key => $category) {
            if (isset($loadedData[$category])) {
                unset($categories[$key]);
            }
        }

        return $categories;
    }

    /**
     * Get Products Position data for all their Categories
     *
     * @param array $productIds
     * @return array
     */
    private function getProductPositionData(array $productIds): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection
            ->select()
            ->from($connection->getTableName('catalog_category_product'))
            ->where('product_id in (?)', $productIds);

        return $connection->fetchAll($select);
    }

    /**
     * Load Product Collection
     *
     * @param array $categories
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function getProductCollection(array $categories): Collection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addFieldToFilter(ProductInterface::STATUS, ['eq' => Status::STATUS_ENABLED])
            ->addCategoriesFilter(['in' => $categories])
            ->addUrlRewrite();

        //disable adding stock information via plugin "add_stock_information"
        //vendor/magento/module-catalog-inventory/etc/frontend/di.xml:12
        $productCollection->setFlag('has_stock_status_filter', true);
        $productCollection->setFlag('has_append_offers', true);
        $productCollection->setFlag('has_skip_saleable_check', true);

        $exp = $this->zendDbExprFactory->create(['expression' => 1]);
        $productCollection->getSelect()->columns([
            'is_salable' => $exp
        ]);

        return $productCollection;
    }

    /**
     * Check if Homepage Block is loading
     *
     * @param string $identifier
     * @return bool
     */
    private function isHomePage(string $identifier): bool
    {
        return in_array($identifier, $this->getHomepageBlockContent());
    }

    /**
     * Get List of Identifiers for Homepage Child Blocks
     *
     * @return array
     */
    private function getHomepageBlockContent(): array
    {
        $result = [];
        $homepageId = $this->scopeConfig->getValue(Page::XML_PATH_HOME_PAGE);
        if ($homepageId) {
            try {
                $homepage = $this->cmsPageRepository->getById($homepageId);
                $result = $this->getParametersFromString('block_id', $homepage->getContent());
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $result;
    }
}
