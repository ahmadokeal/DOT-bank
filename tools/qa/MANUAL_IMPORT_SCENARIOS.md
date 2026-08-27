# Manual JSON Import QA Scenarios

Upload `manual_import_scenarios.json` from Admin → JSON Import after selecting any Module and Subject. It contains 35 valid questions across MCQ, Complete, Match, Compare, and Essay, with available and unavailable answers, zero/one/multiple appearances, different years/terms, Final + End Module rows, and frequencies that follow the current consistency rule.

Expected valid preview: **35 valid, 0 invalid**. Confirm the import, then use the Question Bank filters for type, answer status, frequency, year, term, Final, and End Module. The fixture is large enough to exercise pagination.

Duplicate checks use the repeated `[QA DUPLICATE]` records in the main fixture. Upload the record again against the imported question to test an exact duplicate. For controlled merge testing, upload `manual_import_merge_scenarios.json` one record at a time after the main fixture: the first record adds a genuinely new appearance and updates frequency to 2; the second repeats that appearance and should be skipped without creating another question.

For invalid validation, upload `manual_import_invalid_scenarios.json` independently. Expected preview: **21 invalid, 0 valid**. The missing top-level `questions` case is in `manual_import_invalid_missing_questions.json` and should fail with a top-level structure error. The invalid-source case also has a frequency mismatch because the current importer reports unsupported sources as review warnings; this keeps the fixture compatible without changing production behavior.

All valid appearance records use `source`, integer `year`, and `term`. Where appearances exist, `frequency` equals their count; records without appearances demonstrate independent frequencies of 1, 2, and 5.
