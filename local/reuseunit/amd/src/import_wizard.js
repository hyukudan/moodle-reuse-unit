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
        } catch (error) {
            Notification.exception(error);
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

        list.innerHTML = `
            <li><strong>${this.previewData.name}</strong></li>
            <li>${this.previewData.activities} actividades, ${this.previewData.resources} recursos</li>
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

        try {
            const result = await Ajax.call([{
                methodname: 'local_reuseunit_import_section',
                args: {
                    sourcecourseid: this.sourceCourseid,
                    sourcesectionid: this.sourceSectionid,
                    destcourseid: this.destCourseid,
                    position,
                    newsectionname: newName,
                    ...options
                }
            }])[0];

            if (result.success) {
                document.getElementById('btn-view-section').href = result.courseurl;
                document.getElementById('result-stats').innerHTML = `
                    ${result.activities} actividades, ${result.resources} recursos importados
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
