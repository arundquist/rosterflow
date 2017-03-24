@extends('layouts.app')



@section('content')
<div class='container'>
<form action='{{route('layers')}}' method='POST'>
  {{ csrf_field() }}
@foreach ($courses AS $dept=>$dcourses)
  <h2>{{$dept}}</h2>
  <ul class='list-inline'>
    @foreach ($dcourses AS $id=>$c)
      <li><input type='checkbox' name='layer[{{$id}}]' value='{{$c}}'>{{$c}}</li>
    @endforeach
  </ul>
  <hr/>
@endforeach
<input type='submit' name='submit'>
</form>
</div>
@endsection
