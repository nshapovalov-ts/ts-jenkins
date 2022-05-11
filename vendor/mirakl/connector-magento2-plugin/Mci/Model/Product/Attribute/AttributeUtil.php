<?php
namespace Mirakl\Mci\Model\Product\Attribute;

use Mirakl\Mci\Helper\Data as MciHelper;

class AttributeUtil
{
    /**
     * Checks the difference between two arrays of attributes grouped by hierarchy code
     *
     * @param   array   $array1 First array to compare
     * @param   array   $array2 Second array to compare
     * @return  array
     */
    public static function diff($array1, $array2)
    {
        $result = [];
        foreach ($array1 as $hCode => $attributes) {
            if (array_key_exists($hCode, $array2)) {
                $diff = array_diff($attributes, $array2[$hCode]);
                if (count($diff)) {
                    $result[$hCode] = $diff;
                }
            } else {
                $result[$hCode] = $attributes;
            }
        }

        return $result;
    }

    /**
     * Returns true if given attribute code is "system" (for internal usage)
     *
     * @param   string  $attrCode
     * @return  bool
     */
    public static function isSystem($attrCode)
    {
        return in_array($attrCode, self::getSystemAttributes());
    }

    /**
     * Get all system attribute codes
     *
     * @return  string[]
     */
    public static function getSystemAttributes()
    {
        return [
            MciHelper::ATTRIBUTE_SKU,
            MciHelper::ATTRIBUTE_CATEGORY,
            MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE
        ];
    }

    /**
     * Parses given attribute code to identify the original Magento code and a potential locale code
     *
     * @param   string  $attrCode
     * @return  AttributeInfo
     */
    public static function parse($attrCode)
    {
        list ($code, $locale) = explode('-', $attrCode) + ['', null]; // add default values if no locale is found

        return AttributeInfo::create($code, $locale);
    }
}