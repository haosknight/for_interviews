<?php

namespace app\components\apk_parsers\parsers;

use app\components\apk_parsers\sources\AbstractSource;
use app\components\apk_parsers\sources\SiteSource;
use app\models\Proxy;

abstract class SiteParser extends AbstractParser
{
    const PROXY_TRY_LIMIT = 5;

    /** @var int $lengthShortDescription */
    public int $lengthShortDescription = 1000;

    /** @var int $timeout */
    public int $timeout = 240;

    /** @var int $maxRedirects */
    public int $maxRedirects = 10;

    /** @var bool */
    public bool $withProxy = true;

    /** @var array */
    private array $_proxyList = [];

    public function __construct(SiteSource $source)
    {
        parent::__construct($source);
        if ($this->withProxy) {
            $this->setProxyList();
        }
    }

    /**
     * @return AbstractSource|SiteSource
     */
    public function getSource(): SiteSource
    {
        return parent::getSource();
    }

    abstract public function parse();
    abstract public function parseDescription();
    abstract public function downloadApk();
    abstract public function downloadIcon();
    abstract public function downloadScreenshots();
    abstract public function parseSimilarApps();

    /**
     * @return array
     */
    public function getProxyList(): array
    {
        return $this->_proxyList;
    }

    public function setProxyList(): void
    {
        if ($proxies = Proxy::find()
            ->where(['is_mitm' => true])
            ->andWhere(['or',
                ['!=', 'status', Proxy::STATUS_FAIL],
                ['is', 'status', null]
            ])->all()
        ) {
            shuffle($proxies);
        }
        // first without proxy
        //$proxies = array_merge([false], $proxies);
        if (!empty($proxies)) {
            $this->_proxyList = $proxies;
        }
    }
}