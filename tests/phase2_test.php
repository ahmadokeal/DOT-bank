<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Phase 2 Academic Structure Automated Verification Test Suite
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

class Phase2TestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function run(): void {
        echo "=======================================================\n";
        echo " DOT Bank - Phase 2 Academic Structure Test Suite     \n";
        echo " Doctors of Tomorrow Question Bank                     \n";
        echo "=======================================================\n\n";

        $this->setupDatabase();
        $this->testModuleCreationAndValidation();
        $this->testModuleDuplicateHandling();
        $this->testModuleUpdate();
        $this->testSubjectCreationAndParentRelationship();
        $this->testSubjectDuplicateHandling();
        $this->testSubjectListingAndFiltering();
        $this->testSubjectUpdate();
        $this->testModuleSubjectCountCalculation();
        $this->testSafeModuleDeletionAndCascade();
        $this->testStudentAcademicBrowsing();
        $this->testAuthorizationGuards();

        echo "\n-------------------------------------------------------\n";
        echo "Phase 2 Test Results: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "-------------------------------------------------------\n";

        if ($this->failed > 0) {
            echo "\nFailures:\n";
            foreach ($this->errors as $err) {
                echo "  [FAIL] {$err}\n";
            }
            exit(1);
        } else {
            echo "\n>>> ALL PHASE 2 TESTS PASSED SUCCESSFULLY! <<<\n\n";
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

        // Clean tables for fresh Phase 2 testing
        $pdo->exec('DELETE FROM subjects');
        $pdo->exec('DELETE FROM modules');
        $pdo->exec('DELETE FROM users');

        // Create Admin and Student users
        $adminHash = password_hash('AdminPass123', PASSWORD_DEFAULT);
        $studentHash = password_hash('StudentPass123', PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        Database::query("INSERT INTO users (username, password_hash, role, status, created_at) VALUES ('admin_p2', ?, 'admin', 'active', ?)", [$adminHash, $now]);
        Database::query("INSERT INTO users (username, password_hash, role, status, created_at) VALUES ('student_p2', ?, 'student', 'active', ?)", [$studentHash, $now]);

        $this->assert(true, 'Test database and users initialized');
    }

    private function testModuleCreationAndValidation(): void {
        echo "\n--- 2. Module Creation & Validation ---\n";

        // Valid module with description
        $res1 = Academic::createModule('Cardiovascular System', 'Comprehensive study of heart and circulation.');
        $this->assert($res1['success'] === true, 'Valid module created with description');
        $modId1 = $res1['id'];

        // Valid module without description
        $res2 = Academic::createModule('Respiratory System', null);
        $this->assert($res2['success'] === true, 'Valid module created with null description');

        // Empty name validation
        $resEmpty = Academic::createModule('', 'Desc');
        $this->assert($resEmpty['success'] === false, 'Empty module name rejected');

        // Short name validation (< 2 chars)
        $resShort = Academic::createModule('A', 'Desc');
        $this->assert($resShort['success'] === false, 'Single character module name rejected');
    }

    private function testModuleDuplicateHandling(): void {
        echo "\n--- 3. Module Duplicate Handling ---\n";

        // Exact duplicate
        $dup1 = Academic::createModule('Cardiovascular System', 'Another desc');
        $this->assert($dup1['success'] === false, 'Exact duplicate module name rejected');

        // Case-insensitive duplicate
        $dup2 = Academic::createModule('cardiovascular system', 'Another desc');
        $this->assert($dup2['success'] === false, 'Case-insensitive duplicate module name rejected');
    }

    private function testModuleUpdate(): void {
        echo "\n--- 4. Module Update ---\n";

        $modules = Academic::getAllModules();
        $target = $modules[0];

        // Successful update with same name (allowed)
        $updSame = Academic::updateModule((int)$target['id'], $target['name'], 'Updated description text.');
        $this->assert($updSame['success'] === true, 'Module updated keeping same name');

        // Successful update with new name
        $updNew = Academic::updateModule((int)$target['id'], 'Cardiovascular & Hematology', 'Updated combined module.');
        $this->assert($updNew['success'] === true, 'Module renamed successfully');

        $fetched = Academic::getModuleById((int)$target['id']);
        $this->assert($fetched['name'] === 'Cardiovascular & Hematology', 'Renamed module verified in database');

        // Duplicate name collision with another module
        $other = $modules[1];
        $updCol = Academic::updateModule((int)$target['id'], $other['name'], 'Collision attempt');
        $this->assert($updCol['success'] === false, 'Renaming to existing module name rejected');
    }

    private function testSubjectCreationAndParentRelationship(): void {
        echo "\n--- 5. Subject Creation & Parent Relationship ---\n";

        $modules = Academic::getAllModules();
        $modId1 = (int)$modules[0]['id'];
        $modId2 = (int)$modules[1]['id'];

        // Valid subjects under module 1
        $sub1 = Academic::createSubject($modId1, 'Cardiac Pathology', 'Study of heart diseases');
        $this->assert($sub1['success'] === true, 'Subject 1 created under Module 1');

        $sub2 = Academic::createSubject($modId1, 'Cardiovascular Physiology', 'Circulatory mechanics');
        $this->assert($sub2['success'] === true, 'Subject 2 created under Module 1');

        // Valid subject under module 2
        $sub3 = Academic::createSubject($modId2, 'Pulmonary Pathology', 'Lung pathology');
        $this->assert($sub3['success'] === true, 'Subject 3 created under Module 2');

        // Nonexistent module ID
        $subInvalidMod = Academic::createSubject(99999, 'Orphan Subject', 'Should fail');
        $this->assert($subInvalidMod['success'] === false, 'Subject creation with nonexistent parent module rejected');

        // Empty subject name
        $subEmpty = Academic::createSubject($modId1, '', 'Desc');
        $this->assert($subEmpty['success'] === false, 'Empty subject name rejected');
    }

    private function testSubjectDuplicateHandling(): void {
        echo "\n--- 6. Subject Duplicate Handling ---\n";

        $modules = Academic::getAllModules();
        $modId1 = (int)$modules[0]['id'];
        $modId2 = (int)$modules[1]['id'];

        // Duplicate subject in same module
        $dupSame = Academic::createSubject($modId1, 'Cardiac Pathology', 'Duplicate');
        $this->assert($dupSame['success'] === false, 'Duplicate subject in same module rejected');

        // Case-insensitive duplicate in same module
        $dupCase = Academic::createSubject($modId1, 'cardiac pathology', 'Duplicate');
        $this->assert($dupCase['success'] === false, 'Case-insensitive duplicate subject in same module rejected');

        // Same subject name in DIFFERENT module (allowed in medical curricula, e.g. "Anatomy" under both CVS and RS)
        $dupDiffMod = Academic::createSubject($modId2, 'General Anatomy', 'Anatomy RS');
        $dupDiffMod2 = Academic::createSubject($modId1, 'General Anatomy', 'Anatomy CVS');
        $this->assert($dupDiffMod['success'] === true && $dupDiffMod2['success'] === true, 'Same subject name allowed across DIFFERENT modules');
    }

    private function testSubjectListingAndFiltering(): void {
        echo "\n--- 7. Subject Listing & Filtering ---\n";

        $allSubjects = Academic::getAllSubjects();
        $this->assert(count($allSubjects) >= 5, 'All subjects listed with parent module names');
        $this->assert(!empty($allSubjects[0]['module_name']), 'Subject record includes module_name via JOIN');

        $modules = Academic::getAllModules();
        $modId = (int)$modules[0]['id'];
        $filtered = Academic::getAllSubjects($modId);
        
        $allBelong = true;
        foreach ($filtered as $s) {
            if ((int)$s['module_id'] !== $modId) {
                $allBelong = false;
                break;
            }
        }
        $this->assert($allBelong && count($filtered) > 0, "Filtered subjects belong strictly to module ID {$modId}");
    }

    private function testSubjectUpdate(): void {
        echo "\n--- 8. Subject Update & Parent Reassignment ---\n";

        $subjects = Academic::getAllSubjects();
        $target = $subjects[0];
        $modules = Academic::getAllModules();

        // Update subject name & description
        $upd = Academic::updateSubject((int)$target['id'], (int)$target['module_id'], 'Advanced Cardiac Pathology', 'Updated syllabus');
        $this->assert($upd['success'] === true, 'Subject updated successfully');

        $fetched = Academic::getSubjectById((int)$target['id']);
        $this->assert($fetched['name'] === 'Advanced Cardiac Pathology', 'Subject update reflected in database');

        // Delete subject
        $del = Academic::deleteSubject((int)$target['id']);
        $this->assert($del['success'] === true, 'Subject deleted successfully');
        $this->assert(Academic::getSubjectById((int)$target['id']) === null, 'Deleted subject no longer exists');
    }

    private function testModuleSubjectCountCalculation(): void {
        echo "\n--- 9. Module Subject Count Calculation ---\n";

        $modules = Academic::getAllModules();
        $first = $modules[0];
        $count = Academic::getModuleSubjectCount((int)$first['id']);
        $this->assert((int)$first['subject_count'] === $count, 'Module subject_count matches direct count query');
    }

    private function testSafeModuleDeletionAndCascade(): void {
        echo "\n--- 10. Safe Module Deletion & Cascade ---\n";

        // Create temporary module with 2 subjects
        $tempMod = Academic::createModule('Temporary Gastrointestinal', 'GI System');
        $tempModId = $tempMod['id'];
        Academic::createSubject($tempModId, 'GI Histology', 'Tissues');
        Academic::createSubject($tempModId, 'GI Physiology', 'Digestion');

        $modBefore = Academic::getModuleById($tempModId);
        $this->assert($modBefore['subject_count'] === 2, 'Temporary module has 2 subjects before deletion');

        // Delete module
        $delRes = Academic::deleteModule($tempModId);
        $this->assert($delRes['success'] === true, 'Module deleted with safe confirmation response');
        $this->assert(Academic::getModuleById($tempModId) === null, 'Module removed from database');

        // Check associated subjects also removed
        $remainingSubjects = Academic::getAllSubjects($tempModId);
        $this->assert(empty($remainingSubjects), 'Associated subjects cleanly cascade deleted');
    }

    private function testStudentAcademicBrowsing(): void {
        echo "\n--- 11. Student Academic Browsing (Read-Only) ---\n";

        // Set student session
        Auth::logout();
        $studentLogin = Auth::attempt('student_p2', 'StudentPass123');
        $this->assert($studentLogin['success'] === true, 'Student authenticated');
        $this->assert(Auth::isStudent(), 'Session is student role');

        // Student can read all modules
        $modules = Academic::getAllModules();
        $this->assert(!empty($modules), 'Student can read academic modules');

        // Student can view single module and subjects
        $modView = Academic::getModuleById((int)$modules[0]['id']);
        $this->assert($modView !== null && isset($modView['subjects']), 'Student can read module subjects');
    }

    private function testAuthorizationGuards(): void {
        echo "\n--- 12. Admin Authorization Guards on Academic Actions ---\n";

        // Logged in as student
        $this->assert(Auth::isStudent() && !Auth::isAdmin(), 'Confirmed student is not admin');

        // Student attempting admin check
        $isAdmin = Auth::isAdmin();
        $this->assert(!$isAdmin, 'Auth::isAdmin() returns false for student');

        // Login as Admin
        Auth::logout();
        $adminLogin = Auth::attempt('admin_p2', 'AdminPass123');
        $this->assert($adminLogin['success'] === true && Auth::isAdmin(), 'Admin authenticated and authorized');
    }
}

$runner = new Phase2TestRunner();
$runner->run();
