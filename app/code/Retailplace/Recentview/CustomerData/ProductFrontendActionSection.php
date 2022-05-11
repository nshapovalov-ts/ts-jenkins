<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Recentview\CustomerData;

use Magento\Catalog\Model\Product\ProductFrontendAction\Synchronizer;
use Magento\Catalog\Model\ProductFrontendAction;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\App\Config;
use Magento\Framework\View\LayoutFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Response\RedirectInterface;

class ProductFrontendActionSection implements SectionSourceInterface
{
    protected $layoutFactory;
    /**
     * Identification of Type of a Product Frontend Action
     *
     * @var string
     */
    private $typeId;
    /**
     * @var Synchronizer
     */
    private $synchronizer;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Config
     */
    private $appConfig;

    /**
     * @param Synchronizer $synchronizer
     * @param string $typeId Identification of Type of a Product Frontend Action
     * @param LoggerInterface $logger
     * @param Config $appConfig
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Synchronizer $synchronizer,
        $typeId,
        LoggerInterface $logger,
        Config $appConfig,
        LayoutFactory $layoutFactory
    ) {
        $this->typeId = $typeId;
        $this->synchronizer = $synchronizer;
        $this->logger = $logger;
        $this->appConfig = $appConfig;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Post Process collection data in order to eject all customer sensitive information
     *
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        if (!(bool) $this->appConfig->getValue(Synchronizer::ALLOW_SYNC_WITH_BACKEND_PATH)) {
            return [
                'count' => 0,
                'items' => [],
            ];
        }

        $actions = $this->synchronizer->getActionsByType($this->typeId);
        $items = [];

        /** @var ProductFrontendAction $action */
        foreach ($actions as $action) {
            $items[$action->getProductId()] = [
                'added_at'   => $action->getAddedAt(),
                'product_id' => $action->getProductId(),
            ];
        }

        $html = $this->layoutFactory->create()
            ->createBlock(\Retailplace\Recentview\Block\ListingTabsRecentlyViewed::class)
            ->setData([
                "type_show"         => "slider",
                "type_listing"      => "all",
                "category_select"   => "2",
                "type_filter"       => "fieldproducts",
                "type_show"         => "slider",
                "order_by"          => "created_at",
                "order_dir"         => "desc",
                "title"             => "Recently Viewed",
                "display_countdown" => 0,
                "under_price"       => 5,
                "product_ids"       => implode(",", array_keys($items)),
            ])
            ->setTemplate('Retailplace_Recentview::default_ajax.phtml')->toHtml();
        return [
            'count' => count($items),
            'items' => $items,
            'html'  => $html
        ];
    }
}
