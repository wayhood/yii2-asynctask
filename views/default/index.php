<?php
/**
 * @link http://www.wayhood.com/
 */
use yii\helpers\Html;

$this->title = 'Dashboard ';
?>
<div class="col-sm-12 summary_bar">
 <p>&nbsp; </p>
 <p>
  <h3><?=Html::encode($this->title);?></h3>
 </p>

 <h5>&nbsp;</h5>
 <div id="realtime" class="rickshaw_graph">
 </div>
 <h5>
   <span class="history-heading">History</span>
   <a class="history-graph " href="/admin/sidekiq/?days=7">1 week</a>
   <a class="history-graph active" href="/admin/sidekiq/">1 month</a>
   <a class="history-graph " href="/admin/sidekiq/?days=90">3 months</a>
   <a class="history-graph " href="/admin/sidekiq/?days=180">6 months</a>
 </h5>
 <div id="history" class="rickshaw_graph" data-failed="<?= Html::encode(json_encode($days['failed']))?>" data-processed="<?= Html::encode(json_encode($days['processed']))?>">
 </div>
</div>

<?php
$js =<<<EOF
Number.prototype.numberWithDelimiter = function(delimiter) {
  var number = this + '', delimiter = delimiter || ',';
  var split = number.split('.');
  split[0] = split[0].replace(
      /(\d)(?=(\d\d\d)+(?!\d))/g,
      '$1' + delimiter
  );
  return split.join('.');
};

var responsiveWidth = function() {
  //Bootstrap now uses padding instead of margin for gutters. 30px (15 on each side)
  //http://getbootstrap.com/css/#grid
  return document.getElementsByClassName('summary_bar')[0].clientWidth - 30;
};

var createSeries = function(obj) {
  var series = []
  for (var date in obj) {
    var value = obj[date];
    var point = { x: Date.parse(date)/1000, y: value };
    series.unshift(point);
  }
  return series;
};

var historyGraph = function() {
  processed = createSeries($("#history").data("processed"))
  failed = createSeries($("#history").data("failed"))

  var graph = new Rickshaw.Graph( {
    element: document.getElementById("history"),
    width: responsiveWidth(),
    height: 200,
    renderer: 'line',
    interpolation: 'linear',
    series: [
      {
        color: "#B1003E",
        data: failed,
        name: 'Failed'
      }, {
        color: "#006f68",
        data: processed,
        name: 'Processed'
      }
    ]
  } );
  var x_axis = new Rickshaw.Graph.Axis.Time( { graph: graph } );

  var y_axis = new Rickshaw.Graph.Axis.Y({
    graph: graph,
    tickFormat: Rickshaw.Fixtures.Number.formatKMBT,
    ticksTreatment: 'glow'
  });

  graph.render();

  var hoverDetail = new Rickshaw.Graph.HoverDetail({
    graph: graph,
    yFormatter: function(y) { return Math.floor(y).numberWithDelimiter() },
  });
}

historyGraph();
EOF;

$this->registerJs($js);
