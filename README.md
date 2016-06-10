Async Task module for Yii2
=========
a async task process module using redis for yii2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist wayhood/yii2-asynctask "*"
```

or add

```
"wayhood/yii2-asynctask": "*"
```

to the require section of your `composer.json` file.


Usage
-----

To use this module in a web application. simply add the following code in your application configuration:

```php
reutrn [
    'bootstrap' => [..., 'asynctask'],
    'modules' => [
        'asynctask' => [
            'class' => 'wh\asynctask\Module',
            'redis' => 'redis' /* or [
            	'class' => 'yii\redis\Connection',
            	'hostname' => 'localhost',
            	'port' => 6379,
            	'database' => 0,
            ]*/
        ]
    ],
    ...
];
```

http://path/to/index.php?r=asynctask  

To use this module in a console application. like to web application

run

```php
./yii asynctask "a, b, c"  
````

a, b and c was queue name.


CREATE WORKER FILE

```php
<?php
/**
 * @link http://www.wayhood.com/
 */

namespace frontend\workers;

/**
 * Class TestWorker
 * @package frontend\workers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class TestWorker extends \wh\asynctask\Worker
{
    protected static $queue = 'abc'; //a queue name
    
    protected static $redis = 'reids' //or a configure array.

    public static function run($a, $b)
    {
        var_dump($a); //real process code
        var_dump($b);
    }
}
```


CALL THE WORKER IN CONTROLLER OR MODEL AND ANYWHERE.

```
// run one time
    \frontend\workers\TestWorker::runAysnc('a', 'b');
// run after 10 sec
    \frontend\workers\TestWorker::runIn('10s' 'a', 'b');
// run each 10 min
    \frontend\workers\TestWorker::runEach('10m' 'a', 'b');
```
