<?php

namespace app\components\apk_parsers\parsers;

use app\components\apk_parsers\sources\AbstractSource;
use app\components\apk_parsers\sources\FileSource;
use app\components\apk_parsers\sources\SiteSource;
use app\models\Proxy;

abstract class FileParser extends AbstractParser
{
    /** @var int $lengthShortDescription */
    public int $lengthShortDescription = 1000;

    /**
     * @return AbstractSource|SiteSource
     */
    public function getSource(): FileSource
    {
        return parent::getSource();
    }

    abstract public function parse();
}