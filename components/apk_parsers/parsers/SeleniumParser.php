<?php

namespace app\components\apk_parsers\parsers;

use app\components\apk_parsers\sources\SiteSource;
use app\components\Browser;
use app\components\Chrome;
use app\components\ImageLibrary;
use app\models\Proxy;
use cheatsheet\Time;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

class SeleniumParser extends SiteParser
{
    const DOWNLOADS_PATH = '/storage/application/downloads';

    /** @var int */
    public int $similarAppsMaxPages = 5;

    /** @var bool */
    public bool $withSimilarApps = false;

    /** @var bool */
    public bool $withApkFile = false;

    /** @var RemoteWebDriver|null */
    private static $browser = null;

    /** @var null|false|Proxy */
    private static $proxy = null;

    public function __construct(SiteSource $source)
    {
        parent::__construct($source);
    }

    public function parse()
    {
        try {
            $this->setStatus(self::STATUS_PROCESS);
            $this->parseDescription();
            $this->parseVersions();
            $this->downloadIcon();
            $this->downloadScreenshots();
            if ($this->withApkFile) {
                $this->downloadApk();
            }
            if ($this->withSimilarApps) {
                $this->parseSimilarApps($this->similarAppsMaxPages);
            }
            $this->setStatus(self::STATUS_DONE);
        } catch (\Exception $e) {
            self::resetDriver();
            if ($e->getMessage() == 'Not found element') {
                $this->setStatus(self::STATUS_NOT_FOUND);
            } else {
                $this->setStatus(self::STATUS_ERROR);
            }
            $this->addError($e->getMessage(), $e);
        }
        self::resetDriver();
    }

    public function parseDescription()
    {
        $this->setStatus(self::STATUS_PROCESS);
        $source = $this->getSource();
        $this->manageCookies($source->getAppUrl(), $source->getSelector(SiteSource::SELECTOR_PAGE));

        $titleElement = $this->findElement(
            $source->getSelector(SiteSource::SELECTOR_TITLE),
            $source->getAppUrl()
        );
        if ($titleElement) {
            $this->getData()->title = $titleElement->getText();
        } else {
            $this->setStatus(self::STATUS_NOT_FOUND);
            throw new \Exception('Not found element');
        }

        $developerElement = $this->findElement(
            $source->getSelector(SiteSource::SELECTOR_DEVELOPER)
        );
        if ($developerElement) {
            $this->getData()->developerId = $developerElement->getText();
        }

        $versionElement = $this->findElement(
            $source->getSelector(SiteSource::SELECTOR_VERSION)
        );
        if ($versionElement) {
            $this->getData()->version = $versionElement->getText();
        }

        $descriptionElement = $this->findElement(
            $source->getSelector(SiteSource::SELECTOR_DESCRIPTION)
        );
        if ($descriptionElement) {
            $fullDescription = $descriptionElement->getText();
            if (strlen($fullDescription) > $this->lengthShortDescription) {
                $shortDescription = StringHelper::truncate($fullDescription, $this->lengthShortDescription, '!!!');
                $shortDescription = explode('.', $shortDescription);
                array_pop($shortDescription);
                $shortDescription = implode('.', $shortDescription) . '..';
            } else {
                $shortDescription = $fullDescription;
            }
            $this->getData()->descriptionFull = $fullDescription;
            $this->getData()->descriptionShort = $shortDescription;
        }

        $currentUrl = $this->getDriver()->getCurrentURL();
        if (strpos($currentUrl, 'apkcombo')) {
            $this->getData()->apkcomboUrl = $currentUrl;
        }
        if (strpos($currentUrl, 'apkpure')) {
            $this->getData()->apkpureUrl = $currentUrl;
        }
        $this->setStatus(self::STATUS_DONE);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function downloadApk()
    {
        $this->setStatus(self::STATUS_PROCESS);
        $source = $this->getSource();
        $this->manageCookies($source->getAppUrl(), $source->getSelector(SiteSource::SELECTOR_PAGE));

        if (file_exists($this->getData()->apkFile)) {
            return;
        }

        if (empty($this->getData()->versions)) {
            $this->parseVersions();
        }

        if (empty($source->apkVersion) && !empty($this->getData()->versions)) {
            $this->getData()->version = $this->getData()->versions[array_rand($this->getData()->versions)];
        } else {
            $this->getData()->version = $source->apkVersion;
        }

        if (!empty($this->getData()->version)) {
            $apkDownloadUrl = $source->getApkVersionUrl($this->getData()->version);
        } else {
            $apkDownloadUrl = $source->getApkDownloadUrl();
        }
        if (!str_contains($apkDownloadUrl, 'http')) {
            $apkDownloadUrl = $source->getSiteUrl() . $apkDownloadUrl;
        }

        $element = $this->findElement(
            $source->getSelector(SiteSource::SELECTOR_DOWNLOAD_LINK),
            $apkDownloadUrl
        );
        sleep(5);
        if ($element) {
            $apkTitleElement = $this->findElement($source->getSelector(SiteSource::SELECTOR_DOWNLOAD_TITLE));
            if (!$apkTitleElement || empty($apkTitleElement->getText())) {
                self::resetDriver();
                $this->setStatus(self::STATUS_ERROR);
                throw new \Exception('Not found apk title text');
            } else {
                $name = $apkTitleElement->getText();
            }

            $name = str_replace(['/', ':', '?'], '_', $name);

            $isFileExists = $this->getData()->apkFile = $this->getApkFilePath($name);
            if ($isFileExists) {
                self::resetDriver();
                $this->setStatus(self::STATUS_DONE);
                return;
            }

            $this->getData()->apkUrl = $element->getAttribute('href');
            if (empty($this->getData()->apkUrl)) {
                self::resetDriver();
                $this->setStatus(self::STATUS_ERROR);
                throw new \Exception('Apk url not found');
            }
            try {
                $data = $this->getData();
                $this->getDriver()->get($data->apkUrl);
                sleep(10);
                $timeLimit = time() + Time::SECONDS_IN_A_MINUTE * 10;
                do {
                    sleep(5);
                    $isFileExists = $data->apkFile = $this->getApkFilePath($name);
                } while (!$isFileExists && time() < $timeLimit);
            } catch (\Exception $e) {
                self::resetDriver();
                $this->setStatus(self::STATUS_ERROR);
                $this->addError('Unknown error', $e);
            }

            if (!$isFileExists) {
                self::resetDriver();
                $this->setStatus(self::STATUS_NOT_FOUND);
                throw new \Exception('Not found element');
            }
            self::resetDriver();
            $this->setStatus(self::STATUS_DONE);
        } else {
            self::resetDriver();
            $this->setStatus(self::STATUS_NOT_FOUND);
            throw new \Exception('Not found element');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function downloadIcon()
    {
        $this->setStatus(self::STATUS_PROCESS);
        $source = $this->getSource();
        $this->manageCookies($source->getAppUrl(), $source->getSelector(SiteSource::SELECTOR_PAGE));

        if (file_exists($this->getData()->iconFile)) {
            return;
        }

        $element = $this->findElement($source->getSelector(SiteSource::SELECTOR_ICON), $source->getAppUrl());
        sleep(5);
        if ($element) {
            $name = implode('_', [$source->getPackage(), $source->getFileMark(), 'icon.png']);

            $isFileExists = $this->getData()->iconFile = $this->getScreenshotFilePath($name);
            if ($isFileExists) {
                $this->setStatus(self::STATUS_DONE);
                return;
            }

            $this->getData()->iconUrl = $element->getAttribute('src');
            if (empty($this->getData()->iconUrl)) {
                $this->setStatus(self::STATUS_ERROR);
                throw new \Exception('Icon url not found');
            }
            $imagePath = $this->getScreenshotDirPath() . DIRECTORY_SEPARATOR . $name;

            try {
                $this->findElement('img', $this->getData()->iconUrl)
                    ->takeElementScreenshot($imagePath);
                $timeLimit = time() + Time::SECONDS_IN_A_MINUTE;
                do {
                    sleep(2);
                    $isFileExists = $this->getData()->iconFile = $this->getScreenshotFilePath($name);
                } while (!$isFileExists && time() < $timeLimit);
            } catch (\Exception $e) {
                $this->setStatus(self::STATUS_ERROR);
                $this->addError('Unknown error', $e);
            }

            if (!$isFileExists) {
                $this->setStatus(self::STATUS_NOT_FOUND);
                throw new \Exception('Not found element');
            }

            try {
                $this->getData()->iconFile = (new ImageLibrary($imagePath))->compress()->getImagePath();
                $this->setStatus(self::STATUS_DONE);
            } catch (\Exception $e) {
                $this->setStatus(self::STATUS_ERROR);
                $this->addError('Image compress error', $e);
            }
        } else {
            $this->setStatus(self::STATUS_NOT_FOUND);
            throw new \Exception('Not found element');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function downloadScreenshots()
    {
        $this->setStatus(self::STATUS_PROCESS);
        $source = $this->getSource();
        $this->manageCookies($source->getAppUrl(), $source->getSelector(SiteSource::SELECTOR_PAGE));

        $elements = $this->findElements($source->getSelector(SiteSource::SELECTOR_SCREENSHOTS), $source->getAppUrl());
        if (count($this->getData()->screenshotFiles) > 0) {
            $this->setStatus(self::STATUS_DONE);
            return;
        }
        sleep(5);

        if ($elements) {
            $name = implode('_', [$source->getPackage(), $source->getFileMark(), '{num}', 'screenshot.png']);
            foreach ($elements as $element) {
                $url = empty($element) ? null : $element->getAttribute('href');
                if (!empty($url)) {
                    $this->getData()->screenshotUrls[] = $url;
                }
            }

            foreach ($this->getData()->screenshotUrls as $key => $imageUrl) {
                $num = $key + 1;
                $num = $num > 9 ? $num : '0' . $num;
                $currentName = str_replace('{num}', $num, $name);

                $filePath = $this->getScreenshotFilePath($currentName);
                if ($filePath) {
                    $this->getData()->screenshotFiles[] = $filePath;
                    continue;
                }

                $imagePath = $this->getScreenshotDirPath() . DIRECTORY_SEPARATOR . $currentName;

                try {
                    $this->findElement('img', $imageUrl)
                        ->takeElementScreenshot($imagePath);
                    $timeLimit = time() + Time::SECONDS_IN_A_MINUTE;
                    do {
                        sleep(2);
                        $filePath = $this->getScreenshotFilePath($currentName);
                        if ($filePath) {
                            $this->getData()->screenshotFiles[] = (new ImageLibrary($imagePath))->compress()->getImagePath();
                        }
                    } while (!$filePath && time() < $timeLimit);
                } catch (\Exception $e) {
                    $this->setStatus(self::STATUS_ERROR);
                    $this->addError('Unknown error', $e);
                }
            }
            $this->setStatus(self::STATUS_DONE);
        } else {
            $this->setStatus(self::STATUS_NOT_FOUND);
            throw new \Exception('Not found element');
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function parseVersions()
    {
        $source = $this->getSource();

        $versionLinks = $this->findElements(
            $source->getSelector(SiteSource::SELECTOR_VERSION_LINKS),
            $source->getApkOldVersionsUrl()
        );
        if (count($versionLinks) > 0) {
            $this->getData()->versions = [];
            /** @var RemoteWebElement $versionLink */
            foreach ($versionLinks as $versionLink) {
                $this->getData()->versions[] = $source->getVersion($versionLink);
            }
        }
    }

    /**
     * @param int $maxCountPages
     * @param string|null $pageLink
     * @return void
     * @throws \Exception
     */
    public function parseSimilarApps(int $maxCountPages = 5, ?string $pageLink = null)
    {
        $this->setStatus(self::STATUS_PROCESS);
        $source = $this->getSource();
        $this->manageCookies($source->getAppUrl(), $source->getSelector(SiteSource::SELECTOR_PAGE));

        $elements = [];
        $maxCountPages = max($maxCountPages, 1);
        $maxCountPages = min($maxCountPages, $this->similarAppsMaxPages);
        if (!empty($source->getSimilarAppsUrl()) || !empty($pageLink)) {
            $elements = $this->findElements(
                $source->getSelector(SiteSource::SELECTOR_SIMILAR_APPS_LINKS),
                $pageLink ?: $source->getSimilarAppsUrl(),
                true
            );
        }
        if ($elements) {
            foreach ($elements as $element) {
                $url = $element->getAttribute('href');
                $url = explode('/', $url);
                $this->getData()->similarAppIds[] = array_pop($url);
            }

            $pageElement = $this->findElement(
                $source->getSelector(SiteSource::SELECTOR_SIMILAR_APPS_NEXT_PAGE)
            );
            $nextPageUrl = $pageElement->getAttribute('href');
            $display = $pageElement->getAttribute('style');

            if (!$nextPageUrl) {
                return;
            }

            $nextPageUrl = $source->getSiteUrl() . $nextPageUrl;
            if ($maxCountPages > 1 && !empty($nextPageUrl) && $display !== 'display: none;') {
                $this->parseSimilarApps($maxCountPages - 1, $nextPageUrl);
            }
            if (empty($this->getData()->similarAppIds)) {
                $this->setStatus(self::STATUS_NOT_FOUND);
            } else {
                $this->setStatus(self::STATUS_DONE);
            }
        } else {
            self::resetDriver();
            $this->setStatus(self::STATUS_NOT_FOUND);
            throw new \Exception('Not found elements');
        }
        self::resetDriver();
    }

    /**
     * @return RemoteWebDriver
     */
    public function getDriver()
    {
        if (empty(self::$browser)) {
            self::$browser = new Chrome($this->getSource()->getSourceUrl(), self::$proxy ?: null);
            register_shutdown_function(function(){
                Browser::closeSession();
            });
        }
        return self::$browser->getDriver();
    }

    public static function resetDriver()
    {
        self::$browser = null;
        self::$proxy = null;
        Browser::closeSession();
    }

    /**
     * @param string $selector
     * @param null|string $url
     * @param int $timeout
     * @param bool $forceUpdatePage
     * @return bool
     */
    private function waitElement(string $selector, ?string $url = null, int $timeout = 60, bool $forceUpdatePage = false): bool
    {
        try {
            if ($forceUpdatePage) {
                $url = empty($url) ? $this->getDriver()->getCurrentURL() : $url;
                $this->getDriver()->get($url);
            }
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL) && !$this->checkUrl($url)) {
                $this->getDriver()->get($url);
            }
            $this->getDriver()
                ->wait($timeout)
                ->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::cssSelector($selector)));
        } catch (\Exception $exception) {
            $this->setStatus(SiteParser::STATUS_ERROR);
            $this->addError('Cannot find element', $exception);
            return false;
        }
        return true;
    }

    /**
     * @param $selector
     * @param string|null $url
     * @param bool $forceUpdatePage
     * @return RemoteWebElement|false
     */
    private function findElement($selector, ?string $url = null, bool $forceUpdatePage = false)
    {
        if ($this->waitElement($selector, $url, 60, $forceUpdatePage)) {
            return $this->getDriver()->findElement(WebDriverBy::cssSelector($selector));
        }
        return false;
    }

    /**
     * @param string $selector
     * @param string|null $url
     * @param bool $forceUpdatePage
     * @return RemoteWebElement|array
     */
    private function findElements($selector, ?string $url = null, bool $forceUpdatePage = false)
    {
        if ($this->waitElement($selector, $url, 60, $forceUpdatePage)) {
            $elements = $this->getDriver()->findElements(WebDriverBy::cssSelector($selector));
            if (count($elements) > 0) {
                return $elements;
            }
        }
        return [];
    }

    /**
     * @param string $url
     * @return void
     */
    public function loadProxyForUrl(string $url)
    {
        if (!is_null(self::$proxy)) {
            return;
        }
        if ($this->withProxy && $this->getProxyList()) {
            /** @var $proxy Proxy|false */
            foreach ($this->getProxyList() as $index => $proxy) {
                if ($index >= self::PROXY_TRY_LIMIT) {
                    break;
                }
                $banned_list = [];
                $host = parse_url($url, PHP_URL_HOST);

                if ($proxy) {
                    $banned_list = json_decode($proxy->banned_list, true);

                    // skip cloudflare baned proxy
                    if (isset($banned_list[$host]) && !$banned_list[$host]) {
                        continue;
                    }

                    // reload new proxy
                    $proxy->mitmDown();
                    sleep(5);
                    $proxy->mitmUp();
                    sleep(10);
                }

                self::resetDriver();
                self::$proxy = $proxy;
                if ($this->waitElement('#header .nav_container', $url, 30, true) === false) {
                    if ($this->waitElement('.navbar .container', $url, 30, true) === false) {
                        if ($proxy) {
                            // save cloudflare proxy fail
                            $banned_list[$host] = false;
                            $proxy->banned_list = json_encode($banned_list);
                            $proxy->save(false);
                        }
                        continue;
                    }
                }
                break;
            }
        }
    }

    /**
     * @param string $name
     * @return string|false
     */
    private function getApkFilePath(string $name)
    {
        foreach (glob($this->getDownloadDirPath() . DIRECTORY_SEPARATOR . "*.{apk,xapk}", GLOB_BRACE) as $filename) {
            if (strpos($filename, $name) !== false && strpos($filename, $this->getSource()->getFileMark()) !== false) {
                if (!strpos('crdownload', $filename)) {
                    return $filename;
                }
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @return false|mixed
     * @throws Exception
     */
    private function getScreenshotFilePath(string $name)
    {
        foreach (glob($this->getScreenshotDirPath() . DIRECTORY_SEPARATOR . "*.png", GLOB_BRACE) as $filename) {
            if (strpos($filename, $name) !== false && strpos($filename, $this->getSource()->getFileMark()) !== false) {
                return $filename;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    private function getDownloadDirPath(): string
    {
        return \Yii::getAlias('@app/web') . self::DOWNLOADS_PATH;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    private function getScreenshotDirPath(): string
    {
        $path = \Yii::getAlias('@app/web') . self::DOWNLOADS_PATH . DIRECTORY_SEPARATOR . 'screenshots';
        FileHelper::createDirectory($path);
        return $path;
    }

    /**
     * @param string $url
     * @return bool
     */
    private function checkUrl(string $url): bool
    {
        $currentUrl = $this->getDriver()->getCurrentURL();
        if (substr($currentUrl, -1) === '/') {
            $currentUrl = substr($currentUrl, 0, -1);
        }
        $currentUrl = explode('/', $currentUrl);
        $url = explode('/', $url);
        $currentSlug = array_pop($currentUrl);
        $urlSlug = array_pop($url);
        return $currentSlug == $urlSlug;
    }

    /**
     * @param string $url
     * @param string $selector
     * @return void
     * @throws \Exception
     */
    private function manageCookies(string $url, string $selector)
    {
        $source = $this->getSource();
        $filename = $source->getFileMark() . '_cookies.json';
        $cookiesExist = $this->checkCookies($filename);
        $this->loadProxyForUrl($source->getAppUrl());
        file_put_contents(
            $this->getCookieFilePath($source->getFileMark() . '_test.txt'),
            'Proxy: ' . (empty(self::$proxy) ? 'none' : self::$proxy->getFullNameProxy()) . '||'
        );
        if ($cookiesExist
            && count($cookies = json_decode(file_get_contents($this->getCookieFilePath($filename)))) > 0
            && $this->waitElement($selector, $url, 60, true)
        ) {
            foreach ($cookies as $cookie) {
                try {
                    $cookieObj = new Cookie($cookie->name, $cookie->value);
                    $cookieObj->setDomain($cookie->domain);
                    $cookieObj->setPath($cookie->path);
                    $cookieObj->setExpiry($cookie->expiry);
                    $cookieObj->setSecure($cookie->secure);
                    $cookieObj->setHttpOnly($cookie->httpOnly);
                    $cookieObj->setSameSite($cookie->sameSite);
                    $this->getDriver()->manage()->addCookie($cookieObj);
                }catch (\Exception $exception){

                }
            }
        } else {
            $cookies = $this->getDriver()->manage()->getCookies();
            $cookiesArr = [];
            foreach ($cookies as $cookie) {
                $this->getDriver()->manage()->addCookie($cookie);
                $cookiesArr[] = json_encode($cookie->toArray());
            }
            file_put_contents(
                $this->getCookieFilePath($filename),
                '['.implode(', ', $cookiesArr).']'
            );
        }
    }

    /**
     * @param string $fileName
     * @return bool
     * @throws Exception
     */
    protected function checkCookies(string $fileName): bool
    {
        return file_exists($this->getCookieFilePath($fileName));
    }

    /**
     * @param string $fileName
     * @return string
     * @throws Exception
     */
    protected function getCookieFilePath(string $fileName): string
    {
        FileHelper::createDirectory($dir = \Yii::getAlias('@data/selenium'));
        return $dir . DIRECTORY_SEPARATOR . $fileName;
    }

    public function __destruct()
    {
        self::resetDriver();
        foreach (glob($this->getDownloadDirPath() . DIRECTORY_SEPARATOR . "*.crdownload", GLOB_BRACE) as $filename) {
            @unlink($filename);
        }
    }
}
