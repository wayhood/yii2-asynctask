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

    public function actionIndex($day = 30)
    {
        $days = $this->queue->getShowStat($day);
        return $this->render('index', [
            'days' => $days
        ]);
    }


} 