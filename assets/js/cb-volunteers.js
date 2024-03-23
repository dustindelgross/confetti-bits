import { MemberSelection, EventSelection, appendAlert } from './cb-core-modules.js';

jQuery(document).ready(function($) {

	const component = 'transactions_volunteers';
	const spotBonusForm = $(`#cb_${component}_form`);
	const userIDInput = $(`#cb_${component}_user_id`);
	const dateInput = $(`#cb_${component}_calendar_date_input`);
	const adminID = cb_volunteers.user_id;
	MemberSelection({component: component, apiEndpoint: `${cb_volunteers.home_url}/wp-json/buddyboss/v1/members` });
	EventSelection({component: component, apiEndpoint: cb_volunteers.get_events});

	function resetInputs() {

		$(`#cb_${component}_user`).val('');
		$(`#cb_${component}_user_id`).val('');
		$(`#cb_${component}_event`).val('');
		$(`#cb_${component}_event_id`).val('');
		$(`#cb_${component}_hours`).val('');

	}
	//	console.log(cb_volunteers.new_volunteers);

	$('#cb_transactions_volunteers_form').on('submit', async function(e) {

		e.preventDefault();
		let response = '';

		/*
		let data = {
			event_id: $(`#cb_${component}_event_id`).val(),
			recipient_id: $(`#cb_${component}_user_id`).val(),
			sender_id: cb_volunteers.user_id,
			hours: $(`#cb_${component}_hours`).val(),
			api_key: cb_volunteers.api_key
		};
		*/

		await $.ajax({
			url: cb_volunteers.new_volunteers,
			method: 'POST',
			data: {
				event_id: $(`#cb_${component}_event_id`).val(),
				recipient_id: $(`#cb_${component}_user_id`).val(),
				sender_id: cb_volunteers.user_id,
				hours: $(`#cb_${component}_hours`).val(),
				api_key: cb_volunteers.api_key
			},
			success: ({text, type}) => {
				appendAlert(text, type);
				response = text;
				resetInputs();
			},
			error: err => console.error(err)
		});

	});


});