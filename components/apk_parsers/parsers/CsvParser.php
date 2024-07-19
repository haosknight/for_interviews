<?php

namespace app\components\apk_parsers\parsers;

use app\components\apk_parsers\ParserHelper;
use DateTime;

class CsvParser extends FileParser
{
    /** @var int */
    private $csvRowCount = 0;

    /** @var array */
    private $rowNames = [];

    final public function parse()
    {
        $this->setStatus(self::STATUS_PROCESS);
        try {
            $result = false;
            foreach (scandir($dir = $this->getSource()->getSiteUrl()) as $filename) {
                if (substr($filename, -4) == '.csv') {
                    $result = $this->searchByCsvFile($dir . DIRECTORY_SEPARATOR . $filename);
                    if ($result) {
                        break;
                    }
                }
            }
            if ($result) {
                $this->addData($result);
                $this->setStatus(self::STATUS_DONE);
            } else {
                $this->setStatus(self::STATUS_NOT_FOUND);
            }
        } catch (\Exception $e) {
            $this->setStatus(self::STATUS_ERROR);
        }
    }

    /**
     * @param $filepath
     * @return array|false|null
     */
    private function searchByCsvFile($filepath)
    {
        $result = false;
        $f = fopen($filepath, "r");
        while ($row = fgetcsv($f)) {
            $this->csvRowCount++;
            if ($this->csvRowCount === 1) {
                $this->rowNames = $row;
            }
            if ($row[1] == $this->getSource()->getPackage()) {
                $result = array_combine($this->rowNames, $row);
                break;
            }
        }
        fclose($f);

        return $result;
    }

    /**
     * @param array $newData
     * @return void
     * @throws \Exception
     */
    private function addData(array $newData)
    {
        $currentData = $this->getData();
        $currentData->title = ParserHelper::stringCompare($currentData->title, $newData['App Name']);
        $currentData->mainCategory = $currentData->mainCategory ?? $newData['Category'];
        $currentData->categories = ParserHelper::arrayCompare($currentData->categories, $newData['Category']);

        $currentData->rating = $currentData->rating ?? (float)$newData['Rating'];
        $currentData->ratingCount = $currentData->ratingCount ?? (int)$newData['Rating Count'];

        $newData['Installs'] = mb_substr($newData['Installs'], -1) == '+'
            ? rtrim($newData['Installs'], '+')
            : $newData['Installs'];
        $currentData->installs = $currentData->installs ?? (int)str_replace(',', '', $newData['Installs']);
        $currentData->minimumInstalls = $currentData->minimumInstalls ?? (int)$newData['Minimum Installs'];
        $currentData->maximumInstalls = $currentData->maximumInstalls ?? (int)$newData['Maximum Installs'];

        $currentData->free = $currentData->free ?? filter_var($newData['Free'], FILTER_VALIDATE_BOOLEAN);
        $currentData->price = $currentData->price ?? (float)$newData['Price'];
        $currentData->currency = $currentData->currency ?? $newData['Currency'];

        $currentData->size = $currentData->size ?? $newData['Size'];
        $currentData->minimumAndroid = $currentData->minimumAndroid ?? $newData['Minimum Android'];

        $currentData->developerId = $currentData->developerId ?? $newData['Developer Id'];
        $currentData->developerWebsite = $currentData->developerWebsite ?? $newData['Developer Website'];
        $currentData->developerEmail = $currentData->developerEmail ?? $newData['Developer Email'];

        $currentData->released = $currentData->released ?? new DateTime($newData['Released']);
        $currentData->lastUpdated = $currentData->lastUpdated ?? new DateTime($newData['Last Updated']);
        $currentData->scrapedTime = $currentData->scrapedTime ?? new DateTime($newData['Scraped Time']);

        $currentData->contentRating = $currentData->contentRating ?? $newData['Content Rating'];
        $currentData->privacyPolicy = $currentData->privacyPolicy ?? $newData['Privacy Policy'];

        $currentData->adSupported = $currentData->adSupported ?? filter_var($newData['Ad Supported'], FILTER_VALIDATE_BOOLEAN);
        $currentData->inAppPurchases = $currentData->inAppPurchases ?? filter_var($newData['In App Purchases'], FILTER_VALIDATE_BOOLEAN);
        $currentData->editorsChoice = $currentData->editorsChoice ?? filter_var($newData['Editors Choice'], FILTER_VALIDATE_BOOLEAN);

        $this->setData($currentData);
    }
}