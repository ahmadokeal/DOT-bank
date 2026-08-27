<div class="admin-question-delete-container" style="max-width: 580px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('admin/questions.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Question Bank
        </a>
        <h1 style="color: var(--error);">Confirm Question Deletion</h1>
    </div>

    <div class="card" style="border-color: var(--error-border);">
        <p style="font-size: 1.05rem; margin-bottom: 0.75rem;">
            Are you sure you want to permanently delete Question ID #<strong><?= (int)$question['id'] ?></strong>?
        </p>

        <div style="padding: 1rem; background: var(--bg-page); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
            <p style="font-weight: 700; margin-bottom: 0.35rem; color: var(--dark);">Question Text Preview:</p>
            <p style="font-style: italic; color: var(--text); line-height: 1.4; margin: 0; white-space: pre-wrap;"><?= e($question['question_text']) ?></p>
        </div>

        <p style="color: var(--text-muted); font-size: 0.9rem;">
            This action will delete the question and its associated source references. This action is permanent.
        </p>

        <form method="POST" action="<?= url('admin/question-delete.php?id=' . (int)$question['id']) ?>">
            <?= CSRF::field() ?>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="<?= url('admin/questions.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn" style="background-color: var(--error); color: #ffffff;">
                    Permanently Delete Question
                </button>
            </div>
        </form>
    </div>
</div>
