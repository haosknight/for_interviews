<?php
namespace app\components\apk_parsers\sources;

interface Source
{
    public function getSiteUrl(): string;
}