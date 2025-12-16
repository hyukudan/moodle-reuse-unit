/**
 * Diff comparison module for local_reuseunit.
 *
 * @module     local_reuseunit/compare
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    /**
     * Compare class.
     *
     * @param {Object} config Configuration object
     */
    var Compare = function(config) {
        this.config = config;
        this.sections = {};

        this.initEventListeners();
        this.applyPreselections();
    };

    /**
     * Initialize event listeners.
     */
    Compare.prototype.initEventListeners = function() {
        var self = this;

        // Source course selection.
        var sourceCourse = document.getElementById('source-course-select');
        if (sourceCourse) {
            sourceCourse.addEventListener('change', function(e) {
                self.loadSections(e.target.value, 'source');
            });
        }

        // Target course selection.
        var targetCourse = document.getElementById('target-course-select');
        if (targetCourse) {
            targetCourse.addEventListener('change', function(e) {
                self.loadSections(e.target.value, 'target');
            });
        }

        // Source section selection.
        var sourceSection = document.getElementById('source-section-select');
        if (sourceSection) {
            sourceSection.addEventListener('change', function(e) {
                self.showSectionPreview('source', e.target.value);
                self.updateCompareButton();
            });
        }

        // Target section selection.
        var targetSection = document.getElementById('target-section-select');
        if (targetSection) {
            targetSection.addEventListener('change', function(e) {
                self.showSectionPreview('target', e.target.value);
                self.updateCompareButton();
            });
        }

        // Compare sections button.
        var compareSectionsBtn = document.getElementById('compare-sections-btn');
        if (compareSectionsBtn) {
            compareSectionsBtn.addEventListener('click', function() {
                self.compareSections();
            });
        }

        // Template selection.
        var templateSelect = document.getElementById('template-select');
        if (templateSelect) {
            templateSelect.addEventListener('change', function(e) {
                self.loadVersions(e.target.value);
            });
        }

        // Version selections.
        var version1Select = document.getElementById('version1-select');
        var version2Select = document.getElementById('version2-select');
        if (version1Select) {
            version1Select.addEventListener('change', function() {
                self.updateVersionCompareButton();
            });
        }
        if (version2Select) {
            version2Select.addEventListener('change', function() {
                self.updateVersionCompareButton();
            });
        }

        // Compare versions button.
        var compareVersionsBtn = document.getElementById('compare-versions-btn');
        if (compareVersionsBtn) {
            compareVersionsBtn.addEventListener('click', function() {
                self.compareVersions();
            });
        }
    };

    /**
     * Apply preselected values from config.
     */
    Compare.prototype.applyPreselections = function() {
        // Preselect source course and section.
        if (this.config.preselectedsourcecourse) {
            var sourceCourse = document.getElementById('source-course-select');
            if (sourceCourse) {
                sourceCourse.value = this.config.preselectedsourcecourse;
                this.loadSections(this.config.preselectedsourcecourse, 'source', this.config.preselectedsourcesection);
            }
        }

        // Preselect target course and section.
        if (this.config.preselectedtargetcourse) {
            var targetCourse = document.getElementById('target-course-select');
            if (targetCourse) {
                targetCourse.value = this.config.preselectedtargetcourse;
                this.loadSections(this.config.preselectedtargetcourse, 'target', this.config.preselectedtargetsection);
            }
        }

        // Preselect template.
        if (this.config.preselectedtemplate) {
            var templateSelect = document.getElementById('template-select');
            if (templateSelect) {
                templateSelect.value = this.config.preselectedtemplate;
                this.loadVersions(this.config.preselectedtemplate);
            }
        }
    };

    /**
     * Load sections for a course.
     *
     * @param {number} courseId Course ID
     * @param {string} type 'source' or 'target'
     * @param {number} preselect Optional section ID to preselect
     */
    Compare.prototype.loadSections = function(courseId, type, preselect) {
        var self = this;
        var selectEl = document.getElementById(type + '-section-select');
        preselect = preselect || null;

        if (!courseId) {
            selectEl.innerHTML = '<option value="">Select section</option>';
            selectEl.disabled = true;
            return;
        }

        selectEl.disabled = true;
        selectEl.innerHTML = '<option value="">Loading...</option>';

        Ajax.call([{
            methodname: 'local_reuseunit_get_sections',
            args: {
                courseid: parseInt(courseId)
            }
        }])[0].then(function(response) {
            self.sections[type] = response.sections || [];

            selectEl.innerHTML = '<option value="">Select section</option>';
            self.sections[type].forEach(function(section) {
                var option = document.createElement('option');
                option.value = section.id;
                option.textContent = section.name + ' (' + section.activities + ' activities, ' + section.resources + ' resources)';
                selectEl.appendChild(option);
            });

            selectEl.disabled = false;

            // Preselect if specified.
            if (preselect) {
                selectEl.value = preselect;
                self.showSectionPreview(type, preselect);
                self.updateCompareButton();
            }
        }).catch(function(error) {
            Notification.exception(error);
            selectEl.innerHTML = '<option value="">Error loading sections</option>';
        });
    };

    /**
     * Show section preview.
     *
     * @param {string} type 'source' or 'target'
     * @param {number} sectionId Section ID
     */
    Compare.prototype.showSectionPreview = function(type, sectionId) {
        var preview = document.getElementById(type + '-section-preview');
        if (!preview) {
            return;
        }

        if (!sectionId || !this.sections[type]) {
            preview.classList.add('d-none');
            return;
        }

        var section = null;
        for (var i = 0; i < this.sections[type].length; i++) {
            if (this.sections[type][i].id == sectionId) {
                section = this.sections[type][i];
                break;
            }
        }
        if (!section) {
            preview.classList.add('d-none');
            return;
        }

        preview.querySelector('.section-name').textContent = section.name;
        preview.querySelector('.section-content').textContent =
            section.activities + ' activities, ' + section.resources + ' resources';
        preview.classList.remove('d-none');
    };

    /**
     * Update compare sections button state.
     */
    Compare.prototype.updateCompareButton = function() {
        var sourceCourse = document.getElementById('source-course-select').value;
        var sourceSection = document.getElementById('source-section-select').value;
        var targetCourse = document.getElementById('target-course-select').value;
        var targetSection = document.getElementById('target-section-select').value;

        var btn = document.getElementById('compare-sections-btn');
        btn.disabled = !(sourceCourse && sourceSection && targetCourse && targetSection);
    };

    /**
     * Compare two sections.
     */
    Compare.prototype.compareSections = function() {
        var self = this;
        var sourceCourse = parseInt(document.getElementById('source-course-select').value);
        var sourceSection = parseInt(document.getElementById('source-section-select').value);
        var targetCourse = parseInt(document.getElementById('target-course-select').value);
        var targetSection = parseInt(document.getElementById('target-section-select').value);

        var resultsEl = document.getElementById('section-comparison-results');
        var loadingEl = document.getElementById('compare-loading');

        resultsEl.classList.add('d-none');
        loadingEl.classList.remove('d-none');

        Ajax.call([{
            methodname: 'local_reuseunit_compare_sections',
            args: {
                source_courseid: sourceCourse,
                source_sectionid: sourceSection,
                target_courseid: targetCourse,
                target_sectionid: targetSection
            }
        }])[0].then(function(response) {
            self.displaySectionComparison(response);
            loadingEl.classList.add('d-none');
        }).catch(function(error) {
            Notification.exception(error);
            loadingEl.classList.add('d-none');
        });
    };

    /**
     * Display section comparison results.
     *
     * @param {Object} data Comparison data
     */
    Compare.prototype.displaySectionComparison = function(data) {
        var resultsEl = document.getElementById('section-comparison-results');

        // Update counts.
        resultsEl.querySelector('.added-count').textContent = data.added.length;
        resultsEl.querySelector('.removed-count').textContent = data.removed.length;
        resultsEl.querySelector('.modified-count').textContent = data.modified.length;
        resultsEl.querySelector('.unchanged-count').textContent = data.unchanged.length;

        // Update lists.
        this.updateDiffList(resultsEl.querySelector('.added-items'), data.added, 'success');
        this.updateDiffList(resultsEl.querySelector('.removed-items'), data.removed, 'danger');
        this.updateDiffList(resultsEl.querySelector('.modified-items'), data.modified, 'warning');
        this.updateDiffList(resultsEl.querySelector('.unchanged-items'), data.unchanged, 'secondary');

        resultsEl.classList.remove('d-none');
    };

    /**
     * Update a diff list with items.
     *
     * @param {HTMLElement} listEl List element
     * @param {Array} items Items to display
     * @param {string} type Bootstrap color type
     */
    Compare.prototype.updateDiffList = function(listEl, items, type) {
        var self = this;
        if (!items || items.length === 0) {
            listEl.innerHTML = '<li class="list-group-item text-muted no-items">No items</li>';
            return;
        }

        var html = '';
        items.forEach(function(item) {
            html += '<li class="list-group-item diff-item">' +
                '<img src="' + self.getModuleIcon(item.modname) + '" class="item-icon" alt="' + item.modname + '">' +
                '<span class="item-name">' + self.escapeHtml(item.name) + '</span>' +
                '<span class="badge badge-' + type + '">' + item.modname + '</span>' +
                '</li>';
        });
        listEl.innerHTML = html;
    };

    /**
     * Get module icon URL.
     *
     * @param {string} modname Module name
     * @returns {string} Icon URL
     */
    Compare.prototype.getModuleIcon = function(modname) {
        return M.util.image_url('icon', modname);
    };

    /**
     * Load versions for a template.
     *
     * @param {number} templateId Template ID
     */
    Compare.prototype.loadVersions = function(templateId) {
        var self = this;
        var version1Select = document.getElementById('version1-select');
        var version2Select = document.getElementById('version2-select');

        if (!templateId) {
            version1Select.innerHTML = '<option value="">Select version</option>';
            version2Select.innerHTML = '<option value="">Select version</option>';
            version1Select.disabled = true;
            version2Select.disabled = true;
            return;
        }

        version1Select.disabled = true;
        version2Select.disabled = true;
        version1Select.innerHTML = '<option value="">Loading...</option>';
        version2Select.innerHTML = '<option value="">Loading...</option>';

        Ajax.call([{
            methodname: 'local_reuseunit_get_template_versions',
            args: {
                templateid: parseInt(templateId)
            }
        }])[0].then(function(response) {
            var versions = response.versions || [];

            version1Select.innerHTML = '<option value="">Select older version</option>';
            version2Select.innerHTML = '<option value="">Select newer version</option>';

            versions.forEach(function(v) {
                var option1 = document.createElement('option');
                option1.value = v.version;
                option1.textContent = 'Version ' + v.version + ' (' + v.timecreated + ')';
                version1Select.appendChild(option1);

                var option2 = document.createElement('option');
                option2.value = v.version;
                option2.textContent = 'Version ' + v.version + ' (' + v.timecreated + ')';
                version2Select.appendChild(option2);
            });

            version1Select.disabled = versions.length < 2;
            version2Select.disabled = versions.length < 2;

            // Preselect versions if specified.
            if (self.config.preselectedv1) {
                version1Select.value = self.config.preselectedv1;
            }
            if (self.config.preselectedv2) {
                version2Select.value = self.config.preselectedv2;
            }

            self.updateVersionCompareButton();
        }).catch(function(error) {
            Notification.exception(error);
            version1Select.innerHTML = '<option value="">Error loading versions</option>';
            version2Select.innerHTML = '<option value="">Error loading versions</option>';
        });
    };

    /**
     * Update version compare button state.
     */
    Compare.prototype.updateVersionCompareButton = function() {
        var template = document.getElementById('template-select').value;
        var version1 = document.getElementById('version1-select').value;
        var version2 = document.getElementById('version2-select').value;

        var btn = document.getElementById('compare-versions-btn');
        btn.disabled = !(template && version1 && version2 && version1 !== version2);
    };

    /**
     * Compare two template versions.
     */
    Compare.prototype.compareVersions = function() {
        var self = this;
        var templateId = parseInt(document.getElementById('template-select').value);
        var version1 = parseInt(document.getElementById('version1-select').value);
        var version2 = parseInt(document.getElementById('version2-select').value);

        var resultsEl = document.getElementById('version-comparison-results');
        var loadingEl = document.getElementById('compare-loading');

        resultsEl.classList.add('d-none');
        loadingEl.classList.remove('d-none');

        Ajax.call([{
            methodname: 'local_reuseunit_compare_versions',
            args: {
                templateid: templateId,
                version1: Math.min(version1, version2),
                version2: Math.max(version1, version2)
            }
        }])[0].then(function(response) {
            self.displayVersionComparison(response, Math.min(version1, version2), Math.max(version1, version2));
            loadingEl.classList.add('d-none');
        }).catch(function(error) {
            Notification.exception(error);
            loadingEl.classList.add('d-none');
        });
    };

    /**
     * Display version comparison results.
     *
     * @param {Object} data Comparison data
     * @param {number} v1 Version 1 number
     * @param {number} v2 Version 2 number
     */
    Compare.prototype.displayVersionComparison = function(data, v1, v2) {
        var resultsEl = document.getElementById('version-comparison-results');

        // Update version labels.
        document.getElementById('version1-label').textContent = 'Version ' + v1;
        document.getElementById('version2-label').textContent = 'Version ' + v2;

        // Update version 1 details.
        resultsEl.querySelector('.v1-activities').textContent = data.version1.activities_count;
        resultsEl.querySelector('.v1-resources').textContent = data.version1.resources_count;
        resultsEl.querySelector('.v1-course').textContent = data.version1.source_course || '-';
        resultsEl.querySelector('.v1-section').textContent = data.version1.source_section || '-';

        // Update version 2 details.
        resultsEl.querySelector('.v2-activities').textContent = data.version2.activities_count;
        resultsEl.querySelector('.v2-resources').textContent = data.version2.resources_count;
        resultsEl.querySelector('.v2-course').textContent = data.version2.source_course || '-';
        resultsEl.querySelector('.v2-section').textContent = data.version2.source_section || '-';

        // Update changes.
        this.updateChangeBadge(resultsEl.querySelector('.activities-change'), data.changes.activities);
        this.updateChangeBadge(resultsEl.querySelector('.resources-change'), data.changes.resources);
        this.updateChangeBadge(resultsEl.querySelector('.source-change'), data.changes.source);

        resultsEl.classList.remove('d-none');
    };

    /**
     * Update a change badge.
     *
     * @param {HTMLElement} badge Badge element
     * @param {Object} change Change data
     */
    Compare.prototype.updateChangeBadge = function(badge, change) {
        if (!change) {
            badge.textContent = '-';
            badge.className = 'badge badge-secondary';
            return;
        }

        if (change.type === 'increased') {
            badge.textContent = '+' + change.diff;
            badge.className = 'badge badge-success';
        } else if (change.type === 'decreased') {
            badge.textContent = change.diff;
            badge.className = 'badge badge-danger';
        } else if (change.type === 'changed') {
            badge.textContent = 'Changed';
            badge.className = 'badge badge-warning';
        } else {
            badge.textContent = 'No change';
            badge.className = 'badge badge-secondary';
        }
    };

    /**
     * Escape HTML entities.
     *
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    Compare.prototype.escapeHtml = function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    return {
        /**
         * Initialize the compare module.
         *
         * @param {Object} config Configuration object
         */
        init: function(config) {
            return new Compare(config);
        }
    };
});
