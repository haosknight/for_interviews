<?php

namespace app\components\apk_parsers;

class ParserHelper
{
    /**
     * @param string|null $currentValue
     * @param string|null $newValue
     * @return string|null
     */
    public static function stringCompare(?string $currentValue, ?string $newValue): ?string
    {
        if (empty($newValue)) {
            return $currentValue;
        }
        return strcmp($currentValue, $newValue) < 0 ? $newValue : $currentValue;
    }

    /**
     * @param array|null $currentValue
     * @param string|array|null $newValue
     * @return array|null
     */
    public static function arrayCompare(?array $currentValue, $newValue): ?array
    {
        if (empty($newValue)) {
            return $currentValue;
        }
        $currentValue = empty($currentValue) ? [] : $currentValue;
        $currentValue = is_array($currentValue) ? $currentValue : [$currentValue];
        if (is_array($newValue)) {
            foreach ($newValue as $item) {
                if (is_array($item)) {
                    $currentValue = self::arrayCompare($currentValue, $item);
                } else {
                    if (!in_array($item, $currentValue)) {
                        $currentValue[] = $item;
                    }
                }
            }
        } else {
            if (!in_array($newValue, $currentValue)) {
                $currentValue[] = $newValue;
            }
        }
        return $currentValue;
    }
}