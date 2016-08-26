<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\helpers\Json;
use yii\web\UploadedFile;

class FileMultiple extends FileCollection implements IFileAttribute
{
    /**
     * @var string
     */
    public $item = 'paulzi\fileBehavior\File';

    /**
     * @var IFileAttribute[]
     */
    protected $deleted = [];


    /**
     * @inheritdoc
     */
    public function getValue()
    {
        $result = [];
        foreach ($this->data as $file) {
            if ($file->value !== null) {
                $result[] = $file->value;
            }
        }
        return $this->encode($result);
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function initValue($value)
    {
        $this->data = [];
        $value = $this->decode($value);
        foreach ($value as $itemValue) {
            $file = $this->createFile();
            $file->initValue($itemValue);
            $this->data[] = $file;
        }
    }

    /**
     * @inheritdoc
     */
    public function setValue($value)
    {
        if ($value === null) {
            foreach ($this->data as $item) {
                $this->deleted[] = $item;
            }
            $this->data = [];
        } else {
            foreach ($this->data as $item) {
                if (!in_array($item, $value, true)) {
                    $this->deleted[] = $item;
                }
            }
            foreach ($value as $i => $item) {
                if (is_string($item) || $item instanceof UploadedFile) {
                    $value[$i] = $this->createFile();
                    $value[$i]->setValue($item);
                } elseif (is_array($item)) {
                    if (isset($item['content'])) {
                        $content  = $item['content'];
                        $filename = isset($item['filename']) ? $item['filename'] : null;
                        $value[$i] = $this->createFile();
                        $value[$i]->setContent($content, $filename);
                    }
                }
            }
            $this->data = $value;
        }
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function offsetSet($offset, $value)
    {
        if (is_string($value)) {
            if (isset($this->data[$offset])) {
                $this->deleted[] = $this->data[$offset];
            }
            $file = $this->createFile();
            $file->setValue($value);
            $value = $file;
        }
        parent::offsetSet($offset, $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->deleted[] = $this->data[$offset];
        parent::offsetUnset($offset);
    }

    /**
     * @return bool
     */
    public function save()
    {
        $result = true;
        foreach ($this->data as $file) {
            $result = ($file->save() !== false) && $result;
        }
        foreach ($this->deleted as $file) {
            $file->setValue(null);
            $result = ($file->save() !== false) && $result;
        }
        return $result;
    }

    /**
     * @param array $value
     * @return null|string
     */
    protected function encode($value)
    {
        return count($value) ? Json::encode($value) : null;
    }

    /**
     * @param string $value
     * @return array
     */
    protected function decode($value)
    {
        return $value !== null ? Json::decode($value) : [];
    }

    /**
     * @return IFileAttribute
     */
    protected function createFile()
    {
        $options = is_string($this->item) ? ['class' => $this->item] : $this->item;
        $options = array_merge(
            [
                'fileUrl'  => $this->fileUrl,
                'filePath' => $this->filePath,
                'folder'   => $this->folder,
            ],
            $this->hashLength ? ['hashLength' => $this->hashLength] : [],
            $options
        );
        return Yii::createObject($options);
    }
}