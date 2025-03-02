import { getUserName, appendConfirmation, appendAlert } from './cb-core-modules.js';

jQuery(document).ready(function($) { 
	// Fetch events from the database
	// Replace this with your own code to fetch events from your database
	// For simplicity, I'm using sample data here


	let events = [];
	let birthdayEvents = [];
	let anniversaryEvents = [];
	let activeEvent = {};

	const confirmPlacement = async (contestID) => {

		await $.ajax({
			url: cb_events.update_contests,
			method: 'PATCH',
			data: JSON.stringify({
				id: contestID,
				recipient_id: cb_events.user_id,
				api_key: cb_events.api_key
			}),
			success: e => e,
			error: err => console.error(err)
		});

		await $.ajax({
			url: cb_events.new_transactions,
			method: 'POST',
			data: {
				contest_id: contestID,
				recipient_id: cb_events.user_id,
				api_key: cb_events.api_key
			},
			success: ({text, type}) => appendAlert(text, type),
			error: err => console.error(err)
		});

	}

	const withSuffix = (int) => {

		if (int >= 11 && int <= 13) {
			return `${int}th`;
		}

		if ( int % 10 === 1)  {
			return `${int}st`
		};
		if ( int % 10 === 2 )  {
			return `${int}nd`;
		}
		if ( int % 10 === 3 )  {
			return `${int}rd`;
		}

		return `${int}th`;

	}

	const fetchEvents = async (month, year) => {

		const retval = await $.ajax({
			url: cb_events.get_events,
			method: 'GET',
			data: {
				date_query: {
					relation: 'OR',
					0: {
						column: 'event_start',
						month: month,
						year: year
					},
					1: {
						column: 'event_end',
						month: month + 1,
						year: year
					}
				},
				api_key: cb_events.api_key
			}
		});

		return retval.text;

	}


	const fetchBdaEvents = async ( month, fieldId ) => {

		let retval = [];

		await $.ajax({
			url: cb_events.get_bda,
			method: 'GET',
			data: {
				month: month,
				field_id: fieldId
			},
			success: ({text, type}) => retval = text,
			error: err => console.error(err)
		});

		return retval;

	}


	// Initialize the calendar
	let currentDate = new Date();
	let currentMonth = currentDate.getMonth();
	let currentYear = currentDate.getFullYear();

	async function updateCalendar() {
		// Get the element references
		let calendarDays = $('.cb-events-calendar .cb-events-calendar-days');
		let monthYearLabel = $('.cb-events-calendar-month-year');
		events = await fetchEvents(currentMonth + 1, currentYear);
		birthdayEvents = await fetchBdaEvents( currentMonth + 1, 51 );
		anniversaryEvents = await fetchBdaEvents( currentMonth + 1, 52 );

		// Clear the calendar days
		calendarDays.empty();

		// Get the number of days in the current month
		let numDays = daysInMonth(currentMonth, currentYear);

		// Get the first day of the month
		let firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();

		// Add empty cells for previous month's days
		for (let i = 0; i < firstDayOfMonth; i++) {
			calendarDays.append('<div class="cb-events-calendar-day-container border-top bg-secondary-subtle py-2" style="width: calc(100% / 7); text-align: center;"></div>');
		}

		// Iterate over the days of the current month
		for (let day = 1; day <= numDays; day++) {
			// Create a new date object for the current day
			let activeDate = new Date(currentYear, currentMonth, day);

			// Create a cell element for the day
			let dayCell = $(`<div class="cb-events-calendar-day-container border-top py-2" style="width: calc(100% / 7); text-align: center;"></div>`);

			// Create a cell for the day number
			let dayNumber = $('<div class="cb-events-calendar-day-number">' + day + '</div>');
			let eventsContainer = $('<div class="cb-events-calendar-events-container btn-group-vertical mt-2"></div>');
			let bdaEventsContainer = $('<ul class="cb-events-calendar-bda-events-container list-group mt-2"></ul>');

			// Add the day number cell to the day cell
			dayCell.append([dayNumber, eventsContainer, bdaEventsContainer]);

			if ( birthdayEvents ) {

				let filteredBdaEvents = Object.values(birthdayEvents).filter((birthdayEvent) => {
					let bdaEventDate = new Date(birthdayEvent.value.replace(' ', 'T'));
					return bdaEventDate.getDate() === day;
				});

				filteredBdaEvents.forEach(async (bdaEvent) => {

					let userName = await getUserName(bdaEvent.user_id);
					let eventItem = $(`<li class="list-group-item list-group-item-primary fw-light">${userName}'s Birthday</li>`);

					bdaEventsContainer.append(eventItem);

				});

			}

			if ( anniversaryEvents ) {

				let filteredBdaEvents = Object.values(anniversaryEvents).filter((anniversaryEvent) => {
					let bdaEventDate = new Date(anniversaryEvent.value.replace(' ', 'T'));
					return bdaEventDate.getDate() === day;
				});

				filteredBdaEvents.forEach(async (bdaEvent) => {
					let userName = await getUserName(bdaEvent.user_id);
					let eventItem = $(`<li class="list-group-item list-group-item-secondary fw-light">${userName}'s Anniversary</li>`);

					bdaEventsContainer.append(eventItem);

				});

			}


			if ( events ) {
				console.log(events);
				let filteredEvents = events.filter((event) => {

					let eventStartDate = new Date(event.event_start.replace(' ', 'T') + 'Z');
					let eventEndDate = new Date(event.event_end.replace(' ', 'T') + 'Z');
					eventStartDate.setHours(0,0,0,0);
					eventEndDate.setHours(0,0,0,0);

					return eventStartDate.getTime() <= activeDate.getTime() && eventEndDate.getTime() >= activeDate.getTime();

				});

				filteredEvents.forEach(async (event) => {

					let claimed = false;

					await $.ajax({
						url: cb_events.get_transactions,
						method: 'GET',
						data: {
							event_id: event.id,
							recipient_id: cb_events.user_id,
							api_key: cb_events.api_key
						},
						success: ({text,type}) => claimed = (text.length > 0),
						error: err => console.error(err)
					});

					let eventItem = $(`<button class="btn btn-${ claimed ? "success" : "outline-primary"} cb-events-calendar-event-button fs-6 fw-light" type="button" data-cb-event-id="${event.id}" data-bs-toggle="offcanvas" data-bs-target="#cbEventsOffcanvas" aria-controls="cbEventsOffcanvas">${event.event_title.slice(0,12) + '...'}</button>`);

					eventsContainer.append(eventItem);

				});
			}

			// Add the day cell to the calendar days container
			calendarDays.append(dayCell);
		}

		// Update the month and year label
		monthYearLabel.text(
			new Date(currentYear, currentMonth).toLocaleDateString('en-US', {
				month: 'long',
				year: 'numeric'
			})
		);
	}

	function prettyDate( date ) {

		const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
		const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
		let hour = date.getHours();
		const meridiem = hour >= 12 ? 'PM' : 'AM';
		const withSuffix = (day) => {

			if (day >= 11 && day <= 13) {
				return day+'th';
			}

			if ( day % 10 === 1)  {
				return day+'st'
			};
			if ( day % 10 === 2 )  {
				return day+'nd';
			}
			if ( day % 10 === 3 )  {
				return day+'rd';
			}

			return day+'th';

		}

		if (hour === 0) {
			hour = 12;
		}

		if (hour > 12) {
			hour -= 12;
		}



		return `${days[date.getDay()]}, ${months[date.getMonth()]} ${withSuffix(date.getDate())}, ${date.getFullYear()} @ ${hour}:${date.getMinutes().toString().padStart(2, '0')}${meridiem}`;

	}

	// Calculate the number of days in a month
	function daysInMonth(month, year) {
		return new Date(year, month + 1, 0).getDate();
	}

	// Populate the calendar with events
	function populateCalendar() {
		let calendarDays = $('.cb-events-calendar .cb-events-calendar-days');
		calendarDays.empty();

		let numDays = daysInMonth(currentMonth, currentYear);

		// Add empty cells for previous month's days
		let firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
		for (let i = 0; i < firstDayOfMonth; i++) {
			calendarDays.append('<div class="cb-events-calendar-day"></div>');
		}

		// Add days with events
		for (let day = 1; day <= numDays; day++) {
			let eventHTML = '';
			for (let j = 0; j < events.length; j++) {
				let event = events[j];
				let eventStartDate = new Date(event.event_start);
				let eventEndDate = new Date(event.event_end);

				if (
					eventStartDate.getFullYear() === currentYear &&
					eventStartDate.getMonth() === currentMonth &&
					eventStartDate.getDate() <= day &&
					eventEndDate.getDate() >= day
				) {
					eventHTML += '<div class="cb-events-calendar-event">';
					eventHTML += '<div class="cb-events-calendar-event-title">' + event.event_title + '</div>';
					eventHTML += '<div class="cb-events-calendar-event-desc">' + event.event_desc + '</div>';
					eventHTML += '</div>';
				}
			}

			let dayHTML = '<div class="cb-events-calendar-day">';
			dayHTML += '<div class="cb-events-calendar-day-number">' + day + '</div>';
			dayHTML += eventHTML;
			dayHTML += '</div>';

			calendarDays.append(dayHTML);
		}
	}

	// Show previous month
	$('.cb-events-calendar-prev').click(function() {
		currentMonth--;
		if (currentMonth < 0) {
			currentMonth = 11;
			currentYear--;
		}
		updateCalendar();
	});

	// Show next month
	$('.cb-events-calendar-next').click(function() {
		currentMonth++;
		if (currentMonth > 11) {
			currentMonth = 0;
			currentYear++;
		}
		updateCalendar();
	});

	updateCalendar();
	$('.cb-events-calendar-month-year').text(
		new Date(currentYear, currentMonth).toLocaleDateString('en-US', {
			month: 'long',
			year: 'numeric'
		})
	);

	$('.cb-events-calendar').on('click', '.cb-events-calendar-event-button', async function(e) {

		let eventID = $(this).data('cbEventId');
		let hasParticipated = false;
		let contestEntries = [];
		let eventsCalendarOffcanvasBody = $('.cb-events-calendar-offcanvas-body');
		eventsCalendarOffcanvasBody.empty();

		await $.ajax({
			url: cb_events.get_events,
			method: 'GET',
			data: {
				event_id: eventID,
				api_key: cb_events.api_key
			},
			success: ({text,type}) => activeEvent = text[0],
			error: err => console.error(err)
		});

		await $.ajax({
			url: cb_events.get_transactions,
			method: 'GET',
			data: {
				event_id: eventID,
				recipient_id: cb_events.user_id,
				api_key: cb_events.api_key
			},
			success: ({text, type}) => {
				hasParticipated = text.length > 0;
			},
			error: err => console.error(err)
		});

		await $.ajax({
			url: cb_events.get_contests,
			method: 'GET',
			data: {
				event_id: eventID,
				api_key: cb_events.api_key
			},
			success: ({text, type}) => contestEntries = text,
			error: err => console.error(err)
		});

		let today = new Date();
		let startDate = new Date(activeEvent.event_start.replace(' ', 'T') + 'Z');
		let checkInUnavailable = (today.getTime() < startDate.getTime());
		let eventStart = prettyDate(new Date(activeEvent.event_start.replace(' ', 'T') + 'Z'));
		let eventEnd = prettyDate(new Date(activeEvent.event_end.replace(' ', 'T') + 'Z'));

		$('.cb-events-calendar-offcanvas-title').text(activeEvent.event_title);
		eventsCalendarOffcanvasBody.append([
			$(`<p><b>Starts:</b> ${eventStart}</p>`),
			$(`<p><b>Ends:</b> ${eventEnd}</p>`),
			$(`<p>${activeEvent.event_desc}</p>`),
		]);

		if ( hasParticipated === false ) {
			eventsCalendarOffcanvasBody.append(
				$(
					`<button data-cb-event-id="${eventID}" 
class="btn btn-outline-${checkInUnavailable ? 'disabled' : 'primary' } cb-events-check-in-button ${checkInUnavailable ? 'disabled' : '' }">
${checkInUnavailable ? 'Check-In Unavailable' : 'Check In' }</button>`)
			);
		} else {
			eventsCalendarOffcanvasBody.append(
				$(`<span class="btn btn-outline-success disabled">Participation Claimed</button>`)
			);
		}

		if ( contestEntries.length > 0 ) {

			eventsCalendarOffcanvasBody.append([
				$('<h4 class="mt-3">Contest Entries</h4>'),
				$(`<div class="btn-group cb-events-calendar-offcanvas-contest-placement-buttons ${checkInUnavailable ? 'pe-none disabled' : ''}"></div>`)
			]);

			for ( let entry of contestEntries.sort((a,b) => a.placement - b.placement ) ) {


				let buttonText = '';
				let buttonClass = 'btn cb-events-calendar-offcanvas-contest-placement-button ';
				buttonClass += checkInUnavailable ? 'btn-outline-disabled disabled pe-none' : 'btn-outline-primary';
				let dataAttrs = '';

				if (entry.recipient_id) {
					let userName = await getUserName(entry.recipient_id);
					buttonText = `Claimed by ${userName}`; 
					buttonClass += ' disabled pe-none';
				} else {
					buttonText = `${withSuffix(entry.placement)} Place`;
					dataAttrs = `data-cb-contest-id="${entry.id}" data-bs-dismiss="offcanvas" data-bs-target="#cbEventsOffcanvas"`;
				}

				const $button = $(`<button class="${buttonClass}" ${dataAttrs}>${buttonText}</button>`);

				$('.cb-events-calendar-offcanvas-contest-placement-buttons').append($button);

			}
		}


	});

	$(document).on('click', '.cb-events-check-in-button', async function (e) {

		let eventID = $(this).data('cbEventId');

		await $.ajax({
			url: cb_events.new_transactions,
			method: 'POST',
			data: {
				event_id: eventID,
				recipient_id: cb_events.user_id,
				api_key: cb_events.api_key
			},
			success: async ({text, type}) => {
				$('.cb-events-check-in-button').replaceWith(`<p><b>${text}</b></p>`);
			},
			error: err => console.error(err)

		});

	});

	$(document).on('click', '.cb-events-calendar-offcanvas-contest-placement-button', function(e) {

		let contestID = $(this).data('cbContestId');
		appendConfirmation(
			"Are you sure you want to claim this placement?", 
			function () { confirmPlacement(contestID) }, 
			{messageType: 'info', actionButton: 'info'}
		);

	});

	const cbEventsOffcanvas = document.getElementById('cbEventsOffcanvas');

	cbEventsOffcanvas.addEventListener('hide.bs.offcanvas', event => {
		activeEvent = {};
	});

});