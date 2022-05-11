<?php
namespace Mirakl\Api\Helper\ClientHelper;

/**
 * @method \Mirakl\MCM\Front\Client\FrontApiClient getClient()
 */
class MCM extends AbstractClientHelper
{
    const AREA_NAME = 'MCM';

    /**
     * {@inheritdoc}
     */
    protected function getArea()
    {
        return self::AREA_NAME;
    }
}