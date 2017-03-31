@extends('layouts.app')



@section('content')
<div class='container'>

<ul class='list-group'>
  @foreach($saved AS $s)
    <li class='list-group-item'>
      <a href="{{route('savedlevel',[$s->id])}}">{{$s->name}}</a>
    </li>
  @endforeach
</ul>

</div>
@endsection
