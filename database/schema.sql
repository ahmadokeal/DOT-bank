-- DOT Bank (Doctors of Tomorrow Question Bank)
-- SQLite Database Schema Foundation

PRAGMA foreign_keys = ON;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('admin', 'student')),
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'disabled')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Modules table
CREATE TABLE IF NOT EXISTS modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE(module_id, name)
);

-- Questions table
-- Types: 'mcq', 'complete', 'match', 'compare', 'essay', 'true_false'
-- answer_status: 'available', 'unavailable'
-- answer_origin: 'manual', 'json_import'
CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('mcq', 'complete', 'match', 'compare', 'essay', 'true_false')),
    question_text TEXT NOT NULL,
    answer_data TEXT, -- JSON structured according to question type
    answer_status TEXT NOT NULL DEFAULT 'available' CHECK(answer_status IN ('available', 'unavailable')),
    answer_origin TEXT NOT NULL DEFAULT 'manual' CHECK(answer_origin IN ('manual', 'json_import')),
    frequency INTEGER NOT NULL DEFAULT 1 CHECK(frequency >= 1),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Question Sources table (optional source tracking)
CREATE TABLE IF NOT EXISTS question_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    source_name TEXT NOT NULL,
    exam_year INTEGER,
    exam_term TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Unsupported legacy source values may remain during migration for auditability.
-- All new application/import writes validate source_name and exam_term canonically.
CREATE TABLE IF NOT EXISTS question_conflicts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    incoming_answer_data TEXT NOT NULL,
    incoming_appearances TEXT,
    status TEXT NOT NULL DEFAULT 'review' CHECK(status IN ('review','resolved')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Quizzes table
CREATE TABLE IF NOT EXISTS quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    total_questions INTEGER NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    score REAL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE RESTRICT
);

-- Quiz Questions mapping table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    question_order INTEGER NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE RESTRICT
);

-- Quiz Answers table
CREATE TABLE IF NOT EXISTS quiz_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_question_id INTEGER NOT NULL,
    student_answer TEXT,
    is_correct INTEGER DEFAULT NULL, -- 1=correct, 0=incorrect, NULL=pending self-evaluation
    FOREIGN KEY (quiz_question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Application Settings / Metadata table
CREATE TABLE IF NOT EXISTS app_config (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_subjects_module_id ON subjects(module_id);
CREATE INDEX IF NOT EXISTS idx_questions_subject_id ON questions(subject_id);
CREATE INDEX IF NOT EXISTS idx_questions_type ON questions(type);
CREATE INDEX IF NOT EXISTS idx_questions_answer_status ON questions(answer_status);
CREATE INDEX IF NOT EXISTS idx_questions_frequency ON questions(frequency);
CREATE INDEX IF NOT EXISTS idx_question_sources_question_id ON question_sources(question_id);
CREATE INDEX IF NOT EXISTS idx_question_sources_source_name ON question_sources(source_name);
CREATE INDEX IF NOT EXISTS idx_question_sources_exam_year ON question_sources(exam_year);
CREATE INDEX IF NOT EXISTS idx_question_sources_exam_term ON question_sources(exam_term);
CREATE UNIQUE INDEX IF NOT EXISTS uq_question_exam_appearance
    ON question_sources(question_id, source_name, COALESCE(exam_year, -1), COALESCE(exam_term, ''));
CREATE INDEX IF NOT EXISTS idx_quizzes_user_id ON quizzes(user_id);
CREATE INDEX IF NOT EXISTS idx_quiz_questions_quiz_id ON quiz_questions(quiz_id);
CREATE INDEX IF NOT EXISTS idx_quiz_answers_quiz_question_id ON quiz_answers(quiz_question_id);
