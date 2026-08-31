<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireStudent();
$quiz=Quiz::getForStudent((int)($_GET['id']??0),(int)Auth::id());
if(!$quiz){View::flash('error','Quiz not found.');header('Location: '.url('student/quiz-builder.php'));exit;}
// Generate and preserve randomized Match display order per quiz instance (transient, session-scoped)
$quizId = (int)$quiz['id'];
if (!isset($_SESSION['_quiz_match_display'][$quizId])) {
    $_SESSION['_quiz_match_display'][$quizId] = [];
}
foreach ($quiz['questions'] as $idx => $q) {
    if ($q['type'] !== 'match') continue;
    $qqId = (int)$q['quiz_question_id'];
    if (isset($_SESSION['_quiz_match_display'][$quizId][$qqId])) {
        $quiz['questions'][$idx]['display_left'] = $_SESSION['_quiz_match_display'][$quizId][$qqId]['left'];
        $quiz['questions'][$idx]['display_right'] = $_SESSION['_quiz_match_display'][$quizId][$qqId]['right'];
        continue;
    }
    $data = json_decode($q['answer_data'] ?? '', true) ?: [];
    $left = array_values(array_map('strval', $data['left_items'] ?? []));
    $right = array_values(array_map('strval', $data['right_items'] ?? []));
    // Fisher-Yates unbiased shuffle
    for ($i = count($left) - 1; $i > 0; $i--) { $j = random_int(0, $i); [$left[$i], $left[$j]] = [$left[$j], $left[$i]]; }
    for ($i = count($right) - 1; $i > 0; $i--) { $j = random_int(0, $i); [$right[$i], $right[$j]] = [$right[$j], $right[$i]]; }
    $_SESSION['_quiz_match_display'][$quizId][$qqId] = ['left' => $left, 'right' => $right];
    $quiz['questions'][$idx]['display_left'] = $left;
    $quiz['questions'][$idx]['display_right'] = $right;
}
View::render('student/quizzes/take',['quiz'=>$quiz,'pageTitle'=>'Take Quiz'],'main');
