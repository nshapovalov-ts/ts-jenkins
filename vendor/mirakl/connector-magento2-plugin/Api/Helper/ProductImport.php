<?php
namespace Mirakl\Api\Helper;

use Mirakl\MCI\Common\Domain\Product\ProductImportStatus;
use Mirakl\MCI\Front\Request\Product\UpdateProductImportStatusRequest;

class ProductImport extends ClientHelper\MCI
{
    /**
     * (P43) Upload integration and error reports from the
     * operator information system using multipart/form-data
     *
     * @param   int     $importId
     * @param   mixed   $productsFile
     * @param   mixed   $errorsFile
     * @param   string  $status
     * @return  void
     */
    public function sendProductsImportReport(
        $importId,
        $productsFile = null,
        $errorsFile = null,
        $status = ProductImportStatus::COMPLETE
    ) {
        $request = new UpdateProductImportStatusRequest($importId, $status);

        if ($productsFile) {
            $request->setProductsFile($productsFile);
        }

        if ($errorsFile) {
            $request->setErrorsFile($errorsFile);
        }

        $this->send($request);
    }
}
