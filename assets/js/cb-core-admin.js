jQuery(document).ready(($) => {

	let request;
	let $current;
	const $adminEditButton = $('.cb-participation-admin-edit-button');
	const $adminEditForm = $('#cb-participation-admin-edit-form-wrapper');
	const $adminEditFormClose = $('#cb-participation-admin-edit-form-close');
	const $hubNav = $('.cb-hub-nav-container');
	const $adminAmountOverride = $('#cb_participation_amount_override');
	const $adminEventTypeFilter = $('#cb_participation_admin_event_type_filter');
	const $cbParticipationAll = $('a[href=#cb-participation-all]');
	const $cbParticipationAllPanel = $('#cb-participation-all');
	const cbParticipationPageNext = $('.cb-participation-admin-pagination-next');
	const cbParticipationPageLast = $('.cb-participation-admin-pagination-last');
	const cbParticipationPagePrev = $('.cb-participation-admin-pagination-previous');
	const cbParticipationPageFirst = $('.cb-participation-admin-pagination-first');
	const cbTransactionPageNext = $('.cb-participation-admin-transactions-pagination-next');
	const cbTransactionPageLast = $('.cb-participation-admin-transactions-pagination-last');
	const cbTransactionPagePrev = $('.cb-participation-admin-transactions-pagination-previous');
	const cbTransactionPageFirst = $('.cb-participation-admin-transactions-pagination-first');

	const entryTable = $('#cb_participation_admin_table');
	const entryTableHeaderRow = $('#cb_participation_admin_table tr')[0];
	let currentPage = $('.cb-participation-admin-pagination-button.active').attr('data-cb-participation-admin-page');
	let cbTotalEntries = 0;
	let cbTotalTransactions = 0;
	let transactionsTable = $('#cb_participation_admin_transactions_table');

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
		let retval = {
			username: '',
			userDisplayName: ''
		};
		await $.get({
			url: 'https://teamctg.com/wp-json/buddyboss/v1/members',
			data: {
				include: applicantId
			}, success: function (text) {
				retval = {
					username: text[0].user_login,
					userDisplayName: text[0].name
				};
			}, error: e => console.error(e)
		});
		return retval;
	}

	let formMessage = new function () {
		this.element	= $('.cb-feedback-message');
		this.p			= $('<p class="cb-feedback-message">');
		this.br			= $('<br />');
		this.container	= $('.cb-feedback');
		this.position = this.element.offset().top;

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

		this.setMessage = ( text, type ) => {
			if ( typeof(text) === 'object' ) {
				this.element.remove();
				text.forEach((t) => {
					let p = this.p;
					p.css({
						'color': this.style[t.type]
					}).text(t.text);
					this.container.append(p);
				});
			} else if ( typeof(text) === 'string' ) {
				this.element.remove();
				let p = this.p;
				p.css({
					'color': this.style[text.type]
				}).text(text.text);
				this.container.append(p);
			}

			this.element.css('color', this.style[type] );
			this.container.css({ 
				display: 'flex',
				border: '1px solid #dbb778',
				borderRadius: '10px',
				margin: '1rem auto'
			});

			window.scrollTo({top: this.element.offset().top - 135, behavior: 'smooth'})
		}

	};
	/**
	 * CB Get Total Entries
	 *
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {int}
	 *
	 */
	const cbGetTotalEntries = async () => {

		let status = $('.cb-participation-admin-nav-item.active').attr('cb-participation-admin-status-type');
		let eventType = $('.cb-form-selector[name=cb_participation_admin_event_type]').val();

		let getData = {
			count: true,
		}

		if ('' !== status) {
			getData.status = status;
		}

		if ( '' !== eventType ) {
			getData.event_type = eventType;
		}

		let retval = await $.ajax({
			method: "GET",
			url: cb_core_admin.get_participation,
			data: getData,
			success: data => {
				let x = JSON.parse(data.text);
				cbTotalEntries = parseInt(x[0].total_count);
				return cbTotalEntries;
			},
			error: e => console.error(e)
		});

		return retval;

	}

	/**
	 * CB Get Total Transactions
	 *
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {int}
	 *
	 */
	const cbGetTotalTransactions = async (userID) => {

		let getData = {

			recipient_id: userID,
			sender_id: userID,
			or: true,
			count: true,
		};

		let retval = await $.get({
			url: cb_core_admin.get_transactions,
			data: getData,
			success: (x) => {
				cbTotalTransactions = parseInt(x.text[0].total_count);
				return cbTotalTransactions;
			},
			error: e => console.log(e)
		});

		return retval;

	};

	/**
	 * CB Create Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @returns {void}
	 */
	function cbCreatePaginationDigits(page, last) {

		let k;

		if (page >= last - 2 && ((last - 2) > 0)) {
			k = last - 2;
		} else if (page + 2 <= last) {
			k = page;
		} else if (last < 3) {
			k = 1;
		}

		for (k; (k <= (page + 2)) && (k <= last); k++) {
			let paginationButton = $('<button>', {
				class: `cb-participation-admin-pagination-button cb-participation-admin-pagination-numbered ${(k === page ? ' active' : '')}`,
				'data-cb-participation-admin-page': k,
				text: k
			});
			$('.cb-participation-admin-pagination-next').before(paginationButton);
		}
	}

	/**
	 * CB Create Transaction Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @returns {void}
	 */
	function cbCreateTransactionPaginationDigits(page, last) {

		let k;

		if (page >= last - 2 && ((last - 2) > 0)) {
			k = last - 2;
		} else if (page + 2 <= last) {
			k = page;
		} else if (last < 3) {
			k = 1;
		}

		for (k; (k <= (page + 2)) && (k <= last); k++) {
			let paginationButton = $('<button>', {
				class: `cb-participation-admin-transactions-pagination-button cb-participation-admin-transactions-pagination-numbered ${(k === page ? ' active' : '')}`,
				'data-cb-participation-admin-transactions-page': k,
				text: k
			});
			$('.cb-participation-admin-transactions-pagination-next').before(paginationButton);
		}
	}

	/**
	 * CB Refactor Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @param {array} currentButtons
	 * @returns {void}
	 */
	function cbRefactorPaginationDigits(page = 1, last = 0, currentButtons = []) {

		let k = (page >= last - 2) && ((last - 2) > 0) ? last - 2 : page;

		if (k <= (page + 2) && k <= last) {
			$(currentButtons).each((n, el) => {
				$(el).attr('data-cb-participation-admin-page', k);
				$(el).text(k);
				if (k === page) {
					$(el).addClass('active');
				}
				k++;
			});
		}
	}

	/**
	 * CB Refactor Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @param {array} currentButtons
	 * @returns {void}
	 */
	function cbRefactorTransactionPaginationDigits(page = 1, last = 0, currentButtons = []) {

		let k = (page >= last - 2) && ((last - 2) > 0) ? last - 2 : page;

		if (k <= (page + 2) && k <= last) {
			$(currentButtons).each((n, el) => {
				$(el).attr('data-cb-participation-admin-transactions-page', k);
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
	 * @returns {void}
	 */
	function cbSetupPaginationDigits(page = 1, last = 0) {

		let currentButtons = $('.cb-participation-admin-pagination-numbered');

		currentButtons.removeClass('active');

		if (currentButtons.length === 0) {
			cbCreatePaginationDigits(page, last);
		} else {
			if (last < 3) {
				currentButtons.remove();
				cbCreatePaginationDigits(page, last);
			} else {
				if (currentButtons.length < 3) {
					currentButtons.remove();
					cbCreatePaginationDigits(page, last);
				} else {
					cbRefactorPaginationDigits(page, last, currentButtons);
				}
			}
		}
	}

	/**
	 * CB Setup Pagination Digits
	 *
	 * @param {int} page
	 * @param {int} last
	 * @returns {void}
	 */
	function cbSetupTransactionPaginationDigits(page = 1, last = 0) {

		let currentButtons = $('.cb-participation-admin-transactions-pagination-numbered');

		currentButtons.removeClass('active');

		if (currentButtons.length === 0) {
			cbCreateTransactionPaginationDigits(page, last);
		} else {
			if (last < 3) {
				currentButtons.remove();
				cbCreateTransactionPaginationDigits(page, last);
			} else {
				if (currentButtons.length < 3) {
					currentButtons.remove();
					cbCreateTransactionPaginationDigits(page, last);
				} else {
					cbRefactorTransactionPaginationDigits(page, last, currentButtons);
				}
			}
		}
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
	function cbSetupPaginationEnds(page = 1, prev = 0, next = 2, last = 0) {

		cbParticipationPagePrev.attr('data-cb-participation-admin-page', prev);
		cbParticipationPageNext.attr('data-cb-participation-admin-page', next);

		cbParticipationPageNext.toggleClass('disabled', (page === last));
		cbParticipationPageLast.toggleClass('disabled', (page === last));
		cbParticipationPagePrev.toggleClass('disabled', (page === 1));
		cbParticipationPageFirst.toggleClass('disabled', (page === 1));

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
	function cbSetupTransactionPaginationEnds(page = 1, prev = 0, next = 2, last = 0) {

		cbTransactionPagePrev.attr('data-cb-participation-admin-transactions-page', prev);
		cbTransactionPageNext.attr('data-cb-participation-admin-transactions-page', next);

		cbTransactionPageNext.toggleClass('disabled', (page === last));
		cbTransactionPageLast.toggleClass('disabled', (page === last));
		cbTransactionPagePrev.toggleClass('disabled', (page === 1));
		cbTransactionPageFirst.toggleClass('disabled', (page === 1));

	}

	/**
	 * CB Pagination
	 *
	 * @param {int} page
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {void}
	 */
	let cbPagination = async (page) => {

		let status = $('.cb-participation-nav-item.active').attr('cb-participation-status-type');
		let eventType = $('.cb-form-selector[name=cb_participation_event_type]').val();

		await cbGetTotalEntries(status, eventType);
		if (cbTotalEntries > 0) {

			let last = Math.ceil(cbTotalEntries / 6);
			let prev = page - 1;
			let next = page + 1;

			cbParticipationPageLast.attr('data-cb-participation-admin-page', last);

			cbSetupPaginationEnds(page, prev, next, last);
			cbSetupPaginationDigits(page, last);

		} else {
			cbParticipationPageLast.toggleClass('disabled', true);
			cbParticipationPageNext.toggleClass('disabled', true);
			cbParticipationPagePrev.toggleClass('disabled', true);
			cbParticipationPageFirst.toggleClass('disabled', true);
			$('.cb-participation-admin-pagination-numbered').remove();
		}
	}

	/**
	 * CB Pagination
	 *
	 * @param {int} page
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {void}
	 */
	let cbTransactionsPagination = async (page, userID) => {

		await cbGetTotalTransactions(userID);
		if (cbTotalTransactions > 0) {

			let last = Math.ceil(cbTotalTransactions / 15);
			let prev = page - 1;
			let next = page + 1;

			cbTransactionPageLast.attr('data-cb-participation-admin-transactions-page', last);

			cbSetupTransactionPaginationEnds(page, prev, next, last);
			cbSetupTransactionPaginationDigits(page, last);

		} else {
			cbTransactionPageLast.toggleClass('disabled', true);
			cbTransactionPageNext.toggleClass('disabled', true);
			cbTransactionPagePrev.toggleClass('disabled', true);
			cbTransactionPageFirst.toggleClass('disabled', true);
			$('.cb-participation-admin-transactions-pagination-numbered').remove();
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
	 * CB Create Empty Participation Notice
	 *
	 * @param {string} response
	 * @param {object} activePanel
	 * @returns {void}
	 */
	function cbCreateEmptyParticipationNotice(response, activePanel) {

		entryTable.children().remove();

		let $emptyNotice = $(`<div class='cb-participation-admin-empty-notice'><p>${response}</p></div>`);
		activePanel.children().remove();
		activePanel.append($emptyNotice);

	}

	/**
	 * CB Create Empty Transaction Notice
	 *
	 * @param {string} response
	 * @param {object} activePanel
	 * @returns {void}
	 */
	function cbCreateEmptyTransactionNotice() {
		let $emptyNotice = $(`<div class='cb-participation-admin-empty-notice'><p>No results found.</p></div>`);
		transactionsTable.append($emptyNotice);
	}

	/**
	 * Format Header Row
	 *
	 * @returns {void}
	 */
	function formatHeaderRow() {
		let headers = ['cb-cb', 'Status', 'Applicant', 'Event Date', 'Modified', 'Type', 'Notes', 'Edit'];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item;
			if (header === 'cb-cb') {
				item = $(`<th><input type="checkbox" id="cb_participation_admin_bulk_edit_toggle" /></th>`);
			} else {
				item = $(`<th id="cb_participation_admin_table_header_${header}">${header.replaceAll('_', ' ')}</th>`);
				item.css({
					textTransform: 'capitalize',
					fontWeight: 'lighter'
				});

			}
			headerRow.append(item);
		});
		entryTable.append(headerRow);
	}

	/**
	 * Format Header Row
	 *
	 * @returns {void}
	 */
	function formatTransactionsHeaderRow() {
		let headers = ['Sender', 'Recipient', 'Transaction Date', 'Amount', 'Log Entry'];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item;
			item = $(`<th id="cb_participation_admin_transactions_table_header_${header}">${header}</th>`);
			item.css({
				textTransform: 'capitalize',
				fontWeight: 'lighter'
			});

			headerRow.append(item);
		});
		transactionsTable.append(headerRow);
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
	async function cbCreateParticipationEntry(participation) {

		let applicantId = parseInt(participation.applicant_id);
		let eventType = participation.event_type.charAt(0).toUpperCase() + participation.event_type.slice(1).replace('_', ' ').replace('\\', '');
		let eventNote = participation.event_note.replace('\\', '');
		let eventDate = cbFormatDate(participation.event_date);
		let transactionId = (null != participation.transaction_id) ? parseInt(participation.transaction_id) : 0;
		let dateModified = cbFormatDate(participation.date_modified);

		let { username, userDisplayName } = await cbGetUserData(applicantId);

		let $entryDataContainer = $('<td class="cb-participation-admin-entry-data-container">');
		let $entryModule = $('<tr class="cb-participation-admin-entry">');
		let $entryCheckboxContainer = $($entryDataContainer).clone();
		let $entryStatusContainer = $($entryDataContainer).clone();
		let $entryApplicantNameContainer = $($entryDataContainer).clone();
		let $entryEventDateContainer = $($entryDataContainer).clone();
		let $entryModifiedDateContainer = $($entryDataContainer).clone();
		let $entryEventNoteContainer = $($entryDataContainer).clone();
		let $entryEventTypeContainer = $($entryDataContainer).clone();
		let $entryEditDataContainer = $($entryDataContainer).clone();

		let $entryCheckbox = $(`<input type="checkbox" name="cb_participation_admin_entry_selection" 
data-cb-participation-id="${participation.id}"
data-cb-event-type="${participation.event_type}"
data-cb-applicant-id="${participation.applicant_id}"
data-cb-event-note="${participation.event_note}"
date-cb-event-date="${participation.event_date}"
data-cb-applicant-name="${userDisplayName}"
data-cb-transaction-id="${transactionId}"
value="${participation.id}" class="cb-participation-admin-entry-selection" />`);

		let $entryStatus = $('<p>', {
			class: `cb-participation-entry-data cb-participation-status-${participation.status}`,
			text: participation.status.charAt(0).toUpperCase() + participation.status.slice(1)
		});
		let $entryApplicantName = $('<a>', {
			class: "cb-participation-admin-entry-data cb-participation-entry-applicant-name",
			href: `https://teamctg.com/members/${username}/`,
			text: userDisplayName
		});
		let $entryEventDate = $(
			`<p class='cb-participation-admin-entry-data'><b>${eventDate}</b></p>`
		);
		let $entryModifiedDate = $(
			`<p class='cb-participation-admin-entry-data'>${dateModified}</p>`
		);
		let $entryEventType = $('<p>', {
			class: 'cb-participation-admin-entry-data',
			text: `${eventType}`
		});
		let $entryEventNote = $('<p>', {
			class: 'cb-participation-admin-entry-data cb-participation-admin-entry-event-note',
			text: `${eventNote}`
		});

		let $entryEditButton = $("<button>", {
			class: "cb-participation-admin-edit-button",
			'data-cb-participation-id': participation.id,
			'data-cb-event-type': participation.event_type,
			'data-cb-applicant-id': participation.applicant_id,
			'data-cb-event-note': participation.event_note,
			'date-cb-event-date': participation.event_date,
			'data-cb-applicant-name': userDisplayName,
			'data-cb-transaction-id': transactionId,
			text: "Edit"
		});

		$entryStatusContainer.addClass("cb-participation-admin-entry-status");

		$entryCheckboxContainer.append($entryCheckbox);
		$entryStatusContainer.append($entryStatus);
		$entryEventTypeContainer.append($entryEventType);
		$entryApplicantNameContainer.append($entryApplicantName);
		$entryEventDateContainer.append($entryEventDate);
		$entryModifiedDateContainer.append($entryModifiedDate);
		$entryEventTypeContainer.append($entryEventType);
		$entryEventNoteContainer.append($entryEventNote);
		$entryEditDataContainer.append($entryEditButton);

		$entryModule.append($entryCheckboxContainer);
		$entryModule.append($entryStatusContainer);
		$entryModule.append($entryApplicantNameContainer);
		$entryModule.append($entryEventDateContainer);
		$entryModule.append($entryModifiedDateContainer);
		$entryModule.append($entryEventTypeContainer);
		$entryModule.append($entryEventNoteContainer);
		$entryModule.append($entryEditDataContainer);

		entryTable.append($entryModule);

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
	async function cbCreateTransactionEntry(transaction) {

		let senderData = await cbGetUserData(transaction.sender_id);
		let recipientData = await cbGetUserData(transaction.recipient_id);
		let dateSent = cbFormatDate(transaction.date_sent);
		let amount = parseInt(transaction.amount);
		let logEntry = transaction.log_entry;

		let transactionDataContainer = $('<td class="cb-transactions-entry-data-container">');
		let transctionRow = $('<tr class="cb-transactions-admin-entry">');
		let transactionSenderContainer = $(transactionDataContainer).clone();
		let transactionRecipientContainer = $(transactionDataContainer).clone();
		let transactionDateSentContainer = $(transactionDataContainer).clone();
		let transactionAmountContainer = $(transactionDataContainer).clone();
		let transactionLogEntryContainer = $(transactionDataContainer).clone();

		let transactionSender = $('<p>', {
			class: `cb-transactions-entry-data cb-transactions-sender`,
			text: senderData.userDisplayName
		});
		let transactionRecipient = $('<a>', {
			class:`cb-transactions-entry-data cb-transactions-recipient`,
			text: recipientData.userDisplayName
		});
		let transactionDateSent = $(
			`<p class='cb-transactions-entry-data'><b>${dateSent}</b></p>`
		);
		let transactionAmount = $('<p>', {
			class: `cb-transactions-entry-data cb-transactions-amount`,
			text: amount
		});
		let transactionLogEntry = $('<p>', {
			class: `cb-transactions-entry-data cb-transactions-log-entry`,
			text: logEntry
		});

		transactionSenderContainer.append(transactionSender);
		transactionRecipientContainer.append(transactionRecipient);
		transactionDateSentContainer.append(transactionDateSent);
		transactionAmountContainer.append(transactionAmount);
		transactionLogEntryContainer.append(transactionLogEntry);

		transctionRow.append(transactionSenderContainer);
		transctionRow.append(transactionRecipientContainer);
		transctionRow.append(transactionDateSentContainer);
		transctionRow.append(transactionAmountContainer);
		transctionRow.append(transactionLogEntryContainer);

		transactionsTable.append(transctionRow);

	}

	/**
	 * Refresh Table
	 *
	 * Refreshes the table with new data from the server.
	 *
	 * @param {number} page
	 * @param {string} status
	 * @param {string} eventFilter
	 * @returns {void}
	 */
	function refreshTable(page) {

		let status = $('.cb-participation-nav-item.active').attr('cb-participation-status-type');
		let eventType = $('.cb-form-selector[name=cb_participation_event_type]').val();

		let activePanel = $('.cb-participation-admin-panel.active');
		let getData = {
			status: status,
			page: page,
			per_page: 6,
			event_type: eventType,
			orderby: { column: 'id', order: 'DESC' }
		};

		$.get({
			url: cb_core_admin.get_participation,
			data: getData,
			success: function (data) {
				cbPagination(page, status);

				if (data !== false) {
					let entries = JSON.parse(data.text);
					activePanel.children().remove();
					entryTable.children().remove();

					formatHeaderRow();
					for (let r of entries) {
						cbCreateParticipationEntry(r);
					}
				} else {
					cbCreateEmptyParticipationNotice(data.text, activePanel);
				}
			}
		});
	}

	/**
	 * Refresh Table
	 *
	 * Refreshes the table with new data from the server.
	 *
	 * @param {number} page
	 * @param {string} status
	 * @param {string} eventFilter
	 * @returns {void}
	 */
	async function refreshTransactions(page, userID) {
		let getData = {
			select: 'sender_id, recipient_id, amount, log_entry, date_sent',
			recipient_id: userID,
			sender_id: userID,
			or: true,
			page: page,
			per_page: 15,
		};
		await $.get({
			url: cb_core_admin.get_transactions,
			data: getData,
			success: function (data) {
				cbTransactionsPagination(page, userID);
				transactionsTable.children().remove();
				if ( data.text !== false ) {	
					formatTransactionsHeaderRow();
					for (let entry of data.text) {
						cbCreateTransactionEntry(entry);
					}
				} else {
					cbCreateEmptyTransactionNotice();
				}
			},
			error: e => console.log(e)
		});
	}



	/**
	 * Handle Navigation
	 *
	 * Handles the navigation between the different participation statuses
	 *
	 * @param {object} el
	 * @since 1.0.0
	 */
	function handleNavigation(el) {
		let $this = $(el);
		let activePanel = $($('.cb-participation-admin-nav-item.active').find('a').attr('href'));
		let activeItem = $('.cb-participation-admin-nav-item.active');
		let id = $this.attr('href');

		activePanel.removeClass('active');
		activeItem.removeClass('active');
		activePanel = $(id);
		$this.parent().addClass('active');
		activePanel.addClass('active');

		refreshTable(1);

	}

	function handleRowCheckboxChange() {

		const headerCheckbox = $('#cb_participation_admin_bulk_edit_toggle');
		const rowCheckboxes = $('.cb-participation-admin-entry-selection');

		let allChecked = true;
		let anyChecked = false;

		// Check if all row checkboxes are checked or if any row checkbox is checked
		rowCheckboxes.each(function() {
			if (!$(this).prop('checked')) {
				allChecked = false;
			} else {
				anyChecked = true;
			}
		});

		// Update the state of the header checkbox
		if (allChecked) {
			headerCheckbox.prop('checked', true);
			headerCheckbox.prop('indeterminate', false);
			headerCheckbox.data('state', 'checked');
		} else if (anyChecked) {
			headerCheckbox.prop('checked', false);
			headerCheckbox.prop('indeterminate', true);
			headerCheckbox.data('state', 'indeterminate');
		} else {
			headerCheckbox.prop('checked', false);
			headerCheckbox.prop('indeterminate', false);
			headerCheckbox.data('state', 'unchecked');
		}
	}

	function handleHeaderCheckboxChange() {
		const headerCheckbox = $('#cb_participation_admin_bulk_edit_toggle');
		const rowCheckboxes = $('.cb-participation-admin-entry-selection');

		const checked = headerCheckbox.prop('checked');

		if (checked) {
			// Check all row checkboxes
			rowCheckboxes.prop('checked', true);

			headerCheckbox.prop( 'checked', true );
			headerCheckbox.prop('indeterminate', false);
		} else {
			// Uncheck all row checkboxes
			rowCheckboxes.prop('checked', false);

			headerCheckbox.prop('checked', false);
			headerCheckbox.prop('indeterminate', false);
		}
	}

	$('.cb-participation-admin-nav').on(
		'click',
		'.cb-participation-admin-nav-link',
		function (e) {
			e.preventDefault();
			handleNavigation(this);
		});

	$(document).on('click', '.cb-participation-admin-edit-button', async function (e) {

		let participationId = $(this).data('cbParticipationId');
		let applicantId = $(this).data('cbApplicantId');
		let eventType = $(this).data('cbEventType').replaceAll('_',' ');
		let eventNote = $(this).data('cbEventNote').toString().replaceAll('\\', '');
		let eventDate = $(this).data('cbEventDate');
		let applicantName = $(this).data('cbApplicantName');
		let transactionId = $(this).data('cbTransactionId');

		refreshTransactions(1, applicantId);

		$($adminEditForm).addClass('active');
		$('input[name=cb_participation_id]').val(participationId);
		$('input[name=cb_participation_applicant_id]').val(applicantId);
		$('input[name=cb_participation_event_type]').val(eventType);
		$('input[name=cb_participation_event_date]').val(eventDate);
		$('input[name=cb_participation_admin_log_entry]').val(eventNote);
		$('input[name=cb_participation_transaction_id]').val(transactionId);
		$('#cb-participation-admin-applicant-name').text(applicantName);

		$('#cb-participation-admin-applicant-event').text(eventType.charAt(0).toUpperCase() + eventType.substr(1).toLowerCase().replace('\\', ''));

		if ((eventType === 'other' || eventType === 'contest' ) && transactionId === 0) {
			$adminAmountOverride.addClass('override');
			$adminAmountOverride.siblings('label').addClass('override');
			$adminAmountOverride.prop('disabled', false);
		} else {
			$adminAmountOverride.val(0);
			$adminAmountOverride.removeClass('override');
			$adminAmountOverride.siblings('label').removeClass('override');
			$adminAmountOverride.prop('disabled', true);
		}
	});

	$adminEditFormClose.on('click', function (e) {
		$('input[name=cb_participation_id]').val('');
		$('input[name=cb_participation_applicant_id]').val('');
		$('input[name=cb_participation_event_type]').val('');
		$('input[name=cb_participation_event_date]').val('');
		$('input[name=cb_participation_admin_log_entry]').val('');
		$('input[name=cb_participation_transaction_id]').val('');
		$('#cb-participation-admin-applicant-name').text('');
		$('#cb-participation-admin-applicant-event').text('');

		$($adminEditForm).removeClass('active');
	});

	$(document).on(
		'change', '#cb_participation_admin_event_type_filter',
		function (e) {
			e.preventDefault();
			let page = parseInt($('.cb-participation-admin-pagination-button.active').attr('data-cb-participation-admin-page'));
			if ( ! page ) {
				page = 1;
			}
			refreshTable(page);
		}
	);

	$('#cb-participation-admin-transactions-pagination').on('click', '.cb-participation-admin-transactions-pagination-button', function(e) {
		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-transactions-page'));
		let userID = parseInt($('input[name=cb_participation_applicant_id]').val());
		refreshTransactions(page, userID);

	});

	$('.cb-participation-admin-pagination').on('click', '.cb-participation-admin-pagination-button', function (e) {

		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-participation-admin-page'));
		refreshTable(page);

	});

	$('#cb_participation_admin_bulk_edit_form').on('submit', async function(e) {
		e.preventDefault();
		let action = $('#cb_participation_admin_bulk_action').val();

		if ( action !== 'approved' && action !== 'denied') {
			return;
		}

		let selected = $('input[name=cb_participation_admin_entry_selection]:checked');
		let page = $('.cb-participation-admin-pagination-numbered.active').data('cbParticipationPage');

		let status = $('.cb-participation-admin-nav-item.active').find('a').attr('href').replace('#cb-participation-', '');
		let eventFilter = $('#cb_participation_admin_event_type_filter').val();
		let messages = [];
		await selected.each( async (i, item) => {
			let adminId = $('input[name=cb_participation_admin_id]').val();
			let type = "";
			let br = $('<br>');
			await $.ajax({
				url: cb_core_admin.update,
				method: 'POST',
				data: {
					admin_id: adminId,
					participation_id: item.value,
					status: action,
					transaction_id: item.dataset.cbTransactionId
				},
				success: async function(e) {
					let data = await JSON.parse(e);
					type = await data.type;
					await messages.push({ 
						text: `${data.text}`,
						type: type
					});
					formMessage.setMessage( messages, data.type );

				},

				error: function(e) {
					console.log(e)
				}
			});

		});

		await refreshTable( page );


	});

	// Add event listener to each row checkbox
	$(document).on('change', '.cb-participation-admin-entry-selection', handleRowCheckboxChange);

	// Add event listener to the header checkbox
	$(document).on('change','#cb_participation_admin_bulk_edit_toggle', handleHeaderCheckboxChange);

	formatHeaderRow();
	refreshTable(1);

});