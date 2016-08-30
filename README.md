# Yii2 file and image helper

File store and image thumbs behavior for Yii2.

[![Packagist Version](https://img.shields.io/packagist/v/paulzi/yii2-file-behavior.svg)](https://packagist.org/packages/paulzi/yii2-file-behavior)
[![Total Downloads](https://img.shields.io/packagist/dt/paulzi/yii2-file-behavior.svg)](https://packagist.org/packages/paulzi/yii2-file-behavior)

## Features

- single and multiple file store for ActiveRecord
- multiple image thumbs without extra db fields
- user-friendly API
- flexible class inheritance
- support yii alias and different location of web/real path

## Install

Install via Composer:

```bash
composer require paulzi/yii2-file-behavior:~1.0.0
```

or add

```bash
"paulzi/yii2-file-behavior" : "~1.0.0"
```

to the `require` section of your `composer.json` file.

## Usage

Use FileBehavior in model and fill attributes option:

```php
class Sample extends \yii\db\ActiveRecord
{
    public function behaviors() {
        return [
            [
                'class' => 'paulzi\fileBehavior\FileBehavior',
                'path'  => '@webroot/files',
                'url'   => '@web/files',
                'attributes' => [
                    'file'  => [],
                    'files' => [
                        'class' => 'paulzi\fileBehavior\FileMultiple',
                    ],
                    'image' => [
                        'class' => 'paulzi\fileBehavior\Image',
                        'types' => [
                            'original' => [1200, 1200],
                            'mid'      => [400, 400],
                            'thm'      => [120, 120],
                        ],
                    ],
                    'images' => [
                        'class' => 'paulzi\fileBehavior\FileMultiple',
                        'item'  => [
                            'class' => 'paulzi\fileBehavior\Image',
                            'types' => [
                                'thm'  => [120, 120],
                            ],
                        ]
                    ],
                ],
            ],
        ];
    }
}
```

### Set files
```php
$model = Sample::findOne(1);
$file  = UploadedFile::getInstance($model, 'file');
$model->file->value = $file->tempName;
$model->save();

$model = Sample::findOne(2);
$files = UploadedFile::getInstances($model, 'images');
foreach ($files as $file) {
    $model->images[] = $file->tempName;
}
$model->save();
```

### Get files
```php
$model = Sample::findOne(1);
$url   = $model->file->url;
$path  = $model->file->path;

$model = Sample::findOne(2);
foreach ($model->images as $image) {
    echo $image->url;      // original image url
    echo $image->thm->url; // thm image url
}
```

### Remove files
```php
$model = Sample::findOne(1);
$model->file->value = null;
$model->save();

$model = Sample::findOne(1);
$model->files[2]->value = null;
$model->save();

$model = Sample::findOne(2);
$model->images->value = null;
$model->save();
```

### Image salt

To generate a thumbnail file name is using a hash of the file name and type of thumbnail. If you need to protect the possibility of obtaining different types of thumbnail, set options salt by secret:
```php
    public function behaviors() {
        return [
            [
                'class' => 'paulzi\fileBehavior\FileBehavior',
                'attributes' => [
                    'image' => [
                        'class' => 'paulzi\fileBehavior\Image',
                        'salt'  => 'secret',
                        'types' => [
                            'mid'      => [400, 400],
                            'thm'      => [120, 120],
                        ],
                    ],
                ],
            ],
        ];
    }
```

## Set options globally

You can set salt, path and url options globally by using [Dependency Injection](http://www.yiiframework.com/doc-2.0/guide-concept-di-container.html):

`config\main.php`:
```php
    'aliases' => [
        '@cdnWeb' => 'http://s.example.com',
    ],

    'on beforeRequest' => function () {
        \Yii::$container->set('paulzi\fileBehavior\FileBehavior', [
            'path' => '@cdn\web\files',
            'url'  => '@cdnWeb\web\files',
        ]);
        \Yii::$container->set('paulzi\fileBehavior\Image', [
            'salt' => Yii::$app->params['salt'],
        ]);
    },
```

`config\params-local.php`:
```php
    'salt' => 'secret salt',
```

## Extending

You can extend classes for change path building function or change file storing.

By default, files are stores in `{path}/{folder}/{12}/{12}/{1234567890abcdef1234567890ab}.{extension}`