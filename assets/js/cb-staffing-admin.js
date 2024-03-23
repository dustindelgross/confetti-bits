import {toSQLDate, appendAlert, MemberSelection, DateInput, DataTable, appendConfirmation} from './cb-core-modules.js';

jQuery(document).ready(($) => {

	const component = 'transactions_spot_bonus';
	const spotBonusForm = $(`#cb_${component}_form`);
	const userIDInput = $(`#cb_${component}_user_id`);
	const dateInput = $(`#cb_${component}_calendar_date_input`);
	const adminID = cb_staffing_admin.user_id;

	DataTable({
		component: 'transactions_spot_bonuses',
		tableHeaders: ['ID', 'sender', 'recipient', 'date_scheduled', 'transaction_id'],
		fetchCount: {
			url: cb_staffing_admin.get_spot_bonuses,
			method: 'GET',
			data: {
				count: true,
				api_key: cb_staffing_admin.api_key,
			},
			success: ({text, type}) => text[0].total_count,
			error: err => console.err(err)
		},
		fetchList: {
			url: cb_staffing_admin.get_spot_bonuses,
			method: 'GET',
			data: {
				per_page: 15,
				page: 1,
				api_key: cb_staffing_admin.api_key
			},
			success: e => e,
			error: err => console.error(err)
		},
		deleteItemCallback: async function (itemID) {
			jQuery.ajax({
				url: cb_staffing_admin.delete_spot_bonuses,
				method: 'DELETE',
				data: JSON.stringify({
					spot_bonus_id: itemID,
					api_key: cb_staffing_admin.api_key,
				}),
				success: ({text, type}) => appendAlert(text, type),
				error: err => console.error(err)
			});
		}
	});

	MemberSelection({component: component, apiEndpoint: `${cb_staffing_admin.home_url}/wp-json/buddyboss/v1/members` } );
	DateInput('transactions_spot_bonus');

	spotBonusForm.on('submit', async function(e) {
		
		e.preventDefault();

		await $.ajax({
			url: cb_staffing_admin.new_spot_bonuses,
			method: 'POST',
			data: {
				sender_id: cb_staffing_admin.user_id,
				recipient_id: userIDInput.val(),
				api_key: cb_staffing_admin.api_key,
				date: toSQLDate(dateInput.val())
			},
			success: ({text, type}) => appendAlert(text, type),
			error: ({text,type}) => appendAlert(text, 'danger')
		});
		
/*		await $.ajax({
			url: cb_staffing_admin.new_spot_bonuses,
			method: 'GET',
			data: {
				per_page: 15,
				page: 1,
				api_key: cb_staffing_admin.api_key
			},
			success: e => e,
			error: err => console.error(err)
		});*/


		return;

	});




});