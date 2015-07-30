<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;

use Yii;

/**
 * Class DefaultController
 * @package wh\asynctask\controllers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class DefaultController extends \wh\asynctask\base\Controller
{
    public function init()
    {
        parent::init();
    }

    public function actionIndex($days = 30)
    {
        $days = $this->queue->getShowStat($days);
        return $this->render('index', [
            'days' => $days
        ]);
    }
}