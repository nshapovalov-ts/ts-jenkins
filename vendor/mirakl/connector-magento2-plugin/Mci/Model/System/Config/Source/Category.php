<?php
namespace Mirakl\Mci\Model\System\Config\Source;

use Mirakl\Core\Model\System\Config\Source\Attribute\Category as CategorySource;

class Category
{
    /**
     * @var CategorySource
     */
    protected $categorySource;

    /**
     * @param   CategorySource  $categorySource
     */
    public function __construct(CategorySource $categorySource)
    {
        $this->categorySource = $categorySource;
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->categorySource->getAllOptions();
    }
}
