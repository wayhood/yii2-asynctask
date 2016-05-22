<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\console;

use wh\asynctask\Queue;
use Yii;

/**
 * Class AsyncTaskController
 * @package wh\asynctask\console
 * @author Song Yeung <netyum@163.com>
 * @date 12/21/14
 */
class AsyncTaskQuickController extends \yii\console\Controller
{
    /**
     * 处理一个Worker
     * @param string $q queue name
     */
    public function actionWorker($q="default")
    {
        $retried_at = date('Y-m-d H:i:s');

        $queue = Yii::createObject([
            'class' => 'wh\asynctask\Queue',
            'redis' => $this->module->redis
        ]);

        while(1) {
                $data = $queue->pop($q);
                if (!is_null($data)) {
                        forward_static_call_array([$data['class'], 'run'], $data['args']);
                }
        }
    }
}
