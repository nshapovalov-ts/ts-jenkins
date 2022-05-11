<?php

namespace Retailplace\Search\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetSearchEngine implements DataPatchInterface
{
    /**
     * @var WriterInterface
     */
    protected $_configWriter;

    /**
     * @param WriterInterface $configWriter
     */
    public function __construct(
        WriterInterface $configWriter
    ) {
        $this->_configWriter = $configWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->_configWriter->save(\Magento\Config\Model\Config\Backend\Admin\Custom::XML_PATH_CATALOG_SEARCH_ENGINE, 'elastic');
        $this->_configWriter->save(\Mirasvit\Search\Model\Config::CONFIG_ENGINE_PATH, 'elastic');
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
