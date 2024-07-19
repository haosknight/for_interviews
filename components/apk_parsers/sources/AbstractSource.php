<?php

namespace app\components\apk_parsers\sources;

use yii\base\InvalidConfigException;

abstract class AbstractSource implements Source
{
    private string $_package;

    public function __construct($package)
    {
        if (empty($package)) {
            throw new InvalidConfigException("Property `package` must be set!");
        }
        $this->_package = $package;
    }

    abstract public function getSiteUrl(): string;

    public function getPackage()
    {
        return $this->_package;
    }
}