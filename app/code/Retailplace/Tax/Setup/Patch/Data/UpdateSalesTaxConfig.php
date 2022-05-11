<?php

/**
 * Retailplace_Tax
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Tax\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class UpdateSalesTaxConfig
 */
class UpdateSalesTaxConfig implements DataPatchInterface
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param WriterInterface $configWriter
     */
    public function __construct(
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
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

    /**
     * {@inheritdoc}
     */
    public function apply()
    {

        $configData = [
            'mirakl_connector/tax/offer_prices'          => '1',
            'mirakl_connector/tax/shipping_prices'       => '1',
            'mirakl_connector/tax/calculate_us_taxes'    => '0',
            'tax/notification/ignore_discount'           => '0',
            'tax/notification/ignore_price_display'      => '0',
            'tax/notification/ignore_apply_discount'     => '0',
            'tax/vertex_settings/enable_vertex'          => '0',
            'tax/vertex_settings/allowed_countries'      => 'NULL',
            'tax/vertex_delivery_terms/override'         => '[]',
            'tax/vertex_flexfields/code'                 => '"[{""field_id"":""1"",""field_source"":""""},{""field_id"":""2"",""field_source"":""""},{""field_id"":""3"",""field_source"":""""},{""field_id"":""4"",""field_source"":""""},{""field_id"":""5"",""field_source"":""""},{""field_id"":""6"",""field_source"":""""},{""field_id"":""7"",""field_source"":""""},{""field_id"":""8"",""field_source"":""""},{""field_id"":""9"",""field_source"":""""},{""field_id"":""10"",""field_source"":""""},{""field_id"":""11"",""field_source"":""""},{""field_id"":""12"",""field_source"":""""},{""field_id"":""13"",""field_source"":""""},{""field_id"":""14"",""field_source"":""""},{""field_id"":""15"",""field_source"":""""},{""field_id"":""16"",""field_source"":""""},{""field_id"":""17"",""field_source"":""""},{""field_id"":""18"",""field_source"":""""},{""field_id"":""19"",""field_source"":""""},{""field_id"":""20"",""field_source"":""""},{""field_id"":""21"",""field_source"":""""},{""field_id"":""22"",""field_source"":""""},{""field_id"":""23"",""field_source"":""""},{""field_id"":""24"",""field_source"":""""},{""field_id"":""25"",""field_source"":""""}]"',
            'tax/vertex_flexfields/numeric'              => '"[{""field_id"":""1"",""field_source"":""""},{""field_id"":""2"",""field_source"":""""},{""field_id"":""3"",""field_source"":""""},{""field_id"":""4"",""field_source"":""""},{""field_id"":""5"",""field_source"":""""},{""field_id"":""6"",""field_source"":""""},{""field_id"":""7"",""field_source"":""""},{""field_id"":""8"",""field_source"":""""},{""field_id"":""9"",""field_source"":""""},{""field_id"":""10"",""field_source"":""""}]"',
            'tax/vertex_flexfields/date'                 => '"[{""field_id"":""1"",""field_source"":""""},{""field_id"":""2"",""field_source"":""""},{""field_id"":""3"",""field_source"":""""},{""field_id"":""4"",""field_source"":""""},{""field_id"":""5"",""field_source"":""""}]"',
            'tax/classes/shipping_tax_class'             => '2',
            'tax/calculation/algorithm'                  => 'ROW_BASE_CALCULATION',
            'tax/calculation/price_includes_tax'         => '1',
            'tax/calculation/shipping_includes_tax'      => '1',
            'tax/calculation/discount_tax'               => '1',
            'tax/calculation/cross_border_trade_enabled' => '0',
            'tax/defaults/country'                       => 'AU',
            'tax/defaults/postcode'                      => 'NULL',
            'tax/display/type'                           => '2',
            'tax/display/shipping'                       => '2',
            'tax/cart_display/price'                     => '2',
            'tax/cart_display/subtotal'                  => '2',
            'tax/cart_display/shipping'                  => '2',
            'tax/cart_display/grandtotal'                => '0',
            'tax/cart_display/zero_tax'                  => '1',
            'tax/sales_display/price'                    => '2',
            'tax/sales_display/subtotal'                 => '2',
            'tax/sales_display/shipping'                 => '2',
            'tax/sales_display/grandtotal'               => '0',
            'tax/sales_display/zero_tax'                 => '1'
        ];

        foreach ($configData as $key => $configDatum) {
            $this->configWriter->save($key, $configDatum);
        }
    }
}
