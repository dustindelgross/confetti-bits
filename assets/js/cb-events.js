/*
 * jQuery(document).ready(function($) {
	const eventStart = $('#cb_event_start_date');

	var dateFormat = "mm/dd/yy";

	eventStart.datepicker({
    // Set the date format
    dateFormat: dateFormat,

    // Show the current month and day by default
    defaultDate: new Date(),

    // When the user selects a date, update the input field
    onSelect: function(dateText, inst) {
      $(this).val(dateText);
    }
  });
});
*/

jQuery(document).ready(function($) { 
	// Fetch events from the database
	// Replace this with your own code to fetch events from your database
	// For simplicity, I'm using sample data here
	var events = [
		{
			event_title: "Event 1",
			event_desc: "Event 1 description",
			event_start_date: "2023-07-01",
			event_end_date: "2023-07-03",
			event_location: "Location 1",
			participation_amount: 10,
			has_contest: true
		},
		{
			event_title: "Event 2",
			event_desc: "Event 2 description",
			event_start_date: "2023-07-05",
			event_end_date: "2023-07-07",
			event_location: "Location 2",
			participation_amount: 5,
			has_contest: false
		}
		// Add more events as needed
	];

	// Initialize the calendar
	var currentDate = new Date();
	var currentMonth = currentDate.getMonth();
	var currentYear = currentDate.getFullYear();

	function updateCalendar() {
		// Get the element references
		var calendarDays = $('.cb-events-calendar .cb-events-calendar-days');
		var monthYearLabel = $('.cb-events-calendar-month-year');

		// Clear the calendar days
		calendarDays.empty();

		// Get the number of days in the current month
		var numDays = daysInMonth(currentMonth, currentYear);

		// Get the first day of the month
		var firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();

		// Add empty cells for previous month's days
		for (var i = 0; i < firstDayOfMonth; i++) {
			calendarDays.append('<div class="cb-events-calendar-day"></div>');
		}

		// Iterate over the days of the current month
		for (var day = 1; day <= numDays; day++) {
			// Create a new date object for the current day
			var currentDate = new Date(currentYear, currentMonth, day);

			// Create a cell element for the day
			var dayCell = $('<div class="cb-events-calendar-day"></div>');

			// Create a cell for the day number
			var dayNumber = $('<div class="cb-events-calendar-day-number">' + day + '</div>');

			// Add the day number cell to the day cell
			dayCell.append(dayNumber);

			// Add event items for the current day
			for (var j = 0; j < events.length; j++) {
				var event = events[j];

				// Convert event start and end dates to Date objects
				var eventStartDate = new Date(event.event_start_date);
				var eventEndDate = new Date(event.event_end_date);

				// Check if the event falls on the current day
				if (
					currentDate >= eventStartDate &&
					currentDate <= eventEndDate
				) {
					// Create an event element
					var eventItem = $('<div class="cb-events-calendar-event"></div>');

					// Create a title element
					var eventTitle = $('<div class="cb-events-calendar-event-title">' + event.event_title + '</div>');

					// Create a tooltip element for the event description
					var eventDescTooltip = $('<div class="cb-events-calendar-tooltip" style="display:none;">' + event.event_desc + '</div>');

					// Create a location element
					var eventLocation = $('<div class="cb-events-calendar-event-location">' + event.event_location + '</div>');

					// Create a participation amount element
					var eventParticipation = $('<div class="cb-events-calendar-event-participation">' + event.participation_amount + '</div>');

					// Create a contest indicator element
					var eventContest = $('<div class="cb-events-calendar-event-contest">' + (event.has_contest ? 'Yes' : 'No') + '</div>');

					// Add event item elements to the event element
					eventItem.append(eventTitle);
					eventItem.append(eventDescTooltip);
					eventItem.append(eventLocation);
					eventItem.append(eventParticipation);
					eventItem.append(eventContest);

					// Add the event item to the day cell
					dayCell.append(eventItem);

					// Show the event description tooltip on hover
					eventItem.hover(
						function() {
							$(this).children('.cb-events-calendar-tooltip').show();
						},
						function() {
							$(this).children('.cb-events-calendar-tooltip').hide();
						}
					);
				}
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

	// Calculate the number of days in a month
	function daysInMonth(month, year) {
		return new Date(year, month + 1, 0).getDate();
	}

	// Populate the calendar with events
	function populateCalendar() {
		var calendarDays = $('.cb-events-calendar .cb-events-calendar-days');
		calendarDays.empty();

		var numDays = daysInMonth(currentMonth, currentYear);

		// Add empty cells for previous month's days
		var firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
		for (var i = 0; i < firstDayOfMonth; i++) {
			calendarDays.append('<div class="cb-events-calendar-day"></div>');
		}

		// Add days with events
		for (var day = 1; day <= numDays; day++) {
			var eventHTML = '';
			for (var j = 0; j < events.length; j++) {
				var event = events[j];
				var eventStartDate = new Date(event.event_start_date);
				var eventEndDate = new Date(event.event_end_date);

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

			var dayHTML = '<div class="cb-events-calendar-day">';
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

	// Initial calendar rendering
	updateCalendar();
	$('.cb-events-calendar-month-year').text(
		new Date(currentYear, currentMonth).toLocaleDateString('en-US', {
			month: 'long',
			year: 'numeric'
		})
	);
});