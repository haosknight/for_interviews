<?php

namespace app\components\apk_parsers;

use yii\base\BaseObject;

class ApkData extends BaseObject
{
    /** @var string */
    public $id;

    /** @var string */
    public $apkcomboUrl;

    /** @var string */
    public $apkpureUrl;

    /** @var string */
    public $apkUrl;

    /** @var string */
    public $apkFile;

    /** @var string */
    public $title;

    /** @var string */
    public $version;

    /** @var string[] */
    public $versions;

    /** @var string */
    public $iconUrl;

    /** @var string */
    public $iconFile;

    /** @var string */
    public $bannerUrl;

    /** @var string[] */
    public $screenshotUrls = [];

    /** @var string[] */
    public $screenshotFiles = [];

    /** @var string */
    public $descriptionFull = '';

    /** @var string */
    public $descriptionShort = '';

    /** @var string */
    public $mainCategory;

    /** @var array */
    public $categories = [];

    /** @var array */
    public $similarAppIds = [];

    /** @var float */
    public $rating;

    /** @var string */
    public $ratingCount;

    /** @var int */
    public $installs;

    /** @var int */
    public $minimumInstalls;

    /** @var int */
    public $maximumInstalls;

    /** @var bool */
    public $free;

    /** @var float */
    public $price;

    /** @var string */
    public $currency;

    /** @var string */
    public $size;

    /** @var string */
    public $minimumAndroid;

    /** @var string */
    public $developerId;

    /** @var string */
    public $developerWebsite;

    /** @var string */
    public $developerEmail;

    /** @var \DateTime */
    public $released;

    /** @var \DateTime */
    public $lastUpdated;

    /** @var string */
    public $contentRating;

    /** @var string */
    public $privacyPolicy;

    /** @var bool */
    public $adSupported;

    /** @var bool */
    public $inAppPurchases;

    /** @var bool */
    public $editorsChoice;

    /** @var \DateTime */
    public $scrapedTime;

    /**
     * @param string $pathTo
     * @param string|null $fileName
     * @return string|false
     */
    public function copyApkFile(string $pathTo, ?string $fileName = null)
    {
        $fileName = empty($fileName) ? basename($this->apkFile) : $fileName;
        $apkPath = $pathTo . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($this->apkFile)) {
            return @copy($this->apkFile, $apkPath) ? $apkPath : false;
        }
        return false;
    }

    /**
     * @param string $pathTo
     * @return string|false
     */
    public function copyIconFile(string $pathTo)
    {
        $iconPath = $pathTo . DIRECTORY_SEPARATOR . basename($this->iconFile);
        if (file_exists($this->iconFile)) {
            return @copy($this->iconFile, $iconPath) ? $iconPath : false;
        }
        return false;
    }

    /**
     * @param string $pathTo
     * @return string[]
     */
    public function copyScreenshotFiles(string $pathTo): array
    {
        $screenshots = [];
        foreach ($this->screenshotFiles as $thisFile) {
            $screenshotPath = $pathTo . DIRECTORY_SEPARATOR . basename($thisFile);
            if (file_exists($thisFile) && @copy($thisFile, $screenshotPath)) {
                $screenshots[] = $screenshotPath;
            }
        }
        return $screenshots;
    }

    private function deleteFile($filePath)
    {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function __toString()
    {
        return json_encode($this);
    }

    public function __toArray()
    {
        return json_decode(json_encode($this), true);
    }

    public function __destruct()
    {
        $this->deleteFile($this->apkFile);
        $this->deleteFile($this->iconFile);
        foreach ($this->screenshotFiles as $screenshotFile) {
            $this->deleteFile($screenshotFile);
        }
    }
}