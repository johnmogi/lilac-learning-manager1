jQuery(document).ready(function($) {
    'use strict';

    // Cache DOM elements
    const $table = $('.llm-questions-wrap .wp-list-table');
    const $inlineEdit = $('#llm-inline-edit');
    let currentQuestionId = 0;

    // Initialize the interface
    function init() {
        loadQuestions();
        setupEventListeners();
    }

    // Load questions via AJAX
    function loadQuestions() {
        const $tableBody = $table.find('tbody');
        $tableBody.html('<tr><td colspan="6" class="llm-loading"></td></tr>');

        $.ajax({
            url: llmQuestions.ajax_url,
            type: 'POST',
            data: {
                action: 'llm_get_questions',
                nonce: llmQuestions.nonce,
                // Add filter parameters here
            },
            success: function(response) {
                if (response.success) {
                    renderQuestions(response.data.questions);
                } else {
                    showError(response.data.message || 'Failed to load questions');
                }
            },
            error: function() {
                showError('Connection error. Please try again.');
            }
        });
    }


    // Render questions in the table
    function renderQuestions(questions) {
        const $tableBody = $table.find('tbody');
        $tableBody.empty();

        if (questions.length === 0) {
            $tableBody.html('<tr><td colspan="6" style="text-align: center; padding: 20px;">לא נמצאו שאלות</td></tr>');
            return;
        }

        questions.forEach(function(question) {
            const row = `
                <tr id="question-${question.id}" data-id="${question.id}">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="question_ids[]" value="${question.id}">
                    </th>
                    <td class="title column-title has-row-actions column-primary" data-colname="שאלה">
                        <strong>
                            <a href="${question.edit_link}" class="row-title">${question.title}</a>
                        </strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="#" class="editinline" data-id="${question.id}">ערוך</a> |
                            </span>
                            <span class="trash">
                                <a href="${question.delete_link}" class="submitdelete" data-id="${question.id}">מחק</a>
                            </span>
                        </div>
                        <button type="button" class="toggle-row">
                            <span class="screen-reader-text">הצג פרטים נוספים</span>
                        </button>
                    </td>
                    <td data-colname="רמז">${question.hint || '—'}</td>
                    <td data-colname="קורס">${question.course || '—'}</td>
                    <td data-colname="תוכנית">${question.program || '—'}</td>
                    <td data-colname="נוצרה">${question.date_created}</td>
                </tr>`;
            $tableBody.append(row);
        });
    }

    // Setup event listeners
    function setupEventListeners() {
        // Inline edit
        $table.on('click', '.editinline', function(e) {
            e.preventDefault();
            currentQuestionId = $(this).data('id');
            openInlineEdit(currentQuestionId);
        });

        // Save inline edit
        $inlineEdit.on('click', '.save', function() {
            saveQuestion();
        });

        // Close inline edit
        $inlineEdit.on('click', '.cancel', function() {
            closeInlineEdit();
        });

        // Bulk actions
        $('form#llm-questions-form').on('submit', function(e) {
            const $form = $(this);
            const action = $form.find('select[name="action"]').val();
            
            if (action === 'delete') {
                e.preventDefault();
                if (confirm(llmQuestions.i18n.confirm_delete)) {
                    // Handle bulk delete
                    const questionIds = [];
                    $('input[name="question_ids[]"]:checked').each(function() {
                        questionIds.push($(this).val());
                    });
                    
                    if (questionIds.length > 0) {
                        deleteQuestions(questionIds);
                    }
                }
            }
        });
    }

    // Open inline edit form
    function openInlineEdit(questionId) {
        // Show loading state
        $inlineEdit.find('.inline-edit-fields').html('<div class="llm-loading"></div>');
        $inlineEdit.show();

        // Load question data via AJAX
        $.ajax({
            url: llmQuestions.ajax_url,
            type: 'POST',
            data: {
                action: 'llm_get_question',
                nonce: llmQuestions.nonce,
                question_id: questionId
            },
            success: function(response) {
                if (response.success) {
                    renderInlineEditForm(response.data);
                } else {
                    showError(response.data.message || 'Failed to load question');
                    closeInlineEdit();
                }
            },
            error: function() {
                showError('Connection error. Please try again.');
                closeInlineEdit();
            }
        });
    }

    // Render inline edit form with question data
    function renderInlineEditForm(question) {
        const form = `
            <div class="field">
                <label for="llm-question-title">שאלה</label>
                <input type="text" id="llm-question-title" class="widefat" value="${escapeHtml(question.title)}">
            </div>
            <div class="field">
                <label for="llm-question-hint">רמז</label>
                <textarea id="llm-question-hint" class="widefat" rows="3">${escapeHtml(question.hint || '')}</textarea>
            </div>
            <div class="field">
                <label for="llm-question-program">תוכנית</label>
                <select id="llm-question-program" class="widefat">
                    ${question.programs.map(p => 
                        `<option value="${p.id}" ${p.selected ? 'selected' : ''}>${p.name}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="field">
                <label for="llm-question-course">קורס</label>
                <select id="llm-question-course" class="widefat">
                    ${question.courses.map(c => 
                        `<option value="${c.id}" ${c.selected ? 'selected' : ''}>${c.name}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="submit-buttons">
                <button type="button" class="button button-primary save">שמור שינויים</button>
                <button type="button" class="button cancel">ביטול</button>
                <span class="spinner"></span>
            </div>`;

        $inlineEdit.find('.inline-edit-fields').html(form);
    }

    // Save question changes
    function saveQuestion() {
        const $saveButton = $inlineEdit.find('.save');
        const $spinner = $inlineEdit.find('.spinner');
        
        $saveButton.prop('disabled', true);
        $spinner.addClass('is-active');

        const data = {
            action: 'llm_save_question',
            nonce: llmQuestions.nonce,
            question_id: currentQuestionId,
            title: $('#llm-question-title').val(),
            hint: $('#llm-question-hint').val(),
            program_id: $('#llm-question-program').val(),
            course_id: $('#llm-question-course').val()
        };

        $.ajax({
            url: llmQuestions.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Update the row with new data
                    loadQuestions();
                    showNotice('השאלה עודכנה בהצלחה', 'success');
                } else {
                    showError(response.data.message || 'Failed to save question');
                }
            },
            error: function() {
                showError('Connection error. Please try again.');
            },
            complete: function() {
                $saveButton.prop('disabled', false);
                $spinner.removeClass('is-active');
                closeInlineEdit();
            }
        });
    }

    // Delete questions
    function deleteQuestions(questionIds) {
        if (!confirm(llmQuestions.i18n.confirm_delete)) {
            return;
        }

        const $rows = $();
        questionIds.forEach(function(id) {
            $rows.add($(`#question-${id}`));
        });

        $rows.addClass('deleting');

        $.ajax({
            url: llmQuestions.ajax_url,
            type: 'POST',
            data: {
                action: 'llm_delete_questions',
                nonce: llmQuestions.nonce,
                question_ids: questionIds
            },
            success: function(response) {
                if (response.success) {
                    $rows.fadeOut(300, function() {
                        $(this).remove();
                        // Check if table is empty
                        if ($table.find('tbody tr').length === 0) {
                            renderQuestions([]);
                        }
                    });
                    showNotice('השאלות נמחקו בהצלחה', 'success');
                } else {
                    $rows.removeClass('deleting');
                    showError(response.data.message || 'Failed to delete questions');
                }
            },
            error: function() {
                $rows.removeClass('deleting');
                showError('Connection error. Please try again.');
            }
        });
    }

    // Close inline edit form
    function closeInlineEdit() {
        $inlineEdit.hide();
        currentQuestionId = 0;
    }

    // Show error message
    function showError(message) {
        showNotice(message, 'error');
    }

    // Show notice
    function showNotice(message, type = 'success') {
        const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        const $notice = $(`
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">התעלם מההודעה הזו.</span>
                </button>
            </div>`);

        $('.wrap > h1').after($notice);

        // Auto-remove notice after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Helper to escape HTML
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initialize the interface
    init();
});
