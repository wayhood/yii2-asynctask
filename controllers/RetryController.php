<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;

use yii\data\ArrayDataProvider;

/**
 * Class RetryController
 * @package wh\asynctask\controllers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class RetryController extends \wh\asynctask\base\Controller
{
    public function actionIndex()
    {
        $retries = $this->queue->getAllRetries();

        $newRetries = [];

        foreach($retries as $key => $row) {
            if ($key % 2 != 0) {
                $newRetries[$key-1]['next_retry'] = $row;
            } else {
                $data = json_decode($row, true);
                $newRetries[$key] = [
                    'job_id' => $data['job_id'],
                    'retry_count' => $data['retry_count'],
                    'queue' => $data['queue'],
                    'worker' => $data['class'],
                    'arguments' => $data['args'],
                    'error' => $data['error_message']
                ];
            }
        }

        $dataProvider = new ArrayDataProvider([
            'key' => 'job_id',
            'allModels' => $newRetries
        ]);
        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView($queue)
    {

    }
}