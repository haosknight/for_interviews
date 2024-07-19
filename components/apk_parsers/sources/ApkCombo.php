<?php

namespace app\components\apk_parsers\sources;

use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use yii\base\Exception;

class ApkCombo extends SiteSource
{
    const SELECTOR_VERSION_TITLE = 'div.info span.vername';
    /**
     * @return string[]
     */
    final public static function selectors(): array
    {

        return [
            self::SELECTOR_VERSION => '.app_header .version',
            self::SELECTOR_PAGE => 'body section#main div.container',
            self::SELECTOR_TITLE => '.app_name h1 a',
            self::SELECTOR_DEVELOPER => '.app_header .info .author a',
            self::SELECTOR_DESCRIPTION => '.text-description',
            self::SELECTOR_ICON => '.avatar img',
            self::SELECTOR_SCREENSHOTS => '#gallery-screenshots a',
            self::SELECTOR_VERSION_LINKS => '.list-versions li a.ver-item',
            self::SELECTOR_DOWNLOAD_LINK => 'a.variant',
            self::SELECTOR_DOWNLOAD_TITLE => 'nav.breadcrumb p:nth-last-child(2) a span',
        ];
    }

    /**
     * @return string
     */
    final public function getSiteUrl(): string
    {
        return 'https://apkcombo.com';
    }

    /**
     * @return string
     */
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
        return $this->getAppUrl() . '/download/phone-' . $version . '-apk';
    }

    /**
     * @return string
     * @throws Exception
     */
    final public function getApkOldVersionsUrl(): string
    {
        return $this->getAppUrl() . '/old-versions';
    }

    /**
     * @param RemoteWebElement $element
     * @return string
     */
    final public function getVersion(RemoteWebElement $element): string
    {
        $name = $element->findElement(WebDriverBy::cssSelector(self::SELECTOR_VERSION_TITLE))->getText();
        $words = explode(' ', $name);
        return array_pop($words);
    }

    /**
     * @return string
     * @throws Exception
     */
    final public function getApkDownloadUrl(): string
    {
        return $this->getAppUrl() . '/download/apk';
    }

    /**
     * @return string
     */
    final public function getSimilarAppsUrl(): string
    {
        return '';
    }

    /**
     * @return string
     */
    final public function getFileMark(): string
    {
        return 'apkcombo';
    }
}