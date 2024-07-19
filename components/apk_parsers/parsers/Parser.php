<?php

namespace app\components\apk_parsers\parsers;

interface Parser
{
    const STATUS_START = 1;
    const STATUS_PROCESS = 2;
    const STATUS_DONE = 3;
    const STATUS_NOT_FOUND = 4;
    const STATUS_ERROR = 5;

    public function parse();
}