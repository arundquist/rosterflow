<?php

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
  return view('trackclass',
    ['fulllist'=>$string]);

}]);

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
