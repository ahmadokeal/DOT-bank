# DOT Bank — Strict Data-Generation Specification (v2)

**Purpose:** Generate JSON that the current DOT Bank JSON importer accepts. This is implementation-derived from the current source, not a proposed schema. Generate only the external JSON format in this document; do not generate database IDs or internal table columns.

**Audited:** 2026-08-30. Re-audit this document whenever the importer, question validation, forms, or schema changes.

## 1. Import boundary and hierarchy

The administrator selects the target **Module** and **Subject** in the import form. The JSON file does not contain module or subject IDs. The selected subject must belong to the selected module; otherwise parsing fails. Every imported question is assigned to that selected subject.

The top-level JSON value must be an object containing a `questions` key whose value is an array. An empty array is structurally accepted, but imports no questions. The uploaded JSON must be valid UTF-8, valid JSON, and no larger than 2 MiB (`JsonImporter::MAX_FILE_SIZE = 2097152`).

Do not include platform/database IDs such as `id`, `module_id`, `subject_id`, or `question_id` in generated records. They are not part of the importer contract and are never used to assign relationships.

## 2. Canonical finite-choice values

These are the exact backend literals. Display labels in the admin UI are shown after the arrow and must not be substituted into JSON.

| Field | VALID: exact submitted/backend values | UI display labels | INVALID examples proved by implementation |
|---|---|---|---|
| `type` | `mcq`, `complete`, `match`, `compare`, `essay`, `true_false` | Multiple Choice (MCQ), Complete, Match, Compare, Essay, True / False | `MCQ`, `multiple_choice`, `fill_blank`, `matching`, `short_answer` |
| question `answer_status` (internal, derived) | `available`, `unavailable` | Answer Available, Answer Unavailable | `answered`, `unanswered`, `pending` |
| appearance `source` / normalized `source_name` | `final`, `end_module` | Final, End Module | `Final`, `final_exam`, `End Module`, `End of module`, `midterm` as a canonical source |
| appearance `term` / normalized `exam_term` | `first`, `second` | First, Second | `First`, `second_term`, `third` |
| `answer_origin` (internal, derived on import) | `json_import` | none | `manual`, `AI`, `generated` for imported rows |
| `questions.type` (database) | `mcq`, `complete`, `match`, `compare`, `essay`, `true_false` | same labels as above | any other literal; rejected by validation/DB CHECK |
| database `question_conflicts.status` (internal) | `review`, `resolved` | none | any other status |

For JSON appearances, use the external keys `source`, `year`, and `term`. The importer also accepts the aliases `source_name`, `exam_year`, and `exam_term` inside an appearance object, but the canonical generation format is `source`, `year`, `term`.

## 3. Common question-record fields

Each element of `questions` must be an object.

| JSON key | Type | Required / default | Exact rules | Verified in |
|---|---|---|---|---|
| `type` | string | required | Exactly one of the six `type` literals above; case-sensitive | `core/JsonImporter.php`, `JsonImporter::normalize()`; `core/Question.php`, `Question::validate()` |
| `question` | string | required | After trimming, must be non-empty | `core/JsonImporter.php`, `JsonImporter::normalize()` |
| `frequency` | integer JSON number | optional; defaults to `1` only when omitted | Must be an actual JSON integer (`is_int`), and `>= 1`. `"1"`, `1.0`, `0`, and `null` are not valid importer values | `core/JsonImporter.php`, `JsonImporter::normalize()`; `database/schema.sql`, `questions.frequency` |
| `appearances` | array | optional; defaults to `[]` when omitted | Prefer this key. Every strict appearance must be a valid object as specified in §5. If the key is present, it must be an array | `core/JsonImporter.php`, `JsonImporter::appearances()` |
| `answer` | type-dependent | optional | Omit or use `null` for an unavailable answer. For MCQ it is a choice label; for Match, any non-empty value means the generated pair mapping is available; for Complete/Compare/Essay it is the answer text | `core/JsonImporter.php`, `JsonImporter::normalize()` |
| `choices` | object (MCQ only) | required for `mcq` | At least two properties; every key and value must satisfy §4 | `core/JsonImporter.php`, `JsonImporter::normalize()` |
| `pairs` | non-empty array (Match only) | required for `match` | Every item must contain non-empty string `left` and `right`; see §4 | `core/JsonImporter.php`, `JsonImporter::normalize()` |

Unknown extra keys are not used by the importer. Do not rely on them. A generated record’s normalized internal fields are: `type`, `question_text` (from `question`), `frequency`, `appearances`, `answer_status` (derived), and `answer_data` (derived). New rows receive database `answer_origin = 'json_import'`.

## 4. Type-specific formats

### 4.1 MCQ

External format:

```json
{
  "type": "mcq",
  "question": "Which vitamin is fat-soluble?",
  "choices": {"A": "Vitamin C", "B": "Vitamin D"},
  "answer": "B",
  "frequency": 1,
  "appearances": []
}
```

Rules:

- `choices` must be a JSON object, not an array, and must have at least two properties.
- Every choice label must be exactly one uppercase ASCII letter matching `/^[A-Z]$/`. **VALID:** `A`, `B`, `C`. **INVALID:** `a`, `AA`, `1`, `Option A`, `A)`.
- Every choice value must be a string whose trimmed text is non-empty.
- If `answer` is non-null, it must be a string key that exists in `choices`; **VALID:** `"B"` when `choices.B` exists. **INVALID:** `"b"`, `"Vitamin D"`, `"Option B"`, or a missing label such as `"C"` when no `C` property exists.
- Omit `answer` or set it to `null` for an unavailable answer. A non-empty valid label produces internal `answer_status = "available"`; the normalized internal `answer_data` stores the choice texts in `options` and the selected choice text—not the label—in `correct_answer`.
- The importer does not require labels to be consecutive or begin at `A`; each supplied label still must be one uppercase letter. Keep labels unique because JSON object keys are unique.

### 4.2 Match (one question, pair-level scoring)

External format:

```json
{
  "type": "match",
  "question": "Match each structure with its function.",
  "pairs": [
    {"left": "Nephron", "right": "Filters blood"},
    {"left": "Alveolus", "right": "Gas exchange"}
  ],
  "answer": "provided",
  "frequency": 1,
  "appearances": []
}
```

Rules:

- Match is **one question record**, one quiz question, and one result-review question. Never generate one record per pair.
- `pairs` must be a non-empty JSON array.
- Each pair must be an object with non-empty **string** `left` and non-empty **string** `right`; surrounding whitespace is trimmed.
- Left items must be unique after trimming. **INVALID:** two pairs with the same trimmed `left` text.
- Right items are collected as provided; the importer does not reject duplicate right texts, although unique right texts are safest for quiz use.
- If `answer` is omitted, `null`, or the empty string, internal `answer_status = "unavailable"` and normalized `matches = null`.
- If `answer` is any non-empty value, the importer marks the answer available and automatically creates `matches` from every pair (`left` → `right`). The value itself is not used as the mapping. Use a simple string such as `"provided"`; do not assume labels like `A` are interpreted specially.
- The normalized internal structure is `left_items`, `right_items`, and `matches`. The mapping must be complete: every left item has exactly one correct right item.
- During grading, each pair is compared independently with `matches`. Correct pairs receive one scoring unit; incorrect or unanswered pairs receive zero. Partial credit is allowed (`3/4` pairs = `75%` for that Match question). The Match still counts as one question in all UI and question counts.

### 4.3 Complete, Compare, and Essay

For each of the text-based types, use the same external shape:

```json
{
  "type": "complete",
  "question": "The functional unit of the kidney is the ____. ",
  "answer": "Nephron",
  "frequency": 1,
  "appearances": []
}
```

- **VALID:** `type` exactly `complete`, `compare`, or `essay`.
- With a non-null, non-empty `answer`, the importer trims it and stores internal `answer_status = "available"` and `answer_data = {"answer":"..."}`.
- Omit `answer`, or set it to `null` or `""`, for an unavailable answer. It stores internal `answer_status = "unavailable"` and `answer_data = {"answer":null}`.
- When an answer is present it must be convertible to trimmed text by the importer; generate a string to avoid ambiguity.
- These three types are self-graded in the quiz engine: `is_correct` remains `NULL`; the platform does not AI-grade or fuzzy-match them (`core/Quiz.php`, `Quiz::submit()`).

### 4.4 True / False

External format:

```json
{"type":"true_false","question":"The heart has four chambers.","answer":true,"frequency":1,"appearances":[]}
```

- `answer` must be a real JSON boolean (`true` or `false`), not the strings `"true"` or `"false"`.
- Omit `answer` or set it to `null` for an unavailable answer.
- Available answers are normalized to internal `answer_data = {"answer":"true"}` or `{"answer":"false"}` and are auto-graded in quizzes.

## 5. Exam appearances and frequency

Canonical strict format:

```json
"appearances": [
  {"source": "final", "year": 2025, "term": "first"},
  {"source": "end_module", "year": 2026, "term": "second"}
]
```

For each appearance in the explicit `appearances` array:

- For canonical generated data, `source` is required and must be exactly `final` or `end_module`. The strict importer emits a review warning—not a parse error—for another non-empty source, but manual `Question::validate()` rejects it and it is not a valid canonical value. Do not generate warning-level legacy values.
- `year` is required and must be an actual JSON integer greater than zero. The importer’s strict path does **not** impose the admin form’s UI range of `1900–2200`; using `1900–2200` is recommended for normal exam data because that is what the form displays/enforces client-side.
- `term` is required and must be exactly `first` or `second`.
- Duplicate appearances for the same question are invalid in the application’s canonical model. The uniqueness identity is `(source, year, term)`; do not repeat it.
- **VALID:** `"end_module"`. **INVALID for canonical/manual data:** `"End of module"`, `"end module"`, `"final exam"`, `"First"`, `"third"`, `2025` as a string in strict `appearances`, or `20.25`. An explicit unknown source such as `"midterm"` is warning-level accepted by the JSON importer for compatibility, but must not be generated as canonical data.
- `[]` is valid. With zero appearances, `frequency` may independently be any integer `>= 1`.
- If one or more appearances will be stored for a question, `frequency` must equal the total distinct stored appearances for that question. For a new question this is normally `appearances.length`.
- During a merge, the count includes existing appearances plus new distinct appearances. Therefore a record that reuses an existing question must set `frequency` to the resulting total, not merely the number of appearances in the new record.

### Legacy compatibility (do not use for new generation)

If `appearances` is omitted and a top-level `source` key is present, the importer has a permissive legacy path. It recognizes case-insensitively `Final`, `Final Exam`, `Final 2025`, `Final Exam 2025`, and analogous `End Module` / `End Module Exam` forms, optionally with a four-digit year. Unknown legacy sources may be retained with warnings. Missing/unsupported legacy years and terms can produce warnings rather than strict errors. This compatibility behavior is not the canonical generation format; always generate the explicit strict `appearances` array.

## 6. Storage, identity, duplicates, and answer conflicts

- A question is looked up for merge only within the selected `subject_id` and the same `type`.
- Identity uses normalized lowercase/trimmed question text. For MCQ it also includes the ordered normalized option texts; for Match it includes the ordered normalized left and right item texts. Complete/Compare/Essay identity does not include answer text. This is implemented by `JsonImporter::identity()`.
- Repeating identical question content is a merge candidate, not a request to create a duplicate row. Within one import, repeated question+appearance records are reported as duplicate candidates; the same appearance is inserted only once (`INSERT OR IGNORE` plus the schema unique index).
- Different question content is allowed even if it looks similar. The importer does not silently delete arbitrary question rows.
- Existing unavailable answers can be populated by an incoming available answer. Existing available answers are preserved. If an incoming available answer differs, the importer creates a `question_conflicts` row with status `review`; it does not silently overwrite the stored answer.
- For a new imported row, `answer_data` is JSON generated by the importer. Do not provide an internal `answer_data` key and do not provide database IDs.

## 7. Internal `answer_data` produced by the importer

This is what the importer writes, not what the generator should submit:

| Type | Available internal JSON | Unavailable internal JSON |
|---|---|---|
| MCQ | `{"options":["..."],"correct_answer":"selected choice text"}` | `{"options":["..."],"correct_answer":null}` |
| Match | `{"left_items":["..."],"right_items":["..."],"matches":{"left":"right"}}` | `{"left_items":["..."],"right_items":["..."],"matches":null}` |
| Complete / Compare / Essay | `{"answer":"model answer"}` | `{"answer":null}` |

The database also enforces `questions.type`, `answer_status`, `answer_origin`, and `frequency >= 1` with CHECK constraints. `question_sources` references the question with `ON DELETE CASCADE`; its canonical fields are `source_name`, integer `exam_year`, and `exam_term`.

## 8. Complete verified JSON example

This example uses only the strict external keys and covers all six types, available and unavailable answers, zero/single/multiple appearances, canonical sources, canonical terms, and valid frequency counts. It is intended to be passed unchanged to `JsonImporter::parse($json, $selectedModuleId, $selectedSubjectId)` after selecting a subject belonging to the selected module.

```json
{
  "questions": [
    {
      "type": "mcq",
      "question": "Which vitamin is fat-soluble?",
      "choices": {"A": "Vitamin C", "B": "Vitamin D", "C": "Vitamin B12"},
      "answer": "B",
      "frequency": 1,
      "appearances": [{"source": "final", "year": 2025, "term": "first"}]
    },
    {
      "type": "complete",
      "question": "The functional unit of the kidney is the ____. ",
      "answer": "Nephron",
      "frequency": 2,
      "appearances": [
        {"source": "final", "year": 2024, "term": "second"},
        {"source": "end_module", "year": 2025, "term": "first"}
      ]
    },
    {
      "type": "match",
      "question": "Match each structure with its function.",
      "pairs": [
        {"left": "Nephron", "right": "Filters blood"},
        {"left": "Alveolus", "right": "Gas exchange"}
      ],
      "answer": "provided",
      "frequency": 1,
      "appearances": [{"source": "end_module", "year": 2025, "term": "second"}]
    },
    {
      "type": "compare",
      "question": "Compare arteries and veins.",
      "answer": "Arteries carry blood away from the heart; veins return blood to the heart.",
      "frequency": 1,
      "appearances": []
    },
    {
      "type": "essay",
      "question": "Discuss the main mechanisms of homeostasis.",
      "frequency": 1,
      "appearances": []
    }
  ]
}
```

The example yields five valid normalized records: four with available answers and one unavailable; no IDs; four canonical appearance rows in total; and frequency equal to appearance count for every question that has appearances. The MCQ answer is normalized from label `B` to choice text `Vitamin D`; the Match remains one question and its complete mapping is generated from `pairs` because `answer` is non-empty.

## 9. Generator checklist

- [ ] Top level is `{ "questions": [...] }`.
- [ ] Target module/subject are selected outside the JSON; no IDs are generated.
- [ ] Every record has an exact canonical `type` and non-empty string `question`.
- [ ] Every `frequency` is a JSON integer `>= 1`.
- [ ] Every explicit appearance uses exact `source`, integer `year > 0`, and exact `term`.
- [ ] `frequency` equals the final distinct appearance count whenever appearances exist.
- [ ] MCQ `choices` is an object with at least two one-uppercase-letter keys; `answer` is an existing label or is omitted/`null`.
- [ ] Match is one question; `pairs` is non-empty, every `left`/`right` is a non-empty string, left items are unique, and the pairs contain the complete correct mapping.
- [ ] Complete/Compare/Essay use answer text or omit/`null` for unavailable answers.
- [ ] No database IDs, internal `answer_data`, UI labels, guessed source names, or guessed type names appear.
- [ ] Parse the final file with the current `JsonImporter` before delivery and resolve every invalid record and every frequency error.

## 10. Source audit map

- `database/schema.sql`: table columns, defaults, foreign keys, CHECK constraints, and unique appearance index.
- `core/JsonImporter.php`: file/JSON boundary, external keys, strict normalization, legacy normalization, frequency checks, identity, merges, duplicate appearances, and conflicts.
- `core/Question.php`: manual validation parity, canonical appearance validation, frequency invariant, and internal answer-data construction.
- `public/admin/question-form.php`: POST field names and controller mapping.
- `views/admin/questions/form.php`: UI display labels and submitted values for types, answer status, sources, terms, and year `min="1900" max="2200"`.
- `views/admin/import.php`: user-facing JSON guide and module/subject selection outside the file.
- `core/Quiz.php`: objective grading for MCQ/Match/True / False and self-graded handling for Complete/Compare/Essay.
- `tests/phase3_test.php`, `tests/phase4_test.php`, `tests/phase6_test.php`, `tests/pre_phase7_hardening_test.php`, `tests/exam_appearances_test.php`, `tests/frequency_consistency_test.php`, and `tests/manual_import_fixture_test.php`: validation, import, appearance, frequency, duplicate, and grading regression coverage.
