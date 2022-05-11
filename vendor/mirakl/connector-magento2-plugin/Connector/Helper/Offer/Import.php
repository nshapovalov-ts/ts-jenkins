<?php
namespace Mirakl\Connector\Helper\Offer;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Helper\Offer as Api;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Model\ResourceModel\Offer as OfferResource;
use Mirakl\Process\Helper\Data as ProcessHelper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class Import extends AbstractHelper
{
    const PRODUCT_SKU_POSITION_IN_CSV = 1;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var OfferResource
     */
    private $offerResource;

    /**
     * @var ProcessHelper
     */
    private $processHelper;

    /**
     * @var ProcessResourceFactory
     */
    private $processResourceFactory;

    /**
     * @param   Context                 $context
     * @param   Config                  $config
     * @param   Api                     $api
     * @param   OfferResource           $offerResource
     * @param   ProcessHelper           $processHelper
     * @param   ProcessResourceFactory  $processResourceFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        Api $api,
        OfferResource $offerResource,
        ProcessHelper $processHelper,
        ProcessResourceFactory $processResourceFactory
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->api = $api;
        $this->offerResource = $offerResource;
        $this->processHelper = $processHelper;
        $this->processResourceFactory = $processResourceFactory;
    }

    /**
     * Retrieve product SKUs associated to offers present in specified CSV file
     *
     * @param   string  $file
     * @return  array
     */
    protected function getProductSkusFromOffersFile($file)
    {
        $skus = [];
        $fh = fopen($file, 'r');
        if (!$fh) {
            return $skus;
        }


        // Retrieve product SKUs from given file
        while ($row = fgetcsv($fh, 0, ';', '"', "\x80")) {
            if (isset($row[self::PRODUCT_SKU_POSITION_IN_CSV])) {
                $skus[] = $row[self::PRODUCT_SKU_POSITION_IN_CSV];
            }
        }

        // Remove the first value that comes from CSV headers
        array_shift($skus);

        return $skus;
    }

    /**
     * @param   Process $process
     * @param   bool    $full
     * @return  $this
     */
    public function run(Process $process, $full = false)
    {
        $file = $process->getFile();
        if (!$file) {
            $since = $full ? null : $this->config->getSyncDate('offers');

            // Save last synchronization date now if file download is too long
            $this->config->setSyncDate('offers');

            if ($since) {
                $process->output(__('Downloading offers from Mirakl since %1', $since->format('Y-m-d H:i:s')), true);
                // We process the date less 1 minute
                $since->sub(new \DateInterval('PT1M'));
            } else {
                $process->output(__('Downloading all offers from Mirakl'), true);
            }

            $offersFile = $this->api->getOffersFile($since)->getFile();
            $file = $this->processHelper->saveFile($offersFile);
            $process->setFile($file);
            $this->processResourceFactory->create()->save($process);
        }

        $process->output(__('Importing offers...'), true);
        $affected = $this->offerResource->importFile($file, $this->config->isOffersUseDirectDatabaseImport());
        $process->output(__('Done! (total: %1)', $affected));

        $this->_eventManager->dispatch('mirakl_offer_import_after', [
            'process' => $process,
            'file'    => $file,
            'skus'    => $this->getProductSkusFromOffersFile($file),
        ]);

        $process->output(__('Removing deleted offers...'), true);
        $this->offerResource->clearDeletedOffers();

        $process->output(__('Done!'));

        return $this;
    }
}
