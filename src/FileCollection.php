<?php
namespace paulzi\fileBehavior;

use yii\base\Component;

class FileCollection extends Component implements \ArrayAccess, \Iterator, \Countable
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
     * @var IFileAttribute[]
     */
    protected $data = [];


    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        return reset($this->data);
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return key($this->data) !== null;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->data);
    }
}