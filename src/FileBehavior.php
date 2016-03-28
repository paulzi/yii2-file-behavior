<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;
use yii\helpers\FileHelper;

/**
 * @property \yii\db\BaseActiveRecord $owner
 */
class FileBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $folder;


    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_FIND    => 'afterFind',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            BaseActiveRecord::EVENT_AFTER_INSERT  => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE  => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_DELETE  => 'afterDelete',
        ];
    }

    /**
     * @param BaseActiveRecord $owner
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if ($this->folder === null) {
            $this->folder = $owner->getTableSchema()->name;
        }

        $attributes = [];
        foreach ($this->attributes as $attribute => $options) {
            if (is_int($attribute)) {
                $attributes[$options] = [];
            } else {
                $attributes[$attribute] = $options;
            }

        }
        $this->attributes = $attributes;
    }

    /**
     */
    public function afterFind()
    {
        $this->unSerialize();
    }

    /**
     */
    public function beforeSave()
    {
        if (!file_exists(Yii::getAlias($this->path))) {
            throw new InvalidConfigException(Yii::getAlias($this->path) . " directory not exists");
        }

        $path = Yii::getAlias($this->path . ($this->folder ? '/' . $this->folder : null));
        @FileHelper::createDirectory($path, 0755, true);

        foreach ($this->attributes as $attribute => $options) {
            $file = $this->owner->getAttribute($attribute);
            if ($file instanceof IFileAttribute) {
                $file->save();
            }
        }
        $this->serialize();
    }

    /**
     * @param \yii\db\AfterSaveEvent $event
     */
    public function afterSave($event)
    {
        $this->unSerialize();
    }

    /**
     */
    public function afterDelete()
    {
        foreach ($this->attributes as $attribute => $options) {
            $file = $this->owner->getAttribute($attribute);
            if ($file instanceof IFileAttribute) {
                $file->setValue(null);
                $file->save();
            }
        }
    }

    /**
     */
    protected function serialize()
    {
        foreach ($this->attributes as $attribute => $options) {
            $file = $this->owner->getAttribute($attribute);
            if ($file instanceof IFileAttribute) {
                $this->owner->setAttribute($attribute, $file->getValue());
            }
        }
    }

    /**
     */
    protected function unSerialize()
    {
        foreach ($this->attributes as $attribute => $options) {
            $options = array_merge([
                'class'    => File::className(),
                'filePath' => $this->path . ($this->folder ? '/' . $this->folder : null),
                'fileUrl'  => $this->url . ($this->folder ? '/' . $this->folder : null),
            ], $options);
            $file = Yii::createObject($options);
            $file->initValue($this->owner->getAttribute($attribute));
            $this->owner->setAttribute($attribute, $file);
        }
    }
}