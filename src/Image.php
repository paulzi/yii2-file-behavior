<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;

/**
 * @property $typesCurrent
 */
class Image extends File
{
    /**
     * @var string
     */
    public $file = 'paulzi\fileBehavior\File';

    /**
     * @var array|\Closure
     */
    public $types = [];

    /**
     * @var array
     */
    public $resizeExtensions = ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'webp'];

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
        if (isset($this->typesCurrent[$name])) {
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
        if (isset($this->typesCurrent[$name])) {
            return true;
        }
        return parent::__isset($name);
    }

    /**
     * @param string $type
     */
    public function makeImage($type)
    {
        if (!isset($this->typesCurrent[$type])) {
            throw new InvalidParamException;
        }
        if ($this->value !== null) {
            $options = $this->typesCurrent[$type];
            $width   = ArrayHelper::remove($options, 0);
            $height  = ArrayHelper::remove($options, 1);
            if ($type === 'original') {
                $path = $this->getPath();
            } else {
                $filePath = is_string($this->filePath) ? $this->filePath : call_user_func($this->filePath, $this);
                $folder   = is_string($this->folder)  ? $this->folder  : call_user_func($this->folder,  $this);
                $path     = Yii::getAlias($filePath . ($folder ? '/' . $folder : null) . '/' . $this->buildImagePath($type));
            }
            $origExt = pathinfo($this->getPath(), PATHINFO_EXTENSION);
            if (in_array($origExt, $this->resizeExtensions)) {
                $saveOptions = isset($options['saveOptions']) ? $options['saveOptions'] : [];
                $ext = isset($options['ext']) ? $options['ext'] : null;
                if ($ext === 'webp' && !empty($options['webpGd2'])) {
                    $tmp  = sys_get_temp_dir() . '/' . uniqid('webp') . '.png';
                    ImageHelper::resizeCustom($this->getPath(), $width, $height, $options)
                        ->strip()
                        ->save($tmp);
                    $img = imagecreatefrompng($tmp);
                    imagepalettetotruecolor($img);
                    imagewebp($img, $path, isset($saveOptions['webp_quality']) ? $saveOptions['webp_quality'] : 75);
                    unlink($tmp);
                } else {
                    ImageHelper::resizeCustom($this->getPath(), $width, $height, $options)
                        ->strip()
                        ->save($path, $saveOptions);
                }
            }
        }
    }

    /**
     * @param $filename
     * @param $new
     * @return string
     */
    public function replaceExtension($filename, $new)
    {
        $info = pathinfo($filename);
        return ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '') . $info['filename'] . '.' . $new;
    }

    /**
     */
    public function makeImages()
    {
        foreach ($this->typesCurrent as $type => $options) {
            $this->makeImage($type);
            gc_collect_cycles();
        }
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        foreach ($this->typesCurrent as $type => $options) {
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
        $length = (array)$this->hashLength;
        $length = end($length);
        $result = $pi['dirname'] . '/' . substr(md5($pi['filename'] . $type . $this->salt), 0, $length);
        if ($pi['extension']) {
            $result .= '.' . $pi['extension'];
        }
        $options = !empty($this->typesCurrent[$type]) ? $this->typesCurrent[$type] : [];
        $ext = isset($options['ext']) ? $options['ext'] : null;
        if ($ext) {
            $result = $this->replaceExtension($result, $ext);
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
                'folder'   => $this->folder,
            ], $options);
            $file = Yii::createObject($options);
            if ($type === 'original') {
                $file->initValue($this->getValue());
            } else {
                $file->initValue($this->buildImagePath($type));
            }
            $this->data[$type] = $file;
        }
        return $this->data[$type];
    }

    /**
     * @return array
     */
    protected function getTypesCurrent()
    {
        return is_array($this->types) ? $this->types : call_user_func($this->types);
    }
}