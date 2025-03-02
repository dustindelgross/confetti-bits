import { DataTable, appendAlert, appendConfirmation, DateInput, toSQLDate, toMySQLUTCDate } from './cb-core-modules.js';

jQuery( document ).ready( ( $ ) => {

	const cbEventsPageNext = $('.cb-events-admin-pagination-next');
	const cbEventsPageLast = $('.cb-events-admin-pagination-last');
	const cbEventsPagePrev = $('.cb-events-admin-pagination-previous');
	const cbEventsPageFirst = $('.cb-events-admin-pagination-first');
	const eventTitleInput = $('#cb_events_admin_event_title');
	const eventDescInput = $('#cb_events_admin_event_desc');
	const eventHasContestInput = $('#cb_events_admin_has_contest');
	const eventStartDateInput = $('#cb_events_admin_event_start');
	const eventEndDateInput = $('#cb_events_admin_event_end');
	const eventAmountInput = $('#cb_events_admin_participation_amount');
	const eventForm = $('#cb_events_admin_form');
	const editEventForm = $('#cb_events_admin_edit_event_form');
	const cbDestructConfirm = $('.cb-destruct-feedback');
	const cbInfoConfirm = $('.cb-info-feedback');
	const eventsTable = $('#cb_events_admin_table');
	const eventsTableHeaderRow = $('#cb_events_admin_table tr')[0];
	//	const contestModal = $(`<div id="cb-events-admin-contest-modal" title="Contest Placements"><div id="cb-events-admin-contest-modal-inputs"></div></div>`);
	let contestsCache = new Map();
	let contestPlacementCounter = 1;
	let cbTotalEvents = 0;
	let activeEventID = 0;
	DateInput(`events_admin_event_start`);
	DateInput(`events_admin_event_end`);


	const toSQLDate = (dateString) => {
		return new Date(dateString).toISOString().slice(0,19).replace('T', ' ');
	}

	const getContestData = async (eventID) => {

		let retval;
		let id = parseInt(eventID);

		let promise = await $.ajax({
			url: cb_events_admin.get_contests,
			method: 'GET',
			data: {
				event_id: id,
				api_key: cb_events_admin.api_key
			},
			success: res => res,
			error: x => {
				console.error(x);
				return false;
			}
		});

		retval = await promise;
		return retval;

	}

	/**
	 * CB Get Total Entries
	 *
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {int}
	 *
	 */
	const cbGetTotalEvents = async () => {

		let getData = {
			count: true,
		}

		let retval = await $.ajax({
			method: "GET",
			url: cb_requests_admin.get_request_items,
			data: getData,
			success: x => {
				cbTotalEvents = parseInt(JSON.parse(x.text)[0].total_count);
				return cbTotalEvents;
			},
			error: e => console.error(e)
		});

		return retval;

	}



	/**
	 * Get event data from the Confetti Bits API
	 *
	 * @param {int} eventID
	 * @returns {object}
	 * @async
	 * @since 2.3.0
	 */
	async function cbGetEventData(EventID = 0, select = '' ) {

		let retval = {};
		let getData = {
			api_key: cb_events_admin.api_key
		};

		if ( EventID !== 0 ) {
			getData.event_id = EventID;
		}

		if ( select !== '' ) {
			getData.select = select;
		}

		await $.ajax({
			method: 'GET',
			url: cb_events_admin.get_events,
			data: getData,
			success: e => retval = JSON.parse(e.text)
		});

		if ( retval.length === 1 && EventID !== 0 ) {
			retval = retval[0];
		}

		return retval;

	}

	/**
	 * Get request item data from the Confetti Bits API
	 *
	 * @param {int} requestItemID
	 * @returns {object}
	 * @async
	 * @since 2.3.0
	 */
	async function cbGetContestData(EventID = 0, select = '' ) {

		let retval = {};
		let getData = {
			api_key: cb_events_admin.api_key
		};

		if ( EventID !== 0 ) {
			getData.event_id = EventID;
		}

		if ( select !== '' ) {
			getData.select = select;
		}

		await $.ajax({
			method: 'GET',
			url: cb_events_admin.get_contests,
			data: getData,
			success: e => retval = JSON.parse(e.text)
		});

		if ( retval.length === 1 && EventID !== 0 ) {
			retval = retval[0];
		}

		return retval;

	}


	/**
	 * CB Create Requests Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @param {string} component
	 * @returns {void}
	 */
	function cbCreatePaginationDigits(page, last, component, digitsToShow = 6) {

		let k;
		let bottomCap = digitsToShow - 1;

		if (page >= last - bottomCap && ((last - bottomCap) > 0)) {
			k = last - bottomCap;
		} else if (page + bottomCap <= last) {
			k = page;
		} else if (last < digitsToShow) {
			k = 1;
		}

		for (k; (k <= (page + digitsToShow)) && (k <= last); k++) {
			let paginationButton = $('<button>', {
				class: `cb-${component}-pagination-button cb-${component}-pagination-numbered cb-pagination-button ${(k === page ? ' active' : '')}`,
				text: k
			}).attr(`data-cb-${component}-page`, k);
			$(`.cb-${component}-pagination-next`).before(paginationButton);
		}
	}

	/**
	 * CB Refactor Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @param {array} currentButtons
	 * @param {string} component
	 * @returns {void}
	 */
	function cbRefactorPaginationDigits(page = 1, last = 0, currentButtons = [], component = '', digitsToShow = 6 ) {

		let bottomCap = last - digitsToShow;
		let k = (page >= bottomCap) && ((bottomCap) > 0) ? bottomCap : page;

		if (k <= (page + digitsToShow) && k <= last) {
			$(currentButtons).each((n, el) => {
				$(el).attr(`data-cb-${component}-page`, k);
				$(el).text(k);

				if (k === page) {
					$(el).addClass('active');
				}
				k++;
			});
		}
	}

	/**
	 * CB Setup Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @param {string} component
	 * @returns {void}
	 */
	function cbSetupPaginationDigits(page = 1, last = 0, component = '', digitsToShow = 6 ) {

		let currentButtons = $(`.cb-${component}-pagination-numbered`);

		currentButtons.removeClass('active');


		if (last < digitsToShow || currentButtons.length < digitsToShow ) {
			currentButtons.remove();
		}

		if (currentButtons.length === 0 || last < digitsToShow || currentButtons.length < digitsToShow ) {
			return cbCreatePaginationDigits(page, last, component, digitsToShow);
		}

		return cbRefactorPaginationDigits(page, last, currentButtons, component, digitsToShow);

	}

	/**
	 * CB Setup Pagination Ends
	 *
	 * @param {int} page
	 * @param {int} prev
	 * @param {int} next
	 * @param {int} last
	 * @returns {void}
	 */
	function cbSetupPaginationEnds(page = 1, prev = 0, next = 2, last = 0, component = '') {

		$(`.cb-${component}-pagination-previous`).attr(`data-cb-${component}-page`, prev);
		$(`.cb-${component}-pagination-next`).attr(`data-cb-${component}-page`, next);

		$(`.cb-${component}-pagination-next`).toggleClass('disabled', (page === last));
		$(`.cb-${component}-pagination-last`).toggleClass('disabled', (page === last));
		$(`.cb-${component}-pagination-previous`).toggleClass('disabled', (page === 1));
		$(`.cb-${component}-pagination-first`).toggleClass('disabled', (page === 1));

	}



	/**
	 * CB Pagination
	 *
	 * @param {int} page
	 * @returns {void}
	 */
	let cbEventsPagination = async (page) => {

		await cbGetTotalEvents();
		if (cbTotalEvents > 0) {

			let last = Math.ceil(cbTotalEvents / 10);
			let prev = page - 1;
			let next = page + 1;

			cbEventsPageLast.attr('data-cb-events-admin-page', last);

			cbSetupPaginationEnds(page, prev, next, last, 'events-admin' );
			cbSetupPaginationDigits(page, last, 'events-admin' );

		} else {
			cbEventsPageLast.toggleClass('disabled', true);
			cbEventsPageNext.toggleClass('disabled', true);
			cbEventsPagePrev.toggleClass('disabled', true);
			cbEventsPageFirst.toggleClass('disabled', true);
			$('.cb-events-admin-pagination-numbered').remove();
		}
	}

	/**
	 * Tells the user that there aren't any request items yet.
	 *
	 * @returns {void}
	 */
	function cbCreateEmptyEventNotice() {

		eventsTable.empty();
		let emptyNotice = $(`
<div class='cb-events-empty-notice cb-ajax-table-empty-notice'>
<p style="margin-bottom: 0;">
No events found.
</p>
</div>
`);
		emptyNotice.css({
			width: '90%',
			height: '90%',
			margin: '1rem auto',
			backgroundColor: '#d1cbc150',
			borderRadius: '1rem',
			display: 'flex',
			justifyContent: 'center',
			alignItems: 'center',
			padding: '1rem'
		});
		eventsTable.append(emptyNotice);

	}



	/**
	 * Format Header Row
	 *
	 * @returns {void}
	 */
	function formatEventsHeaderRow() {
		let headers = ['Title', 'Description', 'Participation Amount', 'Start Date', 'End Date','Actions' ];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item = $(`<th id="cb_events_admin_table_header_${header}" ${header === 'Actions' ? 'colspan="4"' : ''}>${header}</th>`);
			item.css({fontWeight: 'lighter'});
			headerRow.append(item);

		});
		eventsTable.append(headerRow);	
	}

	/**
	 * Prettifies the input date into a long datetime string.
	 * 
	 * @param string dateString The date to convert.
	 * @return string The formatted datetime. Example output:
	 * 		"Mon Jul 28 2023 at 4:30 PM"
	 */
	function cbConvertLongDate( dateString ) {

		let sqlFormat = new Date(dateString.replace(' ', 'T') + 'Z');

		return `${sqlFormat.getMonth() + 1}/${sqlFormat.getDate()}/${sqlFormat.getFullYear()} @ ${sqlFormat.toLocaleTimeString(
			'en-US', {hour: 'numeric', minute: 'numeric', hour12: true }
		)}`;

	}

	const formatButton = (text, attributes, classes, tag = 'button') => {

		let attrsArray = [];

		for ( let [key,value] of Object.entries(attributes) ) {
			attrsArray.push(`${key}="${value}"`);
		}

		attrsArray.push(`class="${classes.join(' ')}"`);

		let attrs = attrsArray.join(' ');

		return $(`<${tag} ${attrs}>${text}</${tag}>`);
	}

	/**
	 * CB Create Participation Entry
	 *
	 * Creates a participation entry in the participation table
	 *
	 * @param {object} participation
	 * @returns {void}
	 * @async
	 */
	async function cbCreateEventEntry(item) {

		const contestsMap = new Map();

		let entryDataContainer = $('<td class="cb-entry-data-container">');
		let entryModule = $('<tr class="cb-entry-data">');
		let entryTitleContainer = $(entryDataContainer).clone();
		let entryTitle = $('<p>', { text: item.event_title });
		let entryDescContainer = $(entryDataContainer).clone();
		let entryDesc = $('<p>', { text: item.event_desc });
		let entryAmountContainer = $(entryDataContainer).clone();
		let entryAmount = $('<p>', { text: item.participation_amount });
		let entryStartContainer = $(entryDataContainer).clone();
		let contestData = await getContestData(item.id);
		contestsCache.set(item.id, contestData.text);
		let hasContest = contestsCache.get(item.id) !== false;

		let entryStart = $('<p>', { 
			text: cbConvertLongDate(item.event_start) 
		});
		let entryEndContainer = $(entryDataContainer).clone();
		let entryEnd = $('<p>', {
			text: cbConvertLongDate(item.event_end)
		});
		let entryActionsContainer = $(entryDataContainer).clone();
		let entryEdit = formatButton('Edit', {
			'data-cb-event-id': item.id,
			'data-bs-toggle': 'modal',
			'data-bs-target': '#cb_events_admin_edit_event_form_container'
		},["cb-events-admin-edit-button", "btn", "btn-outline-primary"]);
		let entryViewButton = formatButton('View', {}, ['h-100', 'w-100', 'text-info', ], 'a');
		let entryView = formatButton('View', {
			'target': "_blank",
			'href': `${cb_events_admin.home_url}/cb-events/${item.id}`,
			'role': 'button'
		},["cb-events-admin-view-button", "btn", "btn-outline-info"], 'a');
		let entryContest = formatButton(`${hasContest ? 'Edit' : 'Add'} Contest`, {
			'data-cb-event-id': item.id,
			'data-bs-toggle': 'modal',
			'data-bs-target': '#cb_events_admin_edit_contests_modal',
		}, ['cb-events-admin-edit-contest-button', 'btn-outline-secondary', 'btn', `${hasContest ? 'contest' : ''}` ]);
		let entryDelete = formatButton('Delete', {
			'data-cb-event-id': item.id,
		}, ['cb-events-admin-delete-button', 'btn', 'btn-outline-danger']);

		entryTitleContainer.append(entryTitle);
		entryDescContainer.append(entryDesc);
		entryAmountContainer.append(entryAmount);
		entryStartContainer.append(entryStart);
		entryEndContainer.append(entryEnd);
		entryActionsContainer.append([entryContest, entryView, entryEdit, entryDelete]);
		entryModule.append([entryTitleContainer, entryDescContainer, entryAmountContainer, entryStartContainer, entryEndContainer, entryActionsContainer]);

		eventsTable.append(entryModule);

	}



	/**
	 * Refreshes the request items table with new data from the server.
	 *
	 * @param {number} page
	 * @returns {void}
	 */
	function refreshEventsTable(page) {

		let getData = {
			page: page,
			per_page: 10,
			orderby: {column: "id", order: "DESC"},
			api_key: cb_events_admin.api_key
		}

		$.ajax({
			method: "GET",
			url: cb_events_admin.get_events,
			data: getData,
			success: async (data) => {
				await cbEventsPagination(page);
				if ( data.text !== false ) {
					let entries = data.text;
					eventsTable.children().remove();
					formatEventsHeaderRow();
					for (let r of entries) {
						await cbCreateEventEntry(r);
					}
				} else {
					cbCreateEmptyEventNotice();
				}
			},
			error: e => console.log(e.responseText)
		});
	}


	/**
	 * Handles resetting the new request item inputs.
	 */
	function resetEventInputs() {

		$('#cb_events_admin_event_title').val('');
		$('#cb_events_admin_event_desc').val('');
		$('#cb_events_admin_event_start_calendar_date_input').val('');
		$('#cb_events_admin_event_end_calendar_date_input').val('');

		$('#cb_events_admin_event_start_time_selector_hour').val('9');
		$('#cb_events_admin_event_start_time_selector_minute').val('00');
		$('#cb_events_admin_event_start_time_selector_meridian').val('AM');
		$('#cb_events_admin_event_end_time_selector_hour').val('9');
		$('#cb_events_admin_event_end_time_selector_minute').val('00');
		$('#cb_events_admin_event_end_time_selector_meridian').val('AM');

		$('#cb_events_admin_participation_amount').val('');
		$('.cb-events-admin-contests-container').empty();
		$('#cb_events_admin_has_contest').removeAttr("checked");
		$(".cb-events-admin-add-contest-placement").remove();

		$('#cb_events_admin_edit_event_event_title').val('');
		$('#cb_events_admin_edit_event_event_desc').val('');
		$('#cb_events_admin_edit_event_event_start_calendar_date_input').val('');
		$('#cb_events_admin_edit_event_event_end_calendar_date_input').val('');

		$('#cb_events_admin_edit_event_event_start_time_selector_hour').val('9');
		$('#cb_events_admin_edit_event_event_start_time_selector_minute').val('00');
		$('#cb_events_admin_edit_event_event_start_time_selector_meridian').val('AM');
		$('#cb_events_admin_edit_event_event_end_time_selector_hour').val('9');
		$('#cb_events_admin_edit_event_event_end_time_selector_minute').val('00');
		$('#cb_events_admin_edit_event_event_end_time_selector_meridian').val('AM');
		$('#cb_events_admin_edit_event_participation_amount').val('');

	}

	/*************** Event Listeners ***************/

	eventHasContestInput.on('change', function() {

		if ( eventHasContestInput.is(':checked') ) {

			let addAnother = $('<button type="button" class="cb-button square cb-events-admin-add-contest-placement">').text("+");
			let newPlacement = $('<input name="cb_events_admin_contest_placements[]" type="number" placeholder="Placement" />').val(1);
			let newAmount = $('<input name="cb_events_admin_contest_amounts[]" type="number" placeholder="Amount" />' );
			let removeButton = $('<button type="buttpn" class="cb-button remove">');
			let container = $('<div class="cb-events-admin-contests-container">').css({
				display: 'flex',
				gap: '.5rem',
				position: 'relative',
				marginBottom: '.5rem',
				maxWidth: '600px',
			});

			container.append(newPlacement);
			container.append(newAmount);
			container.append(removeButton);
			eventHasContestInput.after(addAnother);
			eventHasContestInput.parent().after(container);
		} else {
			$('.cb-events-admin-contests-container').remove();
			$('.cb-events-admin-add-contest-placement').remove();
		}
	});

	$(document).on('click', '.cb-events-admin-add-contest-placement', function() {
		let lastPlacement = $('.cb-events-admin-contests-container').last();
		let newContainer = lastPlacement.clone();
		let removeButton = $('<button class="cb-button remove">');

		newContainer.children('input')[0].value = (parseInt($('.cb-events-admin-contests-container').last().children('input')[0].value) + 1);
		newContainer.children('input')[1].value = '';

		$(lastPlacement).after(newContainer);

	});

	$(document).on('click', '.cb-events-admin-contests-container button.cb-button.remove', function() {

		if ($('.cb-events-admin-contests-container button.cb-button.remove').length === 1 ) {
			return;
		}

		$(this).parent().remove();
	});

	eventForm.on('submit', async function(e) {

		e.preventDefault();

		const prefix = 'cb_events_admin';
		let eventStart = toMySQLUTCDate('events_admin', 'event_start');
		let eventEnd = toMySQLUTCDate('events_admin', 'event_end');
		let contestPlacements = $('.cb-events-admin-contests-container');

		if ( eventStart > eventEnd ) {
			[eventStart, eventEnd] = [eventEnd, eventStart];
		}

		let postData = {
			event_title: $(`#${prefix}_event_title`).val(),
			event_desc: $(`#${prefix}_event_desc`).val(),
			event_start: eventStart,
			event_end: eventEnd,
			contests: [],
			participation_amount: $(`#${prefix}_event_participation_amount`).val(),
			user_id: cb_events_admin.user_id,
			api_key: cb_events_admin.api_key
		};

		if (contestPlacements.length !==0 ) {
			for ( let i = 0; i < contestPlacements.length; i++) {
				let [placementInput,amountInput] = $(contestPlacements[i]).find('input');
				postData.contests.push({ placement: placementInput.value, amount: amountInput.value });
			}
		}

		await $.ajax({
			url: cb_events_admin.new_events,
			method: 'POST',
			data: postData,
			success: ({text, type}) => appendAlert(text, type),
			error: err => console.error(err), 
		});

		resetEventInputs();
		refreshEventsTable(1);

	});



	/**
	 * Listener for editing an event.
	 *
	 * Triggered when a user clicks on the
	 * "Edit" button in the events table entry. It
	 * enables inline editing for events, to prevent
	 * the need for an entire form. It also sets data for
	 * a cache object to be referenced by the "Save" and
	 * "Cancel" events.
	 */
	$(document).on('click', '.cb-events-admin-edit-button', async function(e) {

		activeEventID = parseInt($(this).data('cbEventId'));
		let prefix = 'cb_events_admin_edit_event';
		let item = {};

		await $.ajax({
			url: cb_events_admin.get_events,
			method: 'GET',
			data: {
				id: activeEventID,
				api_key: cb_events_admin.api_key
			},
			success: ({text,type}) => item = text[0],
			error: x => console.error(x)
		});

		DateInput(`events_admin_edit_event_event_start`);
		DateInput(`events_admin_edit_event_event_end`);

		let startDateObj = new Date(item.event_start.replace(' ', 'T') + 'Z');
		let endDateObj = new Date(item.event_end.replace(' ', 'T') + 'Z');
		let startHours = startDateObj.getHours();
		let endHours = endDateObj.getHours();

		let startDateParts = {
			day: startDateObj.getDate(),
			month: startDateObj.getMonth() + 1,
			year: startDateObj.getFullYear(),
			hours: startHours > 12 ? startHours - 12:startHours,
			minutes: startDateObj.getMinutes().toString().padStart(2,'0'),
			meridiem: startHours > 12 ? 'PM' : 'AM'
		};

		let endDateParts = {
			day: endDateObj.getDate(),
			month: endDateObj.getMonth() + 1,
			year: endDateObj.getFullYear(),
			hours: endHours > 12 ? endHours - 12 : endHours,
			minutes: endDateObj.getMinutes().toString().padStart(2,'0'),
			meridiem: endHours > 12 ? 'PM' : 'AM',
		};

		$(`#${prefix}_event_title`).val(item.event_title);
		$(`#${prefix}_event_desc`).val(item.event_desc);
		$(`#${prefix}_event_start_calendar_date_input`).val(`${startDateParts.month}/${startDateParts.day}/${startDateParts.year}`);
		$(`#${prefix}_event_start_time_selector_hour`).val(startDateParts.hours);
		$(`#${prefix}_event_start_time_selector_minute`).val(startDateParts.minutes);
		$(`#${prefix}_event_start_time_selector_meridiem`).val(startDateParts.meridiem);
		$(`#${prefix}_event_end_calendar_date_input`).val(`${endDateParts.month}/${endDateParts.day}/${endDateParts.year}`);
		$(`#${prefix}_event_end_time_selector_hour`).val(endDateParts.hours);
		$(`#${prefix}_event_end_time_selector_minute`).val(endDateParts.minutes);
		$(`#${prefix}_event_end_time_selector_meridiem`).val(endDateParts.meridiem);
		$(`#${prefix}_event_participation_amount`).val(parseInt(item.participation_amount));

	});

	editEventForm.on('submit', async function (e) {

		e.preventDefault();

		let eventStart = toMySQLUTCDate('events_admin_edit_event', 'event_start');
		let eventEnd = toMySQLUTCDate('events_admin_edit_event', 'event_end');
		if ( eventStart > eventEnd ) {
			[eventStart, eventEnd] = [eventEnd, eventStart];
		}
		let prefix = 'cb_events_admin_edit_event_event';
		let patchData = {
			id: activeEventID,
			event_title: $(`#${prefix}_title`).val(),
			event_desc: $(`#${prefix}_desc`).val(),
			event_start: eventStart,
			event_end: eventEnd,
			participation_amount: $(`#${prefix}_participation_amount`).val(),
			user_id: cb_events_admin.user_id,
			api_key: cb_events_admin.api_key
		};

		await $.ajax({
			url: cb_events_admin.update_events,
			method: 'PATCH',
			data: JSON.stringify(patchData),
			success: ({text, type}) => appendAlert(text,type),
			error: err => console.error(err)
		});

		activeEventID = 0;
		resetEventInputs();
		refreshEventsTable(1);

	});


	$('#cb_events_admin_pagination').on('click', '.cb-events-admin-page-link', function (e) {
		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-events-admin-page'));
		refreshEventsTable(page);
	});

	$(document).on( 'click', '.cb-events-admin-delete-button', async function (e) {
		e.preventDefault();
		activeEventID = parseInt($(this).data('cbEventId'));

		await appendConfirmation(
			"This operation is destructive, which means that you cannot undo this action. Are you sure you want to delete this item?",
			() => {

				if ( cb_events_admin.user_id == 5 )	{
					$.ajax({
						url: cb_events_admin.get_transactions,
						method: 'GET',
						data: {
							event_id: activeEventID,
							api_key: cb_events_admin.api_key
						},
						success: ({text, type}) => console.log(text),
						error: err => console.error(err)
					});
				}

				$.ajax({
					url: cb_events_admin.delete_events,
					method: 'DELETE',
					data: JSON.stringify({
						event_id: activeEventID,
						api_key: cb_events_admin.api_key,
					}),
					success: ({text, type}) => {
						appendAlert(text, type);
						activeEventID = 0;
						refreshEventsTable(1);
					},
					error: err => console.error(err)
				});

			}
		);
	});

	$(document).on('click', '.cb-events-admin-edit-contest-button', function (e) {

		const button = $(this);
		const inputsContainer = $('#cb_events_admin_contests_modal_inputs');

		activeEventID = button.data('cbEventId');

		let placements = contestsCache.get(`${activeEventID}`);

		if ( placements.length > 0 ) {
			placements = placements.sort((a,b) => parseInt(a.placement) - parseInt(b.placement) );
		} else {
			placements = false;
		}



		let container = $(`<div class="d-flex gap-2 position-relative my-2 cb-events-admin-contest-modal-input-container">`).append([
			$('<input name="cb_events_admin_contest_placement" type="number" placeholder="Placement" />'),
			$('<input name="cb_events_admin_contest_amount" type="number" placeholder="Amount" />' ),
			$('<input name="cb_events_admin_contest_id" type="hidden" />' ),
			$('<button class="cb-button remove">')
		]);
		$('.cb-events-admin-contests-modal-remove').remove();
		inputsContainer.empty();

		if ( placements !== false ) {

			for ( let placement of placements ) {
				let newContainer = container.clone();
				newContainer.find('input[name=cb_events_admin_contest_placement]').val(placement.placement);
				newContainer.find('input[name=cb_events_admin_contest_amount]').val(placement.amount);
				newContainer.find('input[name=cb_events_admin_contest_id]').val(placement.id);
				inputsContainer.append(newContainer);
			}

			$($('.cb-events-admin-contests-modal-actions').children('button')[0]).before(`<button data-bs-dismiss="modal" type="button" class="btn btn-danger cb-events-admin-contests-modal-remove">Remove</button>`);

		} else {
			let newContainer = container.clone();
			inputsContainer.append(container);
		}
	});

	$(document).on('click', '.cb-events-admin-contests-modal-add-placement-button', function() {
		let lastPlacement = $('.cb-events-admin-contest-modal-input-container').last();
		let newContainer = lastPlacement.clone();

		newContainer.children('input')[0].value = (parseInt(lastPlacement.children('input')[0].value) + 1);
		newContainer.children('input')[1].value = '';
		lastPlacement.after(newContainer);

	});

	$(document).on('click', '.cb-events-admin-contest-modal-input-container button.cb-button.remove', function() {
		if ( $('.cb-events-admin-contest-modal-input-container').length === 1 ) {
			return;
		}
		$(this).parent().remove();
	});

	$(document).on('click', '.cb-events-admin-contests-modal-save', async e => {

		e.preventDefault();

		let contestModalInputContainers = $('.cb-events-admin-contest-modal-input-container');
		let feedback = '';
		let contestsArray = [];
		await contestModalInputContainers.each( async (i, el) => {
			let [placement, amount] = [$(el).children('input')[0].value, $(el).children('input')[1].value ];
			contestsArray.push({placement: placement, amount: amount});
		});

		await $.ajax({
			method: 'PATCH',
			url: cb_events_admin.update_contests,
			data: JSON.stringify({
				event_id: activeEventID,
				contests: contestsArray,
				api_key: cb_events_admin.api_key,
			}),
			success: ({text, type}) => {
				let response = text;
				for ( let {text, type} of response ) {
					feedback += `${text} `;
				}
				appendAlert(feedback, type);
			},
			error: err => console.error(err),
		});

		contestsCache.set(activeEventID, contestsArray);
		refreshEventsTable(1);

	});

	$(document).on('click', '.cb-events-admin-contests-modal-remove', async e => {

		await $.ajax({
			url: cb_events_admin.delete_contests,
			method: 'DELETE',
			data: JSON.stringify({
				event_id: activeEventID,
				api_key: cb_events_admin.api_key
			}),
			success: ({text, type}) => appendAlert(text, type),
			error: ({text, type}) => appendAlert(text, 'danger')
		});

		contestsCache.delete(activeEventID);
		activeEventID = 0;
		refreshEventsTable(1);

	});

	formatEventsHeaderRow();
	refreshEventsTable(1);

	DataTable({
		component: 'events_admin',
		tableHeaders: ['ID', 'sender', 'recipient', 'date_scheduled', 'transaction_id'],
		fetchCount: {
			url: cb_events_admin.get_events,
			method: 'GET',
			data: {
				count: true,
				api_key: cb_events_admin.api_key,
			},
			success: ({text, type}) => text[0].total_count,
			error: err => console.err(err)
		},
		fetchList: {
			url: cb_events_admin.get_events,
			method: 'GET',
			data: {
				per_page: 15,
				page: 1,
				api_key: cb_events_admin.api_key
			},
			success: e => e,
			error: err => console.error(err)
		},
		deleteItemCallback: async function (itemID) {
			await jQuery.ajax({
				url: cb_events_admin.delete_events,
				method: 'DELETE',
				data: JSON.stringify({
					event_id: itemID,
					api_key: cb_events_admin.api_key,
				}),
				success: ({text, type}) => appendAlert(text, type),
				error: err => console.error(err.message)
			});
		},
		populateTable: false,
	});


});