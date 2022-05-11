<?php
namespace Magecomp\Smspro\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class Edit extends Column
{
     const ROW_EDIT_URL = 'magecompsms/phonebook/edit';
    const ROW_DELETE_URL = 'magecompsms/phonebook/delete';

    protected $_urlBuilder;

    private $_editUrl;
    private $_deleteUrl;


    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $editUrl = self::ROW_EDIT_URL,
        $deleteUrl = self::ROW_DELETE_URL

    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->_editUrl = $editUrl;
        $this->_deleteUrl = $deleteUrl;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['phonebook_id'])) {
                    $item[$name]['edit'] = [
                        'href' => $this->_urlBuilder->getUrl(
                            $this->_editUrl,
                            ['id' => $item['phonebook_id']]
                        ),
                        'label' => __('Edit'),
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->_urlBuilder->getUrl(
                            $this->_deleteUrl,
                            ['id' => $item['phonebook_id']]
                        ),
                        'label' => __('Delete'),
                        'confirm'=> [
                            'title' => __('Delete'),
                            'message' => __('Are you sure you want to delete a this record?'),
                            ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}