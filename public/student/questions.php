<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Student Question Browser Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireStudent();

// Resolve subject and optional module from GET
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$subject   = null;
$module    = null;

if ($subjectId > 0) {
    $sql = "
        SELECT s.id, s.name, s.description, s.module_id,
               m.name AS module_name
        FROM subjects s
        JOIN modules m ON m.id = s.module_id
        WHERE s.id = ?
    ";
    $subject = Database::fetchOne($sql, [$subjectId]);
}

// Build filters
$filters = [
    'module_id'     => $subject ? (int)$subject['module_id'] : (int)($_GET['module_id'] ?? 0),
    'subject_id'    => $subjectId > 0 ? $subjectId : '',
    'type'          => $_GET['type']          ?? '',
    'answer_status' => $_GET['answer_status'] ?? '',
    'search'        => $_GET['search']        ?? '',
    'sort_by'       => $_GET['sort_by']       ?? 'newest',
    'exam_year'     => $_GET['exam_year'] ?? '',
    'exam_term'     => in_array($_GET['exam_term'] ?? '', ['first','second'], true) ? $_GET['exam_term'] : '',
    'source_names'  => array_values(array_intersect((array)($_GET['source_names'] ?? []), ['final','end_module'])),
];

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$questions      = $filters['module_id']>0 ? Question::getQuestions($filters, $limit, $offset) : [];
foreach ($questions as &$questionRow) {
    $questionRow['appearances'] = Question::getSources((int)$questionRow['id']);
}
unset($questionRow);
$totalQuestions = $filters['module_id']>0 ? Question::getQuestionsCount($filters) : 0;
$totalPages     = (int)ceil($totalQuestions / $limit);

// Load modules for optional top-level filter
$modules = Academic::getAllModules();

// Load subjects for the selected module (if filtering at module level)
$subjects = [];
if (!empty($filters['module_id'])) {
    $subjects = Academic::getAllSubjects((int)$filters['module_id']);
}

View::render('student/questions/index', [
    'pageTitle'      => $subject ? 'Questions — ' . $subject['name'] : 'Question Bank',
    'subject'        => $subject,
    'modules'        => $modules,
    'subjects'       => $subjects,
    'questions'      => $questions,
    'totalQuestions' => $totalQuestions,
    'totalPages'     => $totalPages,
    'page'           => $page,
    'limit'          => $limit,
    'offset'         => $offset,
    'filters'        => $filters,
], 'main');
