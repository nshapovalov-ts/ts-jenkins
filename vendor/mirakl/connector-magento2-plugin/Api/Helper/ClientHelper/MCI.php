<?php
namespace Mirakl\Api\Helper\ClientHelper;

/**
 * @method \Mirakl\MCI\Front\Client\FrontApiClient getClient()
 */
class MCI extends AbstractClientHelper
{
    const AREA_NAME = 'MCI';

    /**
     * {@inheritdoc}
     */
    protected function getArea()
    {
        return self::AREA_NAME;
    }
}