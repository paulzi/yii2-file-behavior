<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use Imagine\Image\ManipulatorInterface;

class Image extends File
{
    /**
     * @var string
     */
    public $file = 'paulzi\fileBehavior\File';

    /**
     * @var array
     */
    public $types = [];

    /**
     * @var string
     */
    public $salt;

    /**
     * @var IFileAttribute[]
     */
    protected $data;


    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->types[$name])) {
            return $this->getType($name);
        }
        return parent::__get($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        if (isset($this->types[$name])) {
            return true;
        }
        return parent::__isset($name);
    }

    /**
     * @param string $type
     */
    public function makeImage($type)
    {
        if (!isset($this->types[$type])) {
            throw new InvalidParamException;
        }
        if ($this->value !== null) {
            $options = $this->types[$type];
            $width   = ArrayHelper::remove($options, 0);
            $height  = ArrayHelper::remove($options, 1);
            $mode    = !empty($options['mode']) ? $options['mode'] : ManipulatorInterface::THUMBNAIL_OUTBOUND;
            if ($type === 'original') {
                $path = $this->getPath();
            } else {
                $path = Yii::getAlias($this->filePath . '/' . $this->buildImagePath($type));
            }
            \yii\imagine\Image::thumbnail($this->getPath(), $width, $height, $mode)->save($path);
        }
    }

    /**
     */
    public function makeImages()
    {
        foreach ($this->types as $type => $options) {
            $this->makeImage($type);
        }
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        foreach ($this->types as $type => $options) {
            $this->getType($type);
        }
        if (parent::save() === true) {
            foreach ($this->data as $type => $file) {
                $file->setValue(null);
                $file->save();
            }
            $this->data = [];
            $this->makeImages();
        }
    }

    /**
     * @param string $type
     * @return string
     */
    protected function buildImagePath($type)
    {
        $value  = $this->getValue();
        if ($value === null) {
            return null;
        }
        $pi     = pathinfo($value);
        $result = $pi['dirname'] . '/' . substr(md5($pi['filename'] . $type . $this->salt), 0, 28);
        if ($pi['extension']) {
            $result .= '.' . $pi['extension'];
        }
        return $result;
    }

    /**
     * @param string $type
     * @return IFileAttribute
     * @throws \yii\base\InvalidConfigException
     */
    protected function getType($type)
    {
        if (!isset($this->data[$type])) {
            $options = is_string($this->file) ? ['class' => $this->file] : $this->file;
            $options = array_merge([
                'fileUrl'  => $this->fileUrl,
                'filePath' => $this->filePath,
            ], $options);
            $file = Yii::createObject($options);
            if ($type === 'original') {
                $file->initValue($this->getPath());
            } else {
                $file->initValue($this->buildImagePath($type));
            }
            $this->data[$type] = $file;
        }
        return $this->data[$type];
    }
}