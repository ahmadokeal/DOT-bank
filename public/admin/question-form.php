<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Question Create / Edit Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$isEdit     = false;
$question   = null;
$errors     = [];

// --- Decode existing answer_data for editing ---
$type         = 'mcq';
$answerStatus = 'available';
$questionText = '';
$frequency    = 1;
$sourceName   = '';
$examYear     = '';
$examTerm     = '';
$appearances  = [];
$options      = [];
$correctAnswer = '';
$leftItems    = [];
$rightItems   = [];
$matches      = new stdClass();
$plainAnswer  = '';

// Load all modules and subjects for dropdowns
$modules  = Academic::getAllModules();
$subjects = Academic::getAllSubjects();

// --- Edit mode: load question by ?id= ---
if (isset($_GET['id'])) {
    $qId     = (int)$_GET['id'];
    $question = Question::getQuestionById($qId);
    if (!$question) {
        View::flash('error', 'Question not found.');
        header('Location: ' . url('admin/questions.php'));
        exit;
    }

    $isEdit       = true;
    $type         = $question['type'];
    $answerStatus = $question['answer_status'];
    $questionText = $question['question_text'];
    $frequency    = (int)$question['frequency'];
    $sourceName   = $question['source_name'] ?? '';
    $examYear     = $question['exam_year'] ?? '';
    $examTerm     = $question['exam_term'] ?? '';
    $appearances  = is_array($question['appearances'] ?? null) ? $question['appearances'] : [];

    $decoded = json_decode($question['answer_data'] ?? '', true) ?? [];
    $options       = $decoded['options']      ?? [];
    $correctAnswer = $decoded['correct_answer'] ?? '';
    $leftItems     = $decoded['left_items']   ?? [];
    $rightItems    = $decoded['right_items']  ?? [];
    $matchesArr    = $decoded['matches']      ?? [];
    $matches       = (object)($matchesArr ?: []);
    $plainAnswer   = $decoded['answer']       ?? '';
}

// --- POST: create or update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verify();

    $postData = [
        'subject_id'    => $_POST['subject_id']    ?? '',
        'type'          => $_POST['type']          ?? '',
        'question_text' => $_POST['question_text'] ?? '',
        'answer_status' => $_POST['answer_status'] ?? 'available',
        'frequency'     => $_POST['frequency']     ?? '1',
        'source_name'   => $_POST['source_name']   ?? '',
        'exam_year'     => $_POST['exam_year']     ?? '',
        'exam_term'     => $_POST['exam_term']     ?? '',
        'appearances'   => $_POST['appearances']   ?? [],
        'options'       => $_POST['options']       ?? [],
        'correct_answer'=> $_POST['correct_answer']?? '',
        'left_items'    => $_POST['left_items']    ?? [],
        'right_items'   => $_POST['right_items']   ?? [],
        'matches'       => $_POST['matches']       ?? [],
        'answer'        => $_POST['answer']        ?? '',
    ];

    // Repopulate view variables from POST in case of validation failure
    $type         = $postData['type'];
    $answerStatus = $postData['answer_status'];
    $questionText = $postData['question_text'];
    $frequency    = (int)($postData['frequency'] ?: 1);
    $sourceName   = $postData['source_name'];
    $examYear     = $postData['exam_year'];
    $examTerm     = $postData['exam_term'];
    $appearances  = is_array($postData['appearances']) ? $postData['appearances'] : [];
    $options      = is_array($postData['options']) ? $postData['options'] : [];
    $correctAnswer = $postData['correct_answer'];
    $leftItems    = is_array($postData['left_items'])  ? $postData['left_items']  : [];
    $rightItems   = is_array($postData['right_items']) ? $postData['right_items'] : [];
    $matchesArr   = is_array($postData['matches'])     ? $postData['matches']     : [];
    $matches      = (object)$matchesArr;
    $plainAnswer  = $postData['answer'];

    if ($isEdit && $question) {
        $result = Question::updateQuestion((int)$question['id'], $postData);
    } else {
        $result = Question::createQuestion($postData);
    }

    if ($result['success']) {
        View::flash('success', $result['message'] ?? ($isEdit ? 'Question updated successfully.' : 'Question created successfully.'));
        $redirectId = $isEdit ? (int)$question['id'] : (int)($result['id'] ?? 0);
        header('Location: ' . url('admin/question-view.php?id=' . $redirectId));
        exit;
    }

    $errors = $result['errors'] ?? ['An unexpected error occurred.'];
}

View::render('admin/questions/form', [
    'pageTitle'     => $isEdit ? 'Edit Question' : 'Create Question',
    'isEdit'        => $isEdit,
    'question'      => $question,
    'errors'        => $errors,
    'modules'       => $modules,
    'subjects'      => $subjects,
    'type'          => $type,
    'answerStatus'  => $answerStatus,
    'questionText'  => $questionText,
    'frequency'     => $frequency,
    'sourceName'    => $sourceName,
    'examYear'      => $examYear,
    'examTerm'      => $examTerm,
    'appearances'   => $appearances,
    'options'       => $options,
    'correctAnswer' => $correctAnswer,
    'leftItems'     => $leftItems,
    'rightItems'    => $rightItems,
    'matches'       => $matches,
    'plainAnswer'   => $plainAnswer,
], 'main');
