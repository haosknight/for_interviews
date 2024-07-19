<?php

namespace app\components\apk_parsers\sources;

class CsvFile extends FileSource
{
    /**
     * @return string
     */
    final public function getSiteUrl(): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'data', 'appsDataset']);
    }
}