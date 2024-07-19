<?php

namespace app\components\apk_parsers\sources;

use Facebook\WebDriver\Remote\RemoteWebElement;
use yii\base\Exception;

class ApkPure extends SiteSource
{
    const SELECTOR_VERSION_ATTRIBUTE = 'data-dt-version';
    final public static function selectors(): array
    {
        return [
            self::SELECTOR_VERSION => '.apk_info .details_sdk span',
            self::SELECTOR_PAGE => 'body > main > div.details.container',
            self::SELECTOR_TITLE => 'div.title_link h1',
            self::SELECTOR_DEVELOPER => '.apk_info .details_sdk .developer a',
            self::SELECTOR_DESCRIPTION => '.description .content div > div > div',
            self::SELECTOR_ICON => 'div.apk_info img',
            self::SELECTOR_SCREENSHOTS => '.screenbox .screen a',
            self::SELECTOR_VERSION_LINKS => 'ul.ver-wrap li a.ver_download_link',
            self::SELECTOR_DOWNLOAD_LINK => 'a.download-start-btn',
            self::SELECTOR_DOWNLOAD_TITLE => '.info-title',
            self::SELECTOR_SIMILAR_APPS_PAGE => 'body > div.main.page-q',
            self::SELECTOR_SIMILAR_APPS_LINKS => 'div.allow-href > a.top-left',
            self::SELECTOR_SIMILAR_APPS_NEXT_PAGE => 'a.loadmore',
        ];
    }

    /**
     * @return string
     */
    final public function getSiteUrl(): string
    {
        return 'https://apkpure.net';
    }

    final public function getSourceUrl(): string
    {
        return $this->getSiteUrl() . '/manifest.json';
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    final public function getAppUrl(): string
    {
        return $this->getSiteUrl() . "/" . \Yii::$app->security->generateRandomString(20) . "/" . $this->getPackage();
    }

    /**
     * @param string $version
     * @return string
     * @throws Exception
     */
    final public function getApkVersionUrl(string $version): string
    {
        return $this->getAppUrl() . '/download/' . $version;
    }

    /**
     * @return string
     * @throws Exception
     */
    final public function getApkOldVersionsUrl(): string
    {
        return $this->getAppUrl() . '/versions';
    }

    /**
     * @param RemoteWebElement $element
     * @return string
     */
    final public function getVersion(RemoteWebElement $element): string
    {
        return $element->getAttribute(self::SELECTOR_VERSION_ATTRIBUTE);
    }

    /**
     * @return string
     * @throws Exception
     */
    final public function getApkDownloadUrl(): string
    {
        return $this->getAppUrl() . '/download';
    }

    /**
     * @return string
     */
    final public function getSimilarAppsUrl(): string
    {
        return $this->getSiteUrl() . '/similar/' . $this->getPackage();
    }

    /**
     * @return string
     */
    final public function getFileMark(): string
    {
        return 'Apkpure';
    }
}