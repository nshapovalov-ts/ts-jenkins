<?php
namespace Mirakl\Process\Block\Adminhtml\Process\Grid\Column;

class File extends AbstractColumn
{
    /**
     * {@inheritdoc}
     */
    public function decorate($value, $row, $column, $isExport)
    {
        $isMirakl = strstr($column->getId(), 'mirakl') === false ? false : true;
        $html = '';
        if ($fileSize = $row->getFileSizeFormatted('&nbsp;', $isMirakl)) {
            $html = sprintf('<a href="%s">%s</a>&nbsp;(%s)',
                $row->getDownloadFileUrl($isMirakl),
                __('Download'),
                $fileSize
            );
            if ($row->canShowFile($isMirakl)) {
                $html .= sprintf(
                    '<br/> %s <a target="_blank" href="%s" title="%s">%s</a>',
                    __('or'),
                    $this->getUrl('*/*/showFile', ['id' => $row->getId()]),
                    $this->escapeHtml(__('Open in Browser')),
                    $this->escapeHtml(__('open in browser'))
                );
            }
        }

        return $html;
    }
}
