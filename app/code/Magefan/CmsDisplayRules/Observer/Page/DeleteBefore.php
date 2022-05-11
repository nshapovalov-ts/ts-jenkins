<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Observer\Page;

use Magefan\CmsDisplayRules\Observer\AbstractDeleteBefore;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magefan\CmsDisplayRules\Model\PageRepository;
use Magefan\CmsDisplayRules\Model\Config;

/**
 * Class PageDeleteBefore
 */
class DeleteBefore implements ObserverInterface
{

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * DeleteBefore constructor.
     * @param PageRepository $pageRepository
     * @param Config $config
     */
    public function __construct(
        PageRepository $pageRepository,
        Config $config
    ) {
        $this->pageRepository = $pageRepository;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->pageRepository->delete($observer->getObject());
    }
}
