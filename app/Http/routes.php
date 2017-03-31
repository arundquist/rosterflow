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
  $allconnections=Layers::namedLayers($levels,3);
  $string=implode(',',$allconnections);
  $courseinfo=[];
  foreach($levels AS $key=>$level)
  {
    foreach ($level AS $course)
    {
      $c=App\Course::findOrFail($course);
      $courseinfo[$key][]=['course'=>$c,'enrollment'=>Layers::getSimilarEnrollment($course)];
    }
  };
  return view('trackclass',
    ['fulllist'=>$string,
    'courseinfo'=>$courseinfo]);
}]);

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
