jQuery(document).ready( function($) {

	const memberSearchInput = $('#cb_transactions_recipient_name');
	const recipientIdInput = $('#cb_transactions_recipient_id');
	const addActivityInput = $('#cb_transactions_add_activity');
	const logEntryInput = $('#cb_transactions_log_entry');
	const amountInput = $('#cb_transactions_amount');
	const memberSearchResults = $('#cb_transactions_member_search_results');
	const form = $('#cb_transactions_send_bits_form');


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
	
	function clearForm() {
		memberSearchInput.val('');
		recipientIdInput.val('');
		addActivityInput.val('');
		logEntryInput.val('');
		amountInput.val('');
	}

	$(memberSearchInput).on( 'input', (e) => {

		recipientIdInput.val('');
		if ( e.target.value === '') {
			memberSearchResults.empty();
		} else {

			$.get( 'https://teamctg.com/wp-json/buddyboss/v1/members', {
				page: 1,
				per_page: 5,
				search: e.target.value,
				type: 'alphabetical',
				bp_ps_search: [1,2],
			}, function( data ) {

				memberSearchResults.empty();

				if ( data.length === 0 ) {
					let result = $('<li class="cb-transactions-member-search-result empty">');
					result.text('No results found.');
					memberSearchResults.append(result);
				} else {
					for ( let user of data ) {
						let result = $('<li class="cb-transactions-member-search-result">');
						$(result).text(user.name);
						$(result).attr('data-cb-recipient-id', user.id );
						memberSearchResults.append(result);
					}
				}
			});
		}
	});

	memberSearchResults.on( 'click', '.cb-transactions-member-search-result', async function(e) {
		memberSearchResults.children().remove();
		memberSearchInput.val(e.target.textContent);
		recipientIdInput.val(e.target.dataset.cbRecipientId);
	});

	form.on( 'submit', async function(e) {

		e.preventDefault();
		e.stopPropagation();

		let postData = {
			recipient_id: $('#cb_transactions_recipient_id').val(),
			sender_id: cb_transactions.user_id,
			amount: $('#cb_transactions_amount').val(),
			log_entry: $('#cb_transactions_log_entry').val(),
			api_key: cb_transactions.api_key
		};

		if ( $('#cb_transactions_add_activity:checked').length === 1 ) {
			postData.add_activity = true;
		}
		await $.ajax({
			url: cb_transactions.new_transactions,
			method: 'POST',
			data: postData,
			success: e => formMessage.setMessage(e),
			error: (x) => console.error(x)
		});

		clearForm();

	});

});