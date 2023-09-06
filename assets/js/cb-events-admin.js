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
	const cbDestructConfirm = $('.cb-destruct-feedback');
	const cbInfoConfirm = $('.cb-info-feedback');
	const eventsTable = $('#cb_events_admin_table');
	const eventsTableHeaderRow = $('#cb_events_admin_table tr')[0];
	const contestModal = $(
		`<div id="cb-events-admin-contest-modal" title="Contest Placements"><div id="cb-events-admin-contest-modal-inputs"></div></div>`
	);
	let contestsCache = new Map();
console.log(cb_events_admin.api_key);
	let contestPlacementCounter = 1;
	let cbTotalEvents = 0;
	let activeEventID = 0;

	/**
	 * Handles setting feedback messages on the admin screen.
	 *
	 * Set messages by calling formMessage.setMessage( text, type ),
	 * where text is the message, and type is used as a key to color
	 * the message according to whether it's an error, info, warning,
	 * or success message.
	 *
	 * @since 2.0.0
	 */
	let formMessage = new function() {

		this.element = $('.cb-feedback-message');
		this.p = $('<p class="cb-feedback-message">');
		this.container = $('.cb-feedback');
		this.position = this.element.offset().top;

		this.style = {
			error: '#ffad87',
			info: '#007692',
			warning: '#dbb778',
			success: '#62cc8f'
		};

		this.container.children('.cb-close').on('click', () => {
			this.container.slideUp(400);
			this.container.children("p").remove();
		});

		this.setMessage = (items) => {

			this.container.children("p").remove();
			let itemsArray = Array.isArray(items) ? items : [items];

			itemsArray.forEach((item) => {
				if (Array.isArray(item.text)) {
					item.text.forEach((text) => {
						let p = this.p.clone();
						p.css({
							color: this.style[item.type]
						}).text(text);
						this.container.append(p);
					});
				} else {
					let p = this.p.clone();
					p.css({
						color: this.style[item.type]
					}).text(item.text);
					this.container.append(p);
				}
			});

			this.container.css({
				display: 'flex',
				border: '1px solid #dbb778',
				borderRadius: '10px',
				margin: '1rem auto'
			});

			window.scrollTo({
				top: this.element.offset().top - 135,
				behavior: 'smooth'
			});
		};
	};

	/**
	 * Handles the display for feedback messages that require confirmation.
	 */
	let ConfirmFeedback = function ( component ) {

		this.validComponents = [ 'info', 'destruct' ];
		this.component = component;

		if ( !this.validComponents.includes( component ) ) {
			this.component = 'info';
		}

		this.container	= $(`.cb-${this.component}-feedback`);


		this.style = {
			error:		'#ffad87',
			info:		'#007692',
			warning:	'#dbb778',
			success:	'#62cc8f'
		};

		this.container.children('.cb-close').on( 'click', () => {
			this.container.slideUp( 400 );
			this.element.text('');
		});

		this.container.children( `.cb-${this.component}-confirm` ).on( 'click', () => {
			this.container.slideUp( 400 );
			this.element.text('');
		});

		this.container.children( `.cb-${this.component}-cancel` ).on( 'click', () => {
			this.container.slideUp( 400 );
			this.element.text('');
		});

		this.setMessage = ( text, type ) => {
			this.element = $(`.cb-${this.component}-feedback-message`);
			this.element.text(text);
			this.element.css({
				color: this.style[type],
				width: '100%',
				maxWidth: '400px',
				margin: '1rem'
			});
			this.container.css({
				display: 'flex',
				backgroundColor: 'white',
				border: '1px solid #dbb778',
				borderRadius: '10px',
				width: '100%',
				margin: 'auto',
				padding: '1rem',
			});

			this.position = this.element.offset().top;

			window.scrollTo({top: this.element.offset().top - 135, behavior: 'smooth'})
		}

	};

	/**
	 * CB Format Date
	 *
	 * @param {string} date
	 * @returns {string}
	 */
	function cbFormatDate(date) {

		date = new Date(date);
		let dd = date.getDate();
		let mm = date.getMonth() + 1;
		let yyyy = date.getFullYear();
		let H = date.getHours();
		let i = date.getMinutes();
		let suffix = (H > 12) ? 'pm' : 'am';

		H = (H > 12) ? H - 12 : H;
		i = (i < 10) ? '0' + i : i;
		dd = (dd < 10) ? '0' + dd : dd;
		mm = (mm < 10) ? '0' + mm : mm;

		return [mm, dd, yyyy].join('/');

	}

	/**
	 * CB Get User Data
	 *
	 * Get user data from the BuddyBoss API
	 *
	 * @param {int} applicantId
	 * @returns {object}
	 * @async
	 * @since 1.0.0
	 *
	 */
	async function cbGetUserData(applicantId) {
		let retval = '';
		await $.get({
			url: 'https://teamctg.com/wp-json/buddyboss/v1/members',
			data: {
				include: applicantId
			}, success: function (text) {
				retval = text[0].name;
			}
		});
		return retval;
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
	 * Gets total number of requests from the database.
	 *
	 * @returns {int}
	 */
	const cbGetTotalRequests = async () => {

		let getData = {
			count: true,
		}

		let retval = await $.ajax({
			method: "GET",
			url: cb_requests_admin.get_requests,
			data: getData,
			success: x => {
				cbTotalRequests = parseInt(JSON.parse(x.text)[0].total_count);
				return cbTotalRequests;
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
	 * Get request item data from the Confetti Bits API
	 *
	 * @param {int} requestItemID
	 * @returns {object}
	 * @async
	 * @since 2.3.0
	 */
	async function cbGetRequestData(requestID = 0, select = '' ) {

		let retval = {};

		let getData = {
			id: requestID,
			select: select,
		};

		await $.get({
			url: cb_requests_admin.get_requests,
			data: getData,
			success: e => retval = JSON.parse(e.text)
		});

		if ( retval.length === 1 && requestID !== 0 ) {
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
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {void}
	 */
	let cbRequestsPagination = async (page) => {

		await cbGetTotalRequests();
		if (cbTotalRequests > 0) {

			let last = Math.ceil(cbTotalRequests / 10);
			let prev = page - 1;
			let next = page + 1;

			cbRequestsPageLast.attr('data-cb-requests-admin-page', last);
			cbSetupPaginationEnds(page, prev, next, last, 'requests-admin');
			cbSetupPaginationDigits(page, last, 'requests-admin');
		} else {
			cbRequestsPageLast.toggleClass('disabled', true);
			cbRequestsPageNext.toggleClass('disabled', true);
			cbRequestsPagePrev.toggleClass('disabled', true);
			cbRequestsPageFirst.toggleClass('disabled', true);
			$('.cb-requests-admin-pagination-numbered').remove();
		}

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
	function cbCreateEmptyRequestItemNotice() {

		eventsTable.children().remove();
		let emptyNotice = $(`
<div class='cb-request-item-empty-notice cb-ajax-table-empty-notice'>
<p style="margin-bottom: 0;">
No request items found.
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
	 * Tells the user that there aren't any request items yet.
	 *
	 * @returns {void}
	 */
	function cbCreateEmptyRequestNotice() {

		requestsTable.children().remove();
		let emptyNotice = $(`
<div class='cb-request-empty-notice cb-ajax-table-empty-notice'>
<p style="margin-bottom: 0;">
No requests found.
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
		requestsTable.append(emptyNotice);

	}

	/**
	 * Format Header Row
	 *
	 * @returns {void}
	 */
	function formatEventsHeaderRow() {
		let headers = ['Event Title', 'Event Description', 'Participation Amount', 'Start Date', 'End Date','Contest','Edit', 'Delete' ];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item = $(`<th id="cb_events_admin_table_header_${header}">${header}</th>`);
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

		let parts = dateString.split(' ');
		let sqlFormat = new Date(`${parts[0]}T${parts[1]}`);
		return sqlFormat.toDateString() + ' @ ' + sqlFormat.toLocaleTimeString(
			'en-US', {hour: 'numeric', minute: 'numeric', hour12: true }
		);

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
		let entryContestContainer = $(entryDataContainer).clone();
		let entryContest = $('<button>', {
			class: `cb-events-admin-edit-contest-button cb-button square ${hasContest ? 'contest':''}`,
			text: `${hasContest ? 'Edit Contest':'Add Contest'}`,
			'data-cb-event-id': item.id
		});
		let entryEditContainer = $(entryDataContainer).clone();
		let entryEdit = $("<button>", {
			class: "cb-events-admin-edit-button cb-button square",
			'data-cb-event-id': item.id,
			text: "Edit"
		});
		let entryDeleteContainer = $(entryDataContainer).clone();
		let entryDelete = $("<button>", {
			class: "cb-events-admin-delete-button",
			'data-cb-event-id': item.id,
			text: "Delete",
			css: {
				color: "#ffad87",
				background: "transparent",
				border: "0"
			}
		});

		entryTitleContainer.append(entryTitle);
		entryDescContainer.append(entryDesc);
		entryAmountContainer.append(entryAmount);
		entryStartContainer.append(entryStart);
		entryEndContainer.append(entryEnd);
		entryContestContainer.append(entryContest);
		entryEditContainer.append(entryEdit);
		entryDeleteContainer.append(entryDelete);
		entryModule.append(entryTitleContainer);
		entryModule.append(entryDescContainer);
		entryModule.append(entryAmountContainer);
		entryModule.append(entryStartContainer);
		entryModule.append(entryEndContainer);
		entryModule.append(entryContestContainer);
		entryModule.append(entryEditContainer);
		entryModule.append(entryDeleteContainer);

		eventsTable.append(entryModule);

	}

	/**
	 * CB Create Participation Entry
	 *
	 * Creates an entry in the requests table
	 *
	 * @param {object} participation
	 * @returns {void}
	 * @async
	 */
	async function cbCreateRequestEntry(request) {

		let colors = {
			inactive: '#d1cbc1',
			new: '#007692',
			in_progress: '#dbb778',
			complete: '#62cc8f'
		};

		let applicantName = await cbGetUserData(request.applicant_id);
		let item = await cbGetEventData( request.request_item_id );
		let requestDate = cbFormatDate(request.date_created);

		let entryDataContainer = $('<td class="cb-entry-data-container">');
		let entryModule = $('<tr class="cb-entry-data">');
		let entryStatusContainer = $(entryDataContainer).clone();
		let entryStatus = $('<p>', { text: request.status.replaceAll('_', ' '), class: 'cb-entry-status', css: { textTransform: 'capitalize', color: colors[request.status] } });
		let entryApplicantContainer = $(entryDataContainer).clone();
		let entryApplicant = $('<p>', { text: applicantName });
		let entryItemContainer = $(entryDataContainer).clone();
		let entryItem = $('<p>', { text: item.item_name });
		let entryDateContainer = $(entryDataContainer).clone();
		let entryDate = $('<p>', { text: requestDate });
		let entryEditContainer = $(entryDataContainer).clone();
		let entryEdit = '';
		let entryDeleteContainer = $(entryDataContainer).clone();
		let entryDelete = '';

		if ( request.status === 'new' || request.status === 'in_progress' ) {
			entryEdit = $("<button>", {
				class: "cb-requests-admin-edit-button cb-button square",
				'data-cb-request-id': request.id,
				text: "Edit"
			});
			entryDelete = $("<button>", {
				class: "cb-requests-admin-delete-button",
				'data-cb-request-id': request.id,
				text: "Delete",
				css: {
					color: "#ffad87",
					background: "transparent",
					border: "0"
				}
			});
		}

		entryStatusContainer.append(entryStatus);
		entryApplicantContainer.append(entryApplicant);
		entryItemContainer.append(entryItem);
		entryDateContainer.append(entryDate);
		entryEditContainer.append(entryEdit);
		entryDeleteContainer.append(entryDelete);
		entryModule.append(entryStatusContainer);
		entryModule.append(entryApplicantContainer);
		entryModule.append(entryItemContainer);
		entryModule.append(entryDateContainer);
		entryModule.append(entryEditContainer);
		entryModule.append(entryDeleteContainer);

		requestsTable.append(entryModule);

	}

	/**
	 * Resets the active item cache back to default values.
	 */
	function resetActiveItemCache() {
		activeItemCache = {
			item_name: '',
			item_desc: '',
			amount: 0
		};
	}

	/**
	 * Resets the active item cache back to default values.
	 */
	function resetActiveRequestCache() {
		activeRequestCache = {
			request_item_name: '',
			request_item_id: 0,
		};
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

					let entries = JSON.parse(data.text);
					eventsTable.children().remove();
					formatEventsHeaderRow();
					for (let r of entries) {
						await cbCreateEventEntry(r);
					}
				} else {
					cbCreateEmptyRequestItemNotice();
				}
			},
			error: e => console.log(e.responseText)
		});
	}

	async function deleteRequestItem() {

		if ( activeEventID === 0 ) {
			return;
		}

		let deleteData = {
			request_item_id: activeEventID,
			api_key: cb_requests_admin.api_key
		};

		console.log(deleteData);

		await $.ajax({
			method: 'DELETE',
			url: cb_requests_admin.delete_request_items,
			data: JSON.stringify( deleteData ),
			success: e => formMessage.setMessage(e),
			error: x => console.error(x),
		});

		activeEventID = 0;
		$('.cb-destruct-feedback').slideUp();
		refreshEventsTable(1);

	}

	async function deleteRequest() {

		if ( activeRequestID === 0 ) {
			return;
		}

		let deleteData = {
			request_id: activeRequestID,
			api_key: cb_requests_admin.api_key
		};

		await $.ajax({
			method: 'DELETE',
			url: cb_requests_admin.delete_requests,
			data: JSON.stringify( deleteData ),
			success: e => formMessage.setMessage(e),
			error: x => console.error(x),
		});

		activeRequestID = 0;
		$('.cb-destruct-feedback').slideUp();
		refreshRequestsTable(1);

	}

	/**
	 * Handles resetting the new request item inputs.
	 */
	function resetEventInputs() {

		$('#cb_events_admin_event_title').val('');
		$('#cb_events_admin_event_desc').val('');
		$('#cb_events_admin_event_start_date').val('');
		$('#cb_events_admin_event_end_date').val('');
		$('#cb_events_admin_participation_amount').val('');

	}

	/*************** Event Listeners ***************/

	eventHasContestInput.on('change', function() {

		if ( eventHasContestInput.is(':checked') ) {

			let newPlacement = $('<input name="cb_events_admin_contest_placements[]" type="number" placeholder="Placement" />').val(1);
			let newAmount = $('<input name="cb_events_admin_contest_amounts[]" type="number" placeholder="Amount" />' );
			let addAnother = $('<button class="cb-button square">').text("+");
			let container = $('<div class="cb-events-admin-contests-container">').css({
				display: 'flex',
				gap: '.5rem',
				position: 'relative',
				marginBottom: '.5rem',
				maxWidth: '600px',
			});

			container.append(newPlacement);
			container.append(newAmount);
			container.append(addAnother);
			eventHasContestInput.parent().after(container);
		} else {
			$('.cb-events-admin-contests-container').remove();
		}
	});

	$(document).on('click', '.cb-events-admin-contests-container button:not(.remove)', function() {
		let newContainer = $(this).parent().clone();
		let removeButton = $('<button class="cb-button remove">');

		newContainer.children('input')[0].value = (parseInt($(this).parent().children('input')[0].value) + 1);
		newContainer.children('input')[1].value = '';

		$(this).after(removeButton);
		$(this).parent().after(newContainer);
		$(this).remove();
	});

	$(document).on('click', '.cb-events-admin-contests-container button.cb-button.remove', function() {

		$(this).parent().remove();
	});

	eventForm.on( 'submit', async (e) => {

		e.preventDefault();
		e.stopPropagation();

		let contestPlacements = $('.cb-events-admin-contests-container');

		if (contestPlacements.length !==0 ) {
			for ( let i = 0; i < contestPlacements.length; i++) {
				let [placementInput,amountInput] = $(contestPlacements[i]).find('input');
				console.log(placementInput.value, amountInput.value);
			}
		}


		return;
		let eventDateStart = new Date(eventStartDateInput.val()).toISOString().slice(0, 19).replace('T', ' ');
		let eventDateEnd = new Date(eventEndDateInput.val()).toISOString().slice(0, 19).replace('T', ' ');


		let postData = {
			event_title: eventTitleInput.val(),
			event_desc: eventDescInput.val(),
			event_start: eventDateStart,
			event_end: eventDateEnd,
			participation_amount: eventAmountInput.val(),
			user_id: cb_events_admin.user_id,
			api_key: cb_events_admin.api_key
		};

		console.log(cb_events_admin.new_events);

		await $.ajax({
			method: 'POST',
			url: cb_events_admin.new_events,
			data: postData,
			success: e => formMessage.setMessage(e),
			error: x => console.error(x)
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
		let row = $(this).closest('tr');
		let prevItemData = row.find('.cb-entry-data-container p');

		let saveButton = $('<button>', {
			class: "cb-events-admin-save-button cb-button solid square",
			text: "Save",
			style: "margin-bottom: 5px;"
		});
		let cancelButton = $('<button>', {
			class: "cb-events-admin-cancel-button cb-button square",
			text: "Cancel"
		});
		let item;

		await $.ajax({
			url: cb_events_admin.get_events,
			method: 'GET',
			data: {
				id: activeEventID,
				api_key: cb_events_admin.api_key
			},
			success: e => item = JSON.parse(e.text)[0],
			error: x => console.error(x)
		});

		let inputs = [
			$(`<input type="text" class="cb-edit-event-input" name="cb_edit_event_title" value="${item.event_title}" />`),
			$(`<input type="text" class="cb-edit-event-input" name="cb_edit_event_desc" value="${item.event_desc}" />`),
			$(`<input type="text" class="cb-edit-event-input" name="cb_edit_event_amount" value="${item.participation_amount}" />`),
			$(`<input type="datetime-local" class="cb-edit-event-input" name="cb_edit_event_start" value="${item.event_start}" />`),
			$(`<input type="datetime-local" class="cb-edit-event-input" name="cb_edit_event_end" value="${item.event_end}" />`),
		];

		prevItemData.each( ( index, element ) => {
			element.replaceWith( inputs[index][0] );
		});

		$(this).parent().append(saveButton);
		$(this).parent().append(cancelButton);
		$(this).remove();

	});

	/**
	 * Event listener for the "Save" action.
	 *
	 * Sends the updates for the given request item to the server,
	 * displays the response using our formMessage function,
	 * replaces the input elements with their respective values.
	 * The values will be replaced on page refresh if the update
	 * fails, so we probably won't super worry about making
	 * that 100% failsafe. Sorry :D
	 */
	$(document).on('click', '.cb-events-admin-save-button', async function(e) {

		let parent = $(this).parent();
		let entryEdit = $("<button>", {
			class: "cb-events-admin-edit-button cb-button square",
			'data-cb-event-id': activeEventID,
			text: "Edit"
		});

		let eventDateStart = new Date(
			$(`input[name="cb_edit_event_start"]`).val()
		).toLocaleString('en-US', {timeZone: 'America/New_York'});
		let eventDateEnd = new Date(
			$(`input[name="cb_edit_event_end"]`).val()
		).toLocaleString('en-US', {timeZone: 'America/New_York'});

		let item = [
			$(`input[name="cb_edit_event_title"]`).val(),
			$(`input[name="cb_edit_event_desc"]`).val(),
			$(`input[name="cb_edit_event_amount"]`).val(),
			eventDateStart,
			eventDateEnd,
		];

		let patchData = {
			event_id: activeEventID,
			event_title: item[0],
			event_desc: item[1],
			participation_amount: item[2],
			event_start: item[3],
			event_end: item[4],
			user_id: cb_events_admin.user_id,
			api_key: cb_events_admin.api_key
		};

		await $.ajax({
			url: cb_events_admin.update_events,
			method: "PATCH",
			data: JSON.stringify(patchData),
			success: e => formMessage.setMessage(e),
			error: x => console.error(x)
		});

		$('.cb-edit-event-input').each( (index, inputElement) => {
			let newEntryItemData;
			if ( index === 3 || index === 4  ) {
				newEntryItemData = $('<p>', { text: cbConvertLongDate(item[index]) });
			} else {
				newEntryItemData = $('<p>', { text: item[index] });
			}

			inputElement.replaceWith(newEntryItemData[0]);
		});

		activeEventID = 0;
		parent.children().remove();
		parent.append(entryEdit);

	});

	/**
	 * Event listener for the canceling the "Edit Request" event.
	 *
	 * Replaces the inline editing inputs with <p> elements using
	 * the data stored in the activeItemCache object, then clears
	 * the cache data and replaces the "Save" and "Cancel" actions
	 * with the "Edit" button.
	 */
	$(document).on('click', '.cb-events-admin-cancel-button', async function(e) {

		let parent = $(this).parent();
		let entryEdit = $("<button>", {
			class: "cb-events-admin-edit-button cb-button square",
			'data-cb-event-id': activeEventID,
			text: "Edit"
		});

		let item = await cbGetEventData(activeEventID);

		let textData = [
			$('<p>', { text: item.event_title }),
			$('<p>', { text: item.event_desc }),
			$('<p>', { text: item.participation_amount }),
			$('<p>', { text: cbConvertLongDate(item.event_start) }),
			$('<p>', { text: cbConvertLongDate(item.event_end) }),
		];

		$('.cb-edit-event-input').each( (index, inputElement) => {
			inputElement.replaceWith(textData[index][0]);
		});

		activeEventID = 0;
		parent.children().remove();
		parent.append(entryEdit);

	});

	$('.cb-events-admin-pagination').on('click', '.cb-events-admin-pagination-button', function (e) {
		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-events-admin-page'));
		refreshEventsTable(page);
	});

	$(document).on( 'click', '.cb-events-admin-delete-button', (e) => {
		e.preventDefault();
		activeEventID = parseInt(e.target.dataset.cbEventId);
		let feedback = new ConfirmFeedback('destruct');
		feedback.setMessage("This operation is destructive, which means that you cannot undo this action. Are you sure you want to delete this item?", 'warning');
	});

	async function replaceContestEntries() {

	}

	$(document).on('click', '.cb-events-admin-edit-contest-button', function (e) {



		contestModal.dialog({
			modal: true,
			classes: { 'ui-dialog-titlebar-close':'cb-button square'},
			open: () => {
				$('.ui-dialog-buttonset button').addClass('cb-button square solid');
				$('.ui-dialog-buttonset button').css({
					paddingTop: '.15rem',
					paddingBottom: '.15rem',
				})
			},
			buttons: [
				{
					text: 'Ok',
					click: function() {
						$(this).dialog('close');
					}
				}
			]
		});

		const button = $(this);
		const inputsContainer = $('#cb-events-admin-contest-modal-inputs');

		activeEventID = button.data('cbEventId');

		let placements = contestsCache.get(`${activeEventID}`);

		if ( placements.length > 0 ) {
			placements = placements.sort((a,b) => parseInt(a.placement) - parseInt(b.placement) );
		} else {
			placements = false;
		}

		let container = $('<div class="cb-events-admin-contest-modal-input-container">').css({
			display: 'flex',
			gap: '.5rem',
			position: 'relative',
			marginBottom: '.5rem',
			marginTop: '.5rem',
			maxWidth: '600px',
		}).append([
			$('<input name="cb_events_admin_contest_placement" type="number" placeholder="Placement" />'),
			$('<input name="cb_events_admin_contest_amount" type="number" placeholder="Amount" />' ),
			$('<button class="cb-button square">').text("+")
		]);

		inputsContainer.children().remove();

		if ( placements !== false ) {

			for ( let placement of placements ) {
				let newContainer = container.clone();
				newContainer.find('input[name=cb_events_admin_contest_placement]').val(placement.placement);
				newContainer.find('input[name=cb_events_admin_contest_amount]').val(placement.amount);
				inputsContainer.append(newContainer);
			}

		} else {
			let newContainer = container.clone();
			inputsContainer.append(container);
		}
	});

	$(document).on('click', '.cb-events-admin-contest-modal-input-container button:not(.remove)', function() {
		let newContainer = $(this).parent().clone();
		let removeButton = $('<button class="cb-button remove">');

		newContainer.children('input')[0].value = (parseInt($(this).parent().children('input')[0].value) + 1);
		newContainer.children('input')[1].value = '';

		$(this).after(removeButton);
		$(this).parent().after(newContainer);
		$(this).remove();
	});

	$(document).on('click', '.cb-events-admin-contest-modal-input-container button.cb-button.remove', function() {

		$(this).parent().remove();
	});

	
	$.ajax({
		url: cb_events_admin.new_contests,
		method: 'POST',
		data: {
			event_id: 2,
			api_key: cb_events_admin.api_key,
			contests:[
				{placement: 1, amount: 15},
				{placement: 2, amount: 10},
				{placement: 3, amount: 5},
			]
		},
		success: res => console.log(res)
	});
	

	formatEventsHeaderRow();
	refreshEventsTable(1);

	$('ui-dialog-buttonset button')

});
