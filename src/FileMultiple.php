<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\base\NotSupportedException;
use yii\helpers\Json;

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
            $options = is_string($this->item) ? ['class' => $this->item] : $this->item;
            $options = array_merge([
                'fileUrl'  => $this->fileUrl,
                'filePath' => $this->filePath,
            ], $options);
            $file = Yii::createObject($options);
            $file->initValue($itemValue);
            $this->data[] = $file;
        }
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function setValue($value)
    {
        if ($value === null) {
            foreach ($this->data as $item) {
                $this->deleted[] = $item;
            }
            $this->data = [];
        } else {
            throw new NotSupportedException;
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
            $options = is_string($this->item) ? ['class' => $this->item] : $this->item;
            $options = array_merge([
                'fileUrl'  => $this->fileUrl,
                'filePath' => $this->filePath,
            ], $options);
            $file = Yii::createObject($options);
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
            $result = $file->save() !== false && $result;
        }
        foreach ($this->deleted as $file) {
            $file->setValue(null);
            $result = $file->save() !== false && $result;
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
}