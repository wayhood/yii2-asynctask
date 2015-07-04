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
class Module extends \yii\base\Module
{
    public $redis = 'redis';

    private $_workerLogPath = null;

    public function setWorkerLogPath($path)
    {
        $this->_workerLogPath = Yii::getAlias($path);
    }

    public function getWorkerLogPath()
    {
        if (is_null($this->_workerLogPath)) {
            $this->_workerLogPath = Yii::$app->getRuntimePath();
        }
        return $this->_workerLogPath;
    }
}