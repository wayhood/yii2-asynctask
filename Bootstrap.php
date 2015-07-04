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
                $this->id => $this->id . '/default/index',
                $this->id . '/<id:\w+>' => $this->id . '/default/view',
                $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>' => $this->id . '/<controller>/<action>',
            ], false);
        } elseif ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->id] = [
                'class' => 'wh\asynctask\console\AsyncTaskController',
                'module' => $this,
            ];
        }

    }
}