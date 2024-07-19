<?php

use app\components\apk_parsers\ApkData;
use app\components\apk_parsers\ApkDataCollector;
use app\components\apk_parsers\parsers\SeleniumParser;
use app\components\apk_parsers\sources\ApkPure;

$apkPureSource = new ApkPure("com.example.app");
$apkPureParser = new SeleniumParser($apkPureSource);
$parseData = new ApkData();
try {
    $parseData = (new ApkDataCollector())
        ->collect($apkPureParser, ApkDataCollector::MODE_TEXT_DATA)
        ->collect($apkPureParser, ApkDataCollector::MODE_DOWNLOAD_APK)
        ->getData();
} catch (\Exception $e) {}