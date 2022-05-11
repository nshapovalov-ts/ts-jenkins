<?php
/**
 * Retailplace_CssPreProcessor
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\CssPreProcessor\Model\Adapter\Less;

use Magento\Framework\App\State;
use Magento\Framework\Css\PreProcessor\File\Temporary;
use Magento\Framework\Phrase;
use Magento\Framework\View\Asset\ContentProcessorException;
use Magento\Framework\View\Asset\ContentProcessorInterface;
use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Processor
 */
class Processor implements ContentProcessorInterface
{

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Source
     */
    private $assetSource;

    /**
     * @var Temporary
     */
    private $temporaryFile;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var bool
     */
    private $isDisableLogging;

    /**
     * Constructor
     *
     * @param State $appState
     * @param Source $assetSource
     * @param Temporary $temporaryFile
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        State $appState,
        Source $assetSource,
        Temporary $temporaryFile,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->appState = $appState;
        $this->assetSource = $assetSource;
        $this->temporaryFile = $temporaryFile;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     * @throws ContentProcessorException
     */
    public function processContent(File $asset)
    {
        $path = $asset->getPath();
        $content = '';

        try {
            $parser = new \Less_Parser(
                [
                    'relativeUrls' => false,
                    'compress'     => $this->appState->getMode() !== State::MODE_DEVELOPER
                ]
            );

            $content = $this->assetSource->getContent($asset);

            if (trim($content) === '') {
                throw new ContentProcessorException(
                    new Phrase('Compilation from source: LESS file is empty: ' . $path)
                );
            }

            $tmpFilePath = $this->temporaryFile->createFile($path, $content);

            gc_disable();
            $parser->parseFile($tmpFilePath, '');
            $content = $parser->getCss();
            gc_enable();

            if (trim($content) === '') {
                throw new ContentProcessorException(
                    new Phrase('Compilation from source: LESS file is empty: ' . $path)
                );
            }
        } catch (\Exception $e) {
            if (!$this->isDisableException()) {
                throw new ContentProcessorException(new Phrase($e->getMessage()));
            }
        }

        return $content;
    }

    /**
     * is Disable Exception
     *
     * @return bool
     */
    private function isDisableException(): bool
    {
        if ($this->isDisableLogging === null) {
            $this->isDisableLogging = (bool) $this->scopeConfig->getValue(
                "css_pre_processor/process_content/disable_exception",
                ScopeInterface::SCOPE_STORE
            );
        }
        return $this->isDisableLogging;
    }
}
