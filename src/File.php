<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\helpers\FileHelper;

/**
 * @property string $url
 * @property string $path
 */
class File extends Component implements IFileAttribute
{
    /**
     * @var string
     */
    public $filePath;

    /**
     * @var string
     */
    public $fileUrl;

    /**
     * @var string
     */
    private $_value;

    /**
     * @var array
     */
    protected $newValue;


    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @inheritdoc
     */
    public function initValue($value)
    {
        $this->_value = $value;
    }

    /**
     * @inheritdoc
     * @param bool $copy
     */
    public function setValue($value, $copy = true)
    {
        $this->newValue = [Yii::getAlias($value), $copy];
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return Yii::getAlias($this->fileUrl . '/' . $this->_value);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return Yii::getAlias($this->filePath . '/' . $this->_value);
    }

    /**
     * @return bool|null
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function save()
    {
        if (!$this->newValue) {
            return null;
        }
        list($file, $copy) = $this->newValue;

        if ($file !== null) {
            if (!is_readable($file)) {
                throw new InvalidValueException("{$file} is not readable");
            }
            $ext     = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $value   = $this->buildPath($ext);
            $success = $this->setFile($file, $value, $copy);
        } else {
            $value   = null;
            $success = true;
        }

        if ($success) {
            if ($this->_value !== null && $value !== $this->_value && is_writable($this->getPath())) {
                $this->deleteFile($this->getPath());
            }
            $this->_value   = $value;
            $this->newValue = null;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $extension
     * @return string
     */
    protected function buildPath($extension = null)
    {
        $result = [];
        $hash   = bin2hex(Yii::$app->security->generateRandomKey(16));
        $result[] = substr($hash, 0, 2);
        $result[] = substr($hash, 2, 2);
        $result[] = substr($hash, 4, 28);
        return implode('/', $result) . ($extension ? ".{$extension}" : null);
    }

    /**
     * @param string $file
     * @param $value
     * @param $copy
     * @return bool
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    protected function setFile($file, &$value, $copy)
    {
        if (!file_exists(Yii::getAlias($this->filePath))) {
            throw new InvalidConfigException(Yii::getAlias($this->filePath) . " directory not exists");
        }
        $path = Yii::getAlias($this->filePath . '/' . $value);
        @FileHelper::createDirectory(dirname($path), 0755, true);
        return $copy ? copy($file, $path) : rename($file, $path);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function deleteFile($path)
    {
        $result = unlink($path);
        for ($i = 0; $i < 2; $i++) {
            $path = dirname($path);
            $iterator = new \FilesystemIterator($path);
            if (!$iterator->valid()) {
                @FileHelper::removeDirectory($path);
            }
        }
        return $result;
    }
}