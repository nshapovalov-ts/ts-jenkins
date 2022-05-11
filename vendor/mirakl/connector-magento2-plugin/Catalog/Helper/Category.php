<?php
namespace Mirakl\Catalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Catalog\Model\Category as CategoryObject;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory as CategoryTreeFactory;
use Magento\Catalog\Model\ResourceModel\Category\Tree as CategoryTree;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Api\Helper\Category as Api;
use Mirakl\Connector\Common\ExportInterface;
use Mirakl\Connector\Common\ExportTrait;
use Mirakl\Connector\Helper\Config;
use Mirakl\Process\Model\Process;

class Category extends AbstractHelper implements ExportInterface
{
    use ExportTrait;

    const EXPORT_SOURCE = 'CA01';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CategoryTreeFactory
     */
    protected $categoryTreeFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryResourceFactory
     */
    protected $categoryResourceFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var CategoryTree[]
     */
    protected $treeCache = [];

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   Api                         $api
     * @param   Config                      $config
     * @param   CategoryTreeFactory         $categoryTreeFactory
     * @param   CategoryFactory             $categoryFactory
     * @param   CategoryResourceFactory     $categoryResourceFactory
     * @param   CategoryCollectionFactory   $categoryCollectionFactory
     * @param   EntityManager               $entityManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Api $api,
        Config $config,
        CategoryTreeFactory $categoryTreeFactory,
        CategoryFactory $categoryFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        EntityManager $entityManager
    ) {
        parent::__construct($context);
        $this->storeManager              = $storeManager;
        $this->api                       = $api;
        $this->config                    = $config;
        $this->categoryTreeFactory       = $categoryTreeFactory;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryResourceFactory   = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->entityManager             = $entityManager;
    }

    /**
     * @return  StoreInterface
     */
    protected function getDefaultStore()
    {
        return $this->storeManager->getDefaultStoreView();
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $data)
    {
        if (!$this->isExportable()) {
            return false;
        }

        return $this->api->export($data);
    }

    /**
     * Exports all Magento category tree that have mirakl_sync flag set to 1
     *
     * @param   Process $process
     * @return  int|false
     */
    public function exportAll(Process $process = null)
    {
        if (!$this->isExportable()) {
            $process->output(__('Export has been blocked by another module.'));

            return false;
        }

        if ($process) {
            $process->output(__('Preparing marketplace categories to export...'), true);
        }

        $tree = $this->getTree();

        if ($tree->getNodes()->count() <= 1) {
            $process->output(__('Nothing to export'));

            return false;
        }

        $synchroId = $this->exportTree($tree);

        if ($process) {
            $process->setSynchroId($synchroId);
            $process->output(__('Done! (%1)', $synchroId), true);
        }

        return $synchroId;
    }

    /**
     * Exports custom category collection to Mirakl platform
     *
     * @param   CategoryCollection  $collection
     * @param   string|null         $action
     * @return  int
     */
    public function exportCollection(CategoryCollection $collection, $action = null)
    {
        $this->_eventManager->dispatch(
            'mirakl_catalog_export_category_collection_prepare_before',
            ['collection' => $collection]
        );

        if (!$collection->count()) {
            return false;
        }

        $data = [];
        foreach ($collection as $category) {
            $data[] = $this->prepare($category, $action);
        }

        return $this->export($data);
    }

    /**
     * Exports custom category tree to Mirakl platform
     *
     * @param   CategoryTree    $tree
     * @param   string|null     $action
     * @return  int|false
     */
    public function exportTree(CategoryTree $tree, $action = null)
    {
        $this->_eventManager->dispatch(
            'mirakl_catalog_export_category_tree_prepare_before',
            ['tree' => $tree, 'action' => $action]
        );

        $data = [];
        foreach ($tree->getNodes() as $node) {
            /** @var \Magento\Framework\Data\Tree\Node $node */
            if ($node->getLevel() >= 2) {
                // Root node and Default Category should not be exported
                $data[] = $this->prepare($node, $action);
            }
        }

        return $this->export($data);
    }

    /**
     * Prepares category data for export
     *
     * @param   DataObject  $category
     * @param   null|string $action
     * @return  array
     * @throws  \Exception
     */
    public function prepare(DataObject $category, $action = null)
    {
        /** @var CategoryObject $category */
        if (null === $action) {
            $action = !$category->getId() || $category->getData('mirakl_sync') ? 'update' : 'delete';
        }

        if ($category->getStoreId() != $this->config->getCatalogIntegrationStore()->getId()) {
            // If category store is not the one defined in config "Translation Store", reload category with configured store
            $category = $this->getTree()->getNodeById($category->getId());
            if (!$category) {
                throw new \Exception(__('Could not find category on configured store'));
            }
        }

        $parentId = '';
        if ($category->getLevel() >= 3) {
            // Default Category is root category and level == 1
            // We do not want to send parent code for second level so we start at level == 3
            try {
                $parent = $this->categoryFactory->create();
                $this->categoryResourceFactory->create()->load($parent, $category->getParentId());
                $parentId = $parent->getId();
            } catch (\Exception $e) {
                // Ignore potential exception here
            }
        }

        $data = [
            'category-code'        => $category->getId(),
            'category-label'       => $category->getName(),
            'category-description' => $category->getDescription(),
            'parent-code'          => $parentId,
            'update-delete'        => $action,
        ];

        $dataTranslated = $this->translateCategory($category);

        return array_merge($data, $dataTranslated);
    }

    /**
     * @return  CategoryCollection
     */
    protected function _getDefaultCollection()
    {
        /** @var CategoryCollection $collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->joinUrlRewrite()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('level', ['gteq' => 2])
            ->addAttributeToSelect('description')
            ->addAttributeToFilter('mirakl_sync', ['notnull' => true]);

        $this->_eventManager->dispatch(
            'mirakl_catalog_category_default_collection',
            ['collection' => $collection]
        );

        return $collection;
    }

    /**
     * Returns store locale
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getLocale($store = null)
    {
        return $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param   CategoryObject  $category
     * @param   mixed           $store
     * @return  CategoryTree
     */
    public function getTree(CategoryObject $category = null, $store = null)
    {
        if (null === $store) {
            $storeId = $this->config->getCatalogIntegrationStore()->getId();
        } else {
            $storeId = $this->storeManager->getStore($store)->getId();
        }

        if (!isset($this->treeCache[$storeId])) {
            /** @var CategoryTree $tree */
            $tree = $this->categoryTreeFactory->create();

            if (null === $category) {
                /** @var CategoryObject $category */
                $category = $this->categoryFactory->create();
                $this->entityManager->load($category, 1); // Use "Root Catalog" category to load all Root Categories
            }

            $tree->loadNode($category->getId())->loadChildren();

            $collection = $this->_getDefaultCollection();
            $collection->setStoreId($storeId);

            $tree->addCollectionData($collection);

            $this->treeCache[$storeId] = $tree;
        }

        return $this->treeCache[$storeId];
    }

    /**
     * Prepares translated data for specified category
     *
     * @param   CategoryObject  $category
     * @return  array
     */
    protected function translateCategory($category)
    {
        $data = [];
        $locales = [$this->getLocale($this->config->getCatalogIntegrationStore())];

        // Handle category translations
        foreach ($this->config->getStoresForLabelTranslation() as $store) {
            $locale = $this->getLocale($store);

            if (in_array($locale, $locales)) {
                continue; // Locale already used
            }

            $locales[] = $locale;

            $tree = $this->getTree(null, $store->getId());
            $name = $category->getName();
            $description = $category->getDescription();
            if ($categoryTranslated = $tree->getNodeById($category->getId())) {
                $name = $categoryTranslated->getName();
                $description = $categoryTranslated->getDescription();
            }

            $data["category-label[$locale]"] = $name;
            $data["category-description[$locale]"] = $description;
        }

        return $data;
    }
}
