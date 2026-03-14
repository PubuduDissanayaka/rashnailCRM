/**
 * Appointment Calendar - Enhanced with theme calendar features
 */
import { Modal } from 'bootstrap';
import { Calendar } from '@fullcalendar/core';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import moment from 'moment';
import Choices from 'choices.js';

class AppointmentCalendar {

    constructor() {
        this.body = document.body;
        this.modal = new Modal(document.getElementById('event-modal'), { backdrop: 'static' });
        this.calendar = document.getElementById('calendar');
        this.formEvent = document.getElementById('event-form');
        this.btnNewEvent = document.querySelectorAll('.btn-new-event');
        this.btnDeleteEvent = document.getElementById('btn-delete-event');
        this.btnSaveEvent = document.getElementById('btn-save-event');
        this.modalTitle = document.getElementById('modal-title');
        this.calendarObj = null;
        this.selectedEvent = null;
        this.newEventData = null;
        this.staffFilter = document.getElementById('staffFilter');
        this.statusFilter = document.getElementById('statusFilter');

        // Store Choices instances
        this.choices = {
            customer: null,
            service: null,
            staff: null,
            status: null,
            staffFilter: null,
            statusFilter: null
        };
    }

    onEventClick(info) {
        // Reset form
        this.formEvent?.reset();
        this.formEvent.classList.remove('was-validated');
        this.newEventData = null;
        this.btnDeleteEvent.style.display = "block";
        this.modalTitle.textContent = 'Edit Appointment';
        this.modal.show();
        this.selectedEvent = info.event;

        // Fill form with appointment data
        document.getElementById('event-title').value = this.selectedEvent.title;

        const serviceId = this.selectedEvent.extendedProps.service_id;
        const customerId = this.selectedEvent.extendedProps.customer_id;
        const staffId = this.selectedEvent.extendedProps.user_id;
        const status = this.selectedEvent.extendedProps.status || 'scheduled';
        const notes = this.selectedEvent.extendedProps.notes || '';

        console.log('Populating Modal:', { serviceId, customerId, staffId, status, notes });

        // Set values using Choices instances if available
        this.setChoicesValue('event-service', serviceId);
        this.setChoicesValue('event-customer', customerId);
        this.setChoicesValue('event-staff', staffId);
        this.setChoicesValue('event-status', status);

        document.getElementById('event-notes').value = notes;

        // Set appointment ID for updates
        document.getElementById('appointment-id').value = this.selectedEvent.extendedProps.slug;

        // Set date and time in the input field
        // moment is now imported correctly
        const startDateTime = moment(this.selectedEvent.start).format('YYYY-MM-DDTHH:mm');
        document.getElementById('event-date-time').value = startDateTime;

        // Update button text
        document.getElementById('btn-save-event').textContent = 'Update';
    }

    // Helper method to set value in Choices.js dropdown
    setChoicesValue(elementId, value) {
        if (!value) return;

        // Map element IDs to choices keys
        const map = {
            'event-customer': 'customer',
            'event-service': 'service',
            'event-staff': 'staff',
            'event-status': 'status',
            'staffFilter': 'staffFilter',
            'statusFilter': 'statusFilter'
        };

        const key = map[elementId];
        if (key && this.choices[key]) {
            this.choices[key].setChoiceByValue(value.toString());
        } else {
            const element = document.getElementById(elementId);
            if (element) element.value = value;
        }
    }

    // Helper method to get Choices.js instance
    getChoicesInstance(elementId) {
        // Not used anymore with internal referencing, keeping for compatibility if needed
        return null;
    }

    // Helper method to get value from Choices.js dropdown
    getChoicesValue(elementId) {
        const map = {
            'event-customer': 'customer',
            'event-service': 'service',
            'event-staff': 'staff',
            'event-status': 'status',
            'staffFilter': 'staffFilter',
            'statusFilter': 'statusFilter'
        };

        const key = map[elementId];
        if (key && this.choices[key]) {
            return this.choices[key].getValue(true);
        }

        const element = document.getElementById(elementId);
        return element ? element.value : null;
    }

    // Helper method to check if a date/time is within business hours and duration fits
    isWithinBusinessHours(date, serviceId = null) {
        const businessHours = window.businessHours || {
            'monday': { open: '09:00', close: '18:00', closed: false },
            'tuesday': { open: '09:00', close: '18:00', closed: false },
            'wednesday': { open: '09:00', close: '18:00', closed: false },
            'thursday': { open: '09:00', close: '18:00', closed: false },
            'friday': { open: '09:00', close: '18:00', closed: false },
            'saturday': { open: '10:00', close: '16:00', closed: false },
            'sunday': { open: null, close: null, closed: true }
        };

        const dayOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][date.getDay()];
        const timeOfDay = `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;

        // Check if the business is closed that day
        if (businessHours[dayOfWeek]?.closed) {
            return false;
        }

        // Get open and close times for the day
        const openTime = businessHours[dayOfWeek]?.open;
        const closeTime = businessHours[dayOfWeek]?.close;

        // If no open/close times are set, return false
        if (!openTime || !closeTime) {
            return false;
        }

        // Check if the appointment time is at least within the opening hours
        if (timeOfDay < openTime) {
            return false;
        }

        // Get service duration - we'll need to find this from the services list
        let duration = 60; // Default duration in minutes
        if (serviceId) {
            // Find the service in the dropdown choices to get its duration
            const serviceElement = document.getElementById('event-service');
            if (serviceElement && serviceElement.choices) {
                // If using Choices.js, accessing options directly might not work as expected if elements are hidden
                // But generally the original select options are still there
                const serviceOption = Array.from(serviceElement.options).find(option => option.value == serviceId);
                if (serviceOption) {
                    // Assuming the option text contains the duration (like "Service Name (60 min)")
                    const match = serviceOption.text.match(/\((\d+)\s*min/);
                    if (match) {
                        duration = parseInt(match[1]);
                    }
                }
            } else {
                const serviceOption = Array.from(document.getElementById('event-service').options).find(option => option.value == serviceId);
                if (serviceOption) {
                    const match = serviceOption.text.match(/\((\d+)\s*min/);
                    if (match) {
                        duration = parseInt(match[1]);
                    }
                }
            }
        }

        // Calculate end time of appointment
        const appointmentEndTime = new Date(date.getTime() + duration * 60000); // Add duration in milliseconds
        const endTimeOfDay = `${appointmentEndTime.getHours().toString().padStart(2, '0')}:${appointmentEndTime.getMinutes().toString().padStart(2, '0')}`;

        // Check if the appointment ends before or exactly at closing time
        return endTimeOfDay <= closeTime;
    }

    onSelect(info) {
        // Get selected service ID for the check
        const serviceElement = document.getElementById('event-service');
        const serviceId = info.service_id || (serviceElement ? serviceElement.value : null);

        // Check if the clicked time is within business hours considering service duration
        if (!this.isWithinBusinessHours(info.date, serviceId)) {
            Swal.fire({
                icon: 'warning',
                title: 'Outside Business Hours',
                text: 'Appointments can only be scheduled during business hours, and the service must finish before closing time.'
            });
            return;
        }

        this.formEvent?.reset();
        this.formEvent?.classList.remove('was-validated');
        this.selectedEvent = null;
        this.newEventData = info;
        this.btnDeleteEvent.style.display = "none";
        this.modalTitle.textContent = 'Create New Appointment';
        document.getElementById('appointment-id').value = '';

        if (info.service_id) {
            this.setChoicesValue('event-service', info.service_id);
        } else {
            this.setChoicesValue('event-service', '');
        }

        this.modal.show();
        this.calendarObj.unselect();

        // Pre-fill date
        const selectedDate = moment(info.date).format('YYYY-MM-DDTHH:mm');
        document.getElementById('event-date-time').value = selectedDate;

        // Update button text
        document.getElementById('btn-save-event').textContent = 'Create';
    }

    async onDrop(info) {
        const startDate = moment(info.event.start).toISOString();
        const appointmentId = info.event.extendedProps.slug;

        try {
            const response = await fetch(`/appointments/${appointmentId}/update-datetime`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    appointment_date: startDate
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: 'Appointment rescheduled successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    info.revert();
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: data.message || 'Failed to update appointment time.'
                    });
                }
            } else {
                info.revert();
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'Server error occurred.'
                });
            }
        } catch (error) {
            info.revert();
            console.error('Error updating appointment:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred.'
            });
        }
    }

    init() {
        const today = new Date();
        const self = this;

        // Initialize Draggable for external events
        const externalEventContainerEl = document.getElementById('external-events');
        if (externalEventContainerEl) {
            new Draggable(externalEventContainerEl, {
                itemSelector: '.external-event',
                eventData: function (eventEl) {
                    return {
                        title: eventEl.innerText.trim(),
                        classNames: eventEl.getAttribute('data-class'),
                        service_id: eventEl.getAttribute('data-service-id')
                    };
                }
            });
        }

        // Initialize Choices.js
        const customerSelect = document.getElementById('event-customer');
        const serviceSelect = document.getElementById('event-service');
        const staffSelect = document.getElementById('event-staff');
        const statusSelect = document.getElementById('event-status');
        const staffFilterSelect = document.getElementById('staffFilter');
        const statusFilterSelect = document.getElementById('statusFilter');

        if (customerSelect) {
            this.choices.customer = new Choices(customerSelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search for a customer...',
                shouldSort: true,
                itemSelectText: 'Press to select',
                placeholder: true,
                noResultsText: 'No customers found',
                noChoicesText: 'No customers to choose from'
            });
        }

        if (serviceSelect) {
            this.choices.service = new Choices(serviceSelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search for a service...',
                shouldSort: true,
                itemSelectText: 'Press to select',
                placeholder: true,
                noResultsText: 'No services found',
                noChoicesText: 'No services to choose from'
            });
        }

        if (staffSelect) {
            this.choices.staff = new Choices(staffSelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search for staff...',
                shouldSort: true,
                itemSelectText: 'Press to select',
                placeholder: true,
                noResultsText: 'No staff found',
                noChoicesText: 'No staff to choose from'
            });
        }

        if (statusSelect) {
            this.choices.status = new Choices(statusSelect, {
                searchEnabled: false,
                shouldSort: false,
                itemSelectText: 'Press to select',
                placeholder: true,
                noResultsText: 'No status found',
                noChoicesText: 'No status to choose from'
            });
        }

        if (staffFilterSelect) {
            this.choices.staffFilter = new Choices(staffFilterSelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search for staff...',
                shouldSort: false,
                itemSelectText: 'Press to select',
                noResultsText: 'No staff found',
                noChoicesText: 'No staff to choose from'
            });
        }

        if (statusFilterSelect) {
            this.choices.statusFilter = new Choices(statusFilterSelect, {
                searchEnabled: false,
                shouldSort: false,
                itemSelectText: 'Press to select',
                noResultsText: 'No status found',
                noChoicesText: 'No status to choose from'
            });
        }

        // Determine business hours for calendar configuration
        const businessHours = window.businessHours || {
            'monday': { open: '09:00', close: '18:00', closed: false },
            'tuesday': { open: '09:00', close: '18:00', closed: false },
            'wednesday': { open: '09:00', close: '18:00', closed: false },
            'thursday': { open: '09:00', close: '18:00', closed: false },
            'friday': { open: '09:00', close: '18:00', closed: false },
            'saturday': { open: '10:00', close: '16:00', closed: false },
            'sunday': { open: null, close: null, closed: true }
        };

        // Format business hours for FullCalendar
        const fullCalendarBusinessHours = [];
        for (const [day, hours] of Object.entries(businessHours)) {
            if (!hours.closed && hours.open && hours.close) {
                const dayIndex = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
                    .indexOf(day);
                if (dayIndex !== -1) {
                    fullCalendarBusinessHours.push({
                        daysOfWeek: [dayIndex],
                        startTime: hours.open,
                        endTime: hours.close
                    });
                }
            }
        }

        // Set default slot times based on business hours
        let minTime = '07:00:00';
        let maxTime = '19:00:00';

        // Calculate min and max times based on business hours
        const openTimes = Object.values(businessHours)
            .filter(day => !day.closed && day.open)
            .map(day => day.open);

        const closeTimes = Object.values(businessHours)
            .filter(day => !day.closed && day.close)
            .map(day => day.close);

        if (openTimes.length > 0) {
            const minMinutes = Math.min(...openTimes.map(t => {
                const [h, m] = t.split(':');
                return parseInt(h) * 60 + parseInt(m);
            }));
            const minHours = Math.floor(minMinutes / 60);
            const minMins = minMinutes % 60;
            minTime = `${minHours.toString().padStart(2, '0')}:${minMins.toString().padStart(2, '0')}:00`;
        }

        if (closeTimes.length > 0) {
            const maxMinutes = Math.max(...closeTimes.map(t => {
                const [h, m] = t.split(':');
                return parseInt(h) * 60 + parseInt(m);
            }));
            const maxHours = Math.floor(maxMinutes / 60);
            const maxMins = maxMinutes % 60;
            maxTime = `${maxHours.toString().padStart(2, '0')}:${maxMins.toString().padStart(2, '0')}:00`;
        }

        // Initialize calendar
        self.calendarObj = new Calendar(self.calendar, {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
            slotDuration: '00:30:00',
            slotMinTime: minTime,
            slotMaxTime: maxTime,
            businessHours: fullCalendarBusinessHours,
            themeSystem: 'bootstrap',
            bootstrapFontAwesome: false,
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                day: 'Day',
                list: 'List',
                prev: 'Prev',
                next: 'Next'
            },
            initialView: 'dayGridMonth',
            handleWindowResize: true,
            height: window.innerHeight - 240,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: {
                url: '/api/appointments/calendar-events',
                method: 'GET',
                extraParams: function () {
                    return {
                        staff_id: self.staffFilter?.value || '',
                        status: self.statusFilter?.value || ''
                    };
                },
                failure: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch calendar events.'
                    });
                }
            },
            editable: true,
            droppable: true, // Allow dropping of events
            selectable: true,
            dateClick: function (info) {
                self.onSelect(info);
            },
            eventClick: function (info) {
                self.onEventClick(info);
            },
            eventDrop: async function (info) {
                await self.onDrop(info);
            },
            eventResize: function (info) {
                // Handle event resizing if needed
            },
            loading: function (isLoading) {
                // Handle loading state if needed
            },
            eventReceive: function (info) {
                // Handle external event drop by opening modal
                self.onSelect({
                    date: info.event.start,
                    allDay: info.event.allDay,
                    title: info.event.title,
                    service_id: info.event.extendedProps.service_id
                });
                // Remove the temp event
                info.event.remove();
            }
        });

        self.calendarObj.render();

        // On new event button click
        self.btnNewEvent.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                self.onSelect({
                    date: new Date(),
                    allDay: true
                });
            });
        });

        // Add event listener for service selection changes to validate time fit
        // Use Choices event if available, otherwise fallback
        if (self.choices.service) {
            self.choices.service.passedElement.element.addEventListener('change', function (event) {
                const dateTimeValue = document.getElementById('event-date-time').value;
                if (dateTimeValue) {
                    const date = new Date(dateTimeValue);
                    if (!self.isWithinBusinessHours(date, event.detail.value)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Time/Duration Issue',
                            text: 'Selected service duration does not fit before closing time. Please select an earlier time slot.'
                        });
                    }
                }
            });
        }

        // Save event (appointment)
        self.formEvent?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = self.formEvent;

            // Check if appointment time is within business hours before saving
            const appointmentDateTime = document.getElementById('event-date-time').value;
            const serviceId = self.getChoicesValue('event-service');
            if (appointmentDateTime) {
                const date = new Date(appointmentDateTime);
                if (!self.isWithinBusinessHours(date, serviceId)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Time',
                        text: 'Appointment time must be within business hours, and the service must finish before closing time.'
                    });
                    return;
                }
            }

            // Validation
            if (form.checkValidity()) {
                const formData = {
                    title: document.getElementById('event-title').value,
                    service_id: serviceId,
                    customer_id: self.getChoicesValue('event-customer'),
                    user_id: self.getChoicesValue('event-staff'),
                    status: self.getChoicesValue('event-status'),
                    notes: document.getElementById('event-notes').value,
                    appointment_date: document.getElementById('event-date-time').value
                };

                // Add appointment ID if editing
                const appointmentId = document.getElementById('appointment-id').value;

                try {
                    let url, method;
                    if (appointmentId) {
                        // Update existing appointment via AJAX
                        url = `/appointments/${appointmentId}/ajax`;
                        method = 'PUT';
                    } else {
                        // Create new appointment via AJAX
                        url = '/appointments/ajax';
                        method = 'POST';
                    }

                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(formData)
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            self.modal.hide();

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Always refetch to be safe and ensure data consistency
                            self.calendarObj.refetchEvents();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to save appointment.'
                            });
                        }
                    } else {
                        const errorData = await response.json();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorData.message || 'Server error occurred.'
                        });
                    }
                } catch (error) {
                    console.error('Error saving appointment:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred.'
                    });
                }
            } else {
                e.stopPropagation();
                form.classList.add('was-validated');
            }
        });

        // Delete event (appointment)
        self.btnDeleteEvent.addEventListener('click', async function (e) {
            if (self.selectedEvent) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        const appointmentId = self.selectedEvent.extendedProps.slug;

                        try {
                            const response = await fetch(`/appointments/${appointmentId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });

                            const data = await response.json();

                            if (data.success) {
                                self.selectedEvent.remove();
                                self.selectedEvent = null;
                                self.modal.hide();
                                Swal.fire(
                                    'Deleted!',
                                    data.message || 'Appointment has been deleted.',
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    data.message || 'Failed to delete appointment.',
                                    'error'
                                );
                            }
                        } catch (error) {
                            console.error('Error details:', error);
                            Swal.fire(
                                'Error!',
                                'An unexpected error occurred: ' + (error.message || 'Unknown error'),
                                'error'
                            );
                        }
                    }
                });
            }
        });

        // Update calendar when staff filter changes
        if (self.staffFilter) {
            // For Choices.js enhanced select
            self.staffFilter.addEventListener('change', function () {
                self.calendarObj.refetchEvents();
            });
        }

        // Update calendar when status filter changes
        if (this.statusFilter) {
            this.statusFilter.addEventListener('change', function () {
                self.calendarObj.refetchEvents();
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', function (e) {
    new AppointmentCalendar().init();
});