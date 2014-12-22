<?php
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

$asset = wh\asynctask\AsynctaskAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?php
NavBar::begin([
    'brandLabel' => Html::img($asset->baseUrl . '/logo.png'),
    'brandUrl' => ['default/index'],
    'options' => ['class' => 'navbar-inverse navbar-fixed-top'],
]);
echo Nav::widget([
    'options' => ['class' => 'nav navbar-nav navbar-right'],
    'items' => [
        ['label' => 'Dashboard', 'url' => ['default/index']],
        ['label' => 'Workers', 'url' => ['worker/index']],
        ['label' => 'Queues', 'url' => ['queue/index']],
        ['label' => 'Retries', 'url' => ['retry/index']],
        ['label' => 'Scheduled', 'url' => ['schedule/index']],
    ],
]);
NavBar::end();
?>

<div class="container">
    <div class="col-sm-12 summary_bar">
        <ul class="list-unstyled summary row">
            <li class="processed col-sm-2">
                <span class="count">27</span>
                <span class="desc">Processed</span>
            </li>
            <li class="failed col-sm-2">
                <span class="count">25</span>
                <span class="desc">Failed</span>
            </li>
            <li class="busy col-sm-2">
                <a href="/admin/sidekiq/workers">
                    <span class="count">0</span>
                    <span class="desc">Busy</span>
                </a>
            </li>
            <li class="enqueued col-sm-2">
                <a href="/admin/sidekiq/queues">
                    <span class="count">5</span>
                    <span class="desc">Enqueued</span>
                </a>
            </li>
            <li class="retries col-sm-2">
                <a href="/admin/sidekiq/retries">
                    <span class="count">0</span>
                    <span class="desc">Retries</span>
                </a>
            </li>
            <li class="scheduled col-sm-2">
                <a href="/admin/sidekiq/scheduled">
                    <span class="count">0</span>
                    <span class="desc">Scheduled</span>
                </a>
            </li>
        </ul>

    </div>
    <?= $content ?>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">A Product of <a href="http://www.wayood.com/">Wayhood Technology LLC</a></p>
        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>