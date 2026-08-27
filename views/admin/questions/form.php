<div class="admin-question-form-container" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('admin/questions.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Question Bank
        </a>
        <h1><?= $isEdit ? 'Edit Question' : 'Create Question' ?></h1>
        <p style="color: var(--text-muted);"><?= $isEdit ? 'Update details for Question ID #' . (int)$question['id'] : 'Manually add a medical exam question.' ?></p>
    </div>

    <div class="card">
        <form method="POST" action="<?= url('admin/question-form.php' . ($isEdit ? '?id=' . (int)$question['id'] : '')) ?>" id="question-form">
            <?= CSRF::field() ?>
            <input type="hidden" id="is_edit" value="<?= $isEdit ? '1' : '0' ?>">
            <input type="hidden" id="original_type" value="<?= $isEdit ? e($question['type']) : '' ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Module Selection -->
                <div class="form-group">
                    <label class="form-label" for="form_module_id">Module <span style="color: var(--error);">*</span></label>
                    <select id="form_module_id" class="form-control" required>
                        <option value="">-- Select Module --</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= (int)$m['id'] ?>" <?= ($isEdit && (int)$question['module_id'] === (int)$m['id']) ? 'selected' : '' ?>>
                                <?= e($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Subject Selection -->
                <div class="form-group">
                    <label class="form-label" for="subject_id">Subject <span style="color: var(--error);">*</span></label>
                    <select id="subject_id" name="subject_id" class="form-control" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" data-module-id="<?= (int)$s['module_id'] ?>" <?= ($isEdit && (int)$question['subject_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                                <?= e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Question Type Selection -->
                <div class="form-group">
                    <label class="form-label" for="type">Question Type <span style="color: var(--error);">*</span></label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="mcq" <?= ($type === 'mcq') ? 'selected' : '' ?>>Multiple Choice (MCQ)</option>
                        <option value="complete" <?= ($type === 'complete') ? 'selected' : '' ?>>Complete</option>
                        <option value="match" <?= ($type === 'match') ? 'selected' : '' ?>>Match</option>
                        <option value="compare" <?= ($type === 'compare') ? 'selected' : '' ?>>Compare</option>
                        <option value="essay" <?= ($type === 'essay') ? 'selected' : '' ?>>Essay</option>
                    </select>
                </div>

                <!-- Answer Status -->
                <div class="form-group">
                    <label class="form-label" for="answer_status">Answer Status <span style="color: var(--error);">*</span></label>
                    <select id="answer_status" name="answer_status" class="form-control" required>
                        <option value="available" <?= ($answerStatus === 'available') ? 'selected' : '' ?>>Answer Available</option>
                        <option value="unavailable" <?= ($answerStatus === 'unavailable') ? 'selected' : '' ?>>Answer Unavailable</option>
                    </select>
                </div>
            </div>

            <!-- Question Text -->
            <div class="form-group">
                <label class="form-label" for="question_text">Question Text <span style="color: var(--error);">*</span></label>
                <textarea id="question_text" name="question_text" class="form-control" rows="4" placeholder="Enter the question details or prompt..." required><?= e($questionText) ?></textarea>
            </div>

            <!-- ==========================================
                 TYPE-SPECIFIC CONDITIONAL FIELD CONTAINERS
                 ========================================== -->

            <!-- Standard text answer container (Complete, Compare, Essay) -->
            <div id="answer-container-text" class="card type-container" style="background: var(--bg-page); display: none;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="answer">Correct / Model Answer</label>
                    <textarea id="answer" name="answer" class="form-control" rows="4" placeholder="Enter model answer here..."><?= e($plainAnswer) ?></textarea>
                </div>
            </div>

            <!-- MCQ Options container -->
            <div id="answer-container-mcq" class="card type-container" style="background: var(--bg-page); display: none;">
                <h4 style="margin-bottom: 0.75rem;">MCQ Configuration</h4>
                
                <div id="mcq-options-wrapper" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                    <!-- Options list populated dynamically by JS -->
                </div>

                <button type="button" id="add-mcq-option" class="btn btn-secondary btn-sm">+ Add Option</button>
            </div>

            <!-- Matching Options container -->
            <div id="answer-container-match" class="card type-container" style="background: var(--bg-page); display: none;">
                <h4 style="margin-bottom: 0.75rem;">Matching Configuration</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem;">
                    <div>
                        <h5 style="margin-bottom: 0.5rem; color: var(--text);">Left-side Items</h5>
                        <div id="match-left-wrapper" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.5rem;"></div>
                        <button type="button" id="add-match-left" class="btn btn-secondary btn-sm">+ Add Left Item</button>
                    </div>
                    <div>
                        <h5 style="margin-bottom: 0.5rem; color: var(--text);">Right-side Items</h5>
                        <div id="match-right-wrapper" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.5rem;"></div>
                        <button type="button" id="add-match-right" class="btn btn-secondary btn-sm">+ Add Right Item</button>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 1rem;">
                    <h5 style="margin-bottom: 0.5rem;">Correct Matches Mapping</h5>
                    <div id="matches-mapping-wrapper" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                        <!-- Mappings drop-downs populated dynamically by JS -->
                    </div>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 1.25rem; margin-top: 1.5rem;">
                <!-- Frequency -->
                <div class="form-group">
                    <label class="form-label" for="frequency">Frequency <span style="color: var(--error);">*</span></label>
                    <input type="number" id="frequency" name="frequency" class="form-control" value="<?= (int)$frequency ?>" min="1" required>
                    <div class="form-help" id="frequency-help">Number of recorded exam appearances. With no appearances, you may enter the known frequency independently.</div>
                </div>

            </div>

            <?php $formAppearances = is_array($appearances ?? null) ? $appearances : []; ?>
            <section class="card" style="margin-top: 1.25rem; padding: 1rem;" aria-labelledby="exam-appearances-heading">
                <h3 id="exam-appearances-heading" style="margin: 0 0 .35rem;">Exam Appearances</h3>
                <p class="form-help" style="margin-bottom: .85rem;">Optional. Add one row for each exam in which this question appeared. Each row counts toward Frequency.</p>
                <?php if ($formAppearances): ?><p class="form-help"><strong>Recorded appearances: <?= count($formAppearances) ?>. Frequency is synchronized to this count.</strong></p><?php endif; ?>
                <div id="appearance-list">
                    <?php foreach ($formAppearances as $index => $appearance):
                        $appearanceSource = $appearance['source_name'] ?? $appearance['source'] ?? '';
                        $appearanceYear = $appearance['exam_year'] ?? $appearance['year'] ?? '';
                        $appearanceTerm = $appearance['exam_term'] ?? $appearance['term'] ?? '';
                    ?>
                        <div class="appearance-row" data-appearance-row style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: .75rem; align-items: end; margin-bottom: .75rem;">
                            <div class="form-group" style="margin: 0;"><label class="form-label">Source</label><select name="appearances[<?= (int)$index ?>][source_name]" class="form-control" required><option value="">-- Select source --</option><option value="final" <?= $appearanceSource === 'final' ? 'selected' : '' ?>>Final</option><option value="end_module" <?= $appearanceSource === 'end_module' ? 'selected' : '' ?>>End Module</option></select></div>
                            <div class="form-group" style="margin: 0;"><label class="form-label">Year</label><input name="appearances[<?= (int)$index ?>][exam_year]" type="number" min="1900" max="2200" class="form-control" value="<?= e((string)$appearanceYear) ?>" required></div>
                            <div class="form-group" style="margin: 0;"><label class="form-label">Term</label><select name="appearances[<?= (int)$index ?>][exam_term]" class="form-control" required><option value="">-- Select term --</option><option value="first" <?= $appearanceTerm === 'first' ? 'selected' : '' ?>>First</option><option value="second" <?= $appearanceTerm === 'second' ? 'selected' : '' ?>>Second</option></select></div>
                            <button type="button" class="btn btn-secondary btn-sm" data-remove-appearance>Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p id="no-appearances-message" class="form-help" <?= $formAppearances ? 'hidden' : '' ?>>No exam appearances added.</p>
                <button type="button" class="btn btn-secondary btn-sm" id="add-exam-appearance">+ Add Exam Appearance</button>
            </section>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="<?= url('admin/questions.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <?= $isEdit ? 'Save Changes' : 'Create Question' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     CLIENT-SIDE DYNAMIC INTERACTION SCRIPTS
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('type');
    const answerStatusSelect = document.getElementById('answer_status');
    const moduleSelect = document.getElementById('form_module_id');
    const subjectSelect = document.getElementById('subject_id');
    const submitBtn = document.getElementById('submit-btn');
    const isEdit = document.getElementById('is_edit').value === '1';
    const originalType = document.getElementById('original_type').value;

    // Data passed from backend for editing
    const rawOptions = <?= json_encode($options ?? []) ?>;
    const rawCorrectAnswer = <?= json_encode($correctAnswer ?? '') ?>;
    const rawLeftItems = <?= json_encode($leftItems ?? []) ?>;
    const rawRightItems = <?= json_encode($rightItems ?? []) ?>;
    const rawMatches = <?= json_encode($matches ?? new stdClass()) ?>;

    // --- Module/Subject cascading dropdown filtering ---
    const filterSubjects = () => {
        const selectedModuleId = moduleSelect.value;
        let selectedSubjectExists = false;

        Array.from(subjectSelect.options).forEach(opt => {
            if (!opt.value) return; // skip header
            const parentId = opt.getAttribute('data-module-id');
            if (selectedModuleId === '' || parentId === selectedModuleId) {
                opt.style.display = '';
                if (opt.selected) selectedSubjectExists = true;
            } else {
                opt.style.display = 'none';
                if (opt.selected) {
                    opt.selected = false;
                    subjectSelect.value = '';
                }
            }
        });

        // Set subject selection auto-focus or auto-fill if only one is shown
        const visibleOpts = Array.from(subjectSelect.options).filter(o => o.style.display !== 'none' && o.value);
        if (visibleOpts.length === 1 && !selectedSubjectExists) {
            visibleOpts[0].selected = true;
        }
    };

    moduleSelect.addEventListener('change', filterSubjects);
    // Initialize cascading selection if module is prefilled
    if (moduleSelect.value) {
        filterSubjects();
    }

    // --- Dynamic Question Form Layouts based on Type & Status ---
    const toggleAnswerFields = () => {
        const type = typeSelect.value;
        const status = answerStatusSelect.value;
        
        // Hide all containers initially
        document.querySelectorAll('.type-container').forEach(c => c.style.display = 'none');

        // Manage disabled states for inputs depending on Answer Status
        const setInputsState = (container, disabled) => {
            container.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.id !== 'add-mcq-option' && input.id !== 'add-match-left' && input.id !== 'add-match-right') {
                    input.disabled = disabled;
                }
            });
        };

        if (type === 'mcq') {
            const container = document.getElementById('answer-container-mcq');
            container.style.display = '';
            
            // In MCQ, options are always needed even if correct answer is not defined/unavailable.
            // But Correct Answer selector (checkbox/radio/select) will be hidden/disabled if status is unavailable.
            const correctSelectors = container.querySelectorAll('.mcq-correct-selector');
            correctSelectors.forEach(el => {
                el.style.display = (status === 'available') ? '' : 'none';
                el.disabled = (status !== 'available');
            });

        } else if (type === 'match') {
            const container = document.getElementById('answer-container-match');
            container.style.display = '';
            
            // Left/right lists are always available. The actual correct mapping block is toggled.
            const mappingWrapper = document.getElementById('matches-mapping-wrapper').parentNode;
            mappingWrapper.style.display = (status === 'available') ? '' : 'none';
            container.querySelectorAll('#matches-mapping-wrapper select').forEach(sel => {
                sel.disabled = (status !== 'available');
            });

        } else {
            // complete, compare, essay
            const container = document.getElementById('answer-container-text');
            if (status === 'available') {
                container.style.display = '';
                document.getElementById('answer').disabled = false;
                document.getElementById('answer').required = true;
            } else {
                container.style.display = 'none';
                document.getElementById('answer').disabled = true;
                document.getElementById('answer').required = false;
            }
        }
    };

    // Warn on Type change during Edit mode
    typeSelect.addEventListener('change', (e) => {
        if (isEdit && typeSelect.value !== originalType) {
            const confirmChange = confirm("Warning: Changing the question type will discard the current options/answers configuration. Do you wish to continue?");
            if (!confirmChange) {
                typeSelect.value = originalType;
                return;
            }
        }
        toggleAnswerFields();
        syncDataLayouts();
    });

    answerStatusSelect.addEventListener('change', toggleAnswerFields);

    // --- Dynamic MCQ Options Builder ---
    const mcqWrapper = document.getElementById('mcq-options-wrapper');
    let mcqOptionsCount = 0;

    const renderMcqOption = (value = '', isCorrect = false) => {
        const optionId = mcqOptionsCount++;
        const item = document.createElement('div');
        item.className = 'mcq-option-row';
        item.style.display = 'flex';
        item.style.alignItems = 'center';
        item.style.gap = '0.5rem';
        item.style.marginBottom = '0.25rem';
        
        item.innerHTML = `
            <input type="text" name="options[]" class="form-control mcq-option-text" value="${escapeHtml(value)}" placeholder="Option description" required style="flex: 1;">
            <label class="mcq-correct-selector" style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer; white-space: nowrap; font-size: 0.85rem;">
                <input type="radio" name="correct_answer_radio" value="${optionId}" ${isCorrect ? 'checked' : ''}> Correct
            </label>
            <button type="button" class="btn remove-mcq-opt" style="background-color: var(--error-bg); color: var(--error); border-color: var(--error-border); padding: 0.4rem 0.65rem;">&times;</button>
        `;

        item.querySelector('.remove-mcq-opt').addEventListener('click', () => {
            item.remove();
            updateMcqRadioValues();
        });

        mcqWrapper.appendChild(item);
        toggleAnswerFields();
    };

    document.getElementById('add-mcq-option').addEventListener('click', () => renderMcqOption());

    const updateMcqRadioValues = () => {
        // Sync correct answer name dynamically on submit
    };

    // --- Dynamic Match Items Builder ---
    const leftWrapper = document.getElementById('match-left-wrapper');
    const rightWrapper = document.getElementById('match-right-wrapper');
    const mappingWrapper = document.getElementById('matches-mapping-wrapper');

    const renderMatchLeft = (value = '') => {
        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.alignItems = 'center';
        row.style.gap = '0.35rem';
        
        row.innerHTML = `
            <input type="text" name="left_items[]" class="form-control match-left-item" value="${escapeHtml(value)}" placeholder="Left item" required>
            <button type="button" class="btn remove-left" style="background-color: var(--error-bg); color: var(--error); border-color: var(--error-border); padding: 0.4rem 0.65rem;">&times;</button>
        `;

        row.querySelector('.remove-left').addEventListener('click', () => {
            row.remove();
            rebuildMatchesMapping();
        });

        row.querySelector('.match-left-item').addEventListener('change', rebuildMatchesMapping);
        leftWrapper.appendChild(row);
        rebuildMatchesMapping();
    };

    const renderMatchRight = (value = '') => {
        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.alignItems = 'center';
        row.style.gap = '0.35rem';
        
        row.innerHTML = `
            <input type="text" name="right_items[]" class="form-control match-right-item" value="${escapeHtml(value)}" placeholder="Right item" required>
            <button type="button" class="btn remove-right" style="background-color: var(--error-bg); color: var(--error); border-color: var(--error-border); padding: 0.4rem 0.65rem;">&times;</button>
        `;

        row.querySelector('.remove-right').addEventListener('click', () => {
            row.remove();
            rebuildMatchesMapping();
        });

        row.querySelector('.match-right-item').addEventListener('change', rebuildMatchesMapping);
        rightWrapper.appendChild(row);
        rebuildMatchesMapping();
    };

    document.getElementById('add-match-left').addEventListener('click', () => renderMatchLeft());
    document.getElementById('add-match-right').addEventListener('click', () => renderMatchRight());

    // Dynamically rebuild matching mapping UI selectors
    const rebuildMatchesMapping = () => {
        const lefts = Array.from(leftWrapper.querySelectorAll('.match-left-item')).map(el => el.value.trim()).filter(Boolean);
        const rights = Array.from(rightWrapper.querySelectorAll('.match-right-item')).map(el => el.value.trim()).filter(Boolean);

        // Keep current selected mappings before rebuilding
        const currentSelections = {};
        mappingWrapper.querySelectorAll('.mapping-row').forEach(row => {
            const leftVal = row.querySelector('.mapping-left-label').textContent;
            const rightVal = row.querySelector('.mapping-right-select').value;
            currentSelections[leftVal] = rightVal;
        });

        mappingWrapper.innerHTML = '';

        if (lefts.length === 0 || rights.length === 0) {
            mappingWrapper.innerHTML = '<p style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">Enter Left and Right items above to configure matches.</p>';
            return;
        }

        lefts.forEach(leftItem => {
            const row = document.createElement('div');
            row.className = 'mapping-row';
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '1fr auto 1.2fr';
            row.style.gap = '0.5rem';
            row.style.alignItems = 'center';

            let optionsHtml = '<option value="">-- Choose Right Item --</option>';
            rights.forEach(rightItem => {
                const isSelected = (currentSelections[leftItem] === rightItem) || (rawMatches[leftItem] === rightItem);
                optionsHtml += `<option value="${escapeHtml(rightItem)}" ${isSelected ? 'selected' : ''}>${escapeHtml(rightItem)}</option>`;
            });

            row.innerHTML = `
                <div class="mapping-left-label" style="font-weight: 600; font-size: 0.9rem; padding: 0.4rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-sm);">${escapeHtml(leftItem)}</div>
                <div style="font-weight: bold; color: var(--text-muted);">&rarr;</div>
                <select name="matches[${escapeHtml(leftItem)}]" class="form-control mapping-right-select" required>
                    ${optionsHtml}
                </select>
            `;

            mappingWrapper.appendChild(row);
        });

        toggleAnswerFields();
    };

    // --- Sync Pre-populated data layouts for Editing mode ---
    const syncDataLayouts = () => {
        const type = typeSelect.value;
        
        mcqWrapper.innerHTML = '';
        leftWrapper.innerHTML = '';
        rightWrapper.innerHTML = '';
        mappingWrapper.innerHTML = '';

        if (type === 'mcq') {
            if (rawOptions && rawOptions.length > 0) {
                rawOptions.forEach(opt => {
                    const isCorrect = (opt === rawCorrectAnswer);
                    renderMcqOption(opt, isCorrect);
                });
            } else {
                // Add 4 empty default options for a fresh MCQ question
                renderMcqOption('', false);
                renderMcqOption('', false);
                renderMcqOption('', false);
                renderMcqOption('', false);
            }
        } else if (type === 'match') {
            if (rawLeftItems && rawLeftItems.length > 0) {
                rawLeftItems.forEach(leftVal => renderMatchLeft(leftVal));
            } else {
                renderMatchLeft('');
            }

            if (rawRightItems && rawRightItems.length > 0) {
                rawRightItems.forEach(rightVal => renderMatchRight(rightVal));
            } else {
                renderMatchRight('');
            }
        }
    };

    // Initialize layout sync on page load
    syncDataLayouts();
    toggleAnswerFields();

    // Form pre-submission sanity check/assembly
    const questionForm = document.getElementById('question-form');
    questionForm.addEventListener('submit', (e) => {
        const type = typeSelect.value;
        const status = answerStatusSelect.value;

        if (status === 'available') {
            if (type === 'mcq') {
                const selectedRadio = mcqWrapper.querySelector('input[name="correct_answer_radio"]:checked');
                if (!selectedRadio) {
                    alert('Please select the correct MCQ answer option.');
                    e.preventDefault();
                    return;
                }
                const optIndex = selectedRadio.value;
                const correctTextEl = mcqWrapper.querySelectorAll('.mcq-option-text')[optIndex];
                
                // Build a hidden input containing correct_answer matching option text dynamically
                let correctHidden = document.getElementById('correct-answer-hidden');
                if (!correctHidden) {
                    correctHidden = document.createElement('input');
                    correctHidden.type = 'hidden';
                    correctHidden.id = 'correct-answer-hidden';
                    correctHidden.name = 'correct_answer';
                    questionForm.appendChild(correctHidden);
                }
                correctHidden.value = correctTextEl.value;
            }
        }
    });

    // Helper HTML escaper
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('appearance-list');
    const addButton = document.getElementById('add-exam-appearance');
    const emptyMessage = document.getElementById('no-appearances-message');
    const frequencyInput = document.getElementById('frequency');
    if (!list || !addButton || !emptyMessage || !frequencyInput) return;
    let nextIndex = <?= count($formAppearances) ?>;
    const refresh = () => { const count = list.querySelectorAll('[data-appearance-row]').length; emptyMessage.hidden = count > 0; if (count > 0) { frequencyInput.value = count; frequencyInput.readOnly = true; } else { frequencyInput.readOnly = false; } };
    const addRow = () => {
        const index = nextIndex++;
        const row = document.createElement('div');
        row.className = 'appearance-row';
        row.dataset.appearanceRow = '';
        row.style.cssText = 'display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end;margin-bottom:.75rem;';
        row.innerHTML = `<div class="form-group" style="margin:0"><label class="form-label">Source</label><select name="appearances[${index}][source_name]" class="form-control" required><option value="">-- Select source --</option><option value="final">Final</option><option value="end_module">End Module</option></select></div><div class="form-group" style="margin:0"><label class="form-label">Year</label><input name="appearances[${index}][exam_year]" type="number" min="1900" max="2200" class="form-control" required></div><div class="form-group" style="margin:0"><label class="form-label">Term</label><select name="appearances[${index}][exam_term]" class="form-control" required><option value="">-- Select term --</option><option value="first">First</option><option value="second">Second</option></select></div><button type="button" class="btn btn-secondary btn-sm" data-remove-appearance>Remove</button>`;
        list.appendChild(row); refresh();
    };
    addButton.addEventListener('click', addRow);
    list.addEventListener('click', (event) => { if (event.target.matches('[data-remove-appearance]')) { event.target.closest('[data-appearance-row]').remove(); refresh(); } });
    refresh();
});
</script>
