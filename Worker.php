<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask;

use Yii;

/**
 * Class Worker
 * @package wh\asynctask
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class Worker extends \yii\base\Component
{
    // a queue name
    protected static $queue = 'default';

    // a yii2-redis application component name or configure array
    protected static $redis = 'redis';

    protected static $retry = true;

    /**
     * async process. please feature in subclass.
     * @return mixed
     */
    public static function run()
    {
    }

    /**
     * async push queue
     */
    public static function runAsync()
    {
        $payload = [
            'retry' => static::$retry,
            'queue' => static::$queue,
            'class' => static::className(),
            'args' => func_get_args(),
            'job_id' => self::getRandomId(),
            'enqueued_at' => microtime(true)
        ];

        $queue = Yii::createObject([
            'class' => 'wh\asynctask\Queue',
            'redis' => static::$redis
        ]);

        $queue->push(static::$queue, $payload);
    }

    protected static function getRandomId()
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, 32);
    }
}