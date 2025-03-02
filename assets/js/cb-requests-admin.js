jQuery( document ).ready( ( $ ) => {

	const cbRequestItemsPageNext = $('.cb-request-items-pagination-next');
	const cbRequestItemsPageLast = $('.cb-request-items-pagination-last');
	const cbRequestItemsPagePrev = $('.cb-request-items-pagination-previous');
	const cbRequestItemsPageFirst = $('.cb-request-items-pagination-first');
	const cbRequestsPageNext = $('.cb-requests-admin-pagination-next');
	const cbRequestsPageLast = $('.cb-requests-admin-pagination-last');
	const cbRequestsPagePrev = $('.cb-requests-admin-pagination-previous');
	const cbRequestsPageFirst = $('.cb-requests-admin-pagination-first');
	const itemNameInput = $('#cb_request_items_item_name');
	const itemDescInput = $('#cb_request_items_item_desc');
	const itemAmountInput = $('#cb_request_items_amount');
	const itemForm = $('#cb_request_items_form');
	const cbDestructConfirm = $('.cb-destruct-feedback');
	const cbInfoConfirm = $('.cb-info-feedback');
	const requestItemsTable = $('#cb_request_items_table');
	const requestsTable = $('#cb_requests_admin_table');
	const requestItemsTableHeaderRow = $('#cb_request_items_table tr')[0];
	const requestsTableHeaderRow = $('#cb_requests_admin_table tr')[0];

	let activeRequestCache = {
		request_item_name: '',
		request_item_id: 0,
	};

	let cbTotalRequestItems = 0;
	let cbTotalRequests = 0;
	let activeItemID = 0;
	let activeRequestID = 0;

	let activeItemCache = {
		item_name: '',
		item_desc: '',
		amount: 0
	};

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

	/**
	 * CB Get Total Entries
	 *
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {int}
	 *
	 */
	const cbGetTotalRequestItems = async () => {

		let getData = {
			count: true,
		}

		let retval = await $.ajax({
			method: "GET",
			url: cb_requests_admin.get_request_items,
			data: getData,
			success: x => {
				cbTotalRequestItems = parseInt(JSON.parse(x.text)[0].total_count);
				return cbTotalRequestItems;
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
	 * Get request item data from the Confetti Bits API
	 *
	 * @param {int} requestItemID
	 * @returns {object}
	 * @async
	 * @since 2.3.0
	 */
	async function cbGetRequestItemData(requestItemID = 0, select = '' ) {

		let retval = {};
		let getData = {};

		if ( requestItemID !== 0 ) {
			getData.id = requestItemID;	
		}

		if ( select !== '' ) {
			getData.select = select;
		}

		await $.get({
			url: cb_requests_admin.get_request_items,
			data: getData, 
			success: e => retval = JSON.parse(e.text)
		});

		if ( retval.length === 1 && requestItemID !== 0 ) {
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
	let cbRequestItemsPagination = async (page) => {

		await cbGetTotalRequestItems();
		if (cbTotalRequestItems > 0) {

			let last = Math.ceil(cbTotalRequestItems / 10);
			let prev = page - 1;
			let next = page + 1;

			cbRequestItemsPageLast.attr('data-cb-request-items-page', last);

			cbSetupPaginationEnds(page, prev, next, last, 'request-items' );
			cbSetupPaginationDigits(page, last, 'request-items' );

		} else {
			cbRequestItemsPageLast.toggleClass('disabled', true);
			cbRequestItemsPageNext.toggleClass('disabled', true);
			cbRequestItemsPagePrev.toggleClass('disabled', true);
			cbRequestItemsPageFirst.toggleClass('disabled', true);
			$('.cb-request-items-pagination-numbered').remove();
		}
	}

	/**
	 * Tells the user that there aren't any request items yet.
	 *
	 * @returns {void}
	 */
	function cbCreateEmptyRequestItemNotice() {

		requestItemsTable.children().remove();
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
		requestItemsTable.append(emptyNotice);

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
	function formatRequestItemsHeaderRow() {
		let headers = ['Item Name', 'Item Description', 'Value', 'Edit', 'Delete' ];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item = $(`<th id="cb_request_items_table_header_${header}">${header}</th>`);
			item.css({fontWeight: 'lighter'});
			headerRow.append(item);

		});
		requestItemsTable.append(headerRow);
	}

	/**
	 * Format Header Row
	 *
	 * @returns {void}
	 */
	function formatRequestsHeaderRow() {
		let headers = ['Status', 'Applicant', 'Item', 'Date of Request', 'Edit', 'Delete' ];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item = $(`<th id="cb_requests_admin_table_header_${header}">${header}</th>`);
			item.css({fontWeight: 'lighter'});
			headerRow.append(item);
		});
		requestsTable.append(headerRow);
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
	async function cbCreateRequestItemEntry(item) {

		let entryDataContainer = $('<td class="cb-entry-data-container">');
		let entryModule = $('<tr class="cb-entry-data">');
		let entryItemNameContainer = $(entryDataContainer).clone();
		let entryItemName = $('<p>', { text: item.item_name });
		let entryItemDescContainer = $(entryDataContainer).clone();
		let entryItemDesc = $('<p>', { text: item.item_desc });
		let entryAmountContainer = $(entryDataContainer).clone();
		let entryAmount = $('<p>', { text: item.amount });
		let entryEditContainer = $(entryDataContainer).clone();
		let entryEdit = $("<button>", {
			class: "cb-request-items-edit-button cb-button square",
			'data-cb-request-item-id': item.id,
			text: "Edit"
		});
		let entryDeleteContainer = $(entryDataContainer).clone();
		let entryDelete = $("<button>", {
			class: "cb-request-items-delete-button",
			'data-cb-request-item-id': item.id,
			text: "Delete",
			css: { 
				color: "#ffad87",
				background: "transparent",
				border: "0"
			}
		});

		entryItemNameContainer.append(entryItemName);
		entryItemDescContainer.append(entryItemDesc);
		entryAmountContainer.append(entryAmount);
		entryEditContainer.append(entryEdit);
		entryDeleteContainer.append(entryDelete);
		entryModule.append(entryItemNameContainer);
		entryModule.append(entryItemDescContainer);
		entryModule.append(entryAmountContainer);
		entryModule.append(entryEditContainer);
		entryModule.append(entryDeleteContainer);

		requestItemsTable.append(entryModule);

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
		let item = await cbGetRequestItemData( request.request_item_id );
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
	function refreshRequestItemsTable(page) {

		let getData = {
			page: page,
			per_page: 10,
			orderby: {column: "id", order: "DESC"},
		}

		$.ajax({
			method: "GET",
			url: cb_requests_admin.get_request_items,
			data: getData,
			success: async (data) => {
				await cbRequestItemsPagination(page);
				if ( data.text !== false ) {
					let entries = JSON.parse(data.text);
					requestItemsTable.empty();
					formatRequestItemsHeaderRow();
					entries.sort( (a,b) => b.id - a.id );
					for (let r of entries) {
						await cbCreateRequestItemEntry(r);
					}
				} else {
					cbCreateEmptyRequestItemNotice();
				}
			},
			error: e => console.log(e.responseText)
		});
	}

	/**
	 * Refreshes the request items table with new data from the server.
	 * 
	 * @param {number} page
	 * @returns {void}
	 */
	function refreshRequestsTable(page) {

		let getData = {
			page: page,
			per_page: 10,
			orderby: {column: "id", order: "DESC"},
		}

		$.ajax({
			method: "GET",
			url: cb_requests.get_requests,
			data: getData,
			success: async (data) => {
				await cbRequestsPagination(page);
				if ( data.text !== false ) {
					let entries = JSON.parse(data.text);
					requestsTable.empty();
					formatRequestsHeaderRow();
					entries.sort( (a,b) => b.id - a.id );
					for (let r of entries) {
						await cbCreateRequestEntry(r);
					}
				} else {
					cbCreateEmptyRequestNotice();
				}
			},
			error: e => console.log(e.responseText)
		});
	}

	async function deleteRequestItem() {

		if ( activeItemID === 0 ) {
			return;
		}

		let deleteData = {
			request_item_id: activeItemID,
			api_key: cb_requests_admin.api_key
		};

		await $.ajax({
			method: 'DELETE',
			url: cb_requests_admin.delete_request_items,
			data: JSON.stringify( deleteData ),
			success: e => formMessage.setMessage(e),
			error: x => console.error(x),
		});

		activeItemID = 0;
		$('.cb-destruct-feedback').slideUp();
		refreshRequestItemsTable(1);

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
	function resetRequestItemInputs() {

		$('#cb_request_items_item_name').val('');
		$('#cb_request_items_item_desc').val('');
		$('#cb_request_items_amount').val('');

	}

	/*************** Request Items Event Listeners ***************/

	itemForm.on( 'submit', async (e) => {

		e.preventDefault();
		e.stopPropagation();

		let postData = {
			item_name: itemNameInput.val(),
			item_desc: itemDescInput.val(),
			amount: itemAmountInput.val(),
			api_key: cb_requests_admin.api_key
		};

		await $.ajax({
			type: 'POST',
			url: cb_requests_admin.new_request_items,
			data: postData,
			success: e => formMessage.setMessage(e),
			error: x => console.error(x)
		});

		resetRequestItemInputs();
		refreshRequestItemsTable(1);

	});

	/**
	 * Event listener for editing a request item.
	 * 
	 * This event is triggered when a user clicks on the 
	 * "Edit" button in a request items table entry. It 
	 * enables inline editing for request items, to prevent
	 * the need for an entire form. It also sets data for
	 * a cache object to be referenced by the "Save" and
	 * "Cancel" events.
	 */
	$(document).on('click', '.cb-request-items-edit-button', async function(e) {

		activeItemID = parseInt($(this).data('cbRequestItemId'));
		let row = $(this).closest('tr');
		let prevItemData = row.find('.cb-entry-data-container p');

		let saveButton = $('<button>', {
			class: "cb-request-items-save-button cb-button solid square",
			text: "Save",
			style: "margin-bottom: 5px;"
		});
		let cancelButton = $('<button>', {
			class: "cb-request-items-cancel-button cb-button square",
			text: "Cancel"
		});
		let item;

		await $.ajax({
			url: cb_requests_admin.get_request_items,
			method: 'GET',
			data: {
				id: activeItemID,
				api_key: cb_requests_admin.api_key
			},
			success: e => item = JSON.parse(e.text)[0],
			error: x => console.error(x)
		});

		let inputs = [
			$(`<input type="text" class="cb-edit-request-item-input" name="cb_edit_request_item_item_name" value="${item.item_name}" />`),
			$(`<input type="text" class="cb-edit-request-item-input" name="cb_edit_request_item_item_desc" value="${item.item_desc}" />`),
			$(`<input type="text" class="cb-edit-request-item-input" name="cb_edit_request_item_amount" value="${item.amount}" />`),
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
	$(document).on('click', '.cb-request-items-save-button', async function(e) {

		let parent = $(this).parent();
		let entryEdit = $("<button>", {
			class: "cb-request-items-edit-button cb-button square",
			'data-cb-request-item-id': activeItemID,
			text: "Edit"
		});

		let item = [
			$(`input[name="cb_edit_request_item_item_name"]`).val(),
			$(`input[name="cb_edit_request_item_item_desc"]`).val(),
			$(`input[name="cb_edit_request_item_amount"]`).val()
		];

		let patchData = {
			request_item_id: activeItemID,
			item_name: item[0],
			item_desc: item[1],
			amount: item[2],
			api_key: cb_requests_admin.api_key
		};

		await $.ajax({
			url: cb_requests_admin.update_request_items,
			method: "PATCH",
			data: JSON.stringify(patchData),
			success: e => formMessage.setMessage(e),
			error: x => console.error(x)
		});

		$('.cb-edit-request-item-input').each( (index, inputElement) => {
			let newEntryItemData = $('<p>', { text: item[index] });
			inputElement.replaceWith(newEntryItemData[0]);
		});

		activeItemID = 0;
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
	$(document).on('click', '.cb-request-items-cancel-button', async function(e) {

		let parent = $(this).parent();
		let entryEdit = $("<button>", {
			class: "cb-request-items-edit-button cb-button square",
			'data-cb-request-item-id': activeItemID,
			text: "Edit"
		});

		let item = await cbGetRequestItemData(activeItemID);

		let textData = [
			$('<p>', { text: item.item_name }),
			$('<p>', { text: item.item_desc }),
			$('<p>', { text: item.amount }),
		];

		$('.cb-edit-request-item-input').each( (index, inputElement) => {
			inputElement.replaceWith(textData[index][0]);
		});

		activeItemID = 0;
		parent.children().remove();
		parent.append(entryEdit);

	});

	$('.cb-request-items-pagination').on('click', '.cb-request-items-pagination-button', function (e) {
		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-request-items-page'));
		refreshRequestItemsTable(page);
	});

	$(document).on( 'click', '.cb-request-items-delete-button', (e) => {
		e.preventDefault();
		activeItemID = parseInt(e.target.dataset.cbRequestItemId);
		let feedback = new ConfirmFeedback('destruct');
		feedback.setMessage("This operation is destructive, which means that you cannot undo this action. Are you sure you want to delete this item?", 'warning');
	});

	/*************** Requests Event Listeners ***************/

	/**
	 * Event listener for editing a request.
	 * 
	 * This event is triggered when a user clicks on the 
	 * "Edit" button in a request entry. It 
	 * enables inline editing for requests, to prevent
	 * the need for an entire form. It also caches the
	 * Request ID to be referenced by the "Save" and
	 * "Cancel" events.
	 */
	$(document).on('click', '.cb-requests-admin-edit-button', async function(e) {

		activeRequestID = parseInt($(this).data('cbRequestId'));
		let row = $(this).closest('tr');
		let prevStatusData = row.find('.cb-entry-data-container p.cb-entry-status');

		let saveButton = $('<button>', {
			class: "cb-requests-admin-save-button cb-button solid square",
			text: "Save",
			style: 'margin-bottom: 5px;',
		});
		let cancelButton = $('<button>', {
			class: "cb-requests-admin-cancel-button cb-button square",
			text: "Cancel"
		});

		let selectInput = $(`<select class="cb-edit-requests-input" name="cb_edit_requests_status">`);
		let options = {
			'complete': 'Complete',
			'inactive': 'Inactive',
			'in_progress': 'In Progress',
		};

		for ( let [ value, label ] of Object.entries(options) ) {
			let element = $(`<option value="${value}">${label}</option>`);
			selectInput.append(element);
		}

		prevStatusData.replaceWith( selectInput );

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
	$(document).on('click', '.cb-requests-admin-save-button', async function(e) {

		let parent = $(this).parent();
		let status = $(`select[name="cb_edit_requests_status"]`).val();
		let patchData = {
			request_id: activeRequestID,
			admin_id: cb_requests_admin.user_id,
			status: status,
			api_key: cb_requests.api_key
		};
		let colors = {
			inactive: '#d1cbc1',
			new: '#007692',
			in_progress: '#dbb778',
			complete: '#62cc8f'
		};

		let entryEdit = $("<button>", {
			class: "cb-requests-admin-edit-button cb-button square",
			'data-cb-request-id': activeRequestID,
			text: "Edit"
		});
		
		await $.ajax({
			url: cb_requests_admin.update_requests,
			method: "PATCH",
			data: JSON.stringify(patchData),
			success: e => {
				formMessage.setMessage(e);
				console.log(e);
			},
			error: x => console.error(x)
		});

		let newRequest = await cbGetRequestData( activeRequestID, 'status' );
		let newRequestData = $('<p>', { text: newRequest.status.replaceAll('_', ' '), class: 'cb-entry-status', css: { textTransform: 'capitalize', color: colors[newRequest.status] } });
		$('.cb-edit-requests-input').replaceWith(newRequestData);

		activeRequestID = 0;
		parent.children().remove();
		parent.append(entryEdit);

	});

	/**
	 * Event listener for the "Edit Request" cancel button.
	 * 
	 * Replaces the inline editing inputs with <p> elements using
	 * the activeRequestID, then clears that data. Also 
	 * replaces the "Save" and "Cancel" actions
	 * with the "Edit" button.
	 */
	$(document).on('click', '.cb-requests-admin-cancel-button', async function(e) {

		let parent = $(this).parent();
		let entryEdit = $("<button>", {
			class: "cb-requests-admin-edit-button cb-button square",
			'data-cb-request-id': activeRequestID,
			text: "Edit"
		});
		let colors = {
			inactive: '#d1cbc1',
			new: '#007692',
			in_progress: '#dbb778',
			complete: '#62cc8f'
		};


		let requestData = await cbGetRequestData(activeRequestID);
		let newEntryElement = $('<p>', { class: 'cb-entry-status', text: requestData.status.replaceAll('_', ' '), css: { textTransform: 'capitalize', color: colors[requestData.status] } });

		$('.cb-edit-requests-input').replaceWith(newEntryElement);

		activeRequestID = 0;

		parent.children().remove();
		parent.append(entryEdit);

	});

	$('.cb-requests-admin-pagination').on('click', '.cb-requests-admin-pagination-button', function (e) {
		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-requests-admin-page'));
		refreshRequestsTable(page);
	});

	$(document).on( 'click', '.cb-requests-admin-delete-button', (e) => {
		e.preventDefault();
		activeRequestID = parseInt(e.target.dataset.cbRequestId);
		let feedback = new ConfirmFeedback('destruct');
		feedback.setMessage("This operation is destructive, which means that you cannot undo this action. Are you sure you want to delete this request?", 'warning');
	});

	$(document).on( 'click', '.cb-destruct-confirm', (e) => {

		e.preventDefault();

		if ( activeRequestID !== 0 ) {
			return deleteRequest();
		}

		if ( activeItemID !== 0 ) {
			console.log(activeItemID);
			return deleteRequestItem();
		}

	});

	formatRequestItemsHeaderRow();
	refreshRequestItemsTable(1);

	formatRequestsHeaderRow();
	refreshRequestsTable(1);

});
