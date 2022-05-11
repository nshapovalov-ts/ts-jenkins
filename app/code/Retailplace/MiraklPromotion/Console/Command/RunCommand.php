<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Console\Command;

use Retailplace\MiraklPromotion\Model\MiraklApi\AssociationSync;
use Retailplace\MiraklPromotion\Model\MiraklApi\Sync;
use Mirakl\Sync\Model\Sync\Script\Collection as MiraklScriptCollection;

/**
 * Class RunCommand
 */
class RunCommand extends \Mirakl\Sync\Console\Command\RunCommand
{
    /**
     * Get Sync Scripts
     *
     * @return \Mirakl\Sync\Model\Sync\Script\Collection
     * @throws \Exception
     */
    protected function getScripts(): MiraklScriptCollection
    {
        /** @var \Mirakl\Sync\Model\Sync\Script\Collection $collection */
        $collection = $this->scriptCollectionFactory->create();
        $scriptsList = static::$scripts;

        $scriptsList['Mirakl_Connector'][] = [
            'code'   => 'PR01',
            'title'  => 'Import Promotions from Mirakl',
            'helper' => Sync::class,
            'method' => 'updatePromotions'
        ];

        $scriptsList['Mirakl_Catalog'][] = [
            'code'   => 'PR51',
            'title'  => 'Import Promotions Associations with Offers from Mirakl',
            'helper' => AssociationSync::class,
            'method' => 'updateAssociations'
        ];

        foreach ($scriptsList as $moduleName => $scripts) {
            if ($this->moduleManager->isEnabled($moduleName)) {
                foreach ($scripts as $data) {
                    $collection->addItem($this->scriptFactory->create()->setData($data));
                }
            }
        }

        return $collection;
    }
}
