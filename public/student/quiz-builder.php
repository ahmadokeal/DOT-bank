<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireStudent();
$modules=Academic::getAllModules(); $selectedModuleId=(int)($_POST['module_id']??0); $subjects=Academic::getAllSubjects(); $errors=[]; $plan=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!CSRF::verify()){ $errors[]='Your form session expired. Please try again.'; }
    elseif(isset($_POST['create_quiz'])){
        $stored=$_SESSION['_quiz_plan']??null; unset($_SESSION['_quiz_plan']);
        if(!$stored||empty($stored['question_ids']))$errors[]='The quiz plan expired. Please build it again.';
        else {$created=Quiz::create((int)Auth::id(),$stored);if($created['success']){header('Location: '.url('student/quiz-take.php?id='.(int)$created['id']));exit;} $errors=$created['errors'];}
    } else {
        $plan=Quiz::plan(['module_id'=>$_POST['module_id']??0,'subject_ids'=>$_POST['subject_ids']??[],'total_questions'=>$_POST['total_questions']??'','type_percentages'=>$_POST['type_percentages']??[],'subject_percentages'=>$_POST['subject_percentages']??[]]);
        if(!$plan['success']){
            $errors=$plan['errors'];
            $plan=null;
        } else $_SESSION['_quiz_plan']=$plan;
    }
}
View::render('student/quizzes/builder',compact('modules','subjects','selectedModuleId','errors','plan'),'main');
