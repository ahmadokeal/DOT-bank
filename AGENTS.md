# DOT Bank — AI Agent Onboarding & Project Continuity Guide

> **Doctors of Tomorrow Question Bank**  
> Official Platform Documentation & Source of Truth for Developers and AI Coding Agents.

---

## 1. Project Overview

**DOT Bank** (**Doctors of Tomorrow Question Bank**) is a medical question bank and examination practice web platform designed for medical students.

### Purpose
The platform transforms previous medical school exam questions (traditionally distributed in unstructured, mixed PDF formats) into an organized, searchable question bank structured hierarchically:
```text
Module (Medical System / Discipline)
 └── Subject (Course / Sub-discipline)
      └── Questions (MCQ, Complete, Match, Compare, Essay, True / False)
```

### Core Problems Solved
- Fragmented past exam questions organized into structured curriculum modules and subjects.
- Student self-study browsing without taking a test.
- Deterministic, customizable quiz generation with automated and self-graded feedback.
- Administrative question curation, manual entry, and external JSON imports.

---

## 2. Current Project Status

- **Current Active Phase**: **Phase 7 Core UI + Student Quiz UI + Arabic RTL Landing Page completed** — Student Dashboard language propagated across shared Core UI, Match responsive containment added, and Student Quiz Builder (hierarchy/polish), Quiz Taking (complete redesign), Quiz Confirmation (review/summary), Quiz Result Review (status-aware per-pair), Match display randomization (Fisher-Yates, session-preserved), Match partial-credit scoring fix, and Arabic RTL public landing page delivered. All responsive at 320-1280.
- **Phase 1 (Foundation)**: Completed & verified (83/83 automated tests passed).
- **Phase 2 (Academic Structure)**: Completed & verified (38/38 automated tests passed).
- **Phase 3 (Question Bank)**: Completed & verified (92/92 automated tests passed).
- **Phase 4 (JSON Import)**: Completed & verified (16/16 dedicated assertions).
- **Phase 5 (Quiz Engine)**: Completed & verified (30/30 dedicated assertions).
- **Phase 6 (Grading & Immediate Results)**: Completed & verified (20/20 dedicated assertions; persistent Quiz History removed).
- **Pre-Phase 7 Hardening**: Completed & verified (29/29 dedicated assertions).
- **Additional Test Suites**: True / False (20), Exam Appearances (19), Frequency Consistency (18), Deletion Integrity (11), Manual Import Fixture (15), Match Scoring (20), Match Display Order (16).
- **Total Verified Assertions**: 427 across all test suites (0 failures).

---

## 3. Technology Stack & Constraints

- **Backend**: PHP 8.2 (Modular procedural / OOP service architecture, standard PHP runtime).
- **Database**: SQLite 3 (PDO SQLite extension).
- **Frontend**: Semantic HTML5, Vanilla CSS3 (Custom Academic/Medical Design System), Vanilla JavaScript, and Font Awesome Free 6.5.2 via CDN (No external UI frameworks or build steps).
- **Local Environment**: XAMPP (Apache 2.4 + PHP 8.2 + SQLite) on Windows (`D:\XAMPP\htdocs\DOT Bank`).

### Hard Architectural Constraints
- **SQLite is intentional**: Do NOT introduce MySQL, PostgreSQL, Redis, Docker, or external database servers. The application is built to run on SQLite across both local development and standard free PHP hosting.
- **No Platform AI Dependency**: DOT Bank must NOT call AI APIs (OpenAI, Gemini, Anthropic, etc.) for runtime question generation or subjective grading. Questions originate from manual admin entry or offline JSON imports generated externally (e.g., via NotebookLM).
- **Zero Node/NPM/Python Dependency**: No background daemon workers, build tools, or server runtimes outside Apache/PHP.

---

## 4. Architecture & Directory Structure

The repository follows a clean, decoupled PHP architecture with root-level and `public/` entry compatibility:

```text
DOT Bank/
├── config/
│   └── config.php            # App constants, environment detection, session security, autoloader
├── core/
│   ├── Academic.php          # Academic hierarchy service (Module & Subject CRUD, validation)
│   ├── Auth.php              # Session auth, password hashing, rate limiting, route guards
│   ├── CSRF.php              # Cryptographic CSRF token generation and timing-safe verification
│   ├── Database.php          # PDO SQLite singleton, WAL mode, foreign keys, transactions
│   ├── JsonImporter.php      # JSON import parser, validator, preview, transactional import
│   ├── Question.php          # Question management service (MCQ, Complete, Match, Compare, Essay, True / False CRUD & filters)
│   ├── Quiz.php              # Quiz planning (exact/closest), persistence, submission, grading
│   └── View.php              # Template rendering engine, layout wrapper, flash messages, escaping
├── database/
│   └── schema.sql            # Full SQLite schema (users, modules, subjects, questions, quizzes, config)
├── storage/                  # Protected SQLite database, lock files, and session storage
│   ├── .htaccess             # Apache direct access denial ("Require all denied" / "Deny from all")
│   ├── dot_bank.sqlite       # Primary SQLite database file
│   └── installed.lock        # Installation lock file generated by setup wizard
├── public/                   # Document-root accessible controllers and static assets
│   ├── assets/
│   │   ├── css/app.css       # Academic & Medical Design System stylesheet
│   │   └── js/app.js         # Minimal client-side interactivity
│   ├── admin/
│   │   ├── dashboard.php     # Admin dashboard controller
│   │   ├── modules.php       # Admin module catalog controller
│   │   ├── module-form.php   # Admin create/edit module controller
│   │   ├── module-delete.php # Admin safe module delete controller
│   │   ├── subjects.php      # Admin subject catalog & filter controller
│   │   ├── subject-form.php  # Admin create/edit subject controller
│   │   ├── subject-delete.php# Admin subject delete controller
│   │   ├── questions.php     # Admin question catalog controller
│   │   ├── question-form.php # Admin create/edit question controller
│   │   ├── question-view.php # Admin question detail view controller
│   │   ├── question-delete.php# Admin question delete controller
│   │   └── import.php        # Admin JSON import controller
│   ├── student/
│   │   ├── dashboard.php     # Student portal dashboard controller
│   │   ├── modules.php       # Student curriculum module browser
│   │   ├── module-view.php   # Student module & subjects reader
│   │   ├── questions.php     # Student question browser controller
│   │   ├── quiz-builder.php  # Student quiz builder controller
│   │   ├── quiz-take.php     # Student quiz taking controller
│   │   ├── quiz-submit.php   # Student quiz submission controller
│   │   ├── quiz-result.php   # Student quiz result controller
│   │   └── quiz-discard.php  # Student active quiz discard controller
│   ├── index.php             # Public entry point: Arabic RTL landing page (unauthenticated) / dashboard redirect (authenticated)
│   ├── login.php             # User login controller
│   ├── register.php          # Student registration controller
│   ├── logout.php            # Logout controller
│   └── setup.php             # Initial installation wizard
├── views/                    # Presentation templates and partials
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── import.php
│   │   ├── modules/ (index.php, form.php, delete.php)
│   │   ├── subjects/ (index.php, form.php, delete.php)
│   │   └── questions/ (index.php, form.php, view.php, delete.php)
│   ├── student/
│   │   ├── dashboard.php
│   │   ├── modules/ (index.php, view.php)
│   │   ├── questions/ (index.php)
│   │   └── quizzes/ (builder.php, take.php, result.php)
│   ├── auth/ (login.php, register.php, setup.php)
│   ├── layouts/ (main.php, auth.php)
│   └── partials/ (header.php, nav.php, flash.php, footer.php)
├── tests/
│   ├── bootstrap.php           # Shared disposable test application/database bootstrap
│   ├── phase1_test.php       # Phase 1 Foundation test suite (83 assertions)
│   ├── phase2_test.php       # Phase 2 Academic Structure test suite (38 assertions)
│   ├── phase3_test.php       # Phase 3 Question Bank test suite (92 assertions)
│   ├── phase4_test.php       # Phase 4 JSON Import test suite (15 assertions)
│   ├── phase5_test.php       # Phase 5 Quiz Engine test suite (27 assertions)
│   ├── phase6_test.php       # Phase 6 Grading & Results test suite (19 assertions)
│   ├── pre_phase7_hardening_test.php  # Pre-Phase 7 Hardening test suite (29 assertions)
│   ├── exam_appearances_test.php      # Exam Appearances CRUD test suite (19 assertions)
│   ├── frequency_consistency_test.php # Frequency/Appearance sync test suite (18 assertions)
│   ├── deletion_integrity_test.php    # Academic deletion integrity test suite (11 assertions)
│   └── manual_import_fixture_test.php # Manual JSON import fixture test suite (15 assertions)
├── tools/                    # CLI utilities for development & maintenance
│   ├── seed_demo.php         # Seeds deterministic 300-question demo dataset
│   ├── reset_demo.php        # Removes demo module and its quizzes
│   ├── reset_application.php # Full application reset (data + lock file)
│   ├── migrate_exam_appearances.php  # Migration to canonical exam appearances
│   ├── repair_frequency_consistency.php # Repairs frequency/appearance sync
│   ├── clear_quiz_data.php   # Removes all quiz data
│   └── qa_phase5_demo.php    # Phase 5 QA harness
├── assets/                   # Mirrored root assets for standard shared hosting
├── admin/                    # Root forwarders to public/admin/ controllers
│   ├── questions.php
│   ├── question-form.php
│   ├── question-view.php
│   ├── question-delete.php
│   └── import.php
├── student/                  # Root forwarders to public/student/ controllers
│   ├── questions.php
│   ├── quiz-builder.php
│   ├── quiz-take.php
│   ├── quiz-submit.php
│   ├── quiz-result.php
│   └── quiz-discard.php
├── index.php                 # Root forwarder to public/index.php
├── setup.php                 # Root forwarder to public/setup.php
├── login.php                 # Root forwarder to public/login.php
├── register.php              # Root forwarder to public/register.php
├── logout.php                # Root forwarder to public/logout.php
├── AGENTS.md                 # Primary AI Agent & Developer Source of Truth
├── README.md                 # Developer-facing project overview and setup guide
└── implementation_plan.md    # Active implementation plan
```

---

## 5. Core Components & Responsibilities

### 1. `config/config.php`
- Defines directory constants (`ROOT_PATH`, `CORE_PATH`, `STORAGE_PATH`, `VIEWS_PATH`, `PUBLIC_PATH`, `DB_FILE`, `LOCK_FILE`).
- Configures secure session cookies (`httponly = 1`, `use_only_cookies = 1`, `samesite = Lax`).
- Dynamically resolves `BASE_URL` for web and CLI environments.
- Implements class autoloader for `core/*.php`.
- Provides global helper functions: `url()`, `e()` (HTML escaping), `is_installed()`.

### 2. `core/Database.php`
- Singleton PDO SQLite instance.
- Automatically creates `storage/` directory if missing.
- Sets `PRAGMA foreign_keys = ON` and `PRAGMA journal_mode = WAL` on every connection.
- Provides `query()`, `fetchOne()`, `fetchAll()`, `execute()`, `lastInsertId()`, and `transaction(callable)`.

### 3. `core/Auth.php`
- Role management: strictly `'admin'` or `'student'`.
- Authentication using `password_verify()` against `password_hash($password, PASSWORD_DEFAULT)`.
- Session fixation defense: calls `session_regenerate_id(true)` upon successful login.
- Brute-force rate limiter: locks login attempts after 5 consecutive failures for 300 seconds (5 minutes).
- Route guards: `Auth::requireLogin()`, `Auth::requireAdmin()`, `Auth::requireStudent()`.

### 4. `core/CSRF.php`
- Generates 64-hex-character cryptographically secure tokens (`random_bytes(32)`).
- Renders hidden input fields via `CSRF::field()`.
- Verifies tokens timing-safely via `hash_equals()`.

### 5. `core/Academic.php`
- Business logic service for academic modules and subjects.
- Module validation: name required, 2–100 chars, case-insensitive uniqueness.
- Subject validation: name required, 2–100 chars, parent module validation, duplicate name prevention within the same module (allowed across different modules).
- Safe cascading deletion: calculates subject count before deletion and removes child records cleanly inside a transaction.
- Pre-flight checks for quiz dependencies before module/subject/question deletion (returns controlled error if dependent active quizzes exist).

### 6. `core/Question.php`
- Business logic service for question bank management.
- Supports six question types: `mcq`, `complete`, `match`, `compare`, `essay`, `true_false`.
- Validates question text, subject_id, positive frequency, and type-specific rules.
- Builds and decodes JSON-serialized payload stored in `answer_data` for all supported question types (available and unavailable answer states).
- Handles creation, updates, and deletion of questions and associated sources transactionally.
- Multi-criteria filtering (module, subject, type, answer status, search text, exam year, exam term, source name) with pagination.
- Canonical exam appearances management: `appearances[]` with `source_name` (final/end_module), `exam_year` (integer), `exam_term` (first/second).
- Frequency synchronization: when exam appearances exist, frequency must equal total recorded appearances; zero-appearance questions retain editable frequency.
- Duplicate detection via content identity hashing (normalized question text + type-specific answer structure).

### 7. `core/Quiz.php`
- Quiz planning service with deterministic algorithms:
  - **Largest-remainder allocation** for percentage-based distributions.
  - **Max-flow (Edmonds-Karp)** for exact subject×type matrix feasibility.
  - **Closest-possible fallback**: greedy single-question allocation minimizing squared deviation from requested subject/type counts, restricted to explicitly requested dimensions.
- Question selection: Fisher-Yates shuffle using `random_int()` after allocation; duplicate IDs rejected.
- Quiz persistence: writes `quizzes` and ordered `quiz_questions` in a single transaction.
- Match display randomization: Fisher-Yates (`random_int`) left/right independently shuffled per Match question; canonical `left_items/right_items/matches` never mutated; Student Browse shuffles per request, Quiz Taking generates once per `quizId/qqId` and preserves in `$_SESSION['_quiz_match_display'][$quizId][$qqId]` for stable refresh and Result Review.
- Submission & grading: validates answer formats, auto-grades MCQ (exact option match), Match per pair (partial credit, each pair 1 scoring unit, `match_correct_pairs/match_total_pairs/match_pair_results` ordered by display-left with fallback to canonical), and True / False (exact `true`/`false` string match), stores self-graded types with `is_correct = NULL`, and calculates objective score (`correct_units / total_auto_graded_units *100`, `total_units = MCQ 1 + True/False 1 + Match pairs`) with existing rounding.
- Transient lifecycle: an existing quiz row is in progress; submission or discard transactionally deletes the quiz, question links, and answer rows after preparing the immediate result and cleans `$_SESSION['_quiz_match_display'][$quizId]`. No persistent history exists.
- Active quiz discard endpoint (CSRF-protected, student-owned).

### 8. `core/JsonImporter.php`
- JSON import parser, validator, and transactional importer.
- External contract: top-level `questions[]` with `type`, `question`, `frequency`, `appearances[]` (source/year/term), `choices`/`answer`/`pairs` per type.
- Normalizes legacy source strings (e.g., "Final Exam 2025" → `source_name: "final", exam_year: 2025`).
- Preview workflow: validates, reports invalid records, duplicate candidates, merge/reuse impact, answer availability summary.
- Transactional import: creates new questions (`answer_origin: 'json_import'`), merges new appearances into existing questions (by content identity), preserves existing answers, populates unavailable answers, records conflicts for review.
- Frequency consistency enforced: imported `frequency` must equal total stored appearances for that question.

### 9. `core/View.php`
- Renders view templates within selected layouts (`main` or `auth`).
- Flash messaging queue (`View::flash('success|error|warning|info', '...')`).

---

## 6. Database Schema & Storage

The SQLite database is located at `storage/dot_bank.sqlite`.

### Schema Summary (`database/schema.sql`)
1. `users`: `id`, `username` (UNIQUE COLLATE NOCASE), `password_hash`, `role` (CHECK 'admin'|'student'), `status` (CHECK 'active'|'disabled'), `created_at`.
2. `modules`: `id`, `name` (UNIQUE), `description`, `created_at`.
3. `subjects`: `id`, `module_id` (FK $\to$ `modules.id` ON DELETE CASCADE), `name`, `description`, `created_at`, `UNIQUE(module_id, name)`.
4. `questions`: `id`, `subject_id` (FK $\to$ `subjects.id` ON DELETE CASCADE), `type` (CHECK 'mcq'|'complete'|'match'|'compare'|'essay'|'true_false'), `question_text`, `answer_data` (JSON), `answer_status` (CHECK 'available'|'unavailable'), `answer_origin` (CHECK 'manual'|'json_import'), `frequency` (INTEGER $\ge$ 1), `created_at`, `updated_at`.
5. `question_sources`: `id`, `question_id` (FK $\to$ `questions.id` ON DELETE CASCADE), `source_name`, `exam_year`, `exam_term`, `created_at`.
6. `question_conflicts`: `id`, `question_id` (FK $\to$ `questions.id` ON DELETE CASCADE), `incoming_answer_data` (TEXT), `incoming_appearances` (TEXT), `status` (CHECK 'review'|'resolved'), `created_at`.
7. `quizzes`: `id`, `user_id` (FK $\to$ `users.id`), `module_id` (FK $\to$ `modules.id`), `total_questions`, `created_at`.
8. `quiz_questions`: `id`, `quiz_id` (FK $\to$ `quizzes.id` ON DELETE CASCADE), `question_id` (FK $\to$ `questions.id`), `question_order`.
9. `quiz_answers`: `id`, `quiz_question_id` (FK $\to$ `quiz_questions.id` ON DELETE CASCADE), `student_answer`, `is_correct` (INTEGER nullable: 1/0/NULL).
10. `app_config`: `key` (PRIMARY KEY), `value`, `updated_at`.

### Indexes
Indexes exist on all foreign keys (`module_id`, `subject_id`, `question_id`, `quiz_id`, `user_id`, `quiz_question_id`) and search filters (`type`, `answer_status`, `frequency`).
- Unique index on `question_sources(question_id, source_name, COALESCE(exam_year, -1), COALESCE(exam_term, ''))` to prevent duplicate exam appearances.

---

## 7. Authentication & Authorization Rules

1. **Two Roles Only**:
   - `admin`: Faculty/platform administrator. Exactly one created during initial setup wizard. There is NO public "Register as Admin" option.
   - `student`: Medical student accounts. Created via public registration (`register.php`).
2. **Access Control**:
   - All `/admin/*` actions require `Auth::requireAdmin()`.
   - All `/student/*` actions require `Auth::requireStudent()`.
   - Unauthenticated requests redirect to `login.php` with a flash message.
   - Students attempting to access Admin endpoints are blocked and redirected.
3. **State-Changing Security**:
   - Every POST action MUST verify CSRF via `CSRF::verify()`.
   - All user-supplied output MUST be sanitized with `e()` (`htmlspecialchars`).

---

## 8. Current Feature Status

### ✅ Implemented (Phases 1–6 + Pre-Phase 7 Hardening)

**Phase 1 — Foundation**
- **Initial Setup Wizard**: Database initialization, schema execution, admin account creation, and permanent locking via `installed.lock`.
- **Authentication**: Session-based login, student registration, secure logout, brute-force rate-limiting (5 attempts/5 min), and password hashing.
- **Layouts & Helpers**: Medical/Academic design system, flash messaging, CSRF protection, URL generation, HTML escaping.

**Phase 2 — Academic Structure**
- **Admin Module Management**: List modules with subject counts, create module, edit module, safe deletion with subject cascade warning.
- **Admin Subject Management**: List subjects, interactive module filter, create subject, edit subject, delete subject.
- **Student Curriculum Browser**: Read-only module/subject browsing.

**Phase 3 — Question Bank**
- **Question CRUD**: Admin manual question creation, editing, details view, and cascade deletion for six question types (MCQ, Complete, Match, Compare, Essay, True / False).
- **Answer Availability**: Handles questions both with answers ("available") and without answers ("unavailable").
- **Exam Appearances**: Canonical `question_sources` with `source_name` (final/end_module), `exam_year` (integer), `exam_term` (first/second); multiple appearances per question supported.
- **Frequency Synchronization**: When exam appearances exist, frequency equals total recorded appearances; zero-appearance questions retain editable frequency.
- **Search & Filters**: Multi-criteria filters (module, subject, type, answer status, exam year, exam term, source name, free text search) with pagination.
- **Student Browsing**: Read-only question browser with interactive "Show / Hide Answer" toggling for students.
- **Duplicate Detection**: Content identity hashing warns admin but never silently merges/deletes.

**Phase 4 — JSON Import**
- **Parser & Validator**: Validates external JSON contract (6 question types, appearances, choices/pairs/answers; True / False uses real JSON booleans).
- **Preview Workflow**: Reports invalid records, duplicate candidates, merge/reuse impact, answer availability summary.
- **Transactional Import**: Creates new questions (`answer_origin: 'json_import'`), merges appearances into existing questions (by content identity), preserves existing answers, populates unavailable answers, records conflicts in `question_conflicts` for review.
- **Legacy Normalization**: Accepts legacy source strings (e.g., "Final Exam 2025") for compatibility.

**Phase 5 — Quiz Engine**
- **Quiz Builder**: Module/subject selection, total questions, optional type/subject percentage distributions.
- **Planning Algorithms**: Largest-remainder allocation, max-flow (Edmonds-Karp) for exact subject×type matrices, closest-possible fallback with squared-deviation minimization.
- **Constraints**: Only explicitly entered percentage dimensions are hard constraints; unspecified dimensions remain flexible.
- **Question Selection**: Fisher-Yates shuffle with `random_int()`; duplicate IDs rejected.
- **Quiz Taking**: MCQ and True / False radio options, Match dropdown mappings, text areas for self-graded types; active quiz discard endpoint.
- **Persistence**: `quizzes` + `quiz_questions` written in single transaction.

**Phase 6 — Grading & Immediate Results**
- **Auto-grading**: MCQ (exact option match), Match per pair with partial credit, and True / False (exact normalized string match).
- **Self-graded**: Complete, Compare, Essay stored with `is_correct = NULL`.
- **Scoring**: Objective percentage = (correct auto-graded scoring units / total auto-graded scoring units) × 100; Match remains one question while each pair is one scoring unit. NULL if no auto-graded units exist.
- **Transient Lifecycle**: Existing quiz rows are in-progress; submission or discard transactionally removes the quiz, question links, and answer rows; no persistent history.
- **Result Review**: Immediate read-only result with per-question correct answers and student responses.
- **Idempotency**: Existing answer rows make repeat submissions fail safely.

**Pre-Phase 7 Hardening**
- **Exam Appearances CRUD**: Admin form supports zero/one/multiple appearances with add/remove controls; validation rejects missing/invalid/duplicate rows.
- **Frequency/Appearance Consistency**: Repair tool and validation enforce frequency = appearance count when appearances exist.
- **Deletion Integrity**: Pre-flight quiz dependency checks before question/subject/module deletion; controlled error instead of FK violation.
- **UX Fixes**: Match answer placeholder, "Accept and Start Quiz" wording, subject names in closest previews, improved JSON preview impact reporting, Frequency/Exam Appearance help text.

### ✅ Phase 7 Core UI + Student Quiz UI — Completed
- **Vanilla CSS Foundation**: Shared medical/academic design tokens and reusable primitives are maintained in both CSS asset paths used by the root and `public/` entry points.
- **Responsive Navbar**: Role-aware navigation links are preserved with a Vanilla HTML/CSS/JS mobile menu and accessible state updates.
- **Core UI Redesign**: Admin/student dashboards, academic catalogs, question-bank surfaces, forms, tables, cards, alerts, empty states, and responsive presentation use the shared Vanilla CSS system. The approved Student Dashboard is the visual reference for colors, typography, spacing, surfaces, borders, shadows, buttons, icons, hierarchy, and responsive behavior; no separate palette or design direction should be introduced for Core UI pages.
- **Font Awesome Enhancement**: Font Awesome Free 6.5.2 is loaded centrally with verified SRI in both layouts; a small set of decorative icons enhances navigation, dashboards, catalogs, actions, and search while preserving text labels and accessibility.
- **Mirrored CSS Assets**: `assets/css/app.css` and `public/assets/css/app.css` are kept synchronized. The supplemental `quiz-match-responsive.css` and `student-match-responsive.css` files are mirrored as well; update both asset paths when changing shared presentation.
- **Long-Text Containment**: Shared content containers use `min-width: 0` and scoped wrapping rules so normal text and extremely long unbroken strings remain inside their containers without truncation or ellipsis. Match item layouts use responsive grid constraints and wrapping labels/selects.
- **CSS Organization**: Reusable presentation patterns are extracted into shared classes in `app.css`; page-specific styling remains local where it is genuinely unique. Avoid adding new inline styles when an existing shared component or a small reusable class is appropriate.
- **Documentation**: `README.md` documents the verified architecture, setup, supported question types, import contract, testing, security, and development workflow.
- **Student Quiz UI (Builder/Taking/Confirmation/Result):** Complete redesign of Quiz Taking header, progress, question cards, MCQ/True-False/Match/textarea answer areas, submit/discard, plus polished Builder hierarchy, Confirmation summary, and Result Review status-aware per-pair Match review — all Vanilla CSS, responsive 320-1280, long-text safe, no inline bloat.
- **Match Display Randomization:** Fisher-Yates (`random_int`) left/right independently shuffled per Match; Student Browse per-request, Quiz Taking per-quiz preserved in `$_SESSION['_quiz_match_display']` for stable Result Review; canonical `matches` unchanged, grading canonical.
- **Match Partial-Credit Scoring Fix:** Result summary now uses scoring units (`auto_graded = MCQ 1 + True/False 1 + Match pairs`), `correct/incorrect/unanswered` per-pair, `score = correct/auto*100`, visible Q count remains question count.
- **Test Database Isolation**: All regression suites load `tests/bootstrap.php`, which runs them from a temporary application copy with a disposable SQLite database and installation lock. Tests must never modify `storage/dot_bank.sqlite` or `storage/installed.lock`.
- **Arabic RTL Public Landing Page:** `public/index.php` now serves a complete Arabic RTL landing page for unauthenticated visitors (hero, features, question types, how-it-works, final CTA, footer), while authenticated users redirect to their dashboards. Built with Vanilla CSS using existing design tokens; no new CSS framework; responsive 320-1280.

---

## 9. Non-Negotiable Product Rules

Future agents MUST adhere to these rules without alteration:

1. **No AI Generation in App**: The platform does NOT generate questions using AI APIs. Questions come from manual admin entry or externally generated JSON files.
2. **Browsing Allowed**: Students must always be able to browse questions and answers directly without taking a quiz.
3. **Grading Separation**:
   - Auto-graded: `mcq`, `match`, `true_false`.
   - Self-graded: `complete`, `compare`, `essay`. (No AI grading or complicated fuzzy matching).
4. **Questions Without Answers**: Valid questions may have `answer: null` (`answer_status = 'unavailable'`). They must remain in the database so the admin can add the answer later.
5. **IDs Are Internal**: JSON import files must NOT contain database IDs. IDs are always auto-generated by the database.
6. **Duplicates Allowed**: Duplicate questions in the question bank are permitted. The system may warn the admin, but must never silently delete or merge questions.
7. **Frequency Meaning**: `frequency` indicates how many times a question appeared in source exams, not an inherent academic importance score.
8. **Closest Possible Quiz**: When requested quiz constraints cannot be satisfied, the system calculates the closest feasible distribution. The student must explicitly review and accept this distribution before starting the quiz.
9. **Option Shuffling**: MCQ options must be shuffled during quizzes while preserving the correct answer association (internal answers must not rely merely on letters like `"B"`).

---

## 10. Development Phases Roadmap

| Phase | Description | Status |
|---|---|---|
| **Phase 1** | Foundation (Config, SQLite, Setup Wizard, Auth, Layouts, Tests) | **Completed** |
| **Phase 2** | Academic Structure (Module CRUD, Subject CRUD, Student Browsing, Tests) | **Completed** |
| **Phase 3** | Question Bank (6 Question Types, Manual Entry, Search/Filter, Student Viewer) | **Completed** |
| **Phase 4** | JSON Import (Schema Validation, Transactional Import, Import Guide) | **Completed** |
| **Phase 5** | Quiz Engine (Constraints, Exact & Closest Modes, Shuffling, Interface) | **Completed** |
| **Phase 6** | Grading & Immediate Results (Auto/Self Grading, Review, Transient Lifecycle) | **Completed** |
| **Pre-Phase 7** | Hardening (Exam Appearances CRUD, Frequency Sync, Deletion Integrity, UX Fixes) | **Completed** |
| **Phase 7** | Polish & UI Optimization (Vanilla CSS foundation, responsive Navbar, Core UI, approved Student Dashboard visual propagation, CSS organization, long-text containment, Student Quiz UI Builder/Taking/Confirmation/Result redesign, Match display randomization & partial-credit scoring fix, Arabic RTL public landing page) | **Completed — Core UI + Quiz UI + Match randomization + Landing Page (427 assertions)** |
| **Phase 8** | Final Verification & Delivery | **Not Started** |

---

## 11. Testing & Verification

### Running Automated Tests
Run tests using the XAMPP PHP binary from PowerShell or Command Prompt:

Every suite uses the shared disposable test bootstrap and is safe to run without changing the local application database or installation lock.

```powershell
# Run Phase 1 Foundation tests (83 assertions)
& "D:\xampp\php\php.exe" tests\phase1_test.php

# Run Phase 2 Academic Structure tests (38 assertions)
& "D:\xampp\php\php.exe" tests\phase2_test.php

# Run Phase 3 Question Bank tests (92 assertions)
& "D:\xampp\php\php.exe" tests\phase3_test.php

# Run Phase 4 JSON Import tests (16 assertions)
& "D:\xampp\php\php.exe" tests\phase4_test.php

# Run Phase 5 Quiz Engine tests (30 assertions)
& "D:\xampp\php\php.exe" tests\phase5_test.php

# Run Phase 6 Grading & Results tests (20 assertions)
& "D:\xampp\php\php.exe" tests\phase6_test.php

# Run Pre-Phase 7 Hardening tests (29 assertions)
& "D:\xampp\php\php.exe" tests\pre_phase7_hardening_test.php

# Run Match Scoring tests (20 assertions)
& "D:\xampp\php\php.exe" tests\match_scoring_test.php

# Run Match Display Order tests (16 assertions)
& "D:\xampp\php\php.exe" tests\match_display_order_test.php
```

### Verified Test Results
- **`tests/phase1_test.php`**: **83 Passed, 0 Failed** (Schema integrity, storage security, setup locking, student registration, password hashing, authentication, brute-force rate-limiting, CSRF, and role guards).
- **`tests/phase2_test.php`**: **38 Passed, 0 Failed** (Module CRUD, duplicate prevention, Subject CRUD, parent module relationships, module filtering, safe cascade deletion, student read-only browsing, admin authorization).
- **`tests/phase3_test.php`**: **92 Passed, 0 Failed** (Regression coverage for the original five types; True / False coverage is in `tests/true_false_test.php`, including available/unavailable CRUD, strict validation, filtering, import, quiz planning, taking, grading, results, and migration integrity).
- **`tests/phase4_test.php`**: **16 Passed, 0 Failed** (JSON parsing, validation, preview, import, legacy normalization, module-subject scope).
- **`tests/phase5_test.php`**: **30 Passed, 0 Failed** (Exact/closest planning, uneven subject splits, MCQ/Match grading, discard, UI integration; True / False planner and grading coverage is in `tests/true_false_test.php`).
- **`tests/phase6_test.php`**: **20 Passed, 0 Failed** (Auto/self grading, transient lifecycle, deletion integrity, result review; True / False objective grading and result coverage is in `tests/true_false_test.php`).
- **`tests/pre_phase7_hardening_test.php`**: **29 Passed, 0 Failed** (Hardening assertions across all prior phases).
- **`tests/exam_appearances_test.php`**: **19 Passed, 0 Failed** (Exam Appearances CRUD, validation, UI).
- **`tests/frequency_consistency_test.php`**: **18 Passed, 0 Failed** (Frequency/appearance sync, repair tool).
- **`tests/deletion_integrity_test.php`**: **11 Passed, 0 Failed** (Academic deletion pre-flight checks).
- **`tests/manual_import_fixture_test.php`**: **15 Passed, 0 Failed** (Manual JSON import fixture scenarios).
- **`tests/true_false_test.php`**: **20 Passed, 0 Failed** (True / False CRUD, validation, boolean import normalization, filtering, quiz planning/taking, correct/wrong/unanswered grading, result status, unavailable-answer lifecycle, and migration integrity).
- **`tests/match_scoring_test.php`**: **20 Passed, 0 Failed** (Match per-pair partial credit, 4-pair fixtures, mixed MCQ/TrueFalse/Match 6-unit scoring, result pair-level review, JSON import, cleanup).
- **`tests/match_display_order_test.php`**: **16 Passed, 0 Failed** (Canonical unchanged, left/right permutation validity, independent shuffles, positional mismatch, canonical grading, result display-order preservation).
- **Total Assertions**: **427 Passed, 0 Failed** across all test suites.

---

## 12. Instructions & Rules for AI Coding Agents

When working on this codebase:
1. **Read `AGENTS.md` first**: Always review this document before proposing or executing changes.
2. **Inspect before modifying**: Check existing code in `core/`, `config/`, and `views/` to reuse existing utilities.
3. **Preserve Phase 1 & 2 functionality**: Do not break existing authentication, layout, or academic hierarchy logic.
4. **Strict Scope Control**: Implement ONLY the requested phase. Never implement future phases prematurely.
5. **No Speculative Features**: Do not add social feeds, chat, gamification, AI wrappers, or unrequested features.
6. **Database Integrity**: Never switch away from SQLite. Use `Database::transaction()` for multi-step mutations.
7. **Always Validate Server-Side**: Enforce validation, CSRF checks on POST, and role authorization guards on every endpoint.
8. **Test Every Phase**: Write automated CLI tests for every new phase in `tests/phaseX_test.php` and ensure all previous test suites pass without regressions.
9. **Update `AGENTS.md`**: Upon completing any phase or making significant architectural decisions, update `AGENTS.md` immediately.

---

## 13. Deployment Guidelines

- **Local Development**: Runs on XAMPP (`http://localhost/DOT%20Bank/`).
- **Free Shared PHP Hosting**:
  - The codebase supports document roots pointed either directly to `DOT Bank/` or to `DOT Bank/public/` via root forwarders.
  - SQLite database is stored in `storage/` and protected from direct web downloads via `storage/.htaccess`.
  - Static assets are accessible via both `assets/` and `public/assets/`.

---

## 14. Important Files Reference

| File Path | Description |
|---|---|
| [`config/config.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/config/config.php) | App configuration, autoloader, session settings, base URL, helpers |
| [`core/Database.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/Database.php) | PDO SQLite database wrapper with WAL mode and transactions |
| [`core/Auth.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/Auth.php) | Authentication service, password hashing, session guards, rate-limiting |
| [`core/CSRF.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/CSRF.php) | CSRF token generator and validator |
| [`core/Academic.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/Academic.php) | Academic hierarchy service (Modules & Subjects CRUD) |
| [`core/Question.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/Question.php) | Question management service (MCQ, Complete, Match, Compare, Essay CRUD & validations) |
| [`core/Quiz.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/Quiz.php) | Quiz planning, persistence, submission, grading service |
| [`core/JsonImporter.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/JsonImporter.php) | JSON import parser, validator, preview, transactional importer |
| [`core/View.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/core/View.php) | View rendering engine and flash messaging |
| [`database/schema.sql`](file:///d:/XAMPP/htdocs/DOT%20Bank/database/schema.sql) | Full SQLite DDL schema with foreign keys and indexes |
| [`storage/.htaccess`](file:///d:/XAMPP/htdocs/DOT%20Bank/storage/.htaccess) | Apache security rule blocking direct downloads of `.sqlite` files |
| [`public/setup.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/setup.php) | Initial installation wizard |
| [`public/login.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/login.php) | User sign-in endpoint |
| [`public/register.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/register.php) | Student registration endpoint |
| [`public/admin/modules.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/admin/modules.php) | Admin module management catalog |
| [`public/admin/subjects.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/admin/subjects.php) | Admin subject management catalog |
| [`public/admin/questions.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/admin/questions.php) | Admin question catalog controller |
| [`public/admin/question-form.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/admin/question-form.php) | Admin create/edit question controller |
| [`public/admin/question-view.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/admin/question-view.php) | Admin question details view controller |
| [`public/admin/question-delete.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/admin/question-delete.php) | Admin question delete controller |
| [`public/admin/import.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/admin/import.php) | Admin JSON import controller |
| [`public/student/modules.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/student/modules.php) | Student academic curriculum browser |
| [`public/student/questions.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/student/questions.php) | Student question browser controller |
| [`public/student/quiz-builder.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/student/quiz-builder.php) | Student quiz builder controller |
| [`public/student/quiz-take.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/student/quiz-take.php) | Student quiz taking controller |
| [`public/student/quiz-submit.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/student/quiz-submit.php) | Student quiz submission controller |
| [`public/student/quiz-result.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/student/quiz-result.php) | Student quiz result controller |
| [`public/student/quiz-discard.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/public/student/quiz-discard.php) | Student active quiz discard controller |
| [`views/layouts/main.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/views/layouts/main.php) | Main shared application layout with DOT Bank branding |
| [`assets/css/app.css`](file:///d:/XAMPP/htdocs/DOT%20Bank/assets/css/app.css) | Medical & Academic Design System stylesheet |
| [`assets/css/quiz-match-responsive.css`](file:///d:/XAMPP/htdocs/DOT%20Bank/assets/css/quiz-match-responsive.css) | Responsive Match-question containment stylesheet |
| [`assets/css/student-match-responsive.css`](file:///d:/XAMPP/htdocs/DOT%20Bank/assets/css/student-match-responsive.css) | Responsive Student Match browsing stylesheet |
| [`README.md`](file:///d:/XAMPP/htdocs/DOT%20Bank/README.md) | Developer-facing project overview, setup, testing, and workflow guide |
| [`tests/phase1_test.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/phase1_test.php) | Phase 1 Foundation automated test suite |
| [`tests/phase2_test.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/phase2_test.php) | Phase 2 Academic Structure automated test suite |
| [`tests/phase3_test.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/phase3_test.php) | Phase 3 Question Bank automated test suite |
| [`tests/phase4_test.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/phase4_test.php) | Phase 4 JSON Import automated test suite |
| [`tests/phase5_test.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/phase5_test.php) | Phase 5 Quiz Engine automated test suite |
| [`tests/phase6_test.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/phase6_test.php) | Phase 6 Grading & Results automated test suite |
| [`tests/pre_phase7_hardening_test.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/pre_phase7_hardening_test.php) | Pre-Phase 7 Hardening test suite |
| [`tests/bootstrap.php`](file:///d:/XAMPP/htdocs/DOT%20Bank/tests/bootstrap.php) | Shared disposable test environment bootstrap |

---

## 15. Project History & Change Log

- **2026-08-26 — Phase 1 Completed**:
  - Established project structure, config, PDO SQLite wrapper with WAL mode and foreign keys, CSRF protection, session auth, rate-limiting, and base medical design system.
  - Built Setup Wizard (`setup.php`), Login (`login.php`), Student Registration (`register.php`), and Dashboards.
  - Verified with 83 automated test assertions (0 failures).
- **2026-08-26 — Phase 2 Completed**:
  - Implemented academic hierarchy (`core/Academic.php`): Module CRUD, Subject CRUD, parent-child relationships, module-filtered views, duplicate prevention, and safe cascading deletion.
  - Implemented student read-only academic curriculum browsing (`student/modules.php`, `student/module-view.php`).
  - Updated navigation bar and dashboard statistics.
  - Verified with 38 new automated test assertions (total 121 assertions, 0 failures).
- **2026-08-26 — Phase 3 Completed**:
  - Created question management service (`core/Question.php`) supporting all 6 question types (MCQ, Complete, Match, Compare, Essay, True / False).
  - Built full CRUD capabilities for questions: Create/Edit form with complex client-side JS logic for dynamic schemas, Detail View, and Cascading Delete.
  - Implemented multi-criteria search and filter queries on the admin and student question lists.
  - Set up student browser allowing read-only access to questions with interactive show/hide toggle behavior for answers.
  - Enforced strict authorization rules (admin-only mutation operations, student role guards).
  - Verified with 92 new automated test assertions (total 213 assertions, 0 failures).
- **2026-08-26 — AGENTS.md Created & Maintained**:
  - Established persistent onboarding and continuity documentation for future AI coding agents.
- **2026-08-27 — Phase 3 Route Recovery**:
  - Audited the repository, reran all phase test suites, and verified all PHP files parse successfully.
  - Corrected the root Question Bank forwarders in `admin/` and `student/`; they now resolve through `dirname(__DIR__)` like the established route forwarders.
  - Confirmed unauthenticated requests to `admin/questions.php` and `student/questions.php` reach the authentication guard and redirect to `login.php` instead of producing missing-file errors.
  - No database changes were required.
- **2026-08-27 — Phase 4 Completed (JSON Import)**:
  - Added `core/JsonImporter.php`, admin import controller, preview workflow, transactional import.
  - Legacy source normalization, conflict tracking, frequency consistency.
  - Verified with 15 dedicated assertions; full regression passed.
- **2026-08-27 — Phase 5 Completed (Quiz Engine)**:
  - Added `core/Quiz.php`, student quiz routes (builder, take, submit, discard, result), views, root forwarders.
  - Largest-remainder allocation, max-flow exact planning, closest-possible fallback with squared-deviation minimization.
  - Fisher-Yates shuffle with `random_int`; explicit constraints only; flexible unspecified dimensions.
  - Verified with 27 dedicated assertions; full regression passed.
- **2026-08-27 — Phase 6 Completed (Grading & Immediate Results)**:
  - Server-side auto-grading (MCQ, Match, True / False), self-graded types (Complete, Compare, Essay), transient lifecycle.
  - Result review with per-question correct answers; no persistent history.
  - Quiz History route/view/navigation removed before Phase 7.
  - Verified with 19 dedicated assertions; full regression passed.
- **2026-08-27 — Pre-Phase 7 Hardening Completed**:
  - Exam Appearances CRUD (zero/one/multiple, validation, UI) — 19 assertions.
  - Frequency/Appearance consistency (repair tool, validation) — 18 assertions.
  - Deletion integrity (pre-flight quiz dependency checks) — 11 assertions.
  - Manual Import Fixtures (valid/invalid/merge scenarios) — 15 assertions.
  - UX Fix Pack (Match placeholder, discard endpoint, closest-plan subject names, "Accept and Start Quiz", JSON preview impact, help text).
  - Hardening suite: 29 assertions; total regression: 328+ assertions, 0 failures.
  - All test suites pass in isolated copies.
- **Phase 7**: Core UI foundation completed; the approved Student Dashboard visual language, responsive Navbar, shared Core UI, CSS organization, long-text containment, and focused Match responsive fix are complete. Quiz UI is intentionally deferred to its dedicated pass except for that Match containment fix.
- **Phase 8**: Not started.
- **2026-08-30 — Phase 7 Vanilla Core UI Pass**:
  - Confirmed the live application loads the root `assets/css/app.css` and `assets/js/app.js` URLs; the mirrored `public/assets/` files remain synchronized for the alternate public entry point.
  - Established the Core UI visual layer for dashboards, academic catalogs, question-bank surfaces, forms, tables, cards, alerts, empty states, and responsive layouts without changing application behavior.
  - Kept Quiz Builder, Quiz Taking, and Quiz Result presentation outside this pass.
- **2026-08-30 — Phase 7 Font Awesome Enhancement**:
  - Added centrally loaded Font Awesome Free 6.5.2 from jsDelivr with SHA-384 SRI `PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI`.
  - Added accessible decorative icons to existing navigation, dashboard, catalog, question-bank, and action labels without changing destinations or behavior.
- **2026-08-30 — Test Database Isolation**:
  - Added `tests/bootstrap.php` to run each suite from a temporary application copy with disposable SQLite storage.
  - Updated all regression suite entry points to use the isolated bootstrap.
  - Verified 369 assertions pass with unchanged SHA-256 hashes for the real application database and installation lock.
- **2026-08-30 — Phase 7 UI Foundation & Documentation Completed**:
  - Propagated the approved Student Dashboard visual language across the Core UI: shared surfaces, cards, spacing, borders, shadows, buttons, icons, hierarchy, and responsive behavior.
  - Kept root and `public/` CSS assets synchronized and extracted reusable presentation patterns from repeated inline styles where appropriate.
  - Added shared long-text containment using `min-width: 0` and wrapping rules for normal and unbroken text without truncation or ellipsis.
  - Added responsive Match-question styles in mirrored `quiz-match-responsive.css` files and responsive Student Match browsing styles in mirrored `student-match-responsive.css` files.
  - Added `README.md` with verified project architecture, setup, features, security, testing, and workflow documentation.
  - Verified the complete documented test set at 389 passed assertions and 0 failures; PHP syntax checks and `git diff --check` passed.
- **2026-08-31 — Phase 7 Arabic RTL Public Landing Page Completed**:
  - Converted `public/index.php` from a simple redirect to a full Arabic RTL landing page for unauthenticated visitors (hero, features, question types, how-it-works, final CTA, footer), while authenticated users redirect to their dashboards.
  - Built with Vanilla CSS using existing design tokens (`--primary: #6547d9`, `--accent: #f19a6b`, `--dark: #182230`, `--bg-page: #f7f6f2`); no new CSS framework; responsive 320-1280.
  - Added landing page styles to `assets/css/app.css` and synchronized to `public/assets/css/app.css`.
  - No authentication, routing, backend, or database changes. All regression tests pass (427 assertions).

---

## 16. Phase 7 Branching & Development Protocol (Historical)

Phase 7 development is **complete**. The following protocol was followed during Phase 7:

| Rule | Detail |
|------|--------|
| **Development Branch** | Phase 7 development happened on the `phase7` branch. |
| **Production Branch** | `main` is the stable/production branch and must NOT receive direct Phase 7 development commits. |
| **Testing** | All Phase 7 changes were tested (automated + browser QA) before merging. |
| **Push Policy** | Push `phase7` work to the remote `phase7` branch only. Never push Phase 7 commits to `main`. |
| **Merge Policy** | `main` receives Phase 7 only after review, full regression testing, browser QA, and explicit approval. |
| **Deployment** | Do not deploy Phase 7 to live hosting until it passes all required verification on `main`. |
| **Rollback Point** | Commit `20b906f` (`chore: finalize pre-phase7 baseline`) on `main` is the stable rollback point. |

---

## 17. Technical Risks & Important Implementation Notes

Future agents should be aware of the following coupling, fragility, and maintenance risks before modifying the codebase:

| Area | Risk | Mitigation |
|------|------|------------|
| **`Question::validate()`** (`core/Question.php:63-159`) | 100+ lines handling 6 question types + appearances; changes risk regressions across all types | Run full Phase 3 + Phase 4 + Phase 6 + True / False test suites after any modification |
| **`Quiz::plan()`** (`core/Quiz.php:27-211`) | Max-flow, closest-matrix, largest-remainder all in one class; complex edge cases | Run Phase 5 tests; test uneven subject splits, shortage scenarios, flexible dimensions |
| **`JsonImporter::import()`** (`core/JsonImporter.php:15-74`) | Identity hashing, conflict table, appearance deduplication, frequency validation tightly coupled | Run Phase 4 + manual import fixture tests; verify merge/reuse/conflict scenarios |
| **Client-side form JS** (`views/admin/questions/form.php:167-527`) | 360+ lines of inline JS for dynamic MCQ/Match/True / False/appearance builders; no separation | Keep changes minimal; test all 6 question types in create/edit modes |
| **Question Bank query builder** (`core/Question.php:13-58`) | Raw SQL string concatenation for filters; subtle bugs possible with new filters | Run Phase 3 + Phase 4 filter tests; verify pagination and all filter combinations |
| **Session-dependent CSRF in tests** | CLI tests manipulate `$_SESSION` directly; real browser session behavior may differ | Verify CSRF behavior manually in browser for state-changing endpoints |
| **Deletion pre-flight checks** (`core/Academic.php:128-160, 316-360`) | `countDependentQuizzesForModule/Subject` queries must stay in sync with schema | Run deletion integrity tests; verify FK behavior after schema changes |
| **Frequency/appearance sync** (`core/Question.php:149-156, 187-189, 268, 361`) | Dual code paths (create/update) must maintain invariant; repair tool exists indicating fragility | Run frequency consistency tests; use repair tool after bulk operations |
| **Quiz result session payload** (`public/student/quiz-submit.php:7`, `quiz-result.php:6-8`) | Uses `$_SESSION['_quiz_result_payload']` — single-tab assumption, no persistence | Do not rely on result surviving tab close/refresh; transient by design |
| **Hardcoded 15-row pagination** | Not configurable; changing requires multi-file edit (admin/questions, student/questions, import) | Consider centralizing if pagination changes needed |
| **Root forwarder pattern** | All root-level PHP files forward to `public/` via `dirname(__DIR__)`; maintain consistency | Add new routes in both `public/` and root forwarders |
