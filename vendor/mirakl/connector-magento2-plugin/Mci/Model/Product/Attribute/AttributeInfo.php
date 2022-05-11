<?php
namespace Mirakl\Mci\Model\Product\Attribute;

use Magento\Framework\DataObject;

/**
 * @method  string  getCode()
 * @method  $this   setCode(string $code)
 * @method  string  getLocale()
 * @method  $this   setLocale(string $locale)
 */
class AttributeInfo extends DataObject
{
    /**
     * @param   string      $code
     * @param   string|null $locale
     * @return  $this
     */
    public static function create($code, $locale = null)
    {
        return new self(['code' => $code, 'locale' => $locale]);
    }

    /**
     * @return  bool
     */
    public function isLocalized()
    {
        return null !== $this->getLocale();
    }
}