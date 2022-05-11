<?php
namespace Mirakl\Adminhtml\Block\Sales\Order\View\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection\EntityFactoryInterface as CollectionEntityFactoryInterface;
use Magento\Framework\Registry;
use Mirakl\Adminhtml\Block\Widget\Grid\Column\Renderer;
use Mirakl\Api\Helper\Order as Api;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\MMP\FrontOperator\Domain\Order\OrderLine as MiraklOrderLine;

class MiraklOrders extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var CollectionEntityFactoryInterface
     */
    protected $_collectionEntityFactory;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @param   Context                             $context
     * @param   BackendHelper                       $backendHelper
     * @param   Registry                            $coreRegistry
     * @param   CollectionEntityFactoryInterface    $collectionEntityFactory
     * @param   Api                                 $api
     * @param   array                               $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        Registry $coreRegistry,
        CollectionEntityFactoryInterface $collectionEntityFactory,
        Api $api,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_collectionEntityFactory = $collectionEntityFactory;
        $this->api = $api;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('mirakl_orders_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
    }

    /**
     * @param   string  $html
     * @return  string
     */
    protected function _afterToHtml($html)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_afterToHtml($html);
        }

        $messages = $this->getLayout()->getMessagesBlock();

        if (!$this->getOrder()->getMiraklSent()) {
            $messages->addError(__("This order has not been sent to Mirakl."));

            return $messages->toHtml();
        }

        $messages->addNotice(__('Mirakl orders are retrieved <strong>dynamically</strong>. Nothing is stored in Magento.'));

        return $messages->toHtml() . $html;
    }

    /**
     * @return  \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @param   MiraklOrderLine $miraklOrderLine
     * @return  float
     */
    public function getMiraklOrderLineShippingTaxAmount($miraklOrderLine)
    {
        $taxAmount = 0;

        if ($miraklOrderLine->getStatus()->getState() !== OrderState::REFUSED) {
            /** @var OrderTaxAmount $shippingTax */
            foreach ($miraklOrderLine->getShippingTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount;
    }

    /**
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklOrderShippingTaxAmount($miraklOrder)
    {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineShippingTaxAmount($orderLine);
        }

        return $taxAmount;
    }

    /**
     * @param   MiraklOrderLine $miraklOrderLine
     * @param   bool            $withShipping
     * @return  float
     */
    public function getMiraklOrderLineTaxAmount($miraklOrderLine, $withShipping = false)
    {
        $taxAmount = 0;

        if ($miraklOrderLine->getStatus()->getState() !== OrderState::REFUSED) {
            /** @var OrderTaxAmount $shippingTax */
            foreach ($miraklOrderLine->getTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount + ($withShipping ? $this->getMiraklOrderLineShippingTaxAmount($miraklOrderLine) : 0);
    }

    /**
     * @param   MiraklOrder $miraklOrder
     * @param   bool        $withShipping
     * @return  float
     */
    public function getMiraklOrderTaxAmount($miraklOrder, $withShipping = false)
    {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineTaxAmount($orderLine, $withShipping);
        }

        return $taxAmount;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $collection = new \Magento\Framework\Data\Collection($this->_collectionEntityFactory);
        if ($this->getRequest()->isAjax()) {
            try {
                $commercialId = $this->getOrder()->getIncrementId();
                $orders = $this->api->getOrdersByCommercialId($commercialId);
                if (!empty($orders)) {
                    foreach ($orders as $order) {
                        /** @var MiraklOrder $order */
                        $data = $order->getData();
                        $data['grand_total'] = $order->getTotalPrice() + $this->getMiraklOrderTaxAmount($order, true);
                        $collection->addItem(new DataObject($data));
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage());
                $this->getLayout()
                    ->getMessagesBlock()
                    ->addError($e->getMessage());
            }
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header'            => __('Order #'),
            'index'             => 'id',
            'sortable'          => false,
            'html_decorators'   => ['nobr'],
        ]);

        $this->addColumn('shop_id', [
            'header'            => __('Shop Id'),
            'index'             => 'shop_id',
            'sortable'          => false,
        ]);

        $this->addColumn('shop_name', [
            'header'            => __('Shop Name'),
            'index'             => 'shop_name',
            'sortable'          => false,
        ]);

        $this->addColumn('status', [
            'header'            => __('Status'),
            'index'             => 'status',
            'sortable'          => false,
            'getter'            => function ($row) {
                /** @var MiraklOrder $row */
                return $row->getStatus() ? $row->getStatus()->getState() : '';
            },
        ]);

        $this->addColumn('order_lines', [
            'header'            => __('Order Lines'),
            'index'             => 'order_lines',
            'align'             => 'right',
            'sortable'          => false,
            'getter'            => function ($row) {
                /** @var MiraklOrder $row */
                return $row->getOrderLines() ? $row->getOrderLines()->count() : '';
            },
        ]);

        if ($this->_scopeConfig->isSetFlag(\Mirakl\Connector\Helper\Config::XML_PATH_ENABLE_MULTIPLE_SHIPMENTS)) {
            $this->addColumn('shipments', [
                'header'          => __('Shipments'),
                'index'           => 'shipments',
                'align'           => 'left',
                'sortable'        => false,
                'html_decorators' => ['nobr'],
                'renderer'        => Renderer\MiraklOrder\Shipments::class,
            ]);
        }

        $this->addColumn('has_invoice', [
            'header'            => __('Has Invoice'),
            'index'             => 'has_invoice',
            'type'              => 'options',
            'align'             => 'center',
            'sortable'          => false,
            'options'           => [
                1  => __('Yes'),
                0  => __('No'),
            ],
        ]);

        $this->addColumn('has_incident', [
            'header'            => __('Has Incident'),
            'index'             => 'has_incident',
            'type'              => 'options',
            'align'             => 'center',
            'sortable'          => false,
            'options'           => [
                1  => __('Yes'),
                0  => __('No'),
            ],
        ]);

        $this->addColumn('last_updated_date', [
            'header'            => __('Last Updated Date'),
            'index'             => 'last_updated_date',
            'sortable'          => false,
            'getter'            => function ($row) {
                /** @var MiraklOrder $row */
                return $row->getLastUpdatedDate()
                    ->setTimezone(new \DateTimeZone('GMT'))
                    ->format('d/m/Y H:i:s');
            },
        ]);

        $this->addColumn('total_commission', [
            'header'            => __('Total Commission'),
            'index'             => 'total_commission',
            'type'              => 'currency',
            'currency'          => 'currency_iso_code',
            'rate'              => 1,
            'sortable'          => false,
        ]);

        $this->addColumn('grand_total', [
            'header'            => __('Total Price'),
            'index'             => 'grand_total',
            'type'              => 'currency',
            'currency'          => 'currency_iso_code',
            'rate'              => 1,
            'sortable'          => false,
        ]);

        $this->addColumn('action',
            [
                'header'     => __('Action'),
                'align'      => 'center',
                'type'       => 'action',
                'getter'     => 'getId',
                'actions'    => [
                    [
                        'caption'   => __('Validate Order'),
                        'url'       => [
                            'base'   => 'mirakl/order/validate',
                            'params' => ['order_id' => $this->getOrder()->getId()]
                        ],
                        'field'     => 'remote_id',
                        'confirm'   => __('Are you sure? This will impact all Mirakl orders of this list.'),
                        'statuses'  => [OrderState::STAGING],
                        'type'      => 'order',
                    ],
                    [
                        'caption'   => __('Invalidate Order'),
                        'url'       => [
                            'base'   => 'mirakl/order/invalidate',
                            'params' => ['order_id' => $this->getOrder()->getId()]
                        ],
                        'field'     => 'remote_id',
                        'confirm'   => __('Are you sure? This will impact all Mirakl orders of this list.'),
                        'statuses'  => [OrderState::STAGING],
                        'type'      => 'order',
                    ],
                    [
                        'caption'   => __('Validate Payment'),
                        'url'       => [
                            'base'   => 'mirakl/payment/validate',
                            'params' => ['order_id' => $this->getOrder()->getId()]
                        ],
                        'field'     => 'remote_id',
                        'confirm'   => __('Are you sure?'),
                        'type'      => 'payment',
                    ],
                    [
                        'caption'   => __('Refuse Payment'),
                        'url'       => [
                            'base'   => 'mirakl/payment/refuse',
                            'params' => ['order_id' => $this->getOrder()->getId()]
                        ],
                        'field'     => 'remote_id',
                        'confirm'   => __('Are you sure?'),
                        'type'      => 'payment',
                    ],
                ],
                'filter'     => false,
                'sortable'   => false,
                'renderer'   => Renderer\MiraklOrder\Action::class,
            ]
        );

        return parent::_prepareColumns();
    }
}
