<?php
/**
 * Vdcstore_CategoryTree
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Vdcstore\CategoryTree\Plugin;

use Magento\Catalog\Model\Category\DataProvider;

/**
 * Class ChildCategories
 */
class ChildCategories
{
    /**
     * @param DataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(
        DataProvider $subject,
        array $result
    ) {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $subject->getCurrentCategory();
        if (isset($result[$category->getId()]['child_categories'])) {
            $result[$category->getId()]['child_categories'] = explode(",", $result[$category->getId()]['child_categories']);
        }
        return $result;
    }
}
