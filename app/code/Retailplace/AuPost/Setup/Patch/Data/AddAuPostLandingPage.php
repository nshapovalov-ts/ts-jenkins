<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Setup\Patch\Data;

use Exception;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * Class AddAuPostLandingPage
 */
class AddAuPostLandingPage implements DataPatchInterface
{
    /** @var string */
    public const AU_POST_PAGE_IDENTIFIER = 'australia_post';

    /** @var \Magento\Cms\Model\PageFactory */
    private $pageFactory;

    /** @var \Magento\Cms\Api\PageRepositoryInterface */
    private $pageRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AddAuPostLandingPage constructor.
     *
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     * @param \Magento\Cms\Api\PageRepositoryInterface $pageRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        PageFactory $pageFactory,
        PageRepositoryInterface $pageRepository,
        LoggerInterface $logger
    ) {
        $this->pageFactory = $pageFactory;
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    /**
     * Apply patch
     */
    public function apply()
    {
        $this->addCmsPage();
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Add new CMS Page for AU Post landing
     */
    private function addCmsPage()
    {
        $pageData = [
            'title' => 'Australia Post',
            'page_layout' => '1column',
            'identifier' => self::AU_POST_PAGE_IDENTIFIER,
            'stores' => [Store::DEFAULT_STORE_ID]
        ];

        $page = $this->pageFactory->create();
        $page->setData($pageData);
        try {
            $this->pageRepository->save($page);

            /** We can set Selected Layout only after saving CMS Page to the DB */
            $page->setData('layout_update_selected', 'LandingPage');
            $this->pageRepository->save($page);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
