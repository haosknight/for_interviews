<?php

namespace app\components\apk_parsers\sources;

use Facebook\WebDriver\Remote\RemoteWebElement;

abstract class SiteSource extends AbstractSource implements Source
{
    /** @var string */
    public string $apkVersion = '';
    const SELECTOR_VERSION = 'version';
    const SELECTOR_PAGE = 'page';
    const SELECTOR_TITLE = 'title';
    const SELECTOR_DEVELOPER = 'developer';
    const SELECTOR_DESCRIPTION = 'description';
    const SELECTOR_ICON = 'icon';
    const SELECTOR_SCREENSHOTS = 'screenshots';
    const SELECTOR_VERSION_LINKS = 'version_links';
    const SELECTOR_DOWNLOAD_LINK = 'download_link';
    const SELECTOR_DOWNLOAD_TITLE = 'download_title';
    const SELECTOR_SIMILAR_APPS_PAGE = 'similar_apps_page';
    const SELECTOR_SIMILAR_APPS_LINKS = 'similar_apps_links';
    const SELECTOR_SIMILAR_APPS_NEXT_PAGE = 'similar_apps_next_page';

    abstract public static function selectors(): array;

    abstract public function getSourceUrl(): string;

    abstract public function getAppUrl(): string;

    abstract public function getApkOldVersionsUrl(): string;

    abstract public function getApkVersionUrl(string $version): string;

    abstract public function getVersion(RemoteWebElement $element): string;

    abstract public function getApkDownloadUrl(): string;

    abstract public function getSimilarAppsUrl(): string;

    abstract public function getFileMark(): string;

    /**
     * @param string $selector
     * @return string
     * @throws \Exception
     */
    public function getSelector(string $selector): string
    {
        if (empty($selector)) {
            throw new \Exception('Selector must be set');
        }
        $selectors = array_keys(static::selectors());
        if (!in_array($selector, $selectors)) {
            throw new \Exception('Selector not found in ' . static::class);
        }
        return static::selectors()[$selector];
    }
}