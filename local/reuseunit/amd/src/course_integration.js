// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course integration - adds import buttons to course sections.
 *
 * @module     local_reuseunit/course_integration
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Fragment from 'core/fragment';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

let courseid = 0;
let contextid = 0;

/**
 * Initialize course integration.
 *
 * @param {Object} config - Configuration
 * @param {number} config.courseid - Course ID
 * @param {number} config.contextid - Context ID
 */
export const init = (config) => {
    courseid = config.courseid;
    contextid = config.contextid;

    addImportButtons();
    bindEvents();
};

/**
 * Add import buttons to each section.
 */
const addImportButtons = async () => {
    const importText = await getString('importunithere', 'local_reuseunit');
    const saveTemplateText = await getString('saveastemplate', 'local_reuseunit');
    const duplicateText = await getString('duplicatesection', 'local_reuseunit');
    const exportText = await getString('exportasmbz', 'local_reuseunit');

    // Find all section action menus.
    document.querySelectorAll('.course-section-header .section-actions').forEach((actions, index) => {
        // Skip section 0.
        if (index === 0) {
            return;
        }

        const sectionElement = actions.closest('[data-sectionid]');
        const sectionid = sectionElement?.dataset.sectionid;

        if (!sectionid) {
            return;
        }

        // Add import button.
        const importBtn = document.createElement('a');
        importBtn.href = '#';
        importBtn.className = 'reuseunit-import-btn btn btn-sm btn-outline-primary ml-2';
        importBtn.innerHTML = `<i class="fa fa-download"></i> ${importText}`;
        importBtn.dataset.action = 'reuseunit-import';
        importBtn.dataset.sectionid = sectionid;

        // Add save as template button.
        const saveBtn = document.createElement('a');
        saveBtn.href = '#';
        saveBtn.className = 'reuseunit-save-template-btn btn btn-sm btn-outline-secondary ml-1';
        saveBtn.innerHTML = `<i class="fa fa-save"></i>`;
        saveBtn.title = saveTemplateText;
        saveBtn.dataset.action = 'reuseunit-save-template';
        saveBtn.dataset.sectionid = sectionid;

        // Add duplicate button.
        const duplicateBtn = document.createElement('a');
        duplicateBtn.href = '#';
        duplicateBtn.className = 'reuseunit-duplicate-btn btn btn-sm btn-outline-info ml-1';
        duplicateBtn.innerHTML = `<i class="fa fa-copy"></i>`;
        duplicateBtn.title = duplicateText;
        duplicateBtn.dataset.action = 'reuseunit-duplicate';
        duplicateBtn.dataset.sectionid = sectionid;

        // Add export button.
        const exportBtn = document.createElement('a');
        exportBtn.href = '#';
        exportBtn.className = 'reuseunit-export-btn btn btn-sm btn-outline-success ml-1';
        exportBtn.innerHTML = `<i class="fa fa-file-archive-o"></i>`;
        exportBtn.title = exportText;
        exportBtn.dataset.action = 'reuseunit-export';
        exportBtn.dataset.sectionid = sectionid;

        const container = document.createElement('div');
        container.className = 'reuseunit-section-buttons d-inline-flex ml-2';
        container.appendChild(importBtn);
        container.appendChild(saveBtn);
        container.appendChild(duplicateBtn);
        container.appendChild(exportBtn);

        actions.appendChild(container);
    });
};

/**
 * Bind event listeners.
 */
const bindEvents = () => {
    document.addEventListener('click', async (e) => {
        const importBtn = e.target.closest('[data-action="reuseunit-import"]');
        if (importBtn) {
            e.preventDefault();
            await openImportModal(importBtn.dataset.sectionid);
        }

        const saveBtn = e.target.closest('[data-action="reuseunit-save-template"]');
        if (saveBtn) {
            e.preventDefault();
            await openSaveTemplateModal(saveBtn.dataset.sectionid);
        }

        const duplicateBtn = e.target.closest('[data-action="reuseunit-duplicate"]');
        if (duplicateBtn) {
            e.preventDefault();
            await duplicateSection(duplicateBtn.dataset.sectionid);
        }

        const exportBtn = e.target.closest('[data-action="reuseunit-export"]');
        if (exportBtn) {
            e.preventDefault();
            await exportSection(exportBtn.dataset.sectionid);
        }
    });
};

/**
 * Open import modal.
 *
 * @param {number} sectionid - Section ID
 */
const openImportModal = async (sectionid) => {
    try {
        const title = await getString('importunithere', 'local_reuseunit');

        const modal = await ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: title,
            body: Fragment.loadFragment('local_reuseunit', 'import_modal', contextid, {
                courseid,
                sectionid
            }),
            large: true,
        });

        modal.getRoot().on(ModalEvents.shown, () => {
            // Initialize wizard after modal is shown.
            import('./import_wizard').then(wizard => {
                wizard.init(courseid, sectionid);
            });
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Open save template modal.
 *
 * @param {number} sectionid - Section ID
 */
const openSaveTemplateModal = async (sectionid) => {
    try {
        // Get section name.
        const sectionElement = document.querySelector(`[data-sectionid="${sectionid}"]`);
        const sectionName = sectionElement?.querySelector('.sectionname')?.textContent?.trim() || '';

        const title = await getString('saveastemplate', 'local_reuseunit');

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: Fragment.loadFragment('local_reuseunit', 'save_template_modal', contextid, {
                courseid,
                sectionid,
                sectionname: sectionName
            }),
        });

        modal.getRoot().on(ModalEvents.save, async (e) => {
            e.preventDefault();
            await saveTemplate(modal, sectionid);
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Save a section as template.
 *
 * @param {Modal} modal - Modal instance
 * @param {number} sectionid - Section ID
 */
const saveTemplate = async (modal, sectionid) => {
    const name = document.getElementById('template-name')?.value;
    const description = document.getElementById('template-description')?.value || '';
    const tags = document.getElementById('template-tags')?.value || '';
    const sharelevel = document.querySelector('input[name="sharelevel"]:checked')?.value || 'personal';

    if (!name) {
        Notification.alert('Error', 'Template name is required');
        return;
    }

    try {
        const result = await Ajax.call([{
            methodname: 'local_reuseunit_save_template',
            args: {
                courseid,
                sectionid,
                name,
                description,
                tags,
                sharelevel
            }
        }])[0];

        if (result.success) {
            modal.destroy();
            Notification.addNotification({
                message: result.message,
                type: 'success'
            });
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Duplicate a section within the same course.
 *
 * @param {number} sectionid - Section ID
 */
const duplicateSection = async (sectionid) => {
    try {
        const confirmTitle = await getString('duplicatesection', 'local_reuseunit');
        const confirmMsg = await getString('confirmimportmessage', 'local_reuseunit');

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: confirmTitle,
            body: `<p>${confirmMsg}</p>
                <div class="form-group">
                    <label for="duplicate-position">${await getString('duplicateposition', 'local_reuseunit')}</label>
                    <select id="duplicate-position" class="form-control">
                        <option value="after_source">${await getString('position_after_source', 'local_reuseunit')}</option>
                        <option value="end">${await getString('position_end', 'local_reuseunit')}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="duplicate-name">${await getString('newsectionname', 'local_reuseunit')}</label>
                    <input type="text" id="duplicate-name" class="form-control" placeholder="${await getString('keepsectionname', 'local_reuseunit')}">
                </div>`,
        });

        modal.setSaveButtonText(await getString('duplicatesection', 'local_reuseunit'));

        modal.getRoot().on(ModalEvents.save, async (e) => {
            e.preventDefault();
            modal.hide();

            const position = document.getElementById('duplicate-position')?.value || 'after_source';
            const newname = document.getElementById('duplicate-name')?.value || '';

            // Show loading notification.
            const loadingMsg = await getString('duplicating', 'local_reuseunit');
            Notification.addNotification({
                message: loadingMsg,
                type: 'info'
            });

            try {
                const result = await Ajax.call([{
                    methodname: 'local_reuseunit_duplicate_section',
                    args: {
                        courseid,
                        sectionid: parseInt(sectionid),
                        position,
                        newname
                    }
                }])[0];

                if (result.success) {
                    Notification.addNotification({
                        message: result.message,
                        type: 'success'
                    });
                    // Reload page to show new section.
                    window.location.reload();
                }
            } catch (err) {
                Notification.exception(err);
            }

            modal.destroy();
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Export a section as .mbz file.
 *
 * @param {number} sectionid - Section ID
 */
const exportSection = async (sectionid) => {
    try {
        const confirmTitle = await getString('exportsection', 'local_reuseunit');

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: confirmTitle,
            body: `<div class="form-group">
                    <label for="export-filename">${await getString('exportfilename', 'local_reuseunit')}</label>
                    <input type="text" id="export-filename" class="form-control" placeholder="${await getString('exportfilename_help', 'local_reuseunit')}">
                </div>`,
        });

        modal.setSaveButtonText(await getString('exportasmbz', 'local_reuseunit'));

        modal.getRoot().on(ModalEvents.save, async (e) => {
            e.preventDefault();
            modal.hide();

            const filename = document.getElementById('export-filename')?.value || '';

            // Show loading notification.
            const loadingMsg = await getString('exporting', 'local_reuseunit');
            Notification.addNotification({
                message: loadingMsg,
                type: 'info'
            });

            try {
                const result = await Ajax.call([{
                    methodname: 'local_reuseunit_export_section',
                    args: {
                        courseid,
                        sectionid: parseInt(sectionid),
                        filename
                    }
                }])[0];

                if (result.success) {
                    Notification.addNotification({
                        message: result.message,
                        type: 'success'
                    });
                    // Trigger download.
                    window.location.href = result.downloadurl;
                }
            } catch (err) {
                Notification.exception(err);
            }

            modal.destroy();
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};
