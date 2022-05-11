<?php

/**
 * Retailplace_ConfigurableProductVolumePrice
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\ConfigurableProductVolumePrice\Plugin;

use Magento\Framework\Serialize\SerializerInterface;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use Mirakl\FrontendDemo\Plugin\Block\Product\Swatches\Renderer\Listing\ConfigurablePlugin;

/**
 * Class AddOfferRanges
 */
class AddOfferRanges
{
    /** @var \Magento\Framework\Serialize\SerializerInterface */
    private $serializer;

    /** @var \Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory */
    private $offerCollectionFactory;

    /**
     * AddOfferRanges constructor.
     *
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory $offerCollectionFactory
     */
    public function __construct(
        SerializerInterface $serializer,
        OfferCollectionFactory $offerCollectionFactory
    ) {
        $this->serializer = $serializer;
        $this->offerCollectionFactory = $offerCollectionFactory;
    }

    /**
     * Add Offer Price Rages Data
     *
     * @param \Mirakl\FrontendDemo\Plugin\Block\Product\Swatches\Renderer\Listing\ConfigurablePlugin $subject
     * @param string $result
     * @return string
     */
    public function afterAroundGetJsonConfig(ConfigurablePlugin $subject, string $result): string
    {
        $result = $this->serializer->unserialize($result);
        $offerIdList = [];
        if (is_array($result['optionPrices'])) {
            foreach ($result['optionPrices'] as $optionPrice) {
                $offerId = $optionPrice['offerData']['offerId'] ?? 0;
                $offerIdList[] = $offerId;


            }

            $offerCollection = $this->offerCollectionFactory->create();
            $offerCollection->addFieldToFilter('offer_id', ['in' => $offerIdList]);
            $offers = $offerCollection->getItems();

            foreach ($result['optionPrices'] as $key => &$optionPrice) {
                if (isset($optionPrice['offerData']['offerId'])) {
                    $offer = $offers[$optionPrice['offerData']['offerId']];
                    $optionPrice['offerData']['priceRanges'] = $offer->getData('price_ranges');
                    $optionPrice['offerData']['discountRanges'] = $offer->getData('discount_ranges');
                }
            }
        }

        return $this->serializer->serialize($result);
    }
}
