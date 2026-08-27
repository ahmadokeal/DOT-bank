<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Phase 3 Question Bank Automated Verification Test Suite
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

class Phase3TestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    // Fixture IDs
    private int $moduleId   = 0;
    private int $subjectId  = 0;
    private int $subject2Id = 0;
    private int $adminId    = 0;
    private int $studentId  = 0;

    public function run(): void {
        echo "=======================================================\n";
        echo " DOT Bank - Phase 3 Question Bank Test Suite          \n";
        echo " Doctors of Tomorrow Question Bank                     \n";
        echo "=======================================================\n\n";

        $this->setupDatabase();
        $this->testServiceClassExists();
        $this->testCreateMcqAvailable();
        $this->testCreatedQuestionAppearsInListing();
        $this->testCreateMcqUnavailable();
        $this->testCreateComplete();
        $this->testCreateMatch();
        $this->testCreateEssay();
        $this->testCreateCompare();
        $this->testMcqValidationNoOptions();
        $this->testMcqValidationCorrectAnswerNotInOptions();
        $this->testMcqValidationMinimumOptions();
        $this->testMatchValidationNoItems();
        $this->testMatchValidationBadMapping();
        $this->testValidationEmptyQuestionText();
        $this->testValidationFrequencyMin();
        $this->testValidationInvalidType();
        $this->testEditPreservesIdAndCreatedAt();
        $this->testEditUpdatesUpdatedAt();
        $this->testDeleteQuestion();
        $this->testGetQuestionById();
        $this->testGetQuestionsWithFilters();
        $this->testSearchFilter();
        $this->testAnswerStatusFilter();
        $this->testTypeFilter();
        $this->testModuleFilter();
        $this->testSubjectFilter();
        $this->testQuestionSourceRecorded();
        $this->testDeleteCascadesToSource();
        $this->testUnavailableQuestionHasNullAnswer();

        echo "\n-------------------------------------------------------\n";
        echo "Phase 3 Test Results: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "-------------------------------------------------------\n";

        if ($this->failed > 0) {
            echo "\nFailures:\n";
            foreach ($this->errors as $err) {
                echo "  [FAIL] {$err}\n";
            }
            exit(1);
        } else {
            echo "\n>>> ALL PHASE 3 TESTS PASSED SUCCESSFULLY! <<<\n\n";
        }
    }

    private function assert(bool $condition, string $testName, string $failureDetails = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$testName}\n";
        } else {
            $this->failed++;
            $msg = $testName . ($failureDetails ? " -> {$failureDetails}" : '');
            $this->errors[] = $msg;
            echo "  [FAIL] {$testName}\n";
        }
    }

    private function setupDatabase(): void {
        echo "--- 1. Test Setup & Schema Initialization ---\n";
        Database::reset();

        $pdo = Database::getInstance();
        $schemaFile = DATABASE_PATH . '/schema.sql';
        $pdo->exec(file_get_contents($schemaFile));

        // Wipe all relevant tables
        $pdo->exec('DELETE FROM quiz_answers');
        $pdo->exec('DELETE FROM quiz_questions');
        $pdo->exec('DELETE FROM quizzes');
        $pdo->exec('DELETE FROM question_sources');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM subjects');
        $pdo->exec('DELETE FROM modules');
        $pdo->exec('DELETE FROM users');

        $now = date('Y-m-d H:i:s');

        // Create admin user
        $adminHash   = password_hash('AdminPass123', PASSWORD_DEFAULT);
        $studentHash = password_hash('StudentPass123', PASSWORD_DEFAULT);

        $pdo->prepare("INSERT INTO users (username, password_hash, role, status, created_at) VALUES (?, ?, 'admin', 'active', ?)")
            ->execute(['testadmin', $adminHash, $now]);
        $this->adminId = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO users (username, password_hash, role, status, created_at) VALUES (?, ?, 'student', 'active', ?)")
            ->execute(['teststudent', $studentHash, $now]);
        $this->studentId = (int)$pdo->lastInsertId();

        // Create module and subjects
        $pdo->prepare("INSERT INTO modules (name, description, created_at) VALUES (?, ?, ?)")
            ->execute(['Anatomy', 'Human body structure', $now]);
        $this->moduleId = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO subjects (module_id, name, description, created_at) VALUES (?, ?, ?, ?)")
            ->execute([$this->moduleId, 'Musculoskeletal', 'Bones and muscles', $now]);
        $this->subjectId = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO subjects (module_id, name, description, created_at) VALUES (?, ?, ?, ?)")
            ->execute([$this->moduleId, 'Neuroanatomy', 'Nervous system anatomy', $now]);
        $this->subject2Id = (int)$pdo->lastInsertId();

        $this->assert(true, 'Database initialized with schema, users, module, and subjects');
    }

    // --- Service class availability ---

    private function testServiceClassExists(): void {
        echo "\n--- 2. Question Service ---\n";
        $this->assert(class_exists('Question'), 'Question service class exists (core/Question.php)');
        $this->assert(method_exists('Question', 'createQuestion'), 'Question::createQuestion() method exists');
        $this->assert(method_exists('Question', 'updateQuestion'), 'Question::updateQuestion() method exists');
        $this->assert(method_exists('Question', 'deleteQuestion'), 'Question::deleteQuestion() method exists');
        $this->assert(method_exists('Question', 'getQuestionById'), 'Question::getQuestionById() method exists');
        $this->assert(method_exists('Question', 'getQuestions'),    'Question::getQuestions() method exists');
        $this->assert(method_exists('Question', 'getQuestionsCount'), 'Question::getQuestionsCount() method exists');
    }

    // --- Creation tests ---

    private function testCreateMcqAvailable(): void {
        echo "\n--- 3. MCQ Question Creation (Available) ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'mcq',
            'question_text' => 'Which bone is the longest in the human body?',
            'answer_status' => 'available',
            'frequency'     => 3,
            'options'       => ['Humerus', 'Femur', 'Tibia', 'Radius'],
            'correct_answer'=> 'Femur',
            'source_name'   => 'Final Exam 2024',
            'exam_year'     => '2024',
            'exam_term'     => 'Spring',
        ]);

        $this->assert($result['success'] === true, 'MCQ question created successfully');
        $this->assert(isset($result['id']) && $result['id'] > 0, 'Returned valid question ID');

        $q = Question::getQuestionById((int)$result['id']);
        $this->assert($q !== null, 'MCQ question retrievable by ID');
        $this->assert($q['type'] === 'mcq', 'MCQ type stored correctly');
        $this->assert($q['frequency'] === 3, 'Frequency stored correctly');

        $decoded = json_decode($q['answer_data'], true);
        $this->assert(is_array($decoded['options']) && count($decoded['options']) === 4, 'MCQ options array has 4 items');
        $this->assert($decoded['correct_answer'] === 'Femur', 'MCQ correct_answer stored as text');
        $this->assert($q['answer_status'] === 'available', 'Answer status is available');
        $this->assert($q['answer_origin'] === 'manual', 'Answer origin is manual');
    }

    private function testCreatedQuestionAppearsInListing(): void {
        echo "\n--- 4. Created Question Appears In Listing ---\n";
        $result = Question::createQuestion([
            'subject_id' => $this->subjectId,
            'type' => 'complete',
            'question_text' => 'Regression listing retrieval question.',
            'answer_status' => 'available',
            'answer' => 'Regression answer',
            'frequency' => 1,
        ]);

        $listed = Question::getQuestions([
            'subject_id' => $this->subjectId,
            'search' => 'Regression listing retrieval question',
        ], 15, 0);
        $this->assert($result['success'] === true, 'Regression question creation succeeds');
        $this->assert(count($listed) === 1 && (int)$listed[0]['id'] === (int)$result['id'], 'Created question is retrieved by Question Bank listing');
        $this->assert(Question::getQuestionsCount(['subject_id' => $this->subjectId, 'search' => 'Regression listing retrieval question']) === 1, 'Question Bank count includes created question');
    }

    private function testCreateMcqUnavailable(): void {
        echo "\n--- 4. MCQ Question Creation (Unavailable Answer) ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'mcq',
            'question_text' => 'What is the nerve supply of the deltoid muscle?',
            'answer_status' => 'unavailable',
            'frequency'     => 1,
            'options'       => ['Radial', 'Axillary', 'Musculocutaneous', 'Ulnar'],
        ]);

        $this->assert($result['success'] === true, 'MCQ question with unavailable answer created');

        $q = Question::getQuestionById((int)$result['id']);
        $decoded = json_decode($q['answer_data'], true);
        $this->assert(array_key_exists('correct_answer', $decoded), 'MCQ answer_data has correct_answer key');
        $this->assert($decoded['correct_answer'] === null, 'MCQ correct_answer is null when unavailable');
        $this->assert(count($decoded['options']) === 4, 'MCQ options still stored when unavailable');
        $this->assert($q['answer_status'] === 'unavailable', 'Answer status is unavailable');
    }

    private function testCreateComplete(): void {
        echo "\n--- 5. Complete Question Creation ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'complete',
            'question_text' => 'Complete: The most common fracture in the elderly is ___.',
            'answer_status' => 'available',
            'frequency'     => 2,
            'answer'        => 'Hip fracture (proximal femur)',
        ]);

        $this->assert($result['success'] === true, 'Complete question created successfully');
        $q  = Question::getQuestionById((int)$result['id']);
        $decoded = json_decode($q['answer_data'], true);
        $this->assert($decoded['answer'] === 'Hip fracture (proximal femur)', 'Complete answer stored correctly');
    }

    private function testCreateMatch(): void {
        echo "\n--- 6. Match Question Creation ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'match',
            'question_text' => 'Match the bone to its classification:',
            'answer_status' => 'available',
            'frequency'     => 2,
            'left_items'    => ['Femur', 'Carpals', 'Parietal'],
            'right_items'   => ['Long bone', 'Short bone', 'Flat bone'],
            'matches'       => [
                'Femur'    => 'Long bone',
                'Carpals'  => 'Short bone',
                'Parietal' => 'Flat bone',
            ],
        ]);

        $this->assert($result['success'] === true, 'Match question created successfully');
        $q = Question::getQuestionById((int)$result['id']);
        $decoded = json_decode($q['answer_data'], true);
        $this->assert(count($decoded['left_items'])  === 3, 'Match left items stored correctly (3)');
        $this->assert(count($decoded['right_items']) === 3, 'Match right items stored correctly (3)');
        $this->assert(isset($decoded['matches']['Femur']) && $decoded['matches']['Femur'] === 'Long bone', 'Match mapping Femur → Long bone correct');
    }

    private function testCreateEssay(): void {
        echo "\n--- 7. Essay Question Creation ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'Describe the histological features of hyaline cartilage.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Hyaline cartilage consists of chondrocytes embedded in a matrix of type II collagen...',
        ]);

        $this->assert($result['success'] === true, 'Essay question created successfully');
        $q = Question::getQuestionById((int)$result['id']);
        $this->assert($q['type'] === 'essay', 'Essay type stored correctly');
    }

    private function testCreateCompare(): void {
        echo "\n--- 8. Compare Question Creation ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'compare',
            'question_text' => 'Compare compact and cancellous bone in terms of structure and function.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Compact bone is dense with osteons; cancellous bone is spongy with trabeculae...',
        ]);

        $this->assert($result['success'] === true, 'Compare question created successfully');
        $q = Question::getQuestionById((int)$result['id']);
        $this->assert($q['type'] === 'compare', 'Compare type stored correctly');
    }

    // --- Validation tests ---

    private function testMcqValidationNoOptions(): void {
        echo "\n--- 9. MCQ Validation: Missing Options ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'mcq',
            'question_text' => 'Test?',
            'answer_status' => 'available',
            'frequency'     => 1,
            'options'       => [],
            'correct_answer'=> '',
        ]);

        $this->assert($result['success'] === false, 'MCQ with no options rejected');
        $this->assert(!empty($result['errors']), 'MCQ validation errors returned');
    }

    private function testMcqValidationCorrectAnswerNotInOptions(): void {
        echo "\n--- 10. MCQ Validation: Correct Answer Not In Options ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'mcq',
            'question_text' => 'Test?',
            'answer_status' => 'available',
            'frequency'     => 1,
            'options'       => ['A', 'B', 'C'],
            'correct_answer'=> 'Z',
        ]);

        $this->assert($result['success'] === false, 'MCQ with correct_answer not in options rejected');
        $hasError = false;
        foreach ($result['errors'] ?? [] as $e) {
            if (str_contains($e, 'Correct answer must match')) { $hasError = true; break; }
        }
        $this->assert($hasError, 'Specific error message about correct_answer mismatch returned');
    }

    private function testMcqValidationMinimumOptions(): void {
        echo "\n--- 11. MCQ Validation: Single Option Not Enough ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'mcq',
            'question_text' => 'Test?',
            'answer_status' => 'available',
            'frequency'     => 1,
            'options'       => ['Only one'],
            'correct_answer'=> 'Only one',
        ]);
        $this->assert($result['success'] === false, 'MCQ with only 1 option rejected');
    }

    private function testMatchValidationNoItems(): void {
        echo "\n--- 12. Match Validation: Empty Items ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'match',
            'question_text' => 'Match test',
            'answer_status' => 'available',
            'frequency'     => 1,
            'left_items'    => [],
            'right_items'   => [],
            'matches'       => [],
        ]);
        $this->assert($result['success'] === false, 'Match with no items rejected');
    }

    private function testMatchValidationBadMapping(): void {
        echo "\n--- 13. Match Validation: Mapping Key Not In Left Items ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'match',
            'question_text' => 'Match test',
            'answer_status' => 'available',
            'frequency'     => 1,
            'left_items'    => ['A'],
            'right_items'   => ['X'],
            'matches'       => ['NONEXISTENT' => 'X'],
        ]);
        $this->assert($result['success'] === false, 'Match with mapping key not in left items rejected');
    }

    private function testValidationEmptyQuestionText(): void {
        echo "\n--- 14. Validation: Empty Question Text ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => '   ',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Some answer',
        ]);
        $this->assert($result['success'] === false, 'Empty/whitespace question_text rejected');
    }

    private function testValidationFrequencyMin(): void {
        echo "\n--- 15. Validation: Frequency Below Minimum ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'Valid question?',
            'answer_status' => 'available',
            'frequency'     => 0,
            'answer'        => 'Some answer',
        ]);
        $this->assert($result['success'] === false, 'Frequency of 0 rejected');
    }

    private function testValidationInvalidType(): void {
        echo "\n--- 16. Validation: Invalid Question Type ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'unknown_type',
            'question_text' => 'Valid?',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Answer',
        ]);
        $this->assert($result['success'] === false, 'Invalid question type rejected');
    }

    // --- Update / Delete tests ---

    private function testEditPreservesIdAndCreatedAt(): void {
        echo "\n--- 17. Edit: Preserves ID & created_at ---\n";
        $createResult = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'Original question text.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Original answer.',
        ]);
        $qId        = (int)$createResult['id'];
        $original   = Question::getQuestionById($qId);

        $updateResult = Question::updateQuestion($qId, [
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'Updated question text.',
            'answer_status' => 'available',
            'frequency'     => 2,
            'answer'        => 'Updated answer.',
        ]);
        $this->assert($updateResult['success'] === true, 'Update succeeds');

        $updated = Question::getQuestionById($qId);
        $this->assert((int)$updated['id'] === $qId, 'Question ID preserved after update');
        $this->assert($updated['created_at'] === $original['created_at'], 'created_at preserved after update');
        $this->assert($updated['question_text'] === 'Updated question text.', 'question_text updated correctly');
    }

    private function testEditUpdatesUpdatedAt(): void {
        echo "\n--- 18. Edit: updated_at changes ---\n";
        $createResult = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'complete',
            'question_text' => 'Timestamp check question.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Answer.',
        ]);
        $qId     = (int)$createResult['id'];
        $before  = Question::getQuestionById($qId);

        // Small delay to ensure different timestamp
        sleep(1);

        Question::updateQuestion($qId, [
            'subject_id'    => $this->subjectId,
            'type'          => 'complete',
            'question_text' => 'Timestamp check question (edited).',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Answer updated.',
        ]);
        $after = Question::getQuestionById($qId);
        $this->assert($after['updated_at'] !== $before['updated_at'], 'updated_at changes after edit');
    }

    private function testDeleteQuestion(): void {
        echo "\n--- 19. Delete Question ---\n";
        $createResult = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'This will be deleted.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Delete me.',
        ]);
        $qId = (int)$createResult['id'];

        $deleteResult = Question::deleteQuestion($qId);
        $this->assert($deleteResult['success'] === true, 'Delete returns success');

        $afterDelete = Question::getQuestionById($qId);
        $this->assert($afterDelete === null, 'Deleted question is no longer retrievable by ID');
    }

    // --- Retrieval & filtering ---

    private function testGetQuestionById(): void {
        echo "\n--- 20. Get Question By ID ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'mcq',
            'question_text' => 'Named retrieval test question.',
            'answer_status' => 'available',
            'frequency'     => 5,
            'options'       => ['Alpha', 'Beta', 'Gamma'],
            'correct_answer'=> 'Beta',
        ]);
        $q = Question::getQuestionById((int)$result['id']);
        $this->assert($q !== null, 'getQuestionById returns question');
        $this->assert($q['subject_name'] === 'Musculoskeletal', 'subject_name joined correctly');
        $this->assert($q['module_name']  === 'Anatomy',         'module_name joined correctly');
        $this->assert(isset($q['answer_data_decoded']), 'answer_data_decoded is present');
        $this->assert(is_array($q['answer_data_decoded']), 'answer_data_decoded is array');

        $notFound = Question::getQuestionById(999999);
        $this->assert($notFound === null, 'getQuestionById returns null for non-existent ID');
    }

    private function testGetQuestionsWithFilters(): void {
        echo "\n--- 21. Question Listing & Pagination ---\n";
        // Create a question in subject2 to test subject filtering
        Question::createQuestion([
            'subject_id'    => $this->subject2Id,
            'type'          => 'essay',
            'question_text' => 'Neuroanatomy essay question for filter test.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Neuroscience answer.',
        ]);

        $all   = Question::getQuestions(['module_id' => $this->moduleId], 100, 0);
        $count = Question::getQuestionsCount(['module_id' => $this->moduleId]);
        $this->assert(count($all) > 0, 'getQuestions returns non-empty result');
        $this->assert($count === count($all), 'getQuestionsCount matches actual count');
    }

    private function testSearchFilter(): void {
        echo "\n--- 22. Search Filter ---\n";
        // Unique term in question_text
        $unique = 'XYZUNIQUESTRINGABC';
        Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => "Describe {$unique} process.",
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Answer.',
        ]);
        $results = Question::getQuestions(['module_id' => $this->moduleId, 'search' => $unique], 50, 0);
        $this->assert(count($results) >= 1, 'Search filter finds question by unique text term');

        $noResults = Question::getQuestions(['module_id' => $this->moduleId, 'search' => 'ZYXNOMATCHTERM999'], 50, 0);
        $this->assert(count($noResults) === 0, 'Search filter returns empty for non-existent term');
    }

    private function testAnswerStatusFilter(): void {
        echo "\n--- 23. Answer Status Filter ---\n";
        Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'Unavailable answer question for filter.',
            'answer_status' => 'unavailable',
            'frequency'     => 1,
        ]);

        $unavailable = Question::getQuestions(['module_id' => $this->moduleId, 'answer_status' => 'unavailable'], 100, 0);
        $this->assert(count($unavailable) >= 1, 'answer_status=unavailable filter returns results');

        foreach ($unavailable as $q) {
            $this->assert($q['answer_status'] === 'unavailable', "All results have answer_status=unavailable (ID {$q['id']})");
        }
    }

    private function testTypeFilter(): void {
        echo "\n--- 24. Type Filter ---\n";
        $mcqOnly = Question::getQuestions(['module_id' => $this->moduleId, 'type' => 'mcq'], 100, 0);
        foreach ($mcqOnly as $q) {
            $this->assert($q['type'] === 'mcq', "Type filter returns only MCQ (ID {$q['id']})");
        }
        $this->assert(count($mcqOnly) >= 1, 'Type filter returns MCQ questions');
    }

    private function testModuleFilter(): void {
        echo "\n--- 25. Module Filter ---\n";
        $pdo = Database::getInstance();
        $pdo->prepare("INSERT INTO modules (name, description, created_at) VALUES (?, ?, ?)")
            ->execute(['Physiology', 'Body functions', date('Y-m-d H:i:s')]);
        $mod2Id = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO subjects (module_id, name, description, created_at) VALUES (?, ?, ?, ?)")
            ->execute([$mod2Id, 'Cardiac Physiology', 'Heart function', date('Y-m-d H:i:s')]);
        $subPhysId = (int)$pdo->lastInsertId();

        Question::createQuestion([
            'subject_id'    => $subPhysId,
            'type'          => 'essay',
            'question_text' => 'Describe cardiac output.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'CO = SV × HR',
        ]);

        $filtered = Question::getQuestions(['module_id' => $this->moduleId], 100, 0);
        foreach ($filtered as $q) {
            $this->assert((int)$q['module_id'] === $this->moduleId, "Module filter restricts to correct module (ID {$q['id']})");
        }
        $this->assert(count($filtered) >= 1, 'Module filter returns results for the Anatomy module');
    }

    private function testSubjectFilter(): void {
        echo "\n--- 26. Subject Filter ---\n";
        $filtered = Question::getQuestions(['subject_id' => $this->subject2Id], 100, 0);
        foreach ($filtered as $q) {
            $this->assert((int)$q['subject_id'] === $this->subject2Id, "Subject filter restricts to correct subject (ID {$q['id']})");
        }
        $this->assert(count($filtered) >= 1, 'Subject filter returns results for Neuroanatomy subject');
    }

    private function testQuestionSourceRecorded(): void {
        echo "\n--- 27. Question Source Recording ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'Source tracking test question.',
            'answer_status' => 'available',
            'frequency'     => 4,
            'answer'        => 'Answer.',
            'source_name'   => 'Midterm 2023',
            'exam_year'     => '2023',
            'exam_term'     => 'Fall',
        ]);

        $q = Question::getQuestionById((int)$result['id']);
        $this->assert($q['source_name'] === 'Midterm 2023', 'source_name stored and retrieved');
        $this->assert($q['exam_year']   === '2023',         'exam_year stored and retrieved');
        $this->assert($q['exam_term']   === 'Fall',         'exam_term stored and retrieved');
    }

    private function testDeleteCascadesToSource(): void {
        echo "\n--- 28. Delete Cascades To question_sources ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'essay',
            'question_text' => 'Cascade delete test question.',
            'answer_status' => 'available',
            'frequency'     => 1,
            'answer'        => 'Answer.',
            'source_name'   => 'Cascade Source',
            'exam_year'     => '2022',
            'exam_term'     => 'Spring',
        ]);
        $qId = (int)$result['id'];

        // Verify source exists
        $src = Database::fetchOne('SELECT id FROM question_sources WHERE question_id = ?', [$qId]);
        $this->assert($src !== null, 'question_sources row exists before delete');

        Question::deleteQuestion($qId);
        $srcAfter = Database::fetchOne('SELECT id FROM question_sources WHERE question_id = ?', [$qId]);
        $this->assert($srcAfter === null, 'question_sources row deleted after question deletion');
    }

    private function testUnavailableQuestionHasNullAnswer(): void {
        echo "\n--- 29. Unavailable Questions Store null Answer Payload ---\n";
        $result = Question::createQuestion([
            'subject_id'    => $this->subjectId,
            'type'          => 'complete',
            'question_text' => 'Null answer test (complete).',
            'answer_status' => 'unavailable',
            'frequency'     => 1,
        ]);

        $q = Question::getQuestionById((int)$result['id']);
        $decoded = json_decode($q['answer_data'], true);
        $this->assert(array_key_exists('answer', $decoded), 'complete/unavailable answer_data has "answer" key');
        $this->assert($decoded['answer'] === null, 'complete/unavailable answer stored as null');
    }
}

$runner = new Phase3TestRunner();
$runner->run();
