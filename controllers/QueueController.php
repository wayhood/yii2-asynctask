<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;

use yii\data\ArrayDataProvider;

/**
 * Class QueueController
 * @package wh\asynctask\controllers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class QueueController extends \wh\asynctask\base\Controller
{
    public function actionIndex()
    {
        $queues = $this->queue->getQueues();

        $newQueues = [];

        foreach($queues as $queue) {
            $newQueues[] = [
                'queue' => $queue,
                'size' => $this->queue->getQueueSize($queue)
            ];
        }

        $dataProvider = new ArrayDataProvider([
            'key' => 'queue',
            'allModels' => $newQueues
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView($queue)
    {
        $list = $this->queue->getQueueList($queue, 0, 20);
        $newList = [];
        foreach($list as $row) {
            $data = json_decode($row, true);
            $newList[] = [
                'job_id' => $data['job_id'],
                'class' => $data['class'],
                'arguments' => $data['args'],
                'queue' => $data['queue'],
                'content' => $row
            ];
        }

        $dataProvider = new ArrayDataProvider([
            'key' => 'job_id',
            'allModels' => $newList
        ]);

        return $this->render('view', ['queue' => $queue, 'dataProvider' => $dataProvider]);
    }

    public function actionDeleteQueue($id)
    {
        $this->queue->removeQueue($id);
        return $this->redirect(['index']);
    }

    public function actionDeleteItem($id, $queue)
    {
        $data = base64_decode($id);
        $this->queue->removeQueueItem($queue, $data);
        return $this->redirect(['view', 'queue' => $queue]);

    }
} 