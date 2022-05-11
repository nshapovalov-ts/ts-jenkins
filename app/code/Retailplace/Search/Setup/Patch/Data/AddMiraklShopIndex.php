<?php

namespace Retailplace\Search\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mirasvit\Search\Api\Repository\IndexRepositoryInterface;

class AddMiraklShopIndex implements DataPatchInterface
{
    /**
     * @var IndexRepositoryInterface
     */
    protected $indexRepository;

    /**
     * @var WriterInterface
     */
    protected $_configWriter;

    /**
     * @param IndexRepositoryInterface $indexRepository
     * @param WriterInterface $configWriter
     */
    public function __construct(
        IndexRepositoryInterface $indexRepository,
        WriterInterface $configWriter
    ) {
        $this->indexRepository = $indexRepository;
        $this->_configWriter = $configWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $shopIndex = $this->indexRepository->create()
            ->setIdentifier('mirakl_shop')
            ->setTitle('Sellers')
            ->setIsActive(1)
            ->setPosition(4)
            ->setAttributes([
                'name' => 10
            ]);
        $this->indexRepository->save($shopIndex);
        $this->_configWriter->save('searchautocomplete/general/index',
            '{"magento_search_query":{"order":"1","is_active":"1","limit":"5"},"magento_catalog_categoryproduct":{"order":"2","is_active":"0","limit":"3"},"catalogsearch_fulltext":{"order":"3","is_active":"0","limit":"5"}, "mirakl_shop":{"order":"4","is_active":"1","limit":"5"}}'
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
