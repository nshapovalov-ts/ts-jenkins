<?php
namespace Mirakl\Mcm\Helper\Product\Export;

use Magento\Framework\App\Helper\AbstractHelper;

class Category extends AbstractHelper
{
    /**
     * Retrieve category id to use for a product that will be exported
     *
     * @param   array   $paths
     * @return  int|false
     */
    public function getCategoryIdFromPaths(array $paths)
    {
        return key($this->sortPaths($paths));
    }

    /**
     * Retrieve category path (as array) to use for a product that will be exported
     *
     * @param   array   $paths
     * @return  array|false
     */
    public function getCategoryFromPaths(array $paths)
    {
        return current($this->sortPaths($paths));
    }

    /**
     * Rules are:
     * - take the deepest category
     * - if several categories have the same level, take the first one alphabetically
     *
     * @param   array   $paths
     * @return  array
     */
    private function sortPaths(array $paths)
    {
        uasort($paths, function ($a1, $a2) {
            $sortByName = function ($a1, $a2) {
                for ($i = count($a1) - 1; $i >= 0; $i--) {
                    $compare = strcmp($a1[$i], $a2[$i]);
                    if (1 === $compare) {
                        return 1;
                    }
                }

                return -1;
            };

            return count($a1) > count($a2) ? -1 : (count($a1) < count($a2) ? 1 : $sortByName($a1, $a2));
        });

        return $paths;
    }
}
