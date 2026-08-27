<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireStudent();
$quizId=(int)($_GET['id']??0);
$result=($_SESSION['_quiz_result']??0)===$quizId?($_SESSION['_quiz_result_payload']??null):null;
unset($_SESSION['_quiz_result'],$_SESSION['_quiz_result_payload']);
if(!$result){View::flash('info','Quiz results are available immediately after submission only.');header('Location: '.url('student/quiz-builder.php'));exit;}
View::render('student/quizzes/result',['result'=>$result,'pageTitle'=>'Quiz Results'],'main');
