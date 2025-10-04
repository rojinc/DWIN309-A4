document.addEventListener('DOMContentLoaded', function () {
    highlightActiveNav();
    initEnrollmentWizard();
    renderRevenueChart();
    initScheduleCalendar();
    initInvoiceEditor();
});

function highlightActiveNav() {
    const params = new URLSearchParams(window.location.search);
    const page = params.get('page') || 'home';
    document.querySelectorAll('.app-nav .nav-link').forEach(function (link) {
        const url = new URL(link.href, window.location.origin);
        const linkPage = url.searchParams.get('page') || 'dashboard';
        if (linkPage === page) {
            link.classList.add('active-nav');
        }
    });
}

function renderRevenueChart() {
    const card = document.querySelector('.chart-card');
    if (!card) {
        return;
    }
    const canvas = card.querySelector('canvas');
    if (!canvas) {
        return;
    }
    const ctx = canvas.getContext('2d');
    let raw = [];
    if (card.dataset.revenueSeries) {
        try {
            raw = JSON.parse(card.dataset.revenueSeries);
        } catch (error) {
            console.error('Failed to parse revenue series', error);
            raw = [];
        }
    }
    if (!raw.length) {
        ctx.font = '16px Segoe UI';
        ctx.fillStyle = '#6b7280';
        ctx.fillText('No revenue records available.', 20, 40);
        return;
    }
    const padding = 40;
    const width = (canvas.width = Math.max(card.clientWidth, 320));
    const height = (canvas.height = 280);
    const max = Math.max.apply(null, raw.map(function (row) { return Number(row.total); }));
    const barWidth = (width - padding) / raw.length - 20;
    ctx.clearRect(0, 0, width, height);
    ctx.font = '12px Segoe UI';
    raw.forEach(function (row, index) {
        const value = Number(row.total);
        const barHeight = max === 0 ? 0 : (value / max) * (height - padding * 2);
        const x = padding + index * (barWidth + 20);
        const y = height - padding - barHeight;
        ctx.fillStyle = '#1f6feb';
        ctx.fillRect(x, y, barWidth, barHeight);
        ctx.fillStyle = '#1f2937';
        ctx.fillText('$' + value.toFixed(0), x, y - 8);
        ctx.fillStyle = '#6b7280';
        const label = formatPeriod(row.period);
        ctx.save();
        ctx.translate(x + barWidth / 2, height - padding + 14);
        ctx.rotate(-Math.PI / 8);
        ctx.textAlign = 'center';
        ctx.fillText(label, 0, 0);
        ctx.restore();
    });
    ctx.strokeStyle = '#d7deea';
    ctx.beginPath();
    ctx.moveTo(padding / 2, height - padding);
    ctx.lineTo(width - padding / 2, height - padding);
    ctx.stroke();
}

function formatPeriod(period) {
    const parts = period.split('-').map(function (value) { return Number(value); });
    const date = new Date(parts[0], parts[1] - 1, 1);
    return date.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
}

function initScheduleCalendar() {
    const container = document.querySelector('.schedule-dashboard');
    if (!container) {
        return;
    }

    const calendarEl = document.getElementById('schedule-calendar');
    const upcomingEl = document.getElementById('schedule-upcoming');
    const completedEl = document.getElementById('schedule-completed');
    const navButtons = container.querySelectorAll('.schedule-nav');
    const canManage = container.dataset.canManage === '1';
    let csrfToken = container.dataset.csrf || '';
    const eventsEndpoint = container.dataset.eventsEndpoint;
    const conflictEndpoint = container.dataset.conflictEndpoint;
    const createEndpoint = container.dataset.createEndpoint;

    let currentYear = Number(container.dataset.year);
    let currentMonth = Number(container.dataset.month);

    navButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            currentYear = Number(button.dataset.year);
            currentMonth = Number(button.dataset.month);
            loadCalendar(currentYear, currentMonth);
        });
    });

    loadCalendar(currentYear, currentMonth);

    var modal = null;
    var modalClose = null;
    var modalCancel = null;
    var modalOpen = null;
    var form = null;
    var conflictMessage = null;

    const updateCsrfToken = function (token) {
        if (!token) {
            return;
        }
        csrfToken = token;
        container.dataset.csrf = token;
        if (form) {
            var csrfField = form.querySelector('[name="csrf_token"]');
            if (csrfField) {
                csrfField.value = token;
            }
        }
    };

    if (canManage) {
        modal = document.getElementById('schedule-modal');
        modalClose = document.getElementById('schedule-modal-close');
        modalCancel = document.getElementById('schedule-modal-cancel');
        modalOpen = document.getElementById('schedule-open-modal');
        form = document.getElementById('schedule-form');
        conflictMessage = document.getElementById('schedule-conflict-message');

        const closeModal = function () {
            modal.setAttribute('hidden', 'hidden');
            form.querySelectorAll('input, textarea, select').forEach(function (input) {
                if (input.name !== 'csrf_token') {
                    input.value = '';
                }
            });
            if (conflictMessage) {
                conflictMessage.setAttribute('hidden', 'hidden');
                conflictMessage.textContent = '';
                conflictMessage.classList.remove('error');
            }
        };

        const openModal = function (date, start, end) {
            if (date) {
                form.querySelector('[name="scheduled_date"]').value = date;
            }
            if (start) {
                form.querySelector('[name="start_time"]').value = start;
            }
            if (end) {
                form.querySelector('[name="end_time"]').value = end;
            }
            modal.removeAttribute('hidden');
            form.querySelector('[name="student_id"]').focus();
        };

        modalOpen && modalOpen.addEventListener('click', function () {
            openModal();
        });
        modalClose && modalClose.addEventListener('click', closeModal);
        modalCancel && modalCancel.addEventListener('click', closeModal);
        modal && modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        form && form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());
            const conflictParams = new URLSearchParams({
                instructor_id: payload.instructor_id,
                scheduled_date: payload.scheduled_date,
                start_time: payload.start_time,
                end_time: payload.end_time
            });
            if (payload.vehicle_id) {
                conflictParams.append('vehicle_id', payload.vehicle_id);
            }
            try {
                const conflictResponse = await fetch(conflictEndpoint + '&' + conflictParams.toString());
                const conflictJson = await conflictResponse.json();
                if (conflictJson.conflict) {
                    showFormMessage('Schedule conflict detected. Please adjust the time.', true);
                    return;
                }
            } catch (error) {
                showFormMessage('Unable to validate conflicts. Please try again.', true);
                return;
            }

            try {
                const response = await fetch(createEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const json = await response.json();
                if (json && json.csrf_token) {
                    updateCsrfToken(json.csrf_token);
                }
                if (!response.ok) {
                    showFormMessage(json.error || 'Unable to create schedule.', true);
                    return;
                }
                showFormMessage(json.message || 'Schedule created.', false);
                loadCalendar(currentYear, currentMonth);
                setTimeout(function () {
                    closeModal();
                }, 1500);
            } catch (error) {
                showFormMessage('Unexpected error. Please try again.', true);
            }

        });

        function showFormMessage(message, isError) {
            if (!conflictMessage) {
                return;
            }
            conflictMessage.textContent = message;
            conflictMessage.classList.toggle('error', Boolean(isError));
            conflictMessage.removeAttribute('hidden');
        }

        calendarEl && calendarEl.addEventListener('click', function (event) {
            const target = event.target.closest('.calendar-cell-manage');
            if (!target) {
                return;
            }
            const date = target.getAttribute('data-date');
            openModal(date);
        });
    }

    async function loadCalendar(year, month) {
        if (!calendarEl || !upcomingEl) {
            return;
        }
        calendarEl.innerHTML = '<div class=\"calendar-loading\">Loading...</div>';
        upcomingEl.innerHTML = '';
        if (completedEl) {
            completedEl.innerHTML = '';
        }
        try {
            const params = new URLSearchParams({ year: year, month: month });
            const response = await fetch(eventsEndpoint + '&' + params.toString());
            const events = await response.json();
            container.dataset.year = String(year);
            container.dataset.month = String(month);
            renderCalendar(calendarEl, year, month, events, canManage);
            renderUpcoming(upcomingEl, events);
            if (completedEl) {
                renderCompleted(completedEl, events);
            }
        } catch (error) {
            calendarEl.innerHTML = '<p class=\"calendar-error\">Unable to load schedule. Please refresh.</p>';
        }
    }
}
function renderCalendar(calendarEl, year, month, events, canManage) {
    const firstDay = new Date(year, month - 1, 1);
    const totalDays = new Date(year, month, 0).getDate();
    const startOffset = (firstDay.getDay() + 6) % 7;
    const today = new Date();
    const groupedEvents = groupEventsByDate(events);

    let day = 1;
    const rows = [];
    for (let week = 0; week < 6 && day <= totalDays; week++) {
        const cells = [];
        for (let dow = 0; dow < 7; dow++) {
            if ((week === 0 && dow < startOffset) || day > totalDays) {
                cells.push('<div class="calendar-cell calendar-empty"></div>');
            } else {
                const dateKey = formatDateKey(year, month, day);
                const isToday = today.getFullYear() === year && today.getMonth() + 1 === month && today.getDate() === day;
                const dayEvents = groupedEvents.get(dateKey) || [];
                const eventItems = dayEvents.map(function (event) {
                    return '<li>' + escapeHtml(formatTimeRange(event.start, event.end)) + ' - ' + escapeHtml(event.student) + '</li>';
                }).join('');
                const manageClass = canManage ? ' calendar-cell-manage' : '';
                const manageData = canManage ? ' data-date="' + escapeAttr(dateKey) + '"' : '';
                const todayClass = isToday ? ' today' : '';
                cells.push('<div class="calendar-cell' + todayClass + manageClass + '"' + manageData + '>' +
                    '<div class="calendar-date">' + day + '</div>' +
                    '<ul class="calendar-events">' + eventItems + '</ul>' +
                '</div>');
                day++;
            }
        }
        rows.push('<div class="calendar-row">' + cells.join('') + '</div>');
    }
    const headerHtml = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        .map(function (label) { return '<div class="calendar-head">' + label + '</div>'; })
        .join('');
    calendarEl.innerHTML = '<div class="calendar-header">' + headerHtml + '</div>' + rows.join('');
}

function renderUpcoming(container, events) {
    const upcoming = events
        .map(function (event) {
            return Object.assign({}, event, { startDate: new Date(event.start) });
        })
        .filter(function (event) {
            return event.status !== 'completed' && event.status !== 'cancelled' && event.startDate >= new Date();
        })
        .sort(function (a, b) { return a.startDate - b.startDate; })
        .slice(0, 8);

    if (!upcoming.length) {
        container.innerHTML = '<li class=\"schedule-empty\">No upcoming lessons in this period.</li>';
        return;
    }

    container.innerHTML = upcoming.map(function (event) {
        const dateText = event.startDate.toLocaleDateString(undefined, { day: 'numeric', month: 'short', weekday: 'short' });
        const timeText = event.startDate.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        const details = [];
        if (event.course) {
            details.push(event.course);
        }
        if (event.lesson_topic) {
            details.push(event.lesson_topic);
        }
        const detailText = details.length ? ' - ' + details.join(' | ') : '';
        const statusLabel = formatStatusLabel(event.status);
        const statusBadge = statusLabel ? '<span class=\"status-pill status-' + escapeAttr(event.status) + '\">' + escapeHtml(statusLabel) + '</span>' : '';
        return '<li class=\"schedule-upcoming-item\"><div class=\"schedule-upcoming-text\"><strong>' + escapeHtml(event.student) + '</strong><span>' + escapeHtml(dateText + ' ' + timeText + detailText) + '</span>' + statusBadge + '</div></li>';
    }).join('');
}

function renderCompleted(container, events) {
    const completed = events
        .map(function (event) {
            return Object.assign({}, event, { startDate: new Date(event.start) });
        })
        .filter(function (event) { return event.status === 'completed'; })
        .sort(function (a, b) { return b.startDate - a.startDate; })
        .slice(0, 8);

    if (!completed.length) {
        container.innerHTML = '<li class=\"schedule-empty\">No completed lessons yet.</li>';
        return;
    }

    container.innerHTML = completed.map(function (event) {
        const dateText = event.startDate.toLocaleDateString(undefined, { day: 'numeric', month: 'short', weekday: 'short' });
        const timeText = event.startDate.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        const details = [];
        if (event.course) {
            details.push(event.course);
        }
        if (event.lesson_topic) {
            details.push(event.lesson_topic);
        }
        const detailText = details.length ? ' - ' + details.join(' | ') : '';
        const statusLabel = formatStatusLabel(event.status);
        const statusBadge = statusLabel ? '<span class=\"status-pill status-' + escapeAttr(event.status) + '\">' + escapeHtml(statusLabel) + '</span>' : '';
        return '<li class=\"schedule-completed-item\"><div class=\"schedule-upcoming-text\"><strong>' + escapeHtml(event.student) + '</strong><span>' + escapeHtml(dateText + ' ' + timeText + detailText) + '</span>' + statusBadge + '</div></li>';
    }).join('');
}

function formatStatusLabel(value) {
    if (!value) {
        return '';
    }
    return value.replace(/_/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
}

function groupEventsByDate(events) {
    const map = new Map();
    events.forEach(function (event) {
        const date = event.start.split('T')[0];
        if (!map.has(date)) {
            map.set(date, []);
        }
        map.get(date).push(event);
    });
    return map;
}

function formatDateKey(year, month, day) {
    return [year, String(month).padStart(2, '0'), String(day).padStart(2, '0')].join('-');
}

function formatTimeRange(start, end) {
    return start.slice(11, 16) + '-' + end.slice(11, 16);
}

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char];
    });
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
}function initInvoiceEditor() {
    const form = document.getElementById('invoice-edit-form');
    if (!form) {
        return;
    }
    const linesBody = document.getElementById('invoice-lines-body');
    const template = document.getElementById('invoice-line-template');
    const addButton = document.getElementById('invoice-add-line');
    const subtotalEl = document.getElementById('invoice-subtotal');
    const taxEl = document.getElementById('invoice-tax');
    const totalEl = document.getElementById('invoice-total');
    const taxRateInput = form.querySelector('[name="tax_rate"]');

    const bindRow = row => {
        const qty = row.querySelector('.invoice-line-qty');
        const price = row.querySelector('.invoice-line-price');
        const removeBtn = row.querySelector('.invoice-line-remove');
        [qty, price].forEach(input => {
            input && input.addEventListener('input', recalcTotals);
        });
        removeBtn && removeBtn.addEventListener('click', () => {
            const rows = linesBody.querySelectorAll('.invoice-line');
            if (rows.length <= 1) {
                row.querySelectorAll('input').forEach(input => {
                    if (input.name === 'description[]') {
                        input.value = '';
                    } else {
                        input.value = '0';
                    }
                });
                recalcTotals();
                return;
            }
            row.remove();
            recalcTotals();
        });
    };

    if (addButton) {
        addButton.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            const newRow = fragment.querySelector('.invoice-line');
            linesBody.appendChild(newRow);
            bindRow(newRow);
            const descriptionField = newRow.querySelector('input[name="description[]"]');
            if (descriptionField) {
                descriptionField.focus();
            }
            recalcTotals();
        });
    }

    linesBody.querySelectorAll('.invoice-line').forEach(bindRow);
    if (taxRateInput) {
        taxRateInput.addEventListener('input', recalcTotals);
    }
    recalcTotals();

    function recalcTotals() {
        let subtotal = 0;
        linesBody.querySelectorAll('.invoice-line').forEach(row => {
            const qtyField = row.querySelector('.invoice-line-qty');
            const qty = parseFloat((qtyField && qtyField.value) || '0');
            const priceField = row.querySelector('.invoice-line-price');
            const price = parseFloat((priceField && priceField.value) || '0');
            const lineTotal = qty * price;
            subtotal += lineTotal;
            const totalCell = row.querySelector('.invoice-line-total');
            if (totalCell) {
                totalCell.textContent = '$' + lineTotal.toFixed(2);
            }
        });
        const taxRateRaw = taxRateInput ? taxRateInput.value : '0';
        const taxRate = parseFloat(taxRateRaw || '0');
        const taxAmount = subtotal * (taxRate / 100);
        const grandTotal = subtotal + taxAmount;
        if (subtotalEl) subtotalEl.textContent = '$' + subtotal.toFixed(2);
        if (taxEl) taxEl.textContent = '$' + taxAmount.toFixed(2);
        if (totalEl) totalEl.textContent = '$' + grandTotal.toFixed(2);
    }
}



function initEnrollmentWizard() {
    const form = document.getElementById('enrollment-wizard');
    if (!form) {
        return;
    }

    const panels = Array.from(form.querySelectorAll('.wizard-panel'));
    const progressSteps = Array.from(form.querySelectorAll('.wizard-progress-step'));
    const prevButton = form.querySelector('.wizard-prev');
    const nextButton = form.querySelector('.wizard-next');
    const submitButton = form.querySelector('.wizard-submit');
    const dateInput = form.querySelector('#preferred-date');
    const calendarGrid = form.querySelector('.calendar-grid');
    const courseSelect = form.querySelector('#enrollment-course');
    const summary = form.querySelector('#course-summary');

    let currentIndex = 0;
    let courses = [];

    if (form.dataset.courses) {
        try {
            courses = JSON.parse(form.dataset.courses);
        } catch (error) {
            courses = [];
            console.error('Failed to parse course payload', error);
        }
    }

    const minDateParts = (form.dataset.minDate || '').split('-');
    const baseDate = minDateParts.length === 3
        ? new Date(Number(minDateParts[0]), Number(minDateParts[1]) - 1, Number(minDateParts[2]))
        : new Date();
    baseDate.setHours(0, 0, 0, 0);

    const showStep = index => {
        currentIndex = index;
        panels.forEach((panel, panelIndex) => {
            const isActive = panelIndex === index;
            panel.classList.toggle('is-active', isActive);
            if (isActive) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
            }
        });
        progressSteps.forEach((stepButton, stepIndex) => {
            stepButton.classList.toggle('is-active', stepIndex === index);
        });
        if (prevButton) {
            prevButton.disabled = index === 0;
        }
        if (nextButton) {
            nextButton.hidden = index === panels.length - 1;
        }
        if (submitButton) {
            submitButton.hidden = index !== panels.length - 1;
        }
    };

    const validateStep = index => {
        const activePanel = panels[index];
        if (!activePanel) {
            return true;
        }
        const controls = activePanel.querySelectorAll('input, select, textarea');
        for (const control of controls) {
            if (control.disabled || control.type === 'hidden') {
                continue;
            }
            if (!control.reportValidity()) {
                return false;
            }
        }
        return true;
    };

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            if (!validateStep(currentIndex)) {
                return;
            }
            if (currentIndex < panels.length - 1) {
                showStep(currentIndex + 1);
            }
        });
    }

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            if (currentIndex > 0) {
                showStep(currentIndex - 1);
            }
        });
    }

    progressSteps.forEach((stepButton, index) => {
        stepButton.addEventListener('click', () => {
            if (index === currentIndex) {
                return;
            }
            if (index > currentIndex && !validateStep(currentIndex)) {
                return;
            }
            showStep(index);
        });
    });

    form.addEventListener('submit', event => {
        if (form.reportValidity()) {
            return;
        }
        event.preventDefault();
        for (let index = 0; index < panels.length; index += 1) {
            const panel = panels[index];
            const controls = panel.querySelectorAll('input, select, textarea');
            const invalid = Array.from(controls).some(control => !control.checkValidity());
            if (invalid) {
                showStep(index);
                break;
            }
        }
    });

    const renderSummary = courseId => {
        if (!summary) {
            return;
        }
        summary.innerHTML = '<h3>Course snapshot</h3><p class="muted">Pick a course to see the lesson count and price breakdown.</p>';
        const course = courses.find(item => item.id === courseId);
        if (!course) {
            return;
        }
        const description = course.description || 'No description provided.';
        const lessonCount = Number(course.lesson_count || 0);
        const price = Number(course.price || 0);
        summary.innerHTML = '' +
            '<h3>' + escapeHtml(course.title || 'Course snapshot') + '</h3>' +
            '<p>' + escapeHtml(description) + '</p>' +
            '<ul class="course-stats">' +
                '<li><strong>Lessons:</strong> ' + escapeHtml(String(lessonCount)) + '</li>' +
                '<li><strong>Price:</strong> $' + escapeHtml(price.toFixed(2)) + '</li>' +
            '</ul>';
    };

    if (courseSelect) {
        courseSelect.addEventListener('change', event => {
            const courseId = Number(event.target.value || 0);
            renderSummary(courseId);
        });
        renderSummary(Number(courseSelect.value || 0));
    }

    if (calendarGrid && dateInput) {
        const updateSelection = value => {
            calendarGrid.querySelectorAll('.calendar-chip').forEach(chip => {
                chip.classList.toggle('is-selected', chip.dataset.date === value);
            });
        };

        const renderCalendar = () => {
            calendarGrid.innerHTML = '';
            for (let offset = 0; offset < 6; offset += 1) {
                const date = new Date(baseDate.getTime());
                date.setDate(baseDate.getDate() + offset);
                const iso = date.toISOString().slice(0, 10);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'calendar-chip';
                button.dataset.date = iso;
                button.textContent = date.toLocaleDateString(undefined, {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short'
                });
                if (dateInput.value === iso) {
                    button.classList.add('is-selected');
                }
                button.addEventListener('click', () => {
                    dateInput.value = iso;
                    dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                    updateSelection(iso);
                });
                calendarGrid.appendChild(button);
            }
        };

        dateInput.addEventListener('input', () => {
            updateSelection(dateInput.value);
        });
        dateInput.addEventListener('change', () => {
            updateSelection(dateInput.value);
        });

        renderCalendar();
        updateSelection(dateInput.value);
    }

    showStep(0);
}





