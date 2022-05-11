<?php
namespace Mirakl\Mci\Helper;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Tree as CategoryTree;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory as CategoryTreeFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Api\Helper\Hierarchy as Api;
use Mirakl\Connector\Common\ExportInterface;
use Mirakl\Connector\Common\ExportTrait;
use Mirakl\Mci\Helper\Config as MciConfig;
use Mirakl\Process\Model\Process;

class Hierarchy extends AbstractHelper implements ExportInterface
{
    use ExportTrait;

    const EXPORT_SOURCE = 'H01';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var MciConfig
     */
    protected $mciConfig;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryTreeFactory
     */
    protected $categoryTreeFactory;

    /**
     * @var Category
     */
    protected $rootCategory;

    /**
     * @var CategoryResourceFactory
     */
    protected $categoryResourceFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var CategoryTree[]
     */
    protected $treeCache = [];

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   Api                         $api
     * @param   MciConfig                   $mciConfig
     * @param   CategoryFactory             $categoryFactory
     * @param   CategoryTreeFactory         $categoryTreeFactory
     * @param   CategoryResourceFactory     $categoryResourceFactory
     * @param   CategoryCollectionFactory   $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Api $api,
        MciConfig $mciConfig,
        CategoryFactory $categoryFactory,
        CategoryTreeFactory $categoryTreeFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->api = $api;
        $this->mciConfig = $mciConfig;
        $this->categoryFactory = $categoryFactory;
        $this->categoryTreeFactory = $categoryTreeFactory;
        $this->categoryResourceFactory = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Deletes all MCI hierarchy in Mirakl platform
     *
     * @return  $this
     */
    public function deleteAll()
    {
        $tree = $this->getTree();
        $data = [];
        foreach ($tree->getNodes() as $node) {
            /** @var \Magento\Framework\Data\Tree\Node $node */
            $data[] = $this->prepare($node, 'delete');
        }

        return $this->export($data);
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
     * Exports all MCI hierarchy to Mirakl platform
     *
     * @param   Process $process
     * @return  int|false
     */
    public function exportAll(Process $process = null)
    {
        if (!$this->isExportable()) {
            if ($process) {
                $process->output(__('Export has been blocked by another module.'));
            }

            return false;
        }

        if ($process) {
            $process->output(__('Preparing Catalog categories to export...'), true);
        }

        $synchroId = $this->exportTree($this->getTree());

        if ($process) {
            $process->setSynchroId($synchroId);
            $process->output(__('Done! (%1)', $synchroId), true);
        }

        return $synchroId;
    }

    /**
     * Exports Magento hierarchies to Mirakl platform
     *
     * @param   CategoryTree    $tree
     * @param   string|null     $action
     * @return  int|false
     */
    public function exportTree(CategoryTree $tree, $action = null)
    {
        $this->_eventManager->dispatch('mirakl_mci_export_hierarchy_tree_prepare_before', [
            'tree' => $tree,
        ]);

        $data = [];
        foreach ($tree->getNodes() as $node) {
            /** @var Category $node */
            $data[] = $this->prepare($node, $action);
        }

        return $this->export($data);
    }

    /**
     * @return  CategoryCollection
     * @throws  \Exception
     */
    protected function getDefaultCollection()
    {
        /** @var CategoryCollection $collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->joinUrlRewrite()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('level', ['gt' => 1]);

        $this->_eventManager->dispatch('mirakl_mci_hierarchy_default_collection', [
            'collection' => $collection]
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
     * @return  Category
     */
    protected function getRootCategory()
    {
        if (null === $this->rootCategory) {
            $this->rootCategory = $this->categoryFactory->create();
            $rootId = $this->mciConfig->getHierarchyRootCategoryId();
            $this->categoryResourceFactory->create()->load($this->rootCategory, $rootId);
        }

        return $this->rootCategory;
    }

    /**
     * @param   Category    $category
     * @param   mixed       $store
     * @return  CategoryTree
     */
    public function getTree(Category $category = null, $store = null)
    {
        if (null === $store) {
            $storeId = $this->mciConfig->getCatalogIntegrationStore()->getId();
        } else {
            $storeId = $this->storeManager->getStore($store)->getId();
        }

        if (!isset($this->treeCache[$storeId])) {
            /** @var $tree CategoryTree */
            $tree = $this->categoryTreeFactory->create();

            if (null === $category) {
                $category = $this->getRootCategory();
            }

            $tree->load($category->getId());

            $collection = $this->getDefaultCollection();
            $collection->setStoreId($storeId);

            $tree->addCollectionData($collection);

            $this->treeCache[$storeId] = $tree;
        }

        return $this->treeCache[$storeId];
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
        if (null === $action) {
            $action = 'update';
        }

        /** @var Category $category */
        if ($category->getStoreId() != $this->mciConfig->getCatalogIntegrationStore()->getId()) {
            // If category store is not the one defined in config "Translation Store", reload category with configured store
            $category = $this->getTree()->getNodeById($category->getId());
            if (!$category) {
                throw new \Exception(__('Could not find category on configured store'));
            }
        }

        $parent = $category->getParentId();
        $root = $this->getRootCategory();
        if ($category->getLevel() == ($root->getLevel() + 1)) {
            $parent = ''; // if category is the first level under root category, parent is not needed
        }

        $data = [
            'hierarchy-code'        => $category->getId(),
            'hierarchy-label'       => $category->getName(),
            'hierarchy-parent-code' => $parent,
            'update-delete'         => $action,
        ];

        $dataTranslated = $this->translateHierarchy($category);

        return array_merge($data, $dataTranslated);
    }

    /**
     * Prepares translated data for specified category
     *
     * @param   DataObject  $category
     * @return  array
     */
    protected function translateHierarchy($category)
    {
        /** @var Category $category */
        $data = [];
        $locales = [$this->getLocale($this->mciConfig->getCatalogIntegrationStore())];
        // Handle category translations
        foreach ($this->mciConfig->getStoresForLabelTranslation() as $store) {
            $locale = $this->getLocale($store);
            if (in_array($locale, $locales)) {
                continue; // Locale already used
            }
            $locales[] = $locale;
            $tree = $this->getTree(null, $store->getId());
            $name = $category->getName();
            if ($categoryTranslated = $tree->getNodeById($category->getId())) {
                $name = $categoryTranslated->getName();
            }
            $data["hierarchy-label[$locale]"] = $name;
        }

        return $data;
    }
}
