import { appendAlert, DateInput, toSQLDate, toMySQLUTCDate } from './cb-core-modules.js';

jQuery( document ).ready( ( $ ) => {

	const inputs = {
		spotBonusAmount: $('#cb_settings_spot_bonus_amount'),
		volunteerAmount: $('#cb_settings_volunteer_hours_amount'),
		transferLimit: $('#cb_settings_transfer_limit'),
	};

	const settingsForm = $('#cb_settings_form');

	DateInput(`settings_reset_date`);
	DateInput(`settings_transactions_blackout_start`);
	DateInput(`settings_transactions_blackout_end`);

	async function setUpForm() {

		let prefix = 'cb_settings';
		let item = {};

		try {
			const params = new URLSearchParams({
				reset_date: true,
				transactions_blackout_start: true,
				transactions_blackout_end: true,
				admin_id: cb_settings.user_id,
				api_key: cb_settings.api_key
			});
			
			const response = await fetch(`${cb_settings.get_settings}?${params}`);
			if (!response.ok) {
				throw new Error(`Response status: ${response.status}`);
			}
			const json = await response.json();
			item = json.text;

		} catch (err) {
			console.error(err)
		}

		let resetDateObj = new Date(item.reset_date.replace(' ', 'T') + 'Z');
		let blackoutStartDateObj = new Date(item.transactions_blackout_start.replace(' ', 'T') + 'Z');
		let blackoutEndDateObj = new Date(item.transactions_blackout_end.replace(' ', 'T') + 'Z');
		let resetDateHours = resetDateObj.getHours();
		let blackoutStartHours = blackoutStartDateObj.getHours();
		let blackoutEndHours = blackoutEndDateObj.getHours();

		let resetDateParts = {
			day: resetDateObj.getDate(),
			month: resetDateObj.getMonth() + 1,
			year: resetDateObj.getFullYear(),
			hours: resetDateHours > 12 ? resetDateHours - 12: resetDateHours,
			minutes: resetDateObj.getMinutes().toString().padStart(2,'0'),
			meridiem: resetDateHours > 12 ? 'PM' : 'AM'
		};

		let blackoutStartParts = {
			day: blackoutStartDateObj.getDate(),
			month: blackoutStartDateObj.getMonth() + 1,
			year: blackoutStartDateObj.getFullYear(),
			hours: blackoutStartHours > 12 ? blackoutStartHours - 12 : blackoutStartHours,
			minutes: blackoutStartDateObj.getMinutes().toString().padStart(2,'0'),
			meridiem: blackoutStartHours > 12 ? 'PM' : 'AM',
		};

		let blackoutEndParts = {
			day: blackoutEndDateObj.getDate(),
			month: blackoutEndDateObj.getMonth() + 1,
			year: blackoutEndDateObj.getFullYear(),
			hours: blackoutEndHours > 12 ? blackoutEndHours - 12 : blackoutEndHours,
			minutes: blackoutEndDateObj.getMinutes().toString().padStart(2,'0'),
			meridiem: blackoutEndHours > 12 ? 'PM' : 'AM',
		};

		$(`#${prefix}_reset_date_calendar_date_input`).val(`${resetDateParts.month.toString().padStart(2, '0')}/${resetDateParts.day.toString().padStart(2, '0')}/${resetDateParts.year}`);
		$(`#${prefix}_reset_date_time_selector_hour`).val(resetDateParts.hours);
		$(`#${prefix}_reset_date_time_selector_minute`).val(resetDateParts.minutes);
		$(`#${prefix}_reset_date_time_selector_meridiem`).val(resetDateParts.meridiem);

		$(`#${prefix}_transactions_blackout_start_calendar_date_input`).val(`${blackoutStartParts.month.toString().padStart(2, '0')}/${blackoutStartParts.day.toString().padStart(2, '0')}/${blackoutStartParts.year}`);
		$(`#${prefix}_transactions_blackout_start_time_selector_hour`).val(blackoutStartParts.hours);
		$(`#${prefix}_transactions_blackout_start_time_selector_minute`).val(blackoutStartParts.minutes);
		$(`#${prefix}_transactions_blackout_start_time_selector_meridiem`).val(blackoutStartParts.meridiem);

		$(`#${prefix}_transactions_blackout_end_calendar_date_input`).val(`${blackoutEndParts.month.toString().padStart(2, '0')}/${blackoutEndParts.day.toString().padStart(2, '0')}/${blackoutEndParts.year}`);
		$(`#${prefix}_transactions_blackout_end_time_selector_hour`).val(blackoutEndParts.hours);
		$(`#${prefix}_transactions_blackout_end_time_selector_minute`).val(blackoutEndParts.minutes);
		$(`#${prefix}_transactions_blackout_end_time_selector_meridiem`).val(blackoutEndParts.meridiem);
	}
	/*************** Event Listeners ***************/


	settingsForm.on('submit', async function(e) {

		e.preventDefault();

		let resetDate = toMySQLUTCDate('settings', 'reset_date');
		let blackoutStart = toMySQLUTCDate('settings', 'transactions_blackout_start');
		let blackoutEnd = toMySQLUTCDate('settings', 'transactions_blackout_end');
		let patchData = {
			reset_date: resetDate,
			transactions_blackout_start: blackoutStart,
			transactions_blackout_end: blackoutEnd,
			blackout: $('#cb_settings_transactions_blackout_active').is(':checked'),
			spot_bonus_amount: inputs.spotBonusAmount.val(),
			volunteer_amount: inputs.volunteerAmount.val(),
			transfer_limit: inputs.transferLimit.val(),
			admin_id: cb_settings.user_id,
			api_key: cb_settings.api_key
		};

		await $.ajax({
			url: cb_settings.update_settings,
			method: 'PATCH',
			data: JSON.stringify(patchData),
			success: ({text, type}) => appendAlert(text, type),
			error: err => console.error(err), 
		});

	});

	setUpForm();

});