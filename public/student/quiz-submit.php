<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireStudent();
if($_SERVER['REQUEST_METHOD']!=='POST'||!CSRF::verify()){View::flash('error','Unable to submit this quiz.');header('Location: '.url('student/quiz-builder.php'));exit;}
$quizId=(int)($_POST['quiz_id']??0);$result=Quiz::submit($quizId,(int)Auth::id(),$_POST['answers']??[]);
if($result['success']){$_SESSION['_quiz_result']=(int)$quizId;$_SESSION['_quiz_result_payload']=$result;header('Location: '.url('student/quiz-result.php?id='.$quizId));exit;}
View::flash('error',$result['errors'][0]??'Unable to submit quiz.');header('Location: '.url('student/quiz-builder.php'));exit;
