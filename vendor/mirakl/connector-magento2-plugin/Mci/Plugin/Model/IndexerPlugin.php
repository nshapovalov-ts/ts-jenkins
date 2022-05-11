<?php
namespace Mirakl\Mci\Plugin\Model;

use Magento\Framework\Registry;
use Magento\Indexer\Model\Indexer;

class IndexerPlugin
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param   Registry        $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Callback for entity reindex
     *
     * @param   Indexer     $subject
     * @param   \Closure    $proceed
     * @return  bool
     */
    public function aroundIsScheduled(Indexer $subject, \Closure $proceed)
    {
        if ($this->registry->registry('mirakl_import_no_indexer')) {
            return true;
        }

        return $proceed();
    }
}