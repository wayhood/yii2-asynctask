<?php
/**
 * @link http://www.wayhood.com/
 */

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Retries';
?>
<div>
    <p>&nbsp; </p>
    <p>
        <h3><?=Html::encode($this->title);?></h3>
    </p>
    <?= GridView::widget([
        'layout' => "\n{items}",
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'class' => 'yii\grid\CheckboxColumn',
            ],
            [
                'header' => 'Next Retry',
                'value' => function($data) {
                    return date('Y-m-d H:i:s', intval($data['next_retry']));
                }
            ],
            [
                'header' => 'Retry Count',
                'value' => function($data) {
                    return intval($data['retry_count']);
                }
            ],
            'queue',
            'worker',
            [
                'class' => 'yii\grid\DataColumn',
                'header' => 'Arguments',
                'value' => function($data) {
                    return json_encode($data['arguments']);
                }
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'header' => 'Arguments',
                'value' => function($data) {
                    return \yii\helpers\Html::encode($data['error']);
                }
            ]
            /*[
                'class' => 'yii\grid\ActionColumn',
            ],*/
        ],
    ]); ?>
</div>