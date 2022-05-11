<?php
namespace Mirakl\Adminhtml\Block\Widget\Grid\Column\Renderer\MiraklOrder;

use Magento\Framework\DataObject;
use Mirakl\Api\Helper\Payment;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    /**
     * Renders column
     *
     * @param   DataObject   $row
     * @return  string
     */
    public function render(DataObject $row)
    {
        $actions = $this->getColumn()->getActions();
        if (empty($actions) || !is_array($actions)) {
            return '&nbsp;';
        }

        if (sizeof($actions) == 1 && !$this->getColumn()->getNoLink()) {
            foreach ($actions as $action) {
                if (is_array($action)) {
                    return $this->_toLinkHtml($action, $row);
                }
            }
        }

        $out = '<select onchange="varienGridAction.execute(this);">'
            . '<option value="">'. __('-- Select --') .'</option>';
        $i = 0;
        $options = '';
        foreach ($actions as $action) {
            $i++;
            if (is_array($action)) {
                $options .= $this->_toOptionHtml($action, $row);
            }
        }

        if (empty($options)) {
            return '&ndash;';
        }

        $out .= $options;
        $out .= '</select>';

        return $out;
    }

    /**
     * Check if action to render is allowed for current row
     *
     * @param   array       $action
     * @param   DataObject  $row
     * @return  string
     */
    protected function _toOptionHtml($action, DataObject $row)
    {
        if ($action['type'] == 'payment') {
            $action['statuses'] = Payment::getOrderStatusesForPaymentMethod($row->getPaymentWorkflow());
        }

        if (isset($action['statuses']) && !in_array($row->getStatus()->getState(), (array) $action['statuses'])) {
            return '';
        }

        $actionAttributes = new DataObject();

        $actionCaption = '';
        $this->_transformActionData($action, $actionCaption, $row);

        $htmlAttibutes = ['value' => $this->escapeHtml($this->_jsonEncoder->encode($action))];
        $actionAttributes->setData($htmlAttibutes);

        return '<option ' . $actionAttributes->serialize() . '>' . $actionCaption . '</option>';
    }

    /**
     * {@inheritdoc}
     */
    protected function _transformActionData(&$action, &$actionCaption, \Magento\Framework\DataObject $row)
    {
        foreach ($action as $attribute => $value) {
            if (isset($action[$attribute]) && !is_array($action[$attribute])) {
                $this->getColumn()->setFormat($action[$attribute]);
                $action[$attribute] = $this->_getValue($row);
            } else {
                $this->getColumn()->setFormat(null);
            }

            switch ($attribute) {
                case 'caption':
                    $actionCaption = $action['caption'];
                    unset($action['caption']);
                    break;

                case 'url':
                    if (is_array($action['url']) && isset($action['field'])) {
                        $params = [$action['field'] => $this->_getValue($row)];
                        if (isset($action['url']['params'])) {
                            // In Magento 2.3.5, this line has been changed by
                            // $params[] = $action['url']['params'];
                            $params = array_merge($action['url']['params'], $params);
                        }
                        $action['href'] = $this->getUrl($action['url']['base'], $params);
                        unset($action['field']);
                    } else {
                        $action['href'] = $action['url'];
                    }
                    unset($action['url']);
                    break;

                case 'popup':
                    $action['onclick'] = "popWin(this.href, '_blank', 'width=800,height=700,resizable=1,scrollbars=1');return false;";
                    break;
            }
        }
        return $this;
    }
}