@extends('layouts.app')



@section('content')
<div class='container'>
<ul class='list-group'>
  @foreach ($courses as $c)
    <li class="list-group-item">
      <a href="{{route('test',['id'=>$c->id])}}">{{$c->number}} ({{$c->term}})</a>
      <a href="{{route('track', ['id'=>$c->id, 'num'=>3])}}">track future</a>
      <a href="{{route('track', ['id'=>$c->id, 'num'=>-3])}}">track past</a>
    </li>
  @endforeach
</ul>
</div>
@endsection
