<?php
namespace paulzi\fileBehavior;

/**
 * @property string $value
 */
interface IFileAttribute
{
    /**
     * @return string
     */
    public function getValue();

    /**
     * @param string $value
     */
    public function initValue($value);

    /**
     * @param mixed $value
     */
    public function setValue($value);

    /**
     * @return bool
     */
    public function save();
}