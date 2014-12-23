<?php
/**
 * @link http://www.wayhood.com/
 */

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Queues';

?>
<div>
    <p>&nbsp;</p>
    <p>
    <h3> Current messages in <?=$queue;?> </h3>
    </p>
    <?= GridView::widget([
        'layout' => "\n{items}",
        'dataProvider' => $dataProvider,

        'columns' => [
            'class',
            [
                'class' => 'yii\grid\DataColumn',
                'header' => 'Arguments',
                'value' => function($data) {
                        return json_encode($data['arguments']);
                    }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Actions',
                'template' => '{delete-item}',
                'buttons' => [
                    'delete-item' => function ($url, $model) {
                            $url = \yii\helpers\Url::to([
                                'delete-item',
                                'queue' => $model['queue'],
                                'id' => base64_encode($model['content'])
                            ]);
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                                'title' => Yii::t('yii', 'Delete'),
                                'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                'data-method' => 'post',
                                'data-pjax' => '0',
                            ]);
                        }
                ]
            ]
        ],
    ]); ?>
</div>