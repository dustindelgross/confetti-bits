jQuery( document ).ready( ( $ ) => {

	const $hubNav = $('.cb-hub-nav-container');
	// const $adminAmountOverride = $('#cb_participation_amount_override');
	const $eventTypeFilter = $('#cb_participation_event_type_filter');
	/*
	const $cbParticipationAll = $('a[href=#cb-participation-all]');
	const $cbParticipationAllPanel = $('#cb-participation-all');
	*/
	const cbParticipationPageNext = $('.cb-participation-pagination-next');
	const cbParticipationPageLast = $('.cb-participation-pagination-last');
	const cbParticipationPagePrev = $('.cb-participation-pagination-previous');
	const cbParticipationPageFirst = $('.cb-participation-pagination-first');

	const cbApplicantId = $('input[name=cb_applicant_id]').val();
	const entryTable = $('#cb-participation-table');
	const entryTableHeaderRow = $('#cb-participation-table tr')[0];
	let currentPage = $('.cb-participation-pagination-button.active').attr('data-cb-participation-page');
	let cbTotalEntries = 0;


	/**
	 * CB Get Total Entries
	 *
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {int}
	 *
	 */
	const cbGetTotalEntries = async (status = '', eventType = '') => {

		if ('string' !== typeof (status)) {
			status = 'new';
		}
		
		let retval = await $.ajax({
			method: "GET",
			url: cb_upload.total,
			data: {
				status: status,
				event_type: eventType,
				applicant_id: cbApplicantId
			},
			success: (x) => {
				cbTotalEntries = parseInt(JSON.parse(JSON.parse(x)[0].total_count));
				return cbTotalEntries;
			},
			error: (e) => {
				console.log(e)
			}
		});

		return retval;

	}

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
				class: `cb-participation-pagination-button cb-participation-pagination-numbered ${(k === page ? ' active' : '')}`,
				'data-cb-participation-page': k,
				text: k
			});
			$('.cb-participation-pagination-next').before(paginationButton);
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
				$(el).attr('data-cb-participation-page', k);
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

		let currentButtons = $('.cb-participation-pagination-numbered');

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
	 * CB Setup Pagination Ends
	 *
	 * @param {int} page
	 * @param {int} prev
	 * @param {int} next
	 * @param {int} last
	 * @returns {void}
	 */
	function cbSetupPaginationEnds(page = 1, prev = 0, next = 2, last = 0) {

		cbParticipationPagePrev.attr('data-cb-participation-page', prev);
		cbParticipationPageNext.attr('data-cb-participation-page', next);

		cbParticipationPageNext.toggleClass('disabled', (page === last));
		cbParticipationPageLast.toggleClass('disabled', (page === last));
		cbParticipationPagePrev.toggleClass('disabled', (page === 1));
		cbParticipationPageFirst.toggleClass('disabled', (page === 1));

	}

	/**
	 * CB Pagination
	 *
	 * @param {int} page
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {void}
	 */
	let cbPagination = async (page, status, eventType) => {

		await cbGetTotalEntries(status, eventType);
		if (cbTotalEntries > 0) {

			let last = Math.ceil(cbTotalEntries / 6);
			let prev = page - 1;
			let next = page + 1;

			cbParticipationPageLast.attr('data-cb-participation-page', last);

			cbSetupPaginationEnds(page, prev, next, last);
			cbSetupPaginationDigits(page, last);

		} else {
			cbParticipationPageLast.toggleClass('disabled', true);
			cbParticipationPageNext.toggleClass('disabled', true);
			cbParticipationPagePrev.toggleClass('disabled', true);
			cbParticipationPageFirst.toggleClass('disabled', true);
			$('.cb-participation-pagination-numbered').remove();
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
		let $emptyNotice = $(`<div class='cb-participation-empty-notice'><p style="margin-bottom: 0;">${response}</p></div>`);
		$emptyNotice.css({
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
		entryTable.append($emptyNotice);

	}

	/**
	 * Format Header Row
	 *
	 * @returns {void}
	 */
	function formatHeaderRow() {
		let headers = ['Status', 'Applicant', 'Event Date', 'Modified', 'Type', 'Notes'];
		let headerRow = $('<tr>');
		headers.forEach((header) => {
			let item;

			item = $(`<th id="cb_participation_table_header_${header}">${header.replaceAll('_', ' ')}</th>`);
			item.css({
				textTransform: 'capitalize',
				fontWeight: 'lighter'
			});

			headerRow.append(item);
		});
		entryTable.append(headerRow);
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

		let participationApplicantId = parseInt(participation.applicant_id);
		let eventType = participation.event_type.charAt(0).toUpperCase() + participation.event_type.slice(1).replace('_', ' ').replace('\\', '');
		let eventNote = participation.event_note.replace('\\', '');
		let eventDate = cbFormatDate(participation.event_date);
		let transactionId = (null != participation.transaction_id) ? parseInt(participation.transaction_id) : 0;
		let dateModified = cbFormatDate(participation.date_modified);

		let { username, userDisplayName } = await cbGetUserData(participationApplicantId);

		let $entryDataContainer = $('<td class="cb-participation-entry-data-container">');
		let $entryModule = $('<tr class="cb-participation-entry">');
		//		let $entryCheckboxContainer = $($entryDataContainer).clone();
		let $entryStatusContainer = $($entryDataContainer).clone();
		let $entryApplicantNameContainer = $($entryDataContainer).clone();
		let $entryEventDateContainer = $($entryDataContainer).clone();
		let $entryModifiedDateContainer = $($entryDataContainer).clone();
		let $entryEventNoteContainer = $($entryDataContainer).clone();
		let $entryEventTypeContainer = $($entryDataContainer).clone();
		let $viewAttachmentsDataContainer = $entryDataContainer.clone();
		/* let $entryEditDataContainer = $($entryDataContainer).clone(); 
		let $viewAttachmentsContainer = $(
			"<div>",
			{ class: `cb-participation-view-attachments-container
cb-participation-admin-entry-data`
			}
		);
		let $entryCheckbox = $(`<input type="checkbox" name="cb_participation_admin_entry_selection" 
data-cb-participation-id="${participation.id}"
data-cb-event-type="${participation.event_type}"
data-cb-applicant-id="${participation.applicant_id}"
data-cb-event-note="${participation.event_note}"
date-cb-event-date="${participation.event_date}"
data-cb-applicant-name="${userDisplayName}"
data-cb-transaction-id="${transactionId}"
value="${participation.id}" class="cb-participation-admin-entry-selection" />`);


		let mediaFiles = participation.media_filepath.split(', ');
		*/
		let $entryStatus = $('<p>', {
			class: `cb-participation-entry-data cb-participation-status-${participation.status}`,
			text: participation.status.charAt(0).toUpperCase() + participation.status.slice(1)
		});
		let $entryApplicantName = $('<a>', {
			class: "cb-participation-entry-data cb-participation-entry-applicant-name",
			href: `https://teamctg.com/members/${username}/`,
			text: userDisplayName
		});
		let $entryEventDate = $(
			`<p class='cb-participation-entry-data'><b>${eventDate}</b></p>`
		);
		let $entryModifiedDate = $(
			`<p class='cb-participation-entry-data'>${dateModified}</p>`
		);
		let $entryEventType = $('<p>', {
			class: 'cb-participation-entry-data',
			text: `${eventType}`
		});
		let $entryEventNote = $('<p>', {
			class: 'cb-participation-entry-data cb-participation-entry-event-note',
			text: `${eventNote}`
		});
		/*
		let $viewAttachmentsLink = $("<a class='cb-participation-view-attachments-link' href='#cb-participation-file-viewer-wrapper'>View Attachments</a>");

		for (let file of mediaFiles) {
			let mediaContainer = $("<div>", {
				class: "cb-media-urls",
				style: 'display:none;',
				'data-cb-media-url': 'https://teamctg.com/wp-content/uploads/confetti-bits/' + file
			});
			$viewAttachmentsContainer.append(mediaContainer);
		}
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
*/
		$entryStatusContainer.addClass("cb-participation-entry-status");

		//		$entryCheckboxContainer.append($entryCheckbox);
		$entryStatusContainer.append($entryStatus);
		$entryEventTypeContainer.append($entryEventType);
		$entryApplicantNameContainer.append($entryApplicantName);
		$entryEventDateContainer.append($entryEventDate);
		$entryModifiedDateContainer.append($entryModifiedDate);
		$entryEventTypeContainer.append($entryEventType);
		$entryEventNoteContainer.append($entryEventNote);
		/*
		$viewAttachmentsContainer.append($viewAttachmentsLink);
		$viewAttachmentsDataContainer.append($viewAttachmentsContainer);
		$entryEditDataContainer.append($entryEditButton);
		*/
		//		$entryModule.append($entryCheckboxContainer);
		$entryModule.append($entryStatusContainer);
		$entryModule.append($entryApplicantNameContainer);
		$entryModule.append($entryEventDateContainer);
		$entryModule.append($entryModifiedDateContainer);
		$entryModule.append($entryEventTypeContainer);
		$entryModule.append($entryEventNoteContainer);
		/*
		$entryModule.append($viewAttachmentsDataContainer);
		$entryModule.append($entryEditDataContainer);
		*/

		entryTable.append($entryModule);

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
	function refreshTable(page, status, eventFilter) {

		let activePanel = $('.cb-participation-nav-item.active');

		$.get({
			url: cb_upload.paged,
			data: {
				status: status,
				page: page,
				per_page: 6,
				event_type: eventFilter,
				applicant_id: cbApplicantId,
			},
			success: function (data) {
				cbPagination(page, status, eventFilter);
				let response = $.parseJSON(data);

				if (typeof (response) !== 'string') {
					//					activePanel.children().remove();
					entryTable.children().remove();

					formatHeaderRow();
					for (let r of response) {
						cbCreateParticipationEntry(r);
					}
				} else {
					cbCreateEmptyParticipationNotice(response, activePanel);
				}
			}
		});
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
			}
		});
		return retval;
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
		//		let activePanel = $($('.cb-participation-nav-item.active').find('a').attr('href'));
		let activeItem = $('.cb-participation-nav-item.active');
		//		let id = $this.attr('href');

		//		activePanel.removeClass('active');
		activeItem.removeClass('active');
		//		activePanel = $(id);
		$this.parent().addClass('active');
		//		activePanel.addClass('active');
		let status = $this.parent().attr('cb-participation-status-type');
		let eventFilter = $('#cb_participation_event_type_filter').val();

		refreshTable(1, status, eventFilter);

	}
	/*
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
	*/

	$('.cb-participation-nav').on(
		'click',
		'.cb-participation-nav-link',
		function (e) {
			e.preventDefault();
			handleNavigation(this);
		});


	$(document).on(
		'change', '#cb_participation_event_type_filter',
		function (e) {
			e.preventDefault();
			let page = parseInt($('.cb-participation-pagination-button.active').attr('data-cb-participation-page'));
			if ( ! page ) {
				page = 1;
			}
			let activePanel = $('.cb-participation-admin-panel.active');
			let status = activePanel.attr('cb-participation-status-type');
			let eventFilter = $('#cb_participation_event_type_filter').val();

			refreshTable(page, status, eventFilter);
		}
	);


	$('.cb-participation-pagination').on('click', '.cb-participation-pagination-button', function (e) {

		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-participation-page'));
		let eventFilter = $('#cb_participation_event_type_filter').val();
		let activePanel = $('.cb-participation-nav-item.active');
		let status = activePanel.attr('cb-participation-status-type');

		refreshTable(page, status, eventFilter);

	});

	/*	
    // Add event listener to each row checkbox
	$(document).on('change', '.cb-participation-admin-entry-selection', handleRowCheckboxChange);

	// Add event listener to the header checkbox
	$(document).on('change','#cb_participation_admin_bulk_edit_toggle', handleHeaderCheckboxChange);
	*/

	formatHeaderRow();
	refreshTable(1, 'new', '');

	const participationEventSelector	= $('.cb-form-selector[name=cb_participation_event_type]');
	const participationEventNote		= $('.cb-form-textbox[name=cb_participation_event_note]');
	const participationEventDate		= $('.cb-form-datepicker[name=cb_participation_event_date]');
	const eventNoteContainer			= participationEventNote.parents('ul.cb-form-page-section')[0];
	const participationUploadForm		= $('#cb-participation-upload-form');
	const mediaFilesContainer			= document.getElementById('cb-media-selection');
	const applicantId					= $('input[name=cb_applicant_id]');
	let substituteToggle = $('input[name=cb_participation_substitute]');
	let substituteInput = $('#cb_participation_substitute_member');
	let substituteContainer = $('.cb-participation-substitute-member-container');
	let closeButton = $('.cb-close');

	let formMessage = new function () {
		this.element	= $('.cb-feedback-message');
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
			this.element.text(text);
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
/*
	if ( parseInt(cbApplicantId) !== 5 ) {
		let cbDropzone = new Dropzone("div#cb-dropzone", { 
			url: cb_upload.upload,
			uploadMultiple: false,
			paramName: 'cb_participation_image_uploads',
			maxFiles: 1,
			acceptedFiles: 'image/jpg, image/jpeg, image/png, image/heic, image/heif',
			addRemoveLinks: true,
			previewsContainer: '#cb-previews-container',
			dictRemoveFile: '',
			dictMaxFilesExceeded: '',
			dictCancelUpload: '',
			parallelUploads: 1,
			autoProcessQueue: false
		});

		cbDropzone.on("addedfile", async function ( file ) {

		});

		cbDropzone.on("success", async ( file ) => {

			let uuid		= await file.upload.uuid;
			let data		= await JSON.parse(file.xhr.response);

			$.ajax({
				type: 'POST',
				url: cb_upload.create,
				data: {
					'cb_applicant_id': applicantId.val(),
					'cb_participation_event_type'	: participationEventSelector.val(),
					'cb_participation_event_note'	: participationEventNote.val(),
					'cb_participation_event_date'	: participationEventDate.val(),
					'cb_participation_upload_nonce' : cb_upload.nonce,
					'cb_participation_media_file'	: data.filename
				},
				success: function ( text ) {
					let response = JSON.parse(text);
					formMessage.setMessage( response.response, response.success ? 'success' : 'error' );
					participationEventSelector.val('');
					participationEventNote.val('');
					participationEventDate.val('');
					file.previewElement.remove();
					cbDropzone.removeAllFiles();
				},
				error: function ( text ) {
					let response = JSON.parse(text);
					formMessage.setMessage( response.response, 'error' );
				}
			});

		});

		cbDropzone.on("complete", function ( file ) {
			cbDropzone.removeAllFiles();
		});
	}
*/
	substituteToggle.on('click', () => {
		if ( substituteToggle.is(':checked') ) {
			substituteContainer.show(300);
			substituteInput.prop( 'disabled', false );
		} else {
			substituteContainer.hide(300);
			substituteInput.prop( 'disabled', true );
		}
	});

	$(substituteInput).on( 'input', (e) => {

		if ( e.target.value === '') {
			$('.cb-participation-member-search-results').remove();
		} else {

			$.get( 'https://teamctg.com/wp-json/buddyboss/v1/members', {
				page: 1,
				per_page: 5,
				search: e.target.value,
				type: 'alphabetical',
				bp_ps_search: [1,2],
			}, function( data ) {

				let result = $('<li class="cb-participation-member-search-results">');
				let resultItem = $('<span>');

				$('.cb-participation-member-search-results').remove();

				if ( data.length === 0 ) {
					resultItem.text('No results found.');
					result.append(resultItem);
				} else {
					for ( let user of data ) {
						let resultSelect = $('<li class="cb-participation-member-search-result">');
						$(resultSelect).text(user.name);
						$(resultSelect).attr('data-cb-participant-id', user.id );
						result.append(resultSelect);
					}
				}

				substituteContainer.append(result);

			});
		}

	});

	participationEventSelector.change( (e) => {

	});

	participationUploadForm.on( 'submit', async (e) => {

		e.preventDefault();
		e.stopPropagation();
		let now		= new Date();
		let month 	= now.getUTCMonth();
		let prev	= now.getUTCMonth(now.setUTCMonth( month - 1 ));
		let inputMonth	= new Date( participationEventDate.val() ).getUTCMonth();

		if ( ( undefined === typeof( participationEventSelector.val() ) || 
			  '' === participationEventSelector.val() ) && 
			( '' === participationEventNote.val() ) ) {
			formMessage.setMessage( 'Empty or invalid event type.', 'error' );
		} else if ( month !== inputMonth && prev !== inputMonth ){
			formMessage.setMessage( 'Cannot submit participation from outside of current or previous month.', 'error' );
		} /* else if ( parseInt(cbApplicantId) !== 5 ) {
			if ( cbDropzone.files.length < 1 ) {
				formMessage.setMessage( 'No files selected.', 'error' );	
			} else {
				await cbDropzone.processQueue();	
			}
		} */ else {
			$.ajax({
				type: 'POST',
				url: cb_upload.create,
				data: {
					'cb_applicant_id': applicantId.val(),
					'cb_participation_event_type'	: participationEventSelector.val(),
					'cb_participation_event_note'	: participationEventNote.val(),
					'cb_participation_event_date'	: participationEventDate.val(),
					'cb_participation_upload_nonce' : cb_upload.nonce
				},
				success: function ( text ) {
					let response = JSON.parse(text);
					formMessage.setMessage( response.response, response.success ? 'success' : 'error' );
					participationEventSelector.val('');
					participationEventNote.val('');
					participationEventDate.val('');
				},
				error: function ( text ) {
					let response = JSON.parse(text);
					formMessage.setMessage( response.response, 'error' );
				}
			});
		}
	});
	$(document).on( 'click', '.cb-participation-member-search-result', function () {
		substituteInput.val( $(this).text() );
		applicantId.val( $(this).data('cbParticipantId') );
		$('.cb-participation-member-search-results').remove();
	});


});