<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireStudent();
$quiz=Quiz::getForStudent((int)($_GET['id']??0),(int)Auth::id());
if(!$quiz){View::flash('error','Quiz not found.');header('Location: '.url('student/quiz-builder.php'));exit;}
if($quiz['completed_at']){header('Location: '.url('student/quiz-result.php?id='.(int)$quiz['id']));exit;}
View::render('student/quizzes/take',['quiz'=>$quiz,'pageTitle'=>'Take Quiz'],'main');
