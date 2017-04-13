<?php
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('courses/{dept}', function($dept) {
    $courses=App\Course::where('subject', $dept)
      ->orderBy('number', 'ASC')->get();
    return  view('courses',
      ['courses'=>$courses]);
});

Route::get('trackcourse/{course_id}/{num}/{min?}', ['middleware' => 'auth.basic', function($course_id, $num, $min=3)
{
  //$fulllist=Layers::startClass(4898, [201413, 201511]);
  $fulllist=Layers::startClass($course_id,$num,$min);
  $string=implode(', ',$fulllist);
  return view('trackclass',
    ['fulllist'=>$string]);
}])->name('track');

Route::get('noterms', function()
{
  $allconnections=Layers::namedLayers([[7391],[4888,7841],[2190]]);
  $string=implode(',',$allconnections);
  return view('trackclass',
    ['fulllist'=>$string]);
});

Route::get('majorlevels/{dept}/{min?}', ['middleware' => 'auth.basic', function($dept, $min=3)
{
  $level1000=Layers::getLevelNumbers($dept, 1000, 1999);
  $level3000=Layers::getLevelNumbers($dept, 3000, 3999);
  $level5000=Layers::getLevelNumbers($dept, 5000, 5999);
  $allconnections=Layers::namedLayers([$level1000,$level3000,$level5000],$min);
  $string=implode(',',$allconnections);
  $alllevels=[$level1000,$level3000,$level5000];
  $courseinfo=[];
  foreach ($alllevels AS $layernum=>$layerlist)
  {
    foreach ($layerlist AS $courseid)
    {
      $c=App\Course::findOrFail($courseid);
      $courseinfo[$layernum][]=['course'=>$c,'enrollment'=>Layers::getSimilarEnrollment($courseid)];
    }
  }
  return view('trackclass',
    ['fulllist'=>$string,
      'courseinfo'=>$courseinfo]);

}])->name('majorlevels');

Route::get('allcourses', function()
{
  $courses=App\Course::select('id', DB::raw('CONCAT(subject,number) AS name'),'subject')
        ->groupBy("name")->get();
  $biglist=[];
  foreach ($courses AS $course)
  {
    $biglist[$course->subject][$course->id]=$course->name;
  }
  return view('allcourses',
    ['courses'=>$biglist]);
});

Route::post('layers', function(Request $request)
{
  $chosen=$request->input('layer');
  return view('chosencourses',
    ['chosen'=>$chosen]);
})->name('layers');

Route::post('layersready', ['middleware' => 'auth.basic', function(Request $request)
{
  $layersinput=$request->input('layers');
  $layers=[];
  $courseinfo=[];
  foreach($layersinput AS $key=>$value)
  {
    $layers[$value][]=$key;
    $c=App\Course::findOrFail($key);
    $courseinfo[$value][]=['course'=>$c,'enrollment'=>Layers::getSimilarEnrollment($key)];
  };
  $allconnections=Layers::namedLayers($layers,$request->input('min'));
  $string=implode(',',$allconnections);
  $levels=$request->input('levels');
  $savedid='';
  if ($levels != '')
  {
    $level=new App\Level;
    $level->name=$levels;
    //puts [[1,2],[3],[4,5,6]] into database.
    // downside: needs to be run again
    // upside: editable
    $level->levels=json_encode(array_values($layers));
    $level->save();
    $savedid=$level->id;
  }
  return view('trackclass',
    ['fulllist'=>$string,
      'courseinfo'=>$courseinfo,
      'savedid'=>$savedid]);

}])->name('layersready');

Route::get('savedlevel/{id}', ['middleware'=>'auth.basic', function($id)
{
  $level=App\Level::findOrFail($id);
  $levels=$level->levels;
  // it's stored as a string but looks like an array. This seems to fix it:
  eval("\$levels = $levels;");
  session(['levels'=>$levels]);
  $prepared=Layers::prepareLayers($levels);
  return view('trackclass',
    ['fulllist'=>$prepared['string'],
    'courseinfo'=>$prepared['courseinfo'],
    'level_id'=>$id]);
}])->name('savedlevel');

Route::get('savedlevel', function()
{
  $saved=App\Level::all();
  return view('savedlevels',
    ['saved'=>$saved]);
});

Route::get('moveitem/{level_id}/{item_id}/{layer}/{left}', ['middleware'=>'auth.basic', function($level_id,$item_id,$layer,$left)
{
  // $level=App\Level::findOrFail($level_id);
  // $levels=$level->levels;
  // eval("\$levels = $levels;");
  $levels=session('levels');
  //$currentlayer=$levels[$layer];

  // this next part removes the item from whereever it is
  $search = $item_id;
  $result = array_map(function ($value) use ($search) {
     if(($key = array_search($search, $value)) !== false) {
        unset($value[$key]);
     }
     return $value;
   }, $levels);
/* Note that this won't work well because everything will get saved if
you just say something like $level->save() down below.

Need to think about using session variables to do this and then something like
a "save this version" or something
*/
  //prepare extra layers if needed
  if(($left==1)&&($layer==0))
  {
    array_unshift($result,[]);
    $layer=1;
  };
  if (($left==0)&&($layer==count($levels)-1))
  {
    $result[]=[];
  };

  //now place the item in the new layer
  if ($left==1)
  {
    $result[$layer-1][]=$item_id;
  } else {
    $result[$layer+1][]=$item_id;
  };

  //now make sure there are no empty Layers
  $result= array_filter(array_map('array_filter', $result));
  //dd($result);
  //had to do this because the keys got screwed up.
  // not sure if it matters but the array has strings in it and not integers
  $result=array_values($result);

  session(['levels'=>$result]);
  $prepared=Layers::prepareLayers($result);
  return view('trackclass',
    ['fulllist'=>$prepared['string'],
    'courseinfo'=>$prepared['courseinfo'],
    'level_id'=>$level_id]);
  //dd([$levels,$result]);


}])->name('moveitem');

Route::get('singlecourse/{id}/{min?}', ['middleware'=>'auth.basic', function($id, $min=3)
{
  $enrollment=Layers::getSimilarEnrollment($id);
  $pastconnections=Layers::singleCourseNoTerm($id, 0, $min);
  $futureconnections=Layers::singleCourseNoTerm($id, 1, $min);
  $allconnections=array_flatten([$pastconnections,$futureconnections]);
  $string=implode(',',$allconnections);
  return view('trackclass',
    ['fulllist'=>$string,
      'enrollment'=>$enrollment]);
}])->name('singlecourse');


Route::get('/test/{id}/{min?}/{join?}', ['middleware' => 'auth.basic', function($id, $min=3,$join=0) {
    $course=App\Course::findOrFail($id);
    $term=$course->term;
    $whereclause="WHERE course_id=$id";
    $notinclause="AND course_id!=$id";
    if($join){
      $othercourses=App\Course::where('term',$course->term)
        ->where('subject',$course->subject)
        ->where('number',$course->number)
        ->pluck('id')->toArray();
      $whereclause="WHERE course_id IN (";
      $whereclause.=implode(',', $othercourses);
      $whereclause.=") ";
      $notinclause="AND course_id NOT IN (";
      $notinclause.=implode(',', $othercourses);
      $notinclause.=") ";
    }
    $result=DB::Select("SELECT cs.course_id,c.*, count(*) AS q
FROM course_student cs, courses c
WHERE student_id IN (
    SELECT student_id
    FROM course_student
    $whereclause
)
AND c.id=cs.course_id
$notinclause
AND c.term<$term
GROUP BY course_id
HAVING q>=$min
ORDER BY q DESC");
    $resultafter=DB::Select("SELECT cs.course_id,c.*, count(*) AS q
FROM course_student cs, courses c
WHERE student_id IN (
    SELECT student_id
    FROM course_student
    $whereclause
)
AND c.id=cs.course_id
$notinclause
AND c.term>$term
GROUP BY course_id
HAVING q>=$min
ORDER BY q DESC");
    return view('singlecourse',
      ['course'=>$course,
      'result'=>$result,
      'resultafter'=>$resultafter]);
}])->name('test');
