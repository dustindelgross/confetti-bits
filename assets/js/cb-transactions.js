jQuery(document).ready( function($) {

	const memberSearchInput = $('#cb_transactions_recipient_name');
	const recipientIdInput = $('#cb_transactions_recipient_id');
	const addActivityInput = $('#cb_transactions_add_activity');
	const logEntryInput = $('#cb_transactions_log_entry');
	const amountInput = $('#cb_transactions_amount');
	const memberSearchResults = $('#cb_transactions_member_search_results');
	const form = $('#cb_transactions_send_bits_form');

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
					this.element.css('color', this.style[t.type] );
				});
			} else if ( typeof(text) === 'string' ) {
				this.element.remove();
				let p = this.p;
				p.css({
					'color': this.style[type]
				}).text(text);
				this.container.append(p);
			}

			this.container.css({ 
				display: 'flex',
				border: '1px solid #dbb778',
				borderRadius: '10px',
				margin: '1rem auto'
			});

			window.scrollTo({top: this.container.offset().top - 135, behavior: 'smooth'})
		}

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
			memberSearchResults.children().remove();
		} else {

			$.get( 'https://teamctg.com/wp-json/buddyboss/v1/members', {
				page: 1,
				per_page: 5,
				search: e.target.value,
				type: 'alphabetical',
				bp_ps_search: [1,2],
			}, function( data ) {

				memberSearchResults.children().remove();

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
		let values = Object.fromEntries(form.serializeArray().map( (object) => {
			return [ object.name.replace( 'cb_transactions_', '' ), object.value ];
		}));
		let send = $.ajax({
			url: cb_transactions.send,
			method: 'POST',
			data: values,
			success: (response) => {
				console.log(response);
				let data = JSON.parse(response);
				formMessage.setMessage(
					data.text,
					data.type
				);
			},
			error: (response) => {
				let responseObj = JSON.parse(response.responseText);
				let data = {
					text: `Error code ${response.status}: ${response.statusText}. ${responseObj.text}.`,
					type: responseObj.type
				}
				formMessage.setMessage(
					data.text,
					data.type
				);
			},
			complete: [clearForm()]
		});
	});

});