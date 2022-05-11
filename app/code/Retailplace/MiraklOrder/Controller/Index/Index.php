<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklOrder\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    const BASE_URL = "https://retailplace-dev.mirakl.net/api/";
    protected $resultRawFactory;
    protected $miraklApiConfig;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Mirakl\Api\Helper\Config $miraklApiConfig
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->miraklApiConfig = $miraklApiConfig;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId =  $this->getRequest()->getParam('remote_id');//"000000284-A";
        $invoiceDetail = $this->getInvoiceDetails($orderId);
        $docIds = [];
        if (isset($invoiceDetail['order_documents'])) {
            foreach ($invoiceDetail['order_documents'] as $doc) {
                if (isset($doc['type']) && isset($doc['id']) && $doc['type'] == "CUSTOMER_INVOICE") {
                    $docIds[] =  $doc['id'];
                }
            }
        }
        if (count($docIds) == 1) {
            if (isset($docIds[0])) {
                $this->downloadInvoice($docIds[0]);
            }
        } else {
            $this->downloadInvoice($orderId, "CUSTOMER_INVOICE");
        }
    }
    public function getBaseUrl()
    {
        return $this->miraklApiConfig->getApiUrl();
        //"https://retailplace-dev.mirakl.net/api";
    }
    public function getApiToken()
    {
        return $this->miraklApiConfig->getApiKey();
        //"42f60c6b-046d-41a1-98fc-c88a6bd23c49";
    }
    public function getInvoiceDetails($orderId)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
        CURLOPT_URL => $this->getBaseUrl() . "/orders/documents?order_ids=$orderId",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: {$this->getApiToken()}",
            "Accept: application/json"
        ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return  json_decode($response, true);
    }
    public function downloadInvoice($docId, $document_codes = "")
    {
        $url = $this->getBaseUrl() . "/orders/documents/download?document_ids=$docId";
        if ($document_codes) {
            $orderId = $docId;
            $url = $this->getBaseUrl() . "/orders/documents/download?order_ids=$orderId&document_codes=$document_codes";
        }
        $headers = [];
        $curl = curl_init();
        curl_setopt_array($curl, [
        CURLOPT_URL => $url ,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: {$this->getApiToken()}",
            "Accept: application/json"
        ],
        ]);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $header1 = explode(':', $header, 2);
            if (count($header1) < 2) { // ignore invalid headers
                return $len;
            }
            $headers[] = $header;
            return $len;
        });

        $response = curl_exec($curl);
        curl_close($curl);
        $resultRaw = $this->resultRawFactory->create();
        foreach ($headers as $header) {
            header($header);
        }
        echo $response;
        die;
    }
}
