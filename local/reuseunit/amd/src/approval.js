/**
 * Approval module for local_reuseunit.
 *
 * @module     local_reuseunit/approval
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';

/**
 * Approval manager class.
 */
class ApprovalManager {
    /**
     * Constructor.
     */
    constructor() {
        this.initEventListeners();
    }

    /**
     * Initialize event listeners.
     */
    initEventListeners() {
        // Approve buttons.
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const templateId = e.currentTarget.dataset.templateid;
                this.approveTemplate(templateId);
            });
        });

        // Reject buttons - show modal.
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const templateId = e.currentTarget.dataset.templateid;
                this.showRejectModal(templateId);
            });
        });

        // Preview buttons.
        document.querySelectorAll('.preview-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const templateId = e.currentTarget.dataset.templateid;
                this.previewTemplate(templateId);
            });
        });

        // Confirm reject button.
        const confirmRejectBtn = document.getElementById('confirmRejectBtn');
        if (confirmRejectBtn) {
            confirmRejectBtn.addEventListener('click', () => {
                this.confirmReject();
            });
        }

        // Refresh button.
        const refreshBtn = document.getElementById('refreshPendingBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                location.reload();
            });
        }
    }

    /**
     * Approve a template.
     *
     * @param {number} templateId Template ID
     */
    async approveTemplate(templateId) {
        const confirmTitle = await getString('confirmapproval', 'local_reuseunit');
        const confirmMessage = await getString('confirmapprovalmsg', 'local_reuseunit');

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: confirmTitle,
            body: confirmMessage,
        });

        modal.setSaveButtonText(await getString('approve', 'local_reuseunit'));
        modal.getRoot().on(ModalEvents.save, async() => {
            try {
                const response = await Ajax.call([{
                    methodname: 'local_reuseunit_approve_template',
                    args: {templateid: parseInt(templateId)},
                }])[0];

                if (response.success) {
                    this.removeTemplateRow(templateId);
                    Notification.addNotification({
                        message: response.message,
                        type: 'success',
                    });
                }
            } catch (error) {
                Notification.exception(error);
            }
        });

        modal.show();
    }

    /**
     * Show the rejection modal.
     *
     * @param {number} templateId Template ID
     */
    showRejectModal(templateId) {
        const modal = document.getElementById('rejectModal');
        const templateIdInput = document.getElementById('rejectTemplateId');
        const reasonInput = document.getElementById('rejectionReason');

        if (modal && templateIdInput) {
            templateIdInput.value = templateId;
            reasonInput.value = '';

            // Use Bootstrap modal if available.
            if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.modal) {
                window.jQuery(modal).modal('show');
            } else {
                modal.classList.add('show');
                modal.style.display = 'block';
            }
        }
    }

    /**
     * Confirm rejection.
     */
    async confirmReject() {
        const templateIdInput = document.getElementById('rejectTemplateId');
        const reasonInput = document.getElementById('rejectionReason');
        const modal = document.getElementById('rejectModal');

        if (!templateIdInput || !templateIdInput.value) {
            return;
        }

        const templateId = parseInt(templateIdInput.value);
        const reason = reasonInput ? reasonInput.value : '';

        try {
            const response = await Ajax.call([{
                methodname: 'local_reuseunit_reject_template',
                args: {
                    templateid: templateId,
                    reason: reason,
                },
            }])[0];

            if (response.success) {
                // Close modal.
                if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.modal) {
                    window.jQuery(modal).modal('hide');
                } else {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                }

                this.removeTemplateRow(templateId);
                Notification.addNotification({
                    message: response.message,
                    type: 'success',
                });
            }
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Preview a template.
     *
     * @param {number} templateId Template ID
     */
    async previewTemplate(templateId) {
        try {
            const response = await Ajax.call([{
                methodname: 'local_reuseunit_get_templates',
                args: {},
            }])[0];

            const template = response.templates.find(t => t.id === parseInt(templateId));

            if (template) {
                const modal = await ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    title: template.name,
                    body: this.buildPreviewHtml(template),
                    large: true,
                });
                modal.show();
            }
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Build preview HTML.
     *
     * @param {Object} template Template data
     * @returns {string} HTML content
     */
    buildPreviewHtml(template) {
        return `
            <div class="template-preview">
                <p><strong>Description:</strong> ${this.escapeHtml(template.description || 'No description')}</p>
                <p><strong>Tags:</strong> ${this.escapeHtml(template.tags || 'No tags')}</p>
                <p><strong>Activities:</strong> ${template.activitiescount}</p>
                <p><strong>Resources:</strong> ${template.resourcescount}</p>
                <p><strong>Usage count:</strong> ${template.usagecount}</p>
            </div>
        `;
    }

    /**
     * Remove a template row from the table.
     *
     * @param {number} templateId Template ID
     */
    removeTemplateRow(templateId) {
        const row = document.querySelector(`tr[data-templateid="${templateId}"]`);
        if (row) {
            row.classList.add('fade-out');
            setTimeout(() => {
                row.remove();
                this.updateCount();
            }, 300);
        }
    }

    /**
     * Update the pending count badge.
     */
    updateCount() {
        const rows = document.querySelectorAll('#pendingTemplatesTable tbody tr');
        const badge = document.querySelector('.badge-warning');

        if (badge) {
            badge.textContent = rows.length;
        }

        // Show empty message if no more templates.
        if (rows.length === 0) {
            const table = document.getElementById('pendingTemplatesTable');
            if (table) {
                table.style.display = 'none';
            }

            const container = document.querySelector('.reuseunit-approval');
            if (container) {
                const emptyMsg = document.createElement('div');
                emptyMsg.className = 'alert alert-info text-center';
                emptyMsg.innerHTML = '<i class="fa fa-check-circle fa-2x mb-2"></i><p>No pending approvals</p>';
                container.appendChild(emptyMsg);
            }
        }
    }

    /**
     * Escape HTML entities.
     *
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/**
 * Initialize the approval manager.
 */
export const init = () => {
    new ApprovalManager();
};
