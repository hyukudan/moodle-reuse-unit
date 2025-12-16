/**
 * Dashboard module for local_reuseunit.
 *
 * @module     local_reuseunit/dashboard
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Dashboard class.
 */
class Dashboard {
    /**
     * Constructor.
     *
     * @param {Object} config Configuration object
     */
    constructor(config) {
        this.userid = config.userid || 0;
        this.canviewall = config.canviewall || false;
        this.currentPeriod = 'all';
        this.viewAllUsers = false;
        this.charts = {};

        this.initEventListeners();
        this.initCharts();
    }

    /**
     * Initialize event listeners.
     */
    initEventListeners() {
        // Period buttons.
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentPeriod = e.target.dataset.period;
                this.refreshStats();
            });
        });

        // View all users toggle.
        const viewAllToggle = document.getElementById('viewAllUsersToggle');
        if (viewAllToggle) {
            viewAllToggle.addEventListener('change', (e) => {
                this.viewAllUsers = e.target.checked;
                this.refreshStats();
            });
        }
    }

    /**
     * Initialize charts using Chart.js if available.
     */
    initCharts() {
        // Get initial data from embedded JSON.
        const courseDataEl = document.getElementById('importsByCourseData');
        const activityDataEl = document.getElementById('importsByActivityData');

        const courseData = courseDataEl ? JSON.parse(courseDataEl.textContent) : [];
        const activityData = activityDataEl ? JSON.parse(activityDataEl.textContent) : [];

        this.renderCourseChart(courseData);
        this.renderActivityChart(activityData);
    }

    /**
     * Render the imports by course chart.
     *
     * @param {Array} data Chart data
     */
    renderCourseChart(data) {
        const canvas = document.getElementById('importsByCourseChart');
        const emptyMsg = document.getElementById('importsByCourseEmpty');

        if (!canvas) {
            return;
        }

        if (!data || data.length === 0) {
            canvas.style.display = 'none';
            emptyMsg.classList.remove('d-none');
            return;
        }

        canvas.style.display = 'block';
        emptyMsg.classList.add('d-none');

        // Simple bar chart using native canvas.
        this.drawBarChart(canvas, data.map(d => d.name), data.map(d => d.count), '#0d6efd');
    }

    /**
     * Render the imports by activity chart.
     *
     * @param {Array} data Chart data
     */
    renderActivityChart(data) {
        const canvas = document.getElementById('importsByActivityChart');
        const emptyMsg = document.getElementById('importsByActivityEmpty');

        if (!canvas) {
            return;
        }

        if (!data || data.length === 0) {
            canvas.style.display = 'none';
            emptyMsg.classList.remove('d-none');
            return;
        }

        canvas.style.display = 'block';
        emptyMsg.classList.add('d-none');

        // Simple horizontal bar chart.
        this.drawBarChart(canvas, data.map(d => d.name), data.map(d => d.count), '#198754');
    }

    /**
     * Draw a simple bar chart on canvas.
     *
     * @param {HTMLCanvasElement} canvas Canvas element
     * @param {Array} labels Labels for bars
     * @param {Array} values Values for bars
     * @param {string} color Bar color
     */
    drawBarChart(canvas, labels, values, color) {
        const ctx = canvas.getContext('2d');
        const width = canvas.width = canvas.offsetWidth;
        const height = canvas.height = canvas.offsetHeight || 250;

        ctx.clearRect(0, 0, width, height);

        if (labels.length === 0) {
            return;
        }

        const maxValue = Math.max(...values, 1);
        const barWidth = (width - 100) / labels.length - 10;
        const chartHeight = height - 60;
        const startX = 60;
        const startY = height - 40;

        // Draw axes.
        ctx.strokeStyle = '#ccc';
        ctx.beginPath();
        ctx.moveTo(startX, 20);
        ctx.lineTo(startX, startY);
        ctx.lineTo(width - 20, startY);
        ctx.stroke();

        // Draw bars.
        ctx.fillStyle = color;
        labels.forEach((label, i) => {
            const barHeight = (values[i] / maxValue) * chartHeight;
            const x = startX + 10 + i * (barWidth + 10);
            const y = startY - barHeight;

            ctx.fillRect(x, y, barWidth, barHeight);

            // Draw label.
            ctx.fillStyle = '#333';
            ctx.font = '11px sans-serif';
            ctx.textAlign = 'center';
            const displayLabel = label.length > 8 ? label.substring(0, 8) + '...' : label;
            ctx.fillText(displayLabel, x + barWidth / 2, startY + 15);

            // Draw value.
            ctx.fillText(values[i], x + barWidth / 2, y - 5);

            ctx.fillStyle = color;
        });

        // Draw y-axis labels.
        ctx.fillStyle = '#666';
        ctx.textAlign = 'right';
        ctx.fillText('0', startX - 5, startY);
        ctx.fillText(maxValue.toString(), startX - 5, 25);
    }

    /**
     * Refresh statistics from server.
     */
    async refreshStats() {
        const userid = this.viewAllUsers ? 0 : this.userid;

        try {
            const response = await Ajax.call([{
                methodname: 'local_reuseunit_get_statistics',
                args: {
                    userid: userid,
                    period: this.currentPeriod,
                },
            }])[0];

            this.updateDisplay(response);
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Update the display with new statistics.
     *
     * @param {Object} stats Statistics data
     */
    updateDisplay(stats) {
        // Update summary cards.
        this.updateStat('.stat-totalimports', stats.totalimports);
        this.updateStat('.stat-successfulimports', stats.successfulimports);
        this.updateStat('.stat-activitiesimported', stats.activitiesimported);
        this.updateStat('.stat-resourcesimported', stats.resourcesimported);
        this.updateStat('.stat-totaltemplates', stats.totaltemplates);
        this.updateStat('.stat-templateusage', stats.templateusage);

        // Update top templates list.
        this.updateTopTemplates(stats.toptemplates);

        // Update recent imports table.
        this.updateRecentImports(stats.recentimports);

        // Update charts.
        this.renderCourseChart(stats.importsbycourse);
        this.renderActivityChart(stats.importsbyactivity);
    }

    /**
     * Update a stat display with animation.
     *
     * @param {string} selector CSS selector
     * @param {number} value New value
     */
    updateStat(selector, value) {
        const el = document.querySelector(selector);
        if (el) {
            el.textContent = value;
            el.classList.add('stat-updated');
            setTimeout(() => el.classList.remove('stat-updated'), 300);
        }
    }

    /**
     * Update top templates list.
     *
     * @param {Array} templates Templates data
     */
    updateTopTemplates(templates) {
        const list = document.querySelector('.top-templates-list');
        if (!list) {
            return;
        }

        if (!templates || templates.length === 0) {
            list.innerHTML = '<li class="list-group-item text-muted text-center">No templates</li>';
            return;
        }

        list.innerHTML = templates.map(t => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    ${this.escapeHtml(t.name)}
                    <span class="badge badge-secondary ml-2">${t.sharelevel}</span>
                </span>
                <span class="badge badge-primary badge-pill">${t.usagecount}</span>
            </li>
        `).join('');
    }

    /**
     * Update recent imports table.
     *
     * @param {Array} imports Imports data
     */
    updateRecentImports(imports) {
        const tbody = document.querySelector('.recent-imports-table tbody');
        if (!tbody) {
            return;
        }

        if (!imports || imports.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No recent imports</td></tr>';
            return;
        }

        tbody.innerHTML = imports.map(i => `
            <tr>
                <td>${this.escapeHtml(i.sectionname)}</td>
                <td>${this.escapeHtml(i.coursename)}</td>
                <td>${i.activities}A / ${i.resources}R</td>
                <td><small class="text-muted">${this.escapeHtml(i.timeago)}</small></td>
            </tr>
        `).join('');
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
 * Initialize the dashboard.
 *
 * @param {Object} config Configuration object
 */
export const init = (config) => {
    new Dashboard(config);
};
