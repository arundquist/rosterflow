<?php

namespace App\Helpers;

class Layers
{
  static function quicktest()
  {
    return self::testofself("Layers is awesome");
  }

  static function testofself($string)
  {
    return strtoupper($string);
  }

  static function correlateSingleClassAndFutureTerm($studentlist, $earlyclass, $futureterm,$min=3)
  {
    $students=implode(',', $studentlist);
    $list=[];
    $firstcourse=\App\Course::findOrFail($earlyclass);
    $resultafter=\DB::Select("SELECT cs.course_id,c.*, count(*) AS q
        FROM course_student cs, courses c
        WHERE student_id IN (
            SELECT student_id
            FROM course_student
            WHERE course_id = $earlyclass
            AND student_id IN ($students)
        )
        AND c.id=cs.course_id
        AND c.term=$futureterm
        GROUP BY course_id
        HAVING q>=$min
        ORDER BY q DESC");
    foreach ($resultafter AS $r)
    {
        $list[]="['$firstcourse->subject $firstcourse->number $firstcourse->term', '$r->subject $r->number $r->term', $r->q]";
    }
    $newcourses=collect($resultafter)->pluck('course_id')->toArray();
    //return implode(', ',$list);
    //dd(['newcourses'=>$newcourses, 'connections'=>$list]);
    return ['newcourses'=>$newcourses, 'connections'=>$list];
  }

  static function oneLayerToAnother($studentlist, $earlylist, $futureterm, $min=3)
  {
    $fulllist=[];
    $nextcourses=[];
    $layerconnections=[];
    foreach($earlylist AS $earlyclass)
    {
      //$fulllist[]=self::correlateSingleClassAndFutureTerm($studentlist,$earlyclass,$futureterm,$min);
      $current=self::correlateSingleClassAndFutureTerm($studentlist,$earlyclass,$futureterm,$min);
      $nextcourses=array_unique(array_flatten([$nextcourses,$current['newcourses']]));
      $layerconnections=array_flatten([$layerconnections, $current['connections']]);
    };
    //dd(['newcourses'=>$nextcourses, 'connections'=>$layerconnections]);
    return ['newcourses'=>$nextcourses, 'connections'=>$layerconnections];
  }

  static function startClass($firstcourse, $numterms, $min=3)
  {
    $course=\App\Course::findOrFail($firstcourse);
    $students=$course->students()->pluck('id')->toArray();
    $startlist=[$firstcourse];
    $allconnections=[];
    $curterm=$course->term;
    for ($x=0; $x<$numterms; $x++)
    {
      $curterm=self::nextTerm($curterm);
      $nextlayer=self::oneLayerToAnother($students, $startlist, $curterm, $min);
      $allconnections=array_flatten([$allconnections, $nextlayer['connections']]);
      $startlist=$nextlayer['newcourses'];
    }
    
    //dd($allconnections);
    return $allconnections;
  }

  static function nextTerm($currentterm)
  {
    $sem=$currentterm % 100;
    if ($sem == 11)
    {
      $nextterm=$currentterm+2;
    } else {
      $nextterm=$currentterm+98;
    };
    return $nextterm;
  }


}
