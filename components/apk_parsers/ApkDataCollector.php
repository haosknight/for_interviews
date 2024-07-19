<?php

namespace app\components\apk_parsers;

use app\components\apk_parsers\parsers\Parser;
use app\components\apk_parsers\parsers\SiteParser;
use yii\base\InvalidConfigException;

class ApkDataCollector
{
    const MODE_ALL_DATA = 'all_data';
    const MODE_TEXT_DATA = 'text_data';
    const MODE_DOWNLOAD_APK = 'download_apk';
    const MODE_DOWNLOAD_ICON = 'download_icon';
    const MODE_DOWNLOAD_SCREENSHOTS = 'download_screenshots';
    const MODE_SIMILAR_APPS = 'similar_apps';

    /** @var Parser|SiteParser */
    private $_parser;

    /** @var ApkData */
    private $_data;

    /**
     * @param Parser|null $parser
     */
    public function __construct(Parser $parser = null)
    {
        if (!empty($parser)) {
            $this->_parser = $parser;
        }

        if (empty($this->_data)) {
            $this->createNewData();
        } else {
            $this->_parser->setData($this->_data);
        }
    }

    /**
     * @param Parser|null $parser
     * @param string $mode
     * @return $this
     * @throws InvalidConfigException
     */
    public function collect(?Parser $parser = null, $mode = self::MODE_ALL_DATA): self
    {
        if (!empty($parser)) {
            $this->_parser = $parser;
        }
        if (empty($this->_parser)) {
            throw new InvalidConfigException("Property `parser` must be set!");
        }
        $method = explode('_', $mode);
        $method = array_map('ucfirst', $method);
        $method = 'mode' . implode('', $method);
        if (!method_exists($this, $method)) {
            throw new InvalidConfigException("Mode not exist");
        }

        $this->_parser->setData($this->_data);
        $this->$method();
        $this->_data = in_array($this->_parser->getStatus(), [Parser::STATUS_NOT_FOUND, Parser::STATUS_ERROR])
            ? $this->_data
            : $this->_parser->getData();
        return $this;
    }

    private function modeAllData()
    {
        $this->_parser->parse();
    }

    private function modeTextData()
    {
        $this->_parser->parseDescription();
    }

    private function modeDownloadApk()
    {
        $this->_parser->downloadApk();
    }

    private function modeDownloadIcon()
    {
        $this->_parser->downloadIcon();
    }

    private function modeDownloadScreenshots()
    {
        $this->_parser->downloadScreenshots();
    }

    private function modeSimilarApps()
    {
        $this->_parser->parseSimilarApps();
    }

    /**
     * @return ApkData
     */
    public function getData(): ApkData
    {
        return $this->_data;
    }

    private function createNewData(): void
    {
        if (empty($this->_data)) {
            $this->_data = new ApkData(['id' => !empty($this->_parser) ? $this->_parser->getSource()->getPackage() : '']);
        }
    }

}