<?php
namespace Mirakl\Core\Block\Adminhtml\Template\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Mirakl\Core\Model\Shop;

class State extends AbstractRenderer
{
    /**
     * Render grid column
     *
     * @param   DataObject  $row
     * @return  string
     */
    public function render(DataObject $row)
    {
        /** @var Shop $row */
        $state = $this->_getValue($row);

        return $this->decorateState($state, $row);
    }

    /**
     * @param   string  $value
     * @param   Shop    $row
     * @return  string
     */
    public function decorateState($value, $row)
    {
        switch ($row->getState()) {
            case Shop::STATE_CLOSE:
                $class = 'grid-severity-critical';
                break;
            case Shop::STATE_SUSPENDED:
                $class = 'grid-severity-minor';
                break;
            default:
                $class = 'grid-severity-notice';
        }

        return '<span class="' . $class . '"><span>' . __($value) . '</span></span>';
    }
}
