<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * @property string $url
 * @property string $path
 */
class File extends Component implements IFileAttribute
{
    const METHOD_MOVE        = 0;
    const METHOD_COPY        = 1;
    const METHOD_SET_CONTENT = 2;

    /**
     * @var string|callable
     */
    public $filePath;

    /**
     * @var string|callable
     */
    public $fileUrl;

    /**
     * @var string|callable
     */
    public $folder;

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
        $value = is_string($value) ? Yii::getAlias($value) : $value;
        $this->newValue = [$value, $copy ? self::METHOD_COPY : self::METHOD_MOVE, null, null];
    }

    /**
     * @param string $data
     * @param string $filename
     */
    public function setContent($data, $filename = null)
    {
        $this->newValue = [null, self::METHOD_SET_CONTENT, $data, $filename];
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $fileUrl = is_string($this->fileUrl) ? $this->fileUrl : call_user_func($this->fileUrl, $this);
        $folder  = is_string($this->folder)  ? $this->folder  : call_user_func($this->folder,  $this);
        return Yii::getAlias($fileUrl . ($folder ? '/' . $folder : null) . '/' . $this->_value);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $filePath = is_string($this->filePath) ? $this->filePath : call_user_func($this->filePath, $this);
        $folder   = is_string($this->folder)  ? $this->folder  : call_user_func($this->folder,  $this);
        return Yii::getAlias($filePath . ($folder ? '/' . $folder : null) . '/' . $this->_value);
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
        list($file, $copy, $content, $filename) = $this->newValue;

        if ($file !== null) {
            if ($file instanceof UploadedFile) {
                $ext  = strtolower($file->extension);
                $file = $file->tempName;
            } else {
                $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            }
            if (!is_readable($file)) {
                throw new InvalidValueException("{$file} is not readable");
            }
            $value   = $this->buildPath($ext);
            $success = $this->setFile($file, $value, $copy);
        } elseif ($content !== null) {
            $ext     = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $value   = $this->buildPath($ext);
            $success = $this->setFile($content, $value, $copy);
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
        $filePath = is_string($this->filePath) ? $this->filePath : call_user_func($this->filePath, $this);
        $folder   = is_string($this->folder)   ? $this->folder   : call_user_func($this->folder,   $this);
        if (!file_exists(Yii::getAlias($filePath))) {
            throw new InvalidConfigException(Yii::getAlias($filePath) . " directory not exists");
        }
        $filePath .= $folder ? '/' . $folder : null;
        $path = Yii::getAlias($filePath . '/' . $value);
        @FileHelper::createDirectory(dirname($path), 0755, true);
        if ($copy === self::METHOD_SET_CONTENT) {
            return file_put_contents($path, $file) !== false;
        } else {
            return $copy ? copy($file, $path) : rename($file, $path);
        }
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