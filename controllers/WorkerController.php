<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;

use yii\data\ArrayDataProvider;

/**
 * Class WorkerController
 * @package wh\asynctask\controllers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class WorkerController extends \wh\asynctask\base\Controller
{
    public function actionIndex()
    {
        $workers = $this->queue->getWorkerList();

        $newWorkers = [];

        foreach($workers as $identity) {
            $info = $this->queue->getWorkerInfo($identity);
            if (is_null($info)) {
                break;
            }
            $hash = json_decode($info, true);

            $newWorkers[] = [
                //'job_id' => $hash['playload']['job_id'],
                'worker' => $identity,
                'started' => $this->queue->getWorkerStarted($identity),
                'arguments' => $hash['playload']['args'],
                'queue' => $hash['queue'],
                'class' => $hash['playload']['class'],
            ];
        }
        $dataProvider = new ArrayDataProvider([
            'key' => 'worker',
            'allModels' => $newWorkers
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }


    public function actionRetry($id)
    {
        //echo $id;exit;
        $info = @json_decode($this->queue->getWorkerInfo($id), true);
        if (isset($info['payload'])) {
            $data = $info['payload'];
            $this->queue->setRetry([
                'retry' => $data['retry'],
                'class' => $data['class'],
                'args' => $data['args'],
                'enqueued_at' => $data['enqueued_at'],
                'error_message' => 'worker faild',
                'failed_at' => date('Y-m-d H:i:s'),
                'job_id' => $data['job_id'],
                'queue' => $data['queue'],
                'retried_at' => isset($data['retried_at']) ? $retried_at : date('Y-m-d H:i:s'),
                'retry_count' => isset($data['retry_count']) ? intval($data['retry_count'])+1 : 0,
            ]);
            $this->queue->setWorkerEnd($id);
        }
        $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $this->queue->setWorkerEnd($id);
        $this->redirect(['index']);
    }
} 