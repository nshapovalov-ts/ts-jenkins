<?php
/**
 * @category   Retailplace
 * @package    Retailplace_Plpseller
 * @author     dev@magentoguys.com
 * @copyright  This file was generated by using Module Creator(http://code.vky.co.in/magento-2-module-creator/) provided by VKY <viky.031290@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Retailplace\Plpseller\Controller\Adminhtml\Items;

class NewAction extends \Retailplace\Plpseller\Controller\Adminhtml\Items
{

    public function execute()
    {
        $this->_forward('edit');
    }
}