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

  static function correlateSingleClassAndOtherTerm($studentlist, $earlyclass, $futureterm,$future=1,$min=3)
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
        if ($future)
        {
          $list[]="['$firstcourse->subject $firstcourse->number $firstcourse->term', '$r->subject $r->number $r->term', $r->q]";
        } else
        {
          $list[]="['$r->subject $r->number $r->term','$firstcourse->subject $firstcourse->number $firstcourse->term', $r->q]";
        }
    }
    $newcourses=collect($resultafter)->pluck('course_id')->toArray();
    //return implode(', ',$list);
    //dd(['newcourses'=>$newcourses, 'connections'=>$list]);
    return ['newcourses'=>$newcourses, 'connections'=>$list];
  }

  static function oneLayerToAnother($studentlist, $earlylist, $futureterm, $future=1, $min=3)
  {
    $fulllist=[];
    $nextcourses=[];
    $layerconnections=[];
    foreach($earlylist AS $earlyclass)
    {
      //$fulllist[]=self::correlateSingleClassAndFutureTerm($studentlist,$earlyclass,$futureterm,$min);
      $current=self::correlateSingleClassAndOtherTerm($studentlist,$earlyclass,$futureterm,$future,$min);
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
    $future=1;
    $nterms=$numterms;
    if ($numterms<0)
    {
      $nterms=-$numterms;
      $future=0;
    };
    $curterm=$course->term;
    for ($x=0; $x<$nterms; $x++)
    {
      if ($numterms<0)
      {
        $curterm=self::previousTerm($curterm);
      } else
      {
        $curterm=self::nextTerm($curterm);
      }

      $nextlayer=self::oneLayerToAnother($students, $startlist, $curterm, $future, $min);
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

  static function previousTerm($currentterm)
  {
    $sem=$currentterm % 100;
    if ($sem == 11)
    {
      $nextterm=$currentterm-98;
    } else {
      $nextterm=$currentterm-2;
    };
    return $nextterm;
  }

  static function correlateSingleNumToListOfNums($initial, $listoffinal,$min=3)
  {
    $startclass=\App\Course::findOrFail($initial);
    $initialcourses=self::getSimilarClasses($startclass);
    $instartlist=implode(', ', $initialcourses);
    $finallist=[];
    foreach ($listoffinal AS $final)
    {
      $nextclass=\App\Course::findOrFail($final);
      $finallist[]=self::getSimilarClasses($nextclass);
    };
    $finallist=array_flatten($finallist);
    $finallist=implode(', ', $finallist);
    $list=[];
    //dd($finallist);
    $resultafter=\DB::Select("SELECT cs.course_id,c.*, count(*) AS q, concat(c.subject,c.number) AS name
        FROM course_student cs, courses c
        WHERE student_id IN (
            SELECT student_id
            FROM course_student
            WHERE course_id IN ($instartlist)
        )
        AND c.id=cs.course_id
        AND c.id IN ($finallist)
        GROUP BY name
        HAVING q>=$min
        ORDER BY q DESC");
    foreach ($resultafter AS $r)
    {
        $list[]="['$startclass->subject $startclass->number', '$r->subject $r->number', $r->q]";
    }
    //$newcourses=collect($resultafter)->pluck('course_id')->toArray();
    //return implode(', ',$list);
    //dd(['newcourses'=>$newcourses, 'connections'=>$list]);
    return $list;
  }

  static function singleCourseNoTerm($id, $future, $min=3)
  {
    $startclass=\App\Course::findOrFail($id);
    $similar=self::getSimilarClasses($startclass);
    $instartlist=implode(',',$similar);
    $compare="<";
    if($future){$compare=">";};
    $resultafter=\DB::Select("SELECT cs.course_id,c.*, count(*) AS q, concat(c.subject,c.number) AS name
        FROM course_student cs, courses c
        WHERE student_id IN (
            SELECT student_id
            FROM course_student
            WHERE course_id IN ($instartlist)
        )
        AND c.id=cs.course_id
        AND c.number $compare $startclass->number
        GROUP BY name
        HAVING q>=$min
        ORDER BY q DESC");
    foreach ($resultafter AS $r)
      {
          if($future)
          {
            $list[]="['$startclass->subject $startclass->number', '$r->subject $r->number', $r->q]";
          } else {
            $list[]="['$r->subject $r->number','$startclass->subject $startclass->number',  $r->q]";
          }
      }
        //$newcourses=collect($resultafter)->pluck('course_id')->toArray();
        //return implode(', ',$list);
        //dd(['newcourses'=>$newcourses, 'connections'=>$list]);
    return $list;
  }

  static function oneLayerToAnotherNoTerm($earlylist, $nextlist, $min=3)
  {
    $layerconnections=[];
    foreach($earlylist AS $earlyclass)
    {
      //$fulllist[]=self::correlateSingleClassAndFutureTerm($studentlist,$earlyclass,$futureterm,$min);
      $current=self::correlateSingleNumToListOfNums($earlyclass, $nextlist,$min);
      $layerconnections=array_flatten([$layerconnections, $current]);
    };
    //dd(['newcourses'=>$nextcourses, 'connections'=>$layerconnections]);
    return $layerconnections;
  }

  static function namedLayers($layers, $min=3)
  {
    $numlayers=count($layers);
    $allconnections=[];
    for ($x=0; $x<$numlayers-1; $x++)
    {
      $nextlayer=self::oneLayerToAnotherNoTerm($layers[$x],$layers[$x+1],$min);
      $allconnections=array_flatten([$allconnections, $nextlayer]);
    }

    //dd($allconnections);
    return $allconnections;
  }

  static function getSimilarClasses($startclass)
  {
    //$startclass=\App\Course::findOrFail($initialid);
    $initialcourses=\App\Course::where('subject',$startclass->subject)
                      ->where('number',$startclass->number)
                      ->get()->pluck('id')->toArray();
    return $initialcourses;
  }

  static function getSimilarEnrollment($id)
  {
    $class=\App\Course::findOrFail($id);
    $sim=self::getSimilarClasses($class);
    $string=implode(',',$sim);
    $results=\DB::Select("SELECT count(*) AS q
        FROM course_student cs
        WHERE cs.course_id IN ($string)");
    return $results[0]->q;

  }

  static function getLevelNumbers($dept,$min,$max)
  {
    $level=\App\Course::where('subject',$dept)
              ->where('number','<=',$max)
              ->where('number','>=', $min)
              ->select('number','id')->groupBy('number')->pluck('id')->toArray();
    return array_flatten($level);
  }

  static function prepareLayers($levels)
  {
    $allconnections=Layers::namedLayers($levels,3);
    $string=implode(',',$allconnections);
    $courseinfo=[];
    foreach($levels AS $key=>$level)
    {
      foreach ($level AS $course)
      {
        $c=\App\Course::findOrFail($course);
        $courseinfo[$key][]=['course'=>$c,'enrollment'=>Layers::getSimilarEnrollment($course)];
      }
    };
    return ['string'=>$string, 'courseinfo'=>$courseinfo];
  }


}
