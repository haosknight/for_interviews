<?php

namespace app\components\apk_parsers\parsers;

use app\components\apk_parsers\ApkData;
use app\components\apk_parsers\sources\AbstractSource;
use yii\base\InvalidConfigException;

abstract class AbstractParser implements Parser
{
    /** @var bool */
    public bool $isHigherVersion = true;

    /** @var int */
    private int $_status;

    /** @var ApkData */
    private ApkData $_data;

    /** @var AbstractSource */
    private AbstractSource $_source;

    /** @var array */
    private array $_errors = [];

    abstract function parse();

    public function __construct(AbstractSource $source)
    {
        if (empty($source)) {
            throw new InvalidConfigException("Property `source` must be set!");
        }
        $this->_source = $source;
        $this->setStatus(self::STATUS_START);
    }

    /**
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_START,
            self::STATUS_PROCESS,
            self::STATUS_DONE,
            self::STATUS_NOT_FOUND,
            self::STATUS_ERROR,
        ];
    }

    /**
     * @return ApkData
     */
    public function getData(): ApkData
    {
        if (empty($this->_data)) {
            $this->_data = new ApkData(['id' => $this->_source->getPackage()]);
        }
        if (empty($this->_data->id)) {
            $this->_data->id = $this->_source->getPackage();
        }
        return $this->_data;
    }

    /**
     * @param ApkData $data
     */
    public function setData(ApkData $data): void
    {
        $this->_data = $data;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->_status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        if (in_array($status, self::getStatusList())) {
            $this->_status = $status;
        }
    }

    /**
     * @return AbstractSource
     */
    public function getSource(): AbstractSource
    {
        return $this->_source;
    }

    /**
     * @param mixed|null $currentValue
     * @param mixed|null $receivedValue
     * @return mixed
     */
    public function choiceValue($currentValue, $receivedValue)
    {
        if (empty($currentValue) && !is_bool($currentValue)) {
            return $receivedValue;
        }
        if (empty($receivedValue) && !is_bool($receivedValue)) {
            return $currentValue;
        }

        if ($this->isHigherVersion) {
            $value = $receivedValue ?: $currentValue;
        } else {
            $value = $currentValue ?: $receivedValue;
        }
        return $value;
    }

    /**
     * @param ?string $parseVersion
     * @return bool
     */
    protected function isHigherVersion(?string $parseVersion): bool
    {
        if (empty($this->getData()->version)) {
            return true;
        }
        if (empty($parseVersion)) {
            return false;
        }

        $currentVersionParts = explode('.', $this->getData()->version);
        $parseVersionParts = explode('.', $parseVersion);

        $maxComponents = max(count($currentVersionParts), count($parseVersionParts));

        // Дополняем версии нулями, чтобы сравнивать одинаковое количество компонентов
        while (count($currentVersionParts) < $maxComponents) {
            $currentVersionParts[] = 0;
        }
        while (count($parseVersionParts) < $maxComponents) {
            $parseVersionParts[] = 0;
        }

        for ($i = 0; $i < $maxComponents; $i++) {
            $current = (int)$currentVersionParts[$i];
            $parse = (int)$parseVersionParts[$i];

            if ($current < $parse) {
                return true;
            } elseif ($current > $parse) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param string $message
     * @param \Exception|null $exception
     * @return void
     */
    protected function addError(string $message, ?\Exception $exception = null)
    {
        $this->_errors[] = ['message' => $message, 'exception' => $exception];
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }
}