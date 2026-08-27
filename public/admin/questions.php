<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Question Catalog Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 15; // standard pagination limit for MVPs
$offset = ($page - 1) * $limit;

$filters = [
    'module_id'     => $_GET['module_id'] ?? '',
    'subject_id'    => $_GET['subject_id'] ?? '',
    'type'          => $_GET['type'] ?? '',
    'answer_status' => $_GET['answer_status'] ?? '',
    'search'        => $_GET['search'] ?? '',
    'sort_by'       => $_GET['sort_by'] ?? 'newest',
    'exam_year'     => $_GET['exam_year'] ?? '',
    'exam_term'     => in_array($_GET['exam_term'] ?? '', ['first','second'], true) ? $_GET['exam_term'] : '',
    'source_names'  => array_values(array_intersect((array)($_GET['source_names'] ?? []), ['final','end_module']))
];

$modules = Academic::getAllModules();

// If module is selected, restrict subjects to that module
$subjects = [];
if (!empty($filters['module_id'])) $subjects = Academic::getAllSubjects((int)$filters['module_id']);
$allowedSubjectIds=array_map('intval',array_column($subjects,'id'));if($filters['subject_id']!==''&&!in_array((int)$filters['subject_id'],$allowedSubjectIds,true))$filters['subject_id']='';

$questions = $filters['module_id']!=='' ? Question::getQuestions($filters, $limit, $offset) : [];
$totalQuestions = $filters['module_id']!=='' ? Question::getQuestionsCount($filters) : 0;
$totalPages = (int)ceil($totalQuestions / $limit);

View::render('admin/questions/index', [
    'pageTitle'      => 'Question Bank',
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
