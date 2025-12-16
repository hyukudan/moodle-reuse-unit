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
 * Import wizard functionality.
 *
 * @module     local_reuseunit/import_wizard
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

/**
 * Import wizard controller.
 */
class ImportWizard {
    /**
     * Constructor.
     *
     * @param {number} courseid - Destination course ID
     * @param {number} sectionid - Destination section ID
     */
    constructor(courseid, sectionid) {
        this.destCourseid = courseid;
        this.destSectionid = sectionid;
        this.sourceCourseid = null;
        this.sourceSectionid = null;
        this.currentStep = 1;
        this.searchTimeout = null;
        this.advancedSearchOpen = false;
        this.selectedActivityTypes = [];
        this.batchMode = false;
        this.importQueue = [];
        this.selectedCmids = []; // For granular content selection
        this.allCmids = []; // All available cmids in preview

        this.init();
    }

    /**
     * Initialize the wizard.
     */
    init() {
        this.bindEvents();
        this.loadTemplates();
    }

    /**
     * Bind event listeners.
     */
    bindEvents() {
        const wizard = document.getElementById('reuseunit-wizard');
        if (!wizard) {
            return;
        }

        // Course search.
        const sourceSearch = document.getElementById('source-course-search');
        if (sourceSearch) {
            sourceSearch.addEventListener('input', (e) => this.handleCourseSearch(e, 'source'));
            sourceSearch.addEventListener('focus', (e) => this.handleCourseSearch(e, 'source'));
        }

        const destSearch = document.getElementById('dest-course-search');
        if (destSearch) {
            destSearch.addEventListener('input', (e) => this.handleCourseSearch(e, 'dest'));
            destSearch.addEventListener('focus', (e) => this.handleCourseSearch(e, 'dest'));
        }

        // Template search.
        const templateSearch = document.getElementById('template-search');
        if (templateSearch) {
            templateSearch.addEventListener('input', () => this.handleTemplateSearch());
        }

        // Navigation buttons.
        document.getElementById('btn-step1-next')?.addEventListener('click', () => this.goToStep(2));
        document.getElementById('btn-step2-prev')?.addEventListener('click', () => this.goToStep(1));
        document.getElementById('btn-step2-next')?.addEventListener('click', () => this.goToStep(3));
        document.getElementById('btn-step3-prev')?.addEventListener('click', () => this.goToStep(2));
        document.getElementById('btn-import')?.addEventListener('click', () => this.performImport());
        document.getElementById('btn-import-another')?.addEventListener('click', () => this.reset());

        // Click outside to close search results.
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.input-group')) {
                document.querySelectorAll('.reuseunit-search-results').forEach(el => el.classList.add('d-none'));
            }
        });

        // Advanced search toggle.
        const advancedToggle = document.getElementById('toggle-advanced-search');
        if (advancedToggle) {
            advancedToggle.addEventListener('click', () => this.toggleAdvancedSearch());
        }

        // Section search input.
        const sectionSearch = document.getElementById('section-search-input');
        if (sectionSearch) {
            sectionSearch.addEventListener('input', () => this.handleSectionSearch());
        }

        // Activity type checkboxes.
        document.querySelectorAll('.activity-type-filter').forEach(cb => {
            cb.addEventListener('change', () => this.handleActivityTypeFilter());
        });

        // Clear filters button.
        const clearFilters = document.getElementById('clear-filters');
        if (clearFilters) {
            clearFilters.addEventListener('click', () => this.clearSearchFilters());
        }

        // Batch mode toggle.
        const batchToggle = document.getElementById('toggle-batch-mode');
        if (batchToggle) {
            batchToggle.addEventListener('change', () => this.toggleBatchMode());
        }

        // Clear queue button.
        const clearQueue = document.getElementById('clear-queue');
        if (clearQueue) {
            clearQueue.addEventListener('click', () => this.clearImportQueue());
        }

        // Batch import button.
        document.getElementById('btn-batch-import')?.addEventListener('click', () => this.performBatchImport());
    }

    /**
     * Handle course search input.
     *
     * @param {Event} e - Input event
     * @param {string} type - 'source' or 'dest'
     */
    handleCourseSearch(e, type) {
        const query = e.target.value.trim();
        const resultsContainer = document.getElementById(`${type}-course-results`);

        if (query.length < 2) {
            resultsContainer.classList.add('d-none');
            return;
        }

        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.searchCourses(query, type);
        }, 300);
    }

    /**
     * Search for courses.
     *
     * @param {string} query - Search query
     * @param {string} type - 'source' or 'dest'
     */
    async searchCourses(query, type) {
        const resultsContainer = document.getElementById(`${type}-course-results`);

        try {
            const courses = await Ajax.call([{
                methodname: 'local_reuseunit_search_courses',
                args: {query, limit: 10}
            }])[0];

            if (courses.length === 0) {
                const noResults = await getString('nocoursesfound', 'local_reuseunit');
                resultsContainer.innerHTML = `<div class="p-2 text-muted">${noResults}</div>`;
            } else {
                resultsContainer.innerHTML = courses.map(course => `
                    <div class="reuseunit-course-result p-2" data-courseid="${course.id}">
                        <div class="font-weight-bold">${course.fullname}</div>
                        <small class="text-muted">${course.shortname} - ${course.categoryname}</small>
                    </div>
                `).join('');

                resultsContainer.querySelectorAll('.reuseunit-course-result').forEach(el => {
                    el.addEventListener('click', () => this.selectCourse(el.dataset.courseid, type, el));
                });
            }

            resultsContainer.classList.remove('d-none');
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Select a course.
     *
     * @param {number} courseid - Course ID
     * @param {string} type - 'source' or 'dest'
     * @param {HTMLElement} element - Clicked element
     */
    async selectCourse(courseid, type, element) {
        const searchInput = document.getElementById(`${type}-course-search`);
        const resultsContainer = document.getElementById(`${type}-course-results`);
        const hiddenInput = document.getElementById(`${type}-course-id`);

        searchInput.value = element.querySelector('.font-weight-bold').textContent;
        hiddenInput.value = courseid;
        resultsContainer.classList.add('d-none');

        if (type === 'source') {
            this.sourceCourseid = courseid;
            await this.loadSections(courseid);
        } else {
            this.destCourseid = courseid;
        }
    }

    /**
     * Load sections for a course.
     *
     * @param {number} courseid - Course ID
     */
    async loadSections(courseid) {
        const container = document.getElementById('sections-container');
        const list = document.getElementById('sections-list');

        container.style.display = 'block';
        list.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';

        try {
            const sections = await Ajax.call([{
                methodname: 'local_reuseunit_get_sections',
                args: {courseid}
            }])[0];

            if (sections.length === 0) {
                const noSections = await getString('nosections', 'local_reuseunit');
                list.innerHTML = `<div class="alert alert-info">${noSections}</div>`;
                return;
            }

            let html = '';
            for (const section of sections) {
                const rendered = await Templates.render('local_reuseunit/section_item', {
                    ...section,
                    courseid
                });
                html += rendered;
            }
            list.innerHTML = html;

            // Bind section selection.
            list.querySelectorAll('.section-radio').forEach(radio => {
                radio.addEventListener('change', () => {
                    this.sourceSectionid = radio.value;
                    document.getElementById('btn-step1-next').disabled = false;
                });
            });

        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Load available templates.
     */
    async loadTemplates() {
        const list = document.getElementById('templates-list');
        if (!list) {
            return;
        }

        try {
            const templates = await Ajax.call([{
                methodname: 'local_reuseunit_get_templates',
                args: {query: '', limit: 20}
            }])[0];

            if (templates.length === 0) {
                const noTemplates = await getString('notemplates', 'local_reuseunit');
                list.innerHTML = `<p class="text-muted text-center py-3">${noTemplates}</p>`;
                return;
            }

            let html = '';
            for (const template of templates) {
                const rendered = await Templates.render('local_reuseunit/template_item', template);
                html += rendered;
            }
            list.innerHTML = html;

            // Bind template selection.
            list.querySelectorAll('.template-radio').forEach(radio => {
                radio.addEventListener('change', () => {
                    this.selectedTemplateId = radio.value;
                    document.getElementById('btn-step1-next').disabled = false;
                });
            });

        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Handle template search.
     */
    async handleTemplateSearch() {
        const query = document.getElementById('template-search').value.trim();
        const list = document.getElementById('templates-list');

        try {
            const templates = await Ajax.call([{
                methodname: 'local_reuseunit_get_templates',
                args: {query, limit: 20}
            }])[0];

            if (templates.length === 0) {
                const noTemplates = await getString('notemplates', 'local_reuseunit');
                list.innerHTML = `<p class="text-muted text-center py-3">${noTemplates}</p>`;
                return;
            }

            let html = '';
            for (const template of templates) {
                const rendered = await Templates.render('local_reuseunit/template_item', template);
                html += rendered;
            }
            list.innerHTML = html;

        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Navigate to a step.
     *
     * @param {number} step - Step number
     */
    async goToStep(step) {
        // Validate before proceeding.
        if (step === 2 && !this.sourceSectionid && !this.selectedTemplateId) {
            return;
        }

        if (step === 2) {
            await this.loadPreview();
        }

        if (step === 3) {
            this.updateSummary();
        }

        // Update step indicators.
        document.querySelectorAll('.reuseunit-step').forEach(el => {
            const stepNum = parseInt(el.dataset.step);
            el.classList.toggle('active', stepNum === step);
            el.classList.toggle('completed', stepNum < step);
        });

        // Show/hide step content.
        document.querySelectorAll('.reuseunit-step-content').forEach(el => {
            const stepNum = el.dataset.step;
            el.classList.toggle('d-none', stepNum != step);
        });

        this.currentStep = step;
    }

    /**
     * Load section preview.
     */
    async loadPreview() {
        const preview = document.getElementById('section-preview');
        preview.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';

        try {
            const content = await Ajax.call([{
                methodname: 'local_reuseunit_get_section_content',
                args: {
                    courseid: this.sourceCourseid,
                    sectionid: this.sourceSectionid
                }
            }])[0];

            const html = await Templates.render('local_reuseunit/section_preview', content);
            preview.innerHTML = html;

            this.previewData = content;

            // Initialize granular selection.
            this.initContentSelection();
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Initialize content selection for granular import.
     */
    initContentSelection() {
        const preview = document.getElementById('section-preview');
        if (!preview) {
            return;
        }

        // Collect all cmids from content checkboxes.
        this.allCmids = [];
        this.selectedCmids = [];

        preview.querySelectorAll('.content-checkbox').forEach(checkbox => {
            const cmid = parseInt(checkbox.value);
            this.allCmids.push(cmid);
            if (checkbox.checked) {
                this.selectedCmids.push(cmid);
            }
        });

        // Bind events for content checkboxes.
        preview.querySelectorAll('.content-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.handleContentCheckboxChange(checkbox));
        });

        // Bind select all checkbox.
        const selectAll = preview.querySelector('#select-all-content');
        if (selectAll) {
            selectAll.addEventListener('change', () => this.handleSelectAllChange(selectAll.checked));
        }

        // Update initial count.
        this.updateSelectionCount();
    }

    /**
     * Handle individual content checkbox change.
     *
     * @param {HTMLInputElement} checkbox - The checkbox element
     */
    handleContentCheckboxChange(checkbox) {
        const cmid = parseInt(checkbox.value);

        if (checkbox.checked) {
            if (!this.selectedCmids.includes(cmid)) {
                this.selectedCmids.push(cmid);
            }
        } else {
            this.selectedCmids = this.selectedCmids.filter(id => id !== cmid);
        }

        this.updateSelectionCount();
        this.updateSelectAllState();
        this.updatePartialImportNotice();
    }

    /**
     * Handle select all checkbox change.
     *
     * @param {boolean} checked - Whether select all is checked
     */
    handleSelectAllChange(checked) {
        const preview = document.getElementById('section-preview');
        if (!preview) {
            return;
        }

        preview.querySelectorAll('.content-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
        });

        if (checked) {
            this.selectedCmids = [...this.allCmids];
        } else {
            this.selectedCmids = [];
        }

        this.updateSelectionCount();
        this.updatePartialImportNotice();
    }

    /**
     * Update the selection count display.
     */
    updateSelectionCount() {
        const countEl = document.getElementById('selected-count');
        if (countEl) {
            countEl.textContent = this.selectedCmids.length;
        }

        // Disable import button if nothing selected.
        const importBtn = document.getElementById('btn-import');
        if (importBtn) {
            importBtn.disabled = this.selectedCmids.length === 0;
        }
    }

    /**
     * Update the select all checkbox state based on individual selections.
     */
    updateSelectAllState() {
        const selectAll = document.querySelector('#select-all-content');
        if (!selectAll) {
            return;
        }

        if (this.selectedCmids.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        } else if (this.selectedCmids.length === this.allCmids.length) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        }
    }

    /**
     * Update partial import notice visibility.
     */
    updatePartialImportNotice() {
        const notice = document.getElementById('partial-import-notice');
        if (notice) {
            const isPartial = this.selectedCmids.length > 0 && this.selectedCmids.length < this.allCmids.length;
            notice.style.display = isPartial ? 'inline' : 'none';
        }
    }

    /**
     * Update import summary.
     */
    updateSummary() {
        const list = document.getElementById('import-summary-list');
        if (!list || !this.previewData) {
            return;
        }

        const isPartial = this.selectedCmids.length < this.allCmids.length;
        const selectionInfo = isPartial
            ? `<span class="badge badge-warning">${this.selectedCmids.length}/${this.allCmids.length} elementos seleccionados</span>`
            : `<span class="badge badge-success">Todos los elementos</span>`;

        list.innerHTML = `
            <li><strong>${this.previewData.name}</strong></li>
            <li>${this.previewData.activities} actividades, ${this.previewData.resources} recursos</li>
            <li>${selectionInfo}</li>
        `;
    }

    /**
     * Perform the import.
     */
    async performImport() {
        // Show progress.
        this.goToStep('progress');

        const options = {
            resetdates: document.getElementById('option-resetdates')?.checked ?? true,
            includerestrictions: document.getElementById('option-restrictions')?.checked ?? false,
            includegradebook: document.getElementById('option-gradebook')?.checked ?? false,
        };

        const position = document.querySelector('input[name="position"]:checked')?.value ?? 'end';
        const newName = document.getElementById('new-section-name')?.value ?? '';

        // Determine if this is a partial import.
        const isPartial = this.selectedCmids.length < this.allCmids.length;
        const selectedCmidsJson = isPartial ? JSON.stringify(this.selectedCmids) : '';

        try {
            const result = await Ajax.call([{
                methodname: 'local_reuseunit_import_section',
                args: {
                    sourcecourseid: this.sourceCourseid,
                    sourcesectionid: this.sourceSectionid,
                    destcourseid: this.destCourseid,
                    position,
                    newsectionname: newName,
                    selectedcmids: selectedCmidsJson,
                    ...options
                }
            }])[0];

            if (result.success) {
                document.getElementById('btn-view-section').href = result.courseurl;
                const partialNote = result.partial_import ? ' (importación parcial)' : '';
                document.getElementById('result-stats').innerHTML = `
                    ${result.activities} actividades, ${result.resources} recursos importados${partialNote}
                `;
                this.goToStep('result');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Notification.exception(error);
            this.goToStep(3);
        }
    }

    /**
     * Reset the wizard.
     */
    reset() {
        this.sourceCourseid = null;
        this.sourceSectionid = null;
        this.selectedTemplateId = null;
        this.previewData = null;

        document.getElementById('source-course-search').value = '';
        document.getElementById('source-course-id').value = '';
        document.getElementById('sections-container').style.display = 'none';
        document.getElementById('btn-step1-next').disabled = true;

        this.goToStep(1);
    }

    /**
     * Toggle advanced search panel.
     */
    toggleAdvancedSearch() {
        this.advancedSearchOpen = !this.advancedSearchOpen;
        const panel = document.getElementById('advanced-search-panel');
        const toggle = document.getElementById('toggle-advanced-search');

        if (panel) {
            panel.classList.toggle('d-none', !this.advancedSearchOpen);
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', this.advancedSearchOpen);
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-chevron-down', !this.advancedSearchOpen);
                icon.classList.toggle('fa-chevron-up', this.advancedSearchOpen);
            }
        }
    }

    /**
     * Handle section search across all courses.
     */
    async handleSectionSearch() {
        const query = document.getElementById('section-search-input')?.value.trim() || '';

        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.searchSections(query);
        }, 300);
    }

    /**
     * Handle activity type filter change.
     */
    handleActivityTypeFilter() {
        this.selectedActivityTypes = [];
        document.querySelectorAll('.activity-type-filter:checked').forEach(cb => {
            this.selectedActivityTypes.push(cb.value);
        });

        // Re-search with new filters.
        const query = document.getElementById('section-search-input')?.value.trim() || '';
        this.searchSections(query);
    }

    /**
     * Search for sections.
     *
     * @param {string} query - Search query
     */
    async searchSections(query) {
        const resultsContainer = document.getElementById('section-search-results');
        if (!resultsContainer) {
            return;
        }

        // Show loading.
        resultsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';
        resultsContainer.classList.remove('d-none');

        try {
            const activitytypes = this.selectedActivityTypes.join(',');
            const courseid = this.sourceCourseid || 0;

            const sections = await Ajax.call([{
                methodname: 'local_reuseunit_search_sections',
                args: {
                    query,
                    activitytypes,
                    courseid,
                    limit: 20
                }
            }])[0];

            if (sections.length === 0) {
                const noResults = await getString('nosectionsfound', 'local_reuseunit');
                resultsContainer.innerHTML = `<div class="alert alert-info">${noResults}</div>`;
                return;
            }

            // Show count.
            const countText = await getString('sectionsmatching', 'local_reuseunit', sections.length);
            let html = `<div class="mb-2 text-muted small">${countText}</div>`;

            // Render results.
            for (const section of sections) {
                const activityList = section.activitytypes.map(type => `
                    <span class="badge badge-secondary badge-pill mr-1">${type}</span>
                `).join('');

                html += `
                    <div class="reuseunit-section-result p-2 border-bottom" data-courseid="${section.courseid}" data-sectionid="${section.sectionid}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="font-weight-bold">${section.sectionname}</div>
                                <small class="text-muted">${section.coursename} (${section.courseshortname})</small>
                            </div>
                            <div class="text-right">
                                <small>${section.activities} activities, ${section.resources} resources</small>
                            </div>
                        </div>
                        <div class="mt-1">${activityList}</div>
                    </div>
                `;
            }

            resultsContainer.innerHTML = html;

            // Bind click events.
            resultsContainer.querySelectorAll('.reuseunit-section-result').forEach(el => {
                el.addEventListener('click', () => this.selectSearchResult(el));
            });

        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Select a search result.
     *
     * @param {HTMLElement} element - Clicked element
     */
    async selectSearchResult(element) {
        // In batch mode, add to queue instead.
        if (this.batchMode) {
            await this.handleBatchSelection(element);
            return;
        }

        const courseid = element.dataset.courseid;
        const sectionid = element.dataset.sectionid;

        this.sourceCourseid = parseInt(courseid);
        this.sourceSectionid = parseInt(sectionid);

        // Update UI to show selection.
        document.querySelectorAll('.reuseunit-section-result').forEach(el => {
            el.classList.remove('bg-primary', 'text-white');
        });
        element.classList.add('bg-primary', 'text-white');

        // Enable next button.
        document.getElementById('btn-step1-next').disabled = false;
    }

    /**
     * Clear all search filters.
     */
    clearSearchFilters() {
        document.getElementById('section-search-input').value = '';
        document.querySelectorAll('.activity-type-filter').forEach(cb => {
            cb.checked = false;
        });
        this.selectedActivityTypes = [];

        const resultsContainer = document.getElementById('section-search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '';
            resultsContainer.classList.add('d-none');
        }
    }

    /**
     * Toggle batch import mode.
     */
    toggleBatchMode() {
        this.batchMode = document.getElementById('toggle-batch-mode')?.checked || false;

        const queuePanel = document.getElementById('import-queue-panel');
        const singleButtons = document.getElementById('single-import-buttons');
        const batchButtons = document.getElementById('batch-import-buttons');

        if (queuePanel) {
            queuePanel.classList.toggle('d-none', !this.batchMode);
        }
        if (singleButtons) {
            singleButtons.classList.toggle('d-none', this.batchMode);
        }
        if (batchButtons) {
            batchButtons.classList.toggle('d-none', !this.batchMode);
        }

        // Clear queue when switching modes.
        if (!this.batchMode) {
            this.clearImportQueue();
        }
    }

    /**
     * Add a section to the import queue.
     *
     * @param {Object} sectionData - Section data
     */
    async addToQueue(sectionData) {
        // Check if already in queue.
        const exists = this.importQueue.some(
            item => item.sourcecourseid === sectionData.sourcecourseid &&
                    item.sourcesectionid === sectionData.sourcesectionid
        );

        if (exists) {
            return;
        }

        this.importQueue.push(sectionData);
        await this.updateQueueDisplay();
    }

    /**
     * Remove an item from the import queue.
     *
     * @param {number} index - Queue index
     */
    async removeFromQueue(index) {
        this.importQueue.splice(index, 1);
        await this.updateQueueDisplay();
    }

    /**
     * Clear the import queue.
     */
    async clearImportQueue() {
        this.importQueue = [];
        await this.updateQueueDisplay();
    }

    /**
     * Update the queue display.
     */
    async updateQueueDisplay() {
        const queueList = document.getElementById('queue-list');
        const queueCount = document.getElementById('queue-count');
        const batchImportBtn = document.getElementById('btn-batch-import');

        if (!queueList) {
            return;
        }

        if (this.importQueue.length === 0) {
            const noItems = await getString('nosectionsselected', 'local_reuseunit');
            queueList.innerHTML = `<div class="text-muted text-center py-3">${noItems}</div>`;
            if (batchImportBtn) {
                batchImportBtn.disabled = true;
            }
        } else {
            let html = '';
            this.importQueue.forEach((item, index) => {
                html += `
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                        <div>
                            <strong>${item.sectionname}</strong>
                            <small class="text-muted d-block">${item.coursename}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger queue-remove" data-index="${index}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                `;
            });
            queueList.innerHTML = html;

            // Bind remove buttons.
            queueList.querySelectorAll('.queue-remove').forEach(btn => {
                btn.addEventListener('click', () => this.removeFromQueue(parseInt(btn.dataset.index)));
            });

            if (batchImportBtn) {
                batchImportBtn.disabled = false;
            }
        }

        if (queueCount) {
            const countText = await getString('queueitems', 'local_reuseunit', this.importQueue.length);
            queueCount.textContent = countText;
        }
    }

    /**
     * Handle selection in batch mode.
     *
     * @param {HTMLElement} element - Selected element
     */
    async handleBatchSelection(element) {
        const courseid = parseInt(element.dataset.courseid);
        const sectionid = parseInt(element.dataset.sectionid);
        const sectionname = element.querySelector('.font-weight-bold')?.textContent || '';
        const coursename = element.querySelector('.text-muted')?.textContent || '';

        await this.addToQueue({
            sourcecourseid: courseid,
            sourcesectionid: sectionid,
            sectionname,
            coursename,
            newsectionname: '',
        });

        // Visual feedback.
        element.classList.add('bg-success', 'text-white');
        setTimeout(() => {
            element.classList.remove('bg-success', 'text-white');
        }, 500);
    }

    /**
     * Perform batch import.
     */
    async performBatchImport() {
        if (this.importQueue.length === 0) {
            return;
        }

        // Show progress.
        this.goToStep('progress');

        const options = {
            resetdates: document.getElementById('option-resetdates')?.checked ?? true,
            includerestrictions: document.getElementById('option-restrictions')?.checked ?? false,
            includegradebook: document.getElementById('option-gradebook')?.checked ?? false,
        };

        const position = document.querySelector('input[name="position"]:checked')?.value ?? 'end';

        // Prepare sections array.
        const sections = this.importQueue.map(item => ({
            sourcecourseid: item.sourcecourseid,
            sourcesectionid: item.sourcesectionid,
            newsectionname: item.newsectionname || '',
        }));

        try {
            const progressMsg = document.getElementById('progress-message');

            // Update progress message.
            if (progressMsg) {
                const importing = await getString('batchimporting', 'local_reuseunit');
                progressMsg.textContent = importing;
            }

            const result = await Ajax.call([{
                methodname: 'local_reuseunit_batch_import',
                args: {
                    destcourseid: this.destCourseid,
                    sections,
                    position,
                    ...options
                }
            }])[0];

            // Show results.
            document.getElementById('btn-view-section').href = result.courseurl;
            document.getElementById('result-stats').innerHTML = `
                ${result.totalimported}/${sections.length} sections imported<br>
                ${result.totalactivities} activities, ${result.totalresources} resources
            `;

            const resultHeader = document.getElementById('result-header');
            if (!result.success && resultHeader) {
                resultHeader.classList.remove('bg-success');
                resultHeader.classList.add('bg-warning');
            }

            this.goToStep('result');
            this.clearImportQueue();

        } catch (error) {
            Notification.exception(error);
            this.goToStep(3);
        }
    }
}

/**
 * Initialize the import wizard.
 *
 * @param {number} courseid - Course ID
 * @param {number} sectionid - Section ID
 */
export const init = (courseid, sectionid) => {
    new ImportWizard(courseid, sectionid);
};
