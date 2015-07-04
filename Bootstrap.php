<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask;

use yii\base\BootstrapInterface;
use yii\i18n\PhpMessageSource;

class Bootstrap implements BootstrapInterface
{
    /** @inheritdoc */
    public function bootstrap($app)
    {
        if (!isset($app->get('i18n')->translations['asynctask*'])) {
            $app->get('i18n')->translations['asynctask*'] = [
                'class'    => PhpMessageSource::className(),
                'basePath' => __DIR__.'/messages',
            ];
        }

        if ($app instanceof \yii\web\Application) {
            $app->getUrlManager()->addRules([
                'asynctask' => 'asynctask/default/index',
                'asynctask/<id:\w+>' => 'asynctask/default/view',
                'asynctask/<controller:[\w\-]+>/<action:[\w\-]+>' => 'asynctask/<controller>/<action>',
            ], false);
        } elseif ($app instanceof \yii\console\Application) {
            $app->controllerMap['asynctask'] = [
                'class' => 'wh\asynctask\console\AsyncTaskController',
                'module' => $app->getModule('asynctask')
            ];
        }

    }
}