@extends('layouts.app')

@section('headstuff')
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['sankey']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'From');
        data.addColumn('string', 'To');
        data.addColumn('number', 'Weight');
        data.addRows([
          @foreach ($result AS $r)
            ['{{$r->subject}} {{$r->number}} {{$r->term}}', '{{$course->subject}} {{$course->number}} {{$course->term}}', {{$r->q}}],
          @endforeach
          @foreach ($resultafter AS $r)
            ['{{$course->subject}} {{$course->number}} {{$course->term}}', '{{$r->subject}} {{$r->number}} {{$r->term}}',{{$r->q}}],
          @endforeach
        ]);

        // Sets chart options.
        var options = {
          width: 600,
        };

        // Instantiates and draws our chart, passing in some options.
        var chart = new google.visualization.Sankey(document.getElementById('sankey_basic'));
        chart.draw(data, options);
      }
    </script>
@endsection

@section('content')
<div class='container'>
<h1>{{$course->subject}} {{$course->number}} {{$course->term}} {{$course->students()->count()}}</h1>

<div id="sankey_basic" style="width: 900px; height: 600px;"></div>
<ul class='list-group'>
  @foreach ($result AS $c)
    <li class='list-group-item'>
      {{$c->course_id}}: {{$c->q}}, {{$c->subject}} {{$c->number}} {{$c->term}}
    </li>
  @endforeach
</ul>
</div>
@endsection
