# DOT Bank

DOT Bank (Doctors of Tomorrow Question Bank) is a PHP question-bank and examination-practice platform for organizing medical-school questions by module, subject, question type, and exam appearance.

## Purpose

The application provides a structured alternative to mixed, unstructured exam-question files. Administrators curate the academic hierarchy and question bank; students browse questions directly or build and complete quizzes from the available question pool.

## Main features

- Initial installation wizard with administrator account creation.
- Username/password authentication with separate administrator and student roles.
- Module and subject management for administrators.
- Question CRUD for six question types.
- Question browsing for students, including answer visibility where an answer is available.
- Search and filtering by module, subject, type, answer status, exam year, exam term, source, and question text.
- Exam-appearance tracking and frequency consistency checks.
- Duplicate detection warnings without silently deleting or merging questions.
- JSON preview, validation, and transactional question import.
- Configurable quiz planning by total questions and optional subject/type percentages.
- Immediate quiz submission, grading, and result review.

## Supported question types

The database and application support:

- **MCQ** — automatically graded by exact correct-option text.
- **True / False** — automatically graded by exact normalized `true`/`false` value.
- **Match** — automatically graded when the submitted mapping exactly matches the stored mapping.
- **Complete** — self-graded by the student.
- **Compare** — self-graded by the student.
- **Essay** — self-graded by the student.

Questions may have an available or unavailable answer. Unavailable questions remain in the bank and can be updated later.

## Technology stack

- PHP 8.2 on the documented local XAMPP environment.
- SQLite 3 through PDO SQLite.
- Semantic HTML5.
- Vanilla CSS and Vanilla JavaScript.
- Font Awesome Free 6.5.2 loaded from jsDelivr with SRI in the shared layouts.

The repository does not use Bootstrap, Tailwind, Node, npm, or a frontend build step.

## Project architecture

The repository supports entry points from both the project root and `public/`. Root-level route files forward to the public controllers where applicable.

| Directory | Responsibility |
| --- | --- |
| `config/` | Application constants, URL helpers, escaping, sessions, and core autoloading. |
| `core/` | Application services: authentication, CSRF, database access, academic CRUD, questions, quizzes, JSON import, and view rendering. |
| `database/` | SQLite schema and indexes in `schema.sql`. |
| `public/` | Web-accessible controllers and mirrored public assets. |
| `views/` | Layouts, navigation/flash partials, authentication views, admin views, and student views. |
| `assets/` | Mirrored CSS and JavaScript assets for root-level hosting compatibility. |
| `storage/` | SQLite database, installation lock, and storage protection rules. |
| `tests/` | Isolated regression suites and fixtures. |
| `tools/` | Demo-data, migration, repair, reset, and QA utilities. |

Important service classes include:

- `core/Auth.php` — authentication, registration, session role checks, and route guards.
- `core/Academic.php` — module/subject CRUD and safe deletion checks.
- `core/Question.php` — question validation, CRUD, filtering, appearances, and frequency rules.
- `core/Quiz.php` — quiz availability, planning, persistence, submission, grading, and discard.
- `core/JsonImporter.php` — JSON parsing, normalization, preview, conflict handling, and import.
- `core/Database.php` — PDO SQLite access, transactions, foreign keys, and WAL mode.
- `core/View.php` — view rendering and flash messages.

## Authentication and roles

There are two roles:

- **Admin** — created by the initial setup wizard; manages modules, subjects, questions, appearances, and imports.
- **Student** — created through the registration flow; browses academic content and uses the quiz features.

Admin routes use the administrator guard, and student routes use the student guard. There is no public administrator-registration flow.

## Quiz and grading system

Students choose a module, subjects, total question count, and optional subject/type percentage distributions in the quiz builder. The planner uses the available question counts and can produce an exact or closest-possible proposal when constraints cannot be fully satisfied.

Quiz questions are selected from the requested module and subjects without duplicate question IDs. MCQ, True / False, and Match questions are auto-graded. Complete, Compare, and Essay responses are stored for self-evaluation. The result includes the immediate question review and objective score where auto-graded questions exist.

Submitted quizzes are transient: after the result payload is prepared, the quiz and its answer rows are removed. The repository does not provide a persistent quiz-history route.

## JSON question import

Administrators can upload JSON for preview and transactional import. The importer validates the top-level `questions` array, question type, question text, frequency, type-specific answer data, and exam appearances.

The supported import fields include:

- `type`
- `question`
- `frequency`
- `appearances` with source, year, and term
- `choices` and an answer label for MCQ
- `pairs` for Match
- `answer` for the other answer-bearing types

The importer reports invalid records, warnings, duplicate candidates, answer availability, new-question/merge impact, and duplicate appearances. It preserves known answers and records answer conflicts for review. The importer enforces the documented frequency/appearance consistency rule and limits uploaded JSON to 2 MB.

For the full data-generation/import contract, see [`AI_instructions_to_generate_valid_data_v2.md`](AI_instructions_to_generate_valid_data_v2.md). That document describes input preparation; question generation is not performed by the application.

## Database

The application uses SQLite at `storage/dot_bank.sqlite` and initializes the schema from [`database/schema.sql`](database/schema.sql). The schema contains users, modules, subjects, questions, question sources, question conflicts, quizzes, quiz-question mappings, quiz answers, and application configuration.

Foreign keys are enabled, including the documented cascade/restrict relationships. The database wrapper enables SQLite WAL mode and provides transaction helpers. The setup wizard creates the schema, stores initial configuration and the administrator account, and writes `storage/installed.lock` after successful installation.

## Testing and regression coverage

Every test suite loads [`tests/bootstrap.php`](tests/bootstrap.php), which copies the application to a temporary test directory and uses disposable SQLite storage. The real application database and installation lock are excluded from the test environment.

The repository contains suites for:

- Phase 1 foundation and authentication — 83 assertions.
- Phase 2 academic structure — 38 assertions.
- Phase 3 question bank — 92 assertions.
- Phase 4 JSON import — 16 assertions.
- Phase 5 quiz engine — 29 assertions.
- Phase 6 grading and immediate results — 19 assertions.
- Pre-Phase 7 hardening — 29 assertions.
- True / False coverage — 20 assertions.
- Exam appearances — 19 assertions.
- Frequency consistency — 18 assertions.
- Deletion integrity — 11 assertions.
- Manual import fixtures — 15 assertions.

The documented combined coverage is **389 passed assertions and 0 failed assertions**.

Run the complete test set from PowerShell with the XAMPP PHP binary:

```powershell
$php = 'D:\xampp\php\php.exe'
Get-ChildItem tests -Filter '*_test.php' | ForEach-Object {
    & $php $_.FullName
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
```

Individual suites can be run directly, for example:

```powershell
& 'D:\xampp\php\php.exe' tests\phase3_test.php
& 'D:\xampp\php\php.exe' tests\phase5_test.php
& 'D:\xampp\php\php.exe' tests\true_false_test.php
```

## Local development and setup

The documented local environment is XAMPP on Windows with Apache and PHP. Place the project under the XAMPP htdocs directory, start Apache, and open the project through the web server. The root `setup.php` entry point forwards to the public setup controller.

The initial setup wizard:

1. Creates the SQLite schema if the application is not installed.
2. Accepts a platform name, administrator username, password, and confirmation.
3. Creates the initial administrator account.
4. Stores installation metadata and writes the installation lock.
5. Redirects to login after successful setup.

The current configuration resolves the documented local base URL as:

```text
http://localhost/DOT%20Bank/
```

The application must be installed before login, registration, or protected routes can be used.

## Deployment notes

The repository documentation states that the application can be served with a document root pointed either at the project root or at `public/`, with root forwarders available for compatibility. SQLite remains the intended database and `storage/.htaccess` blocks direct access to the storage directory. The repository does not include a separate automated deployment configuration.

## Security considerations

- Passwords are stored using PHP `password_hash()` and checked with `password_verify()`.
- Successful login regenerates the session ID.
- Sessions use HTTP-only cookies, cookies-only sessions, and `SameSite=Lax` as configured by the application.
- Login attempts are rate-limited after five failed attempts for five minutes.
- State-changing forms use CSRF tokens from `core/CSRF.php`.
- Role guards protect admin and student routes.
- User-supplied output is escaped through the shared `e()` helper in rendered views.
- SQLite foreign-key enforcement and transactions protect multi-step writes.
- `storage/.htaccess` denies direct access to protected storage files.

The configuration currently enables error display, so production operators should review the runtime error-reporting settings before exposing an installation publicly.

## Current UI and design approach

The interface uses a shared Vanilla CSS/JS presentation layer with responsive layouts, reusable cards, forms, alerts, tables, dashboard surfaces, navigation, focus states, and Font Awesome icons. Root and `public` CSS assets are maintained as mirrored files. Quiz Builder, Quiz Taking, and Quiz Result screens remain part of the existing application but are documented as a separate UI pass in the project continuity guide.

## Development workflow

The documented Phase 7 workflow uses:

- `phase7` for Phase 7 development.
- `main` as the stable/production branch.
- Automated regression testing and browser QA before merging.
- Explicit review and approval before Phase 7 changes reach `main`.

The project instructions also state that Phase 7 work should not be deployed to live hosting until it has passed the required verification on `main`.

## Useful files

- [`AGENTS.md`](AGENTS.md) — project source of truth and continuity guide.
- [`config/config.php`](config/config.php) — configuration, constants, helpers, and autoloading.
- [`database/schema.sql`](database/schema.sql) — SQLite schema and indexes.
- [`public/setup.php`](public/setup.php) — initial setup wizard controller.
- [`public/login.php`](public/login.php) — login controller.
- [`public/register.php`](public/register.php) — student registration controller.
- [`tools/`](tools/) — maintenance, migration, demo, and QA scripts.
- [`tests/`](tests/) — regression suites and fixtures.
