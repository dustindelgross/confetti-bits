jQuery(document).ready(($) => {

	const cbRequestsPageNext = $('.cb-requests-pagination-next');
	const cbRequestsPageLast = $('.cb-requests-pagination-last');
	const cbRequestsPagePrev = $('.cb-requests-pagination-previous');
	const cbRequestsPageFirst = $('.cb-request-pagination-first');
	const requestItemIDInput = $('#cb_requests_request_item_id');
	const requestAmountInput = $('#cb_requests_amount');
	const requestForm = $('#cb_requests_form');
	const cbDestructConfirm = $('.cb-destruct-feedback');
	const cbInfoConfirm = $('.cb-info-feedback');
	const requestsTable = $('#cb_requests_table');
	const requestsTableHeaderRow = $('#cb_requests_table tr')[0];

	let activeRequestCache = {
		request_item_name: '',
		request_item_id: 0,
	};

	let cbTotalRequests = 0;
	let activeItemID = 0;
	let activeRequestID = 0;

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
	 * Handles the display actions for the destructive feedback message.
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
	 * Get request item data from the Confetti Bits API
	 *
	 * @param {int} requestItemID
	 * @returns {object}
	 * @async
	 * @since 2.3.0
	 */
	async function cbGetRequestItemData(requestItemID = 0, cache = false ) {

		let retval = {};
		let getData = {};

		if ( requestItemID !== 0 ) {
			getData.id = requestItemID;	
		}

		await $.get({
			url: cb_requests.get_request_items,
			data: getData, 
			success: e => retval = JSON.parse(e.text)
		});

		if ( cache === true && requestItemID !== 0 ) {
			activeRequestCache.request_item_id = retval[0].id;
			activeRequestCache.request_item_name = retval[0].item_name;
		}

		if ( retval.length === 1 && requestItemID !== 0 ) {
			retval = retval[0];
		}

		return retval;

	}

	/**
	 * Gets total number of requests from the database.
	 *
	 * @returns {int}
	 */
	const cbGetTotalRequests = async () => {

		let getData = {
			applicant_id: cb_requests.user_id,
			count: true,
		}

		let retval = await $.ajax({
			method: "GET",
			url: cb_requests.get_requests,
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
	 * CB Create Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @param {string} component
	 * @param {int} digitsToShow
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
	let cbRequestsPagination = async (page) => {

		await cbGetTotalRequests();
		if (cbTotalRequests > 0) {

			let last = Math.ceil(cbTotalRequests / 10);
			let prev = page - 1;
			let next = page + 1;

			cbRequestsPageLast.attr('data-cb-requests-page', last);

			cbSetupPaginationEnds(page, prev, next, last, 'requests' );
			cbSetupPaginationDigits(page, last, 'requests' );

		} else {
			cbRequestsPageLast.toggleClass('disabled', true);
			cbRequestsPageNext.toggleClass('disabled', true);
			cbRequestsPagePrev.toggleClass('disabled', true);
			cbRequestsPageFirst.toggleClass('disabled', true);
			$('.cb-requests-pagination-numbered').remove();
		}
	}

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
	 * Tells the user that there aren't any request items yet.
	 *
	 * @returns {void}
	 */
	function cbCreateEmptyRequestNotice() {

		requestsTable.children().remove();
		let emptyNotice = $(`
<div class='cb-request-item-empty-notice cb-ajax-table-empty-notice'>
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
	function formatRequestsHeaderRow() {
		let headers = ['Status', 'Applicant', 'Item', 'Date of Request', 'Edit', 'Delete' ];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item = $(`<th id="cb_requests_table_header_${header}">${header}</th>`);
			item.css({fontWeight: 'lighter'});
			headerRow.append(item);
		});
		requestsTable.append(headerRow);
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
		let entryStatus = $('<p>', { text: request.status.replaceAll('_', ' '), css: { textTransform: 'capitalize', color: colors[request.status] } });
		let entryApplicantContainer = $(entryDataContainer).clone();
		let entryApplicant = $('<p>', { text: applicantName });
		let entryItemContainer = $(entryDataContainer).clone();
		let entryItem = $('<p>', { text: item.item_name, class: 'cb-entry-item' });
		let entryDateContainer = $(entryDataContainer).clone();
		let entryDate = $('<p>', { text: requestDate });
		let entryEditContainer = $(entryDataContainer).clone();
		let entryEdit = '';
		let entryDeleteContainer = $(entryDataContainer).clone();
		let entryDelete = '';

		if ( request.status === 'new' ) {
			entryEdit = $("<button>", {
				class: "cb-requests-edit-button cb-button square",
				'data-cb-request-id': request.id,
				text: "Edit"
			});	
			entryDelete = $("<button>", {
				class: "cb-requests-delete-button cb-button square",
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
	function refreshRequestsTable(page) {

		let getData = {
			applicant_id: cb_requests.user_id,
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
					requestsTable.children().remove();
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

	/**
	 * Handles resetting the new request item inputs.
	 */
	function resetRequestInputs() {

		$('#cb_requests_request_item_id').val('');
		$('#cb_requests_amount').val('');

	}

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
	$(document).on('click', '.cb-requests-edit-button', async function(e) {

		let id = $(this).data('cbRequestId');
		let row = $(this).closest('tr');
		let prevItemData = row.find('.cb-entry-data-container p.cb-entry-item');

		let saveButton = $('<button>', {
			class: "cb-requests-save-button cb-button solid square",
			text: "Save"
		});
		let cancelButton = $('<button>', {
			class: "cb-requests-cancel-button cb-button square",
			text: "Cancel"
		});

		await $.ajax({
			url: cb_requests.get_requests,
			method: 'GET',
			data: {
				id: id,
				api_key: cb_requests.api_key
			},
			success: async e => {
				let data = JSON.parse(e.text)[0];
				await cbGetRequestItemData( data.request_item_id, true );
				activeRequestCache.id = data.id;
			},
			error: x => console.error(x)
		});

		let selectInput = $(`<select class="cb-edit-requests-input" name="cb_edit_requests_request_item_id">`);
		let options = await cbGetRequestItemData();

		for ( let option of options ) {
			let element = $(`<option value="${option.id}">${option.item_name}</option>`);
			selectInput.append(element);
		}

		prevItemData.replaceWith( selectInput );

		$(this).parent().append(saveButton);
		$(this).parent().append(cancelButton);
		$(this).remove();

	});

	/**
	 * Event listener for the edit request item cancel button.
	 * 
	 * Replaces the inline editing inputs with <p> elements using
	 * the data stored in the activeItemCache object, then clears
	 * the cache data and replaces the "Save" and "Cancel" actions
	 * with the "Edit" button.
	 */
	$(document).on('click', '.cb-requests-cancel-button', async function(e) {

		let parent = $(this).parent();
		let entryEdit = $("<button>", {
			class: "cb-requests-edit-button cb-button square",
			'data-cb-request-id': activeRequestCache.id,
			text: "Edit"
		});

		delete activeRequestCache.id;

		let requestItemData = await cbGetRequestItemData(activeRequestCache.request_item_id);

		let newEntryItemElement = $('<p>', { class: 'cb-entry-item', text: requestItemData.item_name });

		$('.cb-edit-requests-input').replaceWith(newEntryItemElement);

		resetActiveRequestCache();

		parent.children().remove();
		parent.append(entryEdit);

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
	$(document).on('click', '.cb-requests-save-button', async function(e) {

		let parent = $(this).parent();
		let item = $(`select[name="cb_edit_requests_request_id"]`).val();
		let patchData = {
			request_id: activeRequestCache.id,
			request_item_id: item,
			api_key: cb_requests.api_key
		};
		let entryEdit = $("<button>", {
			class: "cb-requests-edit-button cb-button square",
			'data-cb-request-id': activeRequestCache.id,
			text: "Edit"
		});

		delete activeRequestCache.id;

		await $.ajax({
			url: cb_requests.update_requests,
			method: "PATCH",
			data: JSON.stringify(patchData),
			success: e => formMessage.setMessage(e),
			error: x => console.error(x)
		});

		let newRequestItem = await cbGetRequestItemData(item, true);
		let newRequestItemData = $('<p>', { text: newRequestItem.item_name });
		$('.cb-edit-requests-input').replaceWith(newRequestItemData);

		resetActiveRequestCache();
		parent.children().remove();
		parent.append(entryEdit);

	});

	$('.cb-requests-pagination').on('click', '.cb-requests-pagination-button', function (e) {
		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-requests-page'));
		refreshRequestsTable(page);
	});

	requestForm.on( 'submit', async (e) => {

		e.preventDefault();
		e.stopPropagation();

		let postData = {
			request_item_id: requestItemIDInput.val(),
			applicant_id: cb_requests.user_id,
			api_key: cb_requests.api_key
		}; 

		await $.ajax({
			type: 'POST',
			url: cb_requests.new_requests,
			data: postData,
			success: e => formMessage.setMessage(e),
			error: x => console.error(x)
		});

		resetRequestInputs();
		refreshRequestsTable(1);

	});

	$(document).on( 'click', '.cb-requests-delete-button', (e) => {
		e.preventDefault();
		activeRequestID = e.target.dataset.cbRequestId;
		let feedback = new ConfirmFeedback('destruct');
		feedback.setMessage("This operation is destructive, which means that you cannot undo this action. Are you sure you want to delete this item?", 'warning');
	});

	$(document).on( 'click', '.cb-destruct-confirm', async (e) => {

		e.preventDefault();
		
		if ( activeRequestID !== 0 ) {
			let deleteData = {
				request_id: activeRequestID,
				api_key: cb_requests.api_key,
			};

			await $.ajax({
				method: 'DELETE',
				url: cb_requests.delete_requests,
				data: JSON.stringify( deleteData ),
				success: e => formMessage.setMessage(e),
				error: x => console.error(x),
			});

			$('.cb-destruct-feedback').slideUp();
			refreshRequestsTable(1);
		}

	});

	$(document).on('change', 'select[name=cb_requests_request_item_id]', async function (e) {
		let requestItem = await cbGetRequestItemData(e.target.value);
		if ( !requestItem.item_desc ) {
			$('.cb-requests-form-content').text("Select an item to display a description.");
		} else {
			$('.cb-requests-form-content').text(requestItem.item_desc);	
		}	
		
		requestAmountInput.val(requestItem.amount);
		
	});

	formatRequestsHeaderRow();
	refreshRequestsTable(1);

});