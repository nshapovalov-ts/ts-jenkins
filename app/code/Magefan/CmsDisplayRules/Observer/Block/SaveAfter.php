<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Observer\Block;

use Magefan\CmsDisplayRules\Observer\AbstractSaveAfter;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magefan\CmsDisplayRules\Model\BlockRepository;
use Magefan\CmsDisplayRules\Model\Config;

/**]
 * Class BlockSaveAfter
 */
class SaveAfter implements ObserverInterface
{
    /**
     * @var BlockRepository
     */
    protected $blockRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * SaveAfter constructor.
     * @param BlockRepository $blockRepository
     * @param Config $config
     */
    public function __construct(
        BlockRepository $blockRepository,
        Config $config
    ) {
        $this->blockRepository = $blockRepository;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->blockRepository->save($observer->getObject());
    }
}
