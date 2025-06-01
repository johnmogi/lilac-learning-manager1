(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Constants
        const $wrapper = $('.llm-questions-wrapper');
        const $table = $('.llm-questions-table');
        const $filters = $('.llm-questions-filters');
        const $pagination = $('.llm-pagination');
        
        // Initialize the questions table
        initQuestionsTable();
        
        // Handle filter changes
        $filters.on('change', 'select, input', debounce(updateQuestions, 300));
        $filters.on('keyup', 'input[type="search"]', debounce(updateQuestions, 500));
        
        // Handle pagination
        $wrapper.on('click', '.llm-pagination-link:not(.current)', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            updateQuestions({ page });
        });
        
        // Handle inline editing
        $wrapper.on('click', '.llm-editable', function() {
            if ($(this).hasClass('editing')) return;
            startEditing($(this));
        });
        
        // Save on Enter, cancel on Escape
        $wrapper.on('keydown', '.llm-edit-field', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveField($(this).closest('.editing'));
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelEditing($(this).closest('.editing'));
            }
        });
        
        // Save/cancel buttons
        $wrapper.on('click', '.llm-edit-save', function() {
            saveField($(this).closest('.editing'));
        });
        
        $wrapper.on('click', '.llm-edit-cancel', function() {
            cancelEditing($(this).closest('.editing'));
        });
        
        // Initialize tooltips
        $(document).tooltip({
            items: '[data-tooltip]',
            content: function() {
                return $(this).data('tooltip');
            },
            position: {
                my: 'center bottom-10',
                at: 'center top',
                using: function(position, feedback) {
                    $(this).css(position);
                    $('<div>')
                        .addClass('arrow')
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            }
        });
    });
    
    /**
     * Initialize the questions table
     */
    function initQuestionsTable() {
        // Add loading class
        $('.llm-questions-wrapper').addClass('llm-loading');
        
        // Load initial data
        updateQuestions();
    }
    
    /**
     * Update the questions table with filtered data
     */
    function updateQuestions(overrides = {}) {
        const $wrapper = $('.llm-questions-wrapper');
        const $table = $wrapper.find('.llm-questions-table tbody');
        const $filters = $wrapper.find('.llm-questions-filters');
        
        // Show loading state
        $wrapper.addClass('llm-loading');
        
        // Get current filters
        const filters = {
            search: $filters.find('input[type="search"]').val(),
            program_id: $filters.find('select[name="program_id"]').val(),
            course_id: $filters.find('select[name="course_id"]').val(),
            topic_id: $filters.find('select[name="topic_id"]').val(),
            paged: $wrapper.data('current-page') || 1,
            per_page: $wrapper.data('per-page') || 20,
            ...overrides
        };
        
        // Update URL with current filters
        updateUrlParams(filters);
        
        // Send AJAX request
        $.ajax({
            url: llmQuestionAdmin.ajaxUrl,
            type: 'GET',
            data: {
                action: 'llm_get_questions',
                nonce: llmQuestionAdmin.nonce,
                ...filters
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderQuestions(response.data.questions, response.data.pagination);
                } else {
                    showError(response.data.message || 'Failed to load questions.');
                }
            },
            error: function(xhr, status, error) {
                showError('Error loading questions: ' + error);
            },
            complete: function() {
                $wrapper.removeClass('llm-loading');
            }
        });
    }
    
    /**
     * Render questions in the table
     */
    function renderQuestions(questions, pagination) {
        const $wrapper = $('.llm-questions-wrapper');
        const $table = $wrapper.find('.llm-questions-table tbody');
        const $pagination = $wrapper.find('.llm-pagination');
        
        // Clear existing rows
        $table.empty();
        
        if (questions.length === 0) {
            $table.append(`
                <tr>
                    <td colspan="5" class="no-items">
                        ${llmQuestionAdmin.i18n.noQuestionsFound}
                    </td>
                </tr>
            `);
            return;
        }
        
        // Add rows for each question
        questions.forEach(function(question) {
            const row = `
                <tr data-question-id="${question.id}">
                    <td class="llm-question-title">
                        <div class="llm-editable" data-field="title" data-value="${escapeHtml(question.title)}">
                            ${escapeHtml(question.title)}
                        </div>
                        ${question.hint ? `
                            <div class="llm-question-hint">
                                <div class="llm-editable" data-field="hint" data-value="${escapeHtml(question.hint)}">
                                    ${escapeHtml(question.hint)}
                                </div>
                            </div>
                        ` : ''}
                    </td>
                    <td class="llm-question-course">
                        ${question.course ? `
                            <a href="${question.course.edit_url}" target="_blank">
                                ${escapeHtml(question.course.name)}
                            </a>
                        ` : '—'}
                    </td>
                    <td class="llm-question-program">
                        ${question.program ? `
                            <a href="${question.program.edit_url}" target="_blank">
                                ${escapeHtml(question.program.name)}
                            </a>
                        ` : '—'}
                    </td>
                    <td class="llm-question-points">
                        <div class="llm-editable" data-field="points" data-value="${question.points}">
                            ${question.points}
                        </div>
                    </td>
                    <td class="llm-question-actions">
                        <a href="${question.edit_url}" class="llm-question-edit" target="_blank">
                            ${llmQuestionAdmin.i18n.edit}
                        </a>
                    </td>
                </tr>
            `;
            
            $table.append(row);
        });
        
        // Update pagination
        renderPagination(pagination);
    }
    
    /**
     * Render pagination controls
     */
    function renderPagination(pagination) {
        const $pagination = $('.llm-pagination');
        
        if (!pagination || pagination.pages <= 1) {
            $pagination.hide();
            return;
        }
        
        $pagination.show();
        
        // Calculate page numbers to show
        const current = parseInt(pagination.current);
        const pages = parseInt(pagination.pages);
        const range = 2;
        const showItems = range * 2 + 1;
        
        let output = [];
        
        // Previous page link
        if (current > 1) {
            output.push(`
                <a href="#" class="llm-pagination-link" data-page="${current - 1}">
                    &laquo; ${llmQuestionAdmin.i18n.previous}
                </a>
            `);
        }
        
        // Page numbers
        for (let i = 1; i <= pages; i++) {
            if (i === 1 || i === pages || (i >= current - range && i <= current + range)) {
                const active = i === current ? 'current' : '';
                output.push(`
                    <a href="#" class="llm-pagination-link ${active}" data-page="${i}">
                        ${i}
                    </a>
                `);
            } else if (output[output.length - 1] !== '...') {
                output.push('...');
            }
        }
        
        // Next page link
        if (current < pages) {
            output.push(`
                <a href="#" class="llm-pagination-link" data-page="${current + 1}">
                    ${llmQuestionAdmin.i18n.next} &raquo;
                </a>
            `);
        }
        
        // Update pagination info
        const start = (current - 1) * pagination.per_page + 1;
        const end = Math.min(current * pagination.per_page, pagination.total);
        
        const info = `
            <span class="llm-pagination-info">
                ${start}–${end} ${llmQuestionAdmin.i18n.of} ${pagination.total}
            </span>
        `;
        
        $pagination.html(`
            <div class="llm-pagination-info">
                ${info}
            </div>
            <div class="llm-pagination-links">
                ${output.join('')}
            </div>
        `);
    }
    
    /**
     * Start editing a field
     */
    function startEditing($field) {
        // If already editing, do nothing
        if ($field.hasClass('editing')) return;
        
        // Cancel any other edits in progress
        cancelAllEditing();
        
        const value = $field.data('value') || $field.text().trim();
        const fieldName = $field.data('field');
        let inputType = 'text';
        
        // Determine input type based on field
        if (fieldName === 'points') {
            inputType = 'number';
        } else if (fieldName === 'hint' || fieldName === 'content') {
            inputType = 'textarea';
        }
        
        // Create input field
        let inputField = '';
        
        if (inputType === 'textarea') {
            inputField = `
                <textarea class="llm-edit-field" rows="3">${escapeHtml(value)}</textarea>
            `;
        } else {
            inputField = `
                <input type="${inputType}" class="llm-edit-field" value="${escapeHtml(value)}">
            `;
        }
        
        // Create actions
        const actions = `
            <div class="llm-edit-actions">
                <button type="button" class="button button-small llm-edit-save">
                    ${llmQuestionAdmin.i18n.save}
                </button>
                <button type="button" class="button button-small llm-edit-cancel">
                    ${llmQuestionAdmin.i18n.cancel}
                </button>
            </div>
        `;
        
        // Save original content and set editing state
        $field
            .addClass('editing')
            .data('original-html', $field.html())
            .html(`
                ${inputField}
                ${actions}
            `);
        
        // Focus the input field
        $field.find('.llm-edit-field').trigger('focus');
    }
    
    /**
     * Save a field being edited
     */
    function saveField($field) {
        const $row = $field.closest('tr');
        const questionId = $row.data('question-id');
        const fieldName = $field.data('field');
        const $input = $field.find('.llm-edit-field');
        const newValue = $input.val();
        
        // Validate required fields
        if ((fieldName === 'title' || fieldName === 'points') && !newValue) {
            showError(llmQuestionAdmin.i18n.fieldRequired);
            $input.focus();
            return;
        }
        
        // Validate points is a number
        if (fieldName === 'points' && isNaN(newValue)) {
            showError(llmQuestionAdmin.i18n.invalidNumber);
            $input.focus();
            return;
        }
        
        // Show saving indicator
        $field.addClass('saving');
        
        // Send AJAX request to save the field
        $.ajax({
            url: llmQuestionAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'llm_update_question',
                nonce: llmQuestionAdmin.nonce,
                question_id: questionId,
                field: fieldName,
                value: newValue
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the field display
                    $field
                        .removeClass('editing saving')
                        .data('value', newValue)
                        .html(fieldName === 'hint' || fieldName === 'content' 
                            ? newValue.replace(/\n/g, '<br>') 
                            : newValue);
                    
                    // Show success message
                    showNotice(llmQuestionAdmin.i18n.saved, 'success');
                } else {
                    throw new Error(response.data.message || llmQuestionAdmin.i18n.saveFailed);
                }
            },
            error: function(xhr, status, error) {
                throw new Error(error || llmQuestionAdmin.i18n.saveFailed);
            }
        }).fail(function(error) {
            $field.removeClass('saving');
            showError(error.message);
        });
    }
    
    /**
     * Cancel editing a field
     */
    function cancelEditing($field) {
        const originalHtml = $field.data('original-html');
        $field
            .removeClass('editing')
            .html(originalHtml);
    }
    
    /**
     * Cancel all active edits
     */
    function cancelAllEditing() {
        $('.llm-editable.editing').each(function() {
            cancelEditing($(this));
        });
    }
    
    /**
     * Show an error message
     */
    function showError(message) {
        showNotice(message, 'error');
    }
    
    /**
     * Show a notice message
     */
    function showNotice(message, type = 'success') {
        // Remove any existing notices
        $('.llm-notice').remove();
        
        // Create and show new notice
        const $notice = $(`
            <div class="notice notice-${type} is-dismissible llm-notice">
                <p>${message}</p>
            </div>
        `);
        
        // Add to the page
        $notice.insertAfter('.wrap > h1').hide().fadeIn(200);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(200, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Dismiss on click
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(200, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Update URL parameters without reloading the page
     */
    function updateUrlParams(params) {
        const url = new URL(window.location.href);
        
        // Remove empty parameters
        Object.keys(params).forEach(key => {
            if (params[key]) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        
        // Update URL without reloading
        window.history.pushState({}, '', url);
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(unsafe) {
        if (typeof unsafe === 'undefined' || unsafe === null) return '';
        return unsafe
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    /**
     * Debounce function to limit the rate at which a function can fire
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }
    
})(jQuery);
