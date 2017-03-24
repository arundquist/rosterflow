@extends('layouts.app')



@section('content')
<div class='container'>
<form action='{{route('layersready')}}' method='POST'>
  {{ csrf_field() }}
  <ul class='list-group'>
  @foreach ($chosen AS $id=>$course)
    <li class='list-group-item'>
      <input type='text' name='layers[{{$id}}]' size="1">{{$course}}
    </li>
  @endforeach
  <input type='text' name='min' value='3'>Minimum students to track<br/>
<input type='submit' name='submit'>
</form>
</div>
@endsection
