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
          {!! $fulllist!!}
        ]);

        // Sets chart options.
        var colors = ['#a6cee3', '#b2df8a', '#fb9a99', '#fdbf6f',
                  '#cab2d6', '#ffff99', '#1f78b4', '#33a02c'];

    var options = {
      height: 600,
      sankey: {
        node: {
          colors: colors
        },
        link: {
          colorMode: 'gradient',
          colors: colors
        }
      }
    };

        // Instantiates and draws our chart, passing in some options.
        var chart = new google.visualization.Sankey(document.getElementById('sankey_basic'));
        chart.draw(data, options);
      }
    </script>
@endsection

@section('content')
<div class='container'>


<div id="sankey_basic" style="width: 900px; height: 600px;"></div>
@if(isset($enrollment))
  enrollment = {{$enrollment}}
@endif
@if(isset($courseinfo))
  <table class='table table-bordered'>
    <tr>
      @foreach($courseinfo AS  $key=>$layer)
        <td>
          <ul class='list-group'>
            @foreach ($layer AS $course)
              <li class='list-group-item'>
                <a href='{{route('singlecourse',['id'=>$course['course']->id,'min'=>10])}}'>
                  {{$course['course']->subject}}{{$course['course']->number}} ({{$course['enrollment']}})
                  @if(isset($level_id))
                  <a href='{{route('moveitem',[$level_id,$course['course']->id,$key,1])}}'>&#x21D0;</a>
                  <a href='{{route('moveitem',[$level_id,$course['course']->id,$key,0])}}'>&#x21D2;</a>
                  <a href=''>&#x2573;</a>
                  @endif
                </a>
              </li>
            @endforeach
          </ul>
        </td>
      @endforeach
    </tr>
  </table>
@endif
</div>
@endsection
