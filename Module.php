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

    public function bootstrap($app)
    {
        if ($app instanceof \yii\web\Application) {
            $app->getUrlManager()->addRules([
                $this->id => $this->id .'/default/index',
                $this->id .'/<id:\w+>' => $this->id .'/default/view',
                $this->id .'/<controller:[\w\-]+>/<action:[\w\-]+>' => $this->id .'/<controller>/<action>',
            ], false);
        } elseif ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->id] = [
                'class' => 'wh\asynctask\console\AsyncTaskController',
                'module' => $this
            ];
            $app->controllerMap[$this->id.'-quick'] = [
                'class' => 'wh\asynctask\console\AsyncTaskQuickController',
                'module' => $this
            ];
        }
    }

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
