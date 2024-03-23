/** 
 * Converts a valid date string to MySQL format.
 * 
 * @param {string} dateString A string that gets passed to a Date object.
 * 
 * @returns {string} A MySQL-friendly datetime string in 'Y-m-d H:i:s' format.
 */
export const toSQLDate = (dateString) => {
	return new Date(dateString).toISOString().slice(0,19).replace('T', ' ');
}

/**
 * Converts the given DateInput values to a UTC MySQL string.
 * 
 * `component` here refers to the snake_cased string that's used to build the
 * template form for the given input. For example, `events_admin` would 
 * be used as a prefix for a `cb_events_admin_{{input_name}}` input.
 * 
 * `input` is going to be the string passed to the input element via our
 * templating structure when building the forms. So for example, passing
 * `event_start` here, in addition to the example above, would output
 * `cb_events_admin_event_start` as the total prefix for the date and time
 * inputs. This function is primarily used to process user inputs from 
 * our custom date/time input elements in our templating format.
 * 
 * @param {string} component The template-friendly component name for the input.
 * @param {string} input The template-friendly name for the input.
 */
export const toMySQLUTCDate = (component, input) => {
	component = `cb_${component}`;
	let timeSelectorPrefix = (modifier) => `#${component}_${input}_time_selector_${modifier}`;
	let dateInput = jQuery(`#${component}_${input}_calendar_date_input`).val();
	let dateParts = dateInput.split("/");
	let formattedDate = `${dateParts[2]}-${dateParts[0].padStart(2, '0')}-${dateParts[1].padStart(2, '0')}`;
	let meridiem = jQuery(timeSelectorPrefix('meridiem')).val();
	let hour = parseInt(jQuery(timeSelectorPrefix('hour')).val());
	let minute = jQuery(timeSelectorPrefix('minute')).val();

	if (meridiem === "PM" && hour !== 12) {
		hour += 12;
	} else if (meridiem === "AM" && hour === 12) {
		hour = 0;
	}

	let formattedTime = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:00`;

	return new Date(`${formattedDate}T${formattedTime}`).toISOString().slice(0,19).replace('T', ' ');

}

export const toStandardDate = (dateString) => {

	const dateOptions = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
	return new Date(dateString).toLocaleString('en-US', dateOptions);
}

export const appendAlert = (message, type) => {
	const alert = jQuery('#cb_alert_container');
	const wrapper = jQuery('<div>');
	const validTypes = [ 'primary', 'secondary', 'info', 'danger', 'success' ];

	type = validTypes.includes(type) ? type : 'info';

	wrapper.append(
		jQuery(`<div class="cb-alert alert alert-${type} alert-dismissible fade show d-flex align-items-center justify-content-between" role="alert">
<div>${message}</div>
<button class="cb-alert-close btn btn-outline-${type}" data-bs-dismiss="alert" aria-label="Close">Got it</button>
</div>
`));

	alert.append(wrapper);

	alert.on('click', '.cb-alert-close', function(e) {
		alert.empty();
	});

}

export const appendConfirmation = (message, actionCallback, options = {}) => {

	const alert = jQuery('#cb_alert_container');
	const wrapper = jQuery('<div>');
	const {
		messageType = 'primary',
		actionButton = 'danger',
		cancelText = 'Nevermind',
		actionButtonText = 'Yes, Continue'
	} = options || {};

	function cleanup() {
		alert.off('click', '.cb-alert-cancel');
		alert.off('click', '.cb-alert-confirm');
		alert.empty();
	}

	wrapper.append(
		jQuery(`<div class="cb-alert alert alert-${messageType} alert-dismissible fade show d-flex align-items-center justify-content-between gap-3" role="alert">
<div>${message}</div>
<div class="d-flex gap-2 items-center">
<button class="cb-alert-cancel btn btn-secondary" data-bs-dismiss="alert" aria-label="Cancel">${cancelText}</button>
<button class="cb-alert-confirm btn btn-${actionButton}" data-bs-dismiss="alert" aria-label="Proceed">${actionButtonText}</button>
</div>
</div>
`));

	alert.append(wrapper);

	alert.off('click', '.cb-alert-cancel').on('click', '.cb-alert-cancel', function(e) {
		cleanup();
	});

	alert.off('click', '.cb-alert-confirm').on('click', '.cb-alert-confirm', async function(e) {
		await actionCallback();
		cleanup();
	});

}

export const MemberSelection = ( userConfig = {} ) => {

	const defaultConfig = {
		component: '',
		apiEndpoint: '', 
	};

	const config = { ...defaultConfig, ...userConfig, component: `cb_${userConfig.component}` };
	const withDashes = config.component.replaceAll('_', '-');
	const input = jQuery(`#${config.component}_user`);
	const results = jQuery(`#${config.component}_search_results`);
	const selected = jQuery(`#${config.component}_user_id`);


	input.on('input', async function(e) {

		let searchTerms = jQuery(this).val();
		selected.val('');

		if ( searchTerms === '') {
			results.empty();
		} else {
			await jQuery.ajax({
				url: config.apiEndpoint,
				method: 'GET',
				data: {
					page: 1,
					per_page: 5,
					search: searchTerms,
					type: 'alphabetical',
					bp_ps_search: [1,2]
				},
				success: function( data ) {
					results.empty();
					if ( data.length === 0 ) {
						let result = jQuery(`<li class="${withDashes}-member-search-result empty">`);
						result.text('No results found.');
						results.append(result);
					} else {
						for ( let user of data ) {
							let result = jQuery(`<li class="${withDashes}-member-search-result list-group-item list-group-item-action">`);
							jQuery(result).text(user.name);
							jQuery(result).attr(`data-user-id`, user.id );
							results.append(result);
						}
					}
				},
				error: err => console.error(err)
			});
		}

		results.on( 'click', `.${withDashes}-member-search-result`, async function(e) {

			input.val(e.target.textContent);
			selected.val(e.target.dataset.userId);
			results.empty();
			selected.val();
		});
	});
}

export const EventSelection = ( userConfig = {} ) => {

	const defaultConfig = {
		component: '',
		apiEndpoint: '', 
	};

	const config = { ...defaultConfig, ...userConfig, component: `cb_${userConfig.component}` };
	const withDashes = config.component.replaceAll('_', '-');
	const input = jQuery(`#${config.component}_event`);
	const results = jQuery(`#${config.component}_event_search_results`);
	const selected = jQuery(`#${config.component}_event_id`);

	input.on('input', async function(e) {

		let searchTerms = jQuery(this).val();
		selected.val('');

		if ( searchTerms === '') {
			results.empty();
		} else {
			await jQuery.ajax({
				url: config.apiEndpoint,
				method: 'GET',
				data: {
					page: 1,
					per_page: 5,
					event_title: searchTerms,
					api_key: cb_volunteers.api_key
				},
				success: function({text, type}) {
					results.empty();
					if ( text.length === 0 ) {
						let result = jQuery(`<li class="${withDashes}-event-search-result empty">`);
						result.text('No results found.');
						results.append(result);
					} else {
						for ( let event of text ) {
							let result = jQuery(`<li class="${withDashes}-event-search-result list-group-item list-group-item-action">`);
							jQuery(result).text(event.event_title);
							jQuery(result).attr(`data-event-id`, event.id );
							results.append(result);
						}
					}
				},
				error: err => console.error(err)
			});
		}

		results.on( 'click', `.${withDashes}-event-search-result`, async function(e) {
			results.empty();
			input.val(e.target.textContent);
			selected.val(e.target.dataset.eventId);
		});
	});
}

export const DateInput = (component) => {

	component = `cb_${component}`;
	const withDashes = component.replaceAll('_', '-');
	const days = jQuery(`#${component}_calendar_days`);
	const input = jQuery(`#${component}_calendar_date_input`);
	const calendar = jQuery(`.${withDashes}-calendar`);
	const monthDropdown = jQuery(`.${withDashes}-calendar-month-dropdown`);
	const yearDropdown = jQuery(`.${withDashes}-calendar-year-dropdown`);
	const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	const today = new Date();
	let currentMonth = today.getMonth();
	let currentYear = today.getFullYear();

	function updateCalendar(month, year) {

		days.empty();
		const firstDay = (new Date(year, month)).getDay();
		const daysInMonth = 32 - new Date(year, month, 32).getDate();
		monthDropdown.val(month);
		yearDropdown.val(year);


		for (let i = 0; i < firstDay; i++) {
			days.append('<span class="col d-block p-3" style="flex: 0 1 calc(100%/7);"></span>');
		}
		for (let i = 1; i <= daysInMonth; i++) {
			days.append(`<span class="col btn btn-light d-block rounded-full p-3 cursor-pointer" role="button" style="flex: 0 1 calc(100%/7);">${i}</span>`);
		}

	}

	input.on('focus', function () {
		calendar.removeClass('d-none');
	});

	jQuery(`.${withDashes}-calendar-prev-btn`).on('click', function () {
		currentYear = (currentMonth === 0) ? currentYear - 1 : currentYear;
		currentMonth = (currentMonth === 0) ? 11 : currentMonth - 1;
		updateCalendar(currentMonth, currentYear);
	});

	jQuery(`.${withDashes}-calendar-next-btn`).on('click', function () {
		currentYear = (currentMonth === 11) ? currentYear + 1 : currentYear;
		currentMonth = (currentMonth + 1) % 12;
		updateCalendar(currentMonth, currentYear);
	});

	days.on('click', 'span', function () {
		const selectedDay = jQuery(this).text();
		input.val(`${(currentMonth + 1).toString().padStart(2, '0')}/${selectedDay.padStart(2, '0')}/${currentYear}`);
		calendar.addClass('d-none');
	});

	// Start with current month
	updateCalendar(currentMonth, currentYear);

	// Populating month dropdown
	for (let i = 0; i < monthNames.length; i++) {
		monthDropdown.append(`<option value="${i}">${monthNames[i]}</option>`);
	}

	// Populating year dropdown (for simplicity, let's include a range of 10 years from the current year)
	for (let i = currentYear - 5; i <= currentYear + 5; i++) {
		yearDropdown.append(`<option value="${i}">${i}</option>`);
	}

	// Set default selected values for dropdowns
	monthDropdown.val(currentMonth);
	yearDropdown.val(currentYear);

	// Handling month and year dropdown changes
	jQuery(`.${withDashes}-calendar-month-dropdown, .${withDashes}-calendar-year-dropdown`).on('change', function () {
		currentMonth = parseInt(monthDropdown.val());
		currentYear = parseInt(yearDropdown.val());
		updateCalendar(currentMonth, currentYear);
	});

	jQuery(document).on('click', function(event) {
		if (!calendar.is(event.target) && calendar.has(event.target).length === 0 && !input.is(event.target)) {
			calendar.addClass('d-none');
		}
	});

	calendar.on('click', function(event) {
		event.stopPropagation();
	});
}

export const getUserName = async (userId) => {

	let retval = '';

	await jQuery.ajax({
		method: 'GET',
		url: 'https://teamctg.com/wp-json/buddyboss/v1/members',
		data: {
			user_ids: [userId]
		},
		success: ([firstUser, ...rest]) => retval = firstUser.name ?? 'Unkown Member',
		error: err => console.error(err)
	});

	return retval;

}

export const DataTable = async (userConfig = {}) => {

	const defaultConfig = {
		component: '', 
		tableHeaders: [],
		fetchCount: {
			url: '',
			method: 'GET',
			data: {count: true},
			success: ({text, type}) => totalItems = text[0]['total_count'],
			error: err => console.err(err)
		},
		fetchItem: {
			url: '',
			method: 'GET',
			data: {},
			success: res => console.log(res),
			error: err => console.err(err)
		},
		fetchList: {
			url: '',
			method: 'GET',
			data: {
				page: 1,
				per_page: 15,
			},
			success: e => console.log(e),
			error: err => console.error(err)
		},
		createEntry: async () => {},
		deleteItemCallback: async (itemID) => {},
		populateTable: true
	};

	const config = { ...defaultConfig, ...userConfig, component: `cb_${userConfig.component}` };

	const withDashes = config.component.replaceAll('_', '-');
	const withCamelCase = config.component.split('_').map((item,index) => {
		if ( index !== 0 ) {
			item = item.charAt(0).toUpperCase() + item.slice(1);
		}
		return item;
	}).join('');

	const table = jQuery(`#${config.component}_table`);
	const tableHeader = jQuery(`#${config.component}_table_header`);
	const tableBody = jQuery(`#${config.component}_table_body`);
	let totalItems;

	async function fetchItems() {

		try {
			let {text, type} = await jQuery.ajax(config.fetchCount);
			return parseInt(text[0].total_count);
		}
		catch (error) {
			console.error("Error in fetchItems:", error);
		}

	}


	function generatePaginationDigits(page, last) {

		let k;

		if (page >= last - 2 && ((last - 2) > 0)) {
			k = last - 2;
		} else if (page + 2 <= last) {
			k = page;
		} else if (last < 3) {
			k = 1;
		}

		for (k; (k <= (page + 2)) && (k <= last); k++) {

			let paginationButton = jQuery(
				`<li class='page-item ${withDashes}-page-item-numbered ${(k === page ? ' active' : '')}'>
<a href="#" class='page-link ${withDashes}-page-link numbered' data-${withDashes}-page="${k}" >${k}</a>
</li>`);
			jQuery(`.${withDashes}-page-link-next`).parent().before(paginationButton);
		}
	}

	function refactorPaginationDigits(page = 1, last = 0) {

		let paginationBars = jQuery(`#${config.component}_pagination`);
		let k = (page >= last - 2) && ((last - 2) > 0) ? last - 2 : page;

		if (k <= (page + 2) && k <= last) {
			paginationBars.each( (i, el) => {
				jQuery(el).find(`a.${withDashes}-page-link.numbered`).each((j, em) => {
					let link = jQuery(em);
					link.attr(`data-${withDashes}-page`, k).text(k);
					if (k === page) {
						link.parent().addClass('active');
					}
					k++;
				});
			});
		}
	}

	function setupPaginationDigits(page = 1, last = 0) {

		let currentButtons = jQuery(`.${withDashes}-page-item-numbered`);

		currentButtons.removeClass('active');

		if (currentButtons.length === 0) {
			generatePaginationDigits(page, last);
		} else {
			if (last < 3) {
				currentButtons.remove();
				generatePaginationDigits(page, last);
			} else {
				if (currentButtons.length < 3) {
					currentButtons.remove();
					generatePaginationDigits(page, last);
				} else {
					refactorPaginationDigits(page, last);
				}
			}
		}
	}

	function setupPaginationEnds(page = 1, prev = 0, next = 2, last = 0) {

		let firstButton = jQuery(`.${withDashes}-page-link-first`);
		let prevButton = jQuery(`.${withDashes}-page-link-prev`);
		let nextButton = jQuery(`.${withDashes}-page-link-next`);
		let lastButton = jQuery(`.${withDashes}-page-link-last`);

		firstButton.attr(`data-${withDashes}-page`, 1);
		prevButton.attr(`data-${withDashes}-page`, prev);
		nextButton.attr(`data-${withDashes}-page`, next);
		lastButton.attr(`data-${withDashes}-page`, last);
		
		firstButton.parent().toggleClass('disabled', (page === 1));
		prevButton.parent().toggleClass('disabled', (page === 1));
		nextButton.parent().toggleClass('disabled', (page === last));
		lastButton.parent().toggleClass('disabled', (page === last));
		
	}

	async function setupPagination (page) {

		let totalItems = await fetchItems();
		if ( totalItems > 0 ) {

			let last = Math.ceil(totalItems / 15);
			let prev = page - 1;
			let next = page + 1;

			jQuery(`.${withDashes}-page-link-last`).attr(`data-${withDashes}-page`, last);

			setupPaginationEnds(page, prev, next, last);
			setupPaginationDigits(page, last);

		} else {
			jQuery(`.${withDashes}-page-link-last`).parent().toggleClass('disabled', true);
			jQuery(`.${withDashes}-page-link-next`).parent().toggleClass('disabled', true);
			jQuery(`.${withDashes}-page-link-prev`).parent().toggleClass('disabled', true);
			jQuery(`.${withDashes}-page-link-first`).parent().toggleClass('disabled', true);
			jQuery(`.${withDashes}-page-link.numbered`).parent().remove();
		}
	}

	function formatHeaderRow() {

		config.tableHeaders.forEach((header) => {
			let item;
			let toText = header.replaceAll('_', ' ');
			item = jQuery(`<th scope="col" id="${config.component}_table_header_${header}">${toText}</th>`);
			item.css({
				textTransform: 'capitalize'
			});

			tableHeader.append(item);
		});

		tableHeader.append(jQuery(`<th scope="col" id="${config.component}_table_header_actions">Actions</th>`));

	}

	async function createEntry(entry) {

		let row = jQuery('<tr scope="row">');
		let users = ['applicant_id', 'recipient_id', 'sender_id', 'admin_id'];
		for ( let [key,value] of Object.entries(entry) ) {

			let tag = 'd';
			if ( key === 'id' ) {
				tag = 'h';
			}

			if ( key.includes('date') ) {
				value = toStandardDate(value);
			}
			if ( users.includes(key) ) {				
				value = await getUserName(value);
			}
			key = key.replaceAll('_','-');
			value = value ?? "None";
			let cell = jQuery(`<t${tag} ${tag === 'h' ? 'scope="row"' : ''} class="${withDashes}-item-${key}">${value}</t${tag}>`);
			await row.append(cell);
		}

		await row.append(jQuery(`<td class="${withDashes}-item-actions"><button class="btn btn-primary mx-1 ${withDashes}-edit-button" data-${withDashes}-item-id="${entry.id}">Edit</button><button class="mx-1 btn btn-outline-danger ${withDashes}-delete-button" data-${withDashes}-item-id="${entry.id}">Delete</button></td>`));

		await tableBody.append(row);
	}

	async function populateTable({ text, type }) {
		setupPagination(config.fetchList.data.page);
		let entries = [];
		if ( config.populateTable !== false ) {
			if (text !== false) {
				tableBody.empty();
				await text.sort((a,b) => parseInt(b.id) - parseInt(a.id) );
				text.forEach(async r => {
					await createEntry(r);
				});
			} else {
				tableBody.append(jQuery('<p>No items found.</p>'));
			}
		}
	}

	async function regenerateTable() {
		try {
			let entries = await jQuery.ajax(config.fetchList);

			populateTable(entries);

		} catch (error) {
			console.error("Error in regenerateTable:", error);
		}
	}

	regenerateTable();

	formatHeaderRow();

	jQuery(`.${withDashes}-pagination-container`).on('click', '.page-link', function(e) {
		e.preventDefault();
		let page = parseInt(jQuery(this).attr(`data-${withDashes}-page`));
		config.fetchList.data.page = page;
		regenerateTable();
	});

	jQuery(document).on('click', `.${withDashes}-delete-button`, async function(e) {

		e.preventDefault();

		let itemID = jQuery(this).data(`${withDashes}ItemId`);
		await config.deleteItemCallback(itemID);

	});

}