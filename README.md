
Module migration Yii2 controller.
===================
Runs module migrations from module 'migrations' folder.
Use it if you want to keep your module migrations inside module 'migrations' folder.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist bariew/yii2-module-migration-controller "*"
```

or add

```
"bariew/yii2-module-migration-controller": "*"
```

to the require section of your `composer.json` file.


Usage
-----
```
    Redefine migrate controller in your console config file:
    ...
    'controllerMap' => [
        'migrate'   => 'bariew\moduleMigration\ModuleMigration'
    ],
    ...

    Also define 'modules' app attribute here like in web app config file, e.g.:
    ...
    'modules'   => [
        'user'  => 'app\modules\user\Module',
        'page'  => [
            'class' => 'app\modules\base\modules\Module'
        ]
    ],
    migrations from your moduleFolder/migrations will be applied.
```

Since 1.2.0 you may use new commands:
```
./yii migrate/module-up yourModuleName
./yii migrate/module-down yourModuleName
```