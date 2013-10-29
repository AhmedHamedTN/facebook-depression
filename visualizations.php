<?php
?>
<html>
<head>
  <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Members', 'Number of Messages'],
          ["549702771",11785],["100000679296659",8107],["500514486",5739],["607618019",5580],["1053610655,1474743578,845865082,527057504,593078957,525874581,644797235,570902471,782508322,614489375",4118],["858195706",3906],["1214506177",3293],["592605465",2541],["834838781",2052],["640541802",1901],["1613534534,561733137,1553550120,610756553,501558461,1078715814,640541802",1830],["570902471",1459],["533026053",1386],["1483883569",1171],["610756553",1133],["1053610655,527057504,593078957,525874581,644797235,570902471,782508322,614489375",1000],["725104831",999],["501558461",999],["730045116",991],["1532190207",989],["557666842",979],["1503660045",974],["1332240137",795],["709411217",787],["845865082",761],["502679233",748],["1201414198",740],["680302900,100001989987192,733823892,1315039587,656949352,858195706,100000479770252,1323680014,559375381,1219753356,713095298,1516087263,1374802886,597642672,725104831,1843098117,549702771",656],["588391453",637],["685862639",609],
        ]);

        var options = {
          title: 'Thread Length',
          hAxis: {title: 'Friend/Friends', titleTextStyle: {color: 'red'}},
          chartArea: {'width': '100%', 'height': '70%', 'left': '100'},
               legend: {'position': 'bottom'}
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
</head>
<body> 
  <div class="graph" style="padding-bottom: 100px;">
    <div id="chart_div" style="width: 1500px; height: 1000px;"></div>
  </div>
</body>
</html>