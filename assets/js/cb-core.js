jQuery( document ).ready( function( $ ) {

	const requestAmount = $('#cb_request_option');
	const memberName = $('.memberName');
	const submitMessage = $('.submission-message-popup');
	const requestSubmitConfirm = $('#cb_request_form');
	const closeNoticeContainer = $('.cb-close-notice-container');
	const $hubNav = $('.cb-hub-nav-container');
	const transactionsTable = $('#cb_transactions_table');
	const cbTransactionPageNext = $('.cb-transactions-pagination-next');
	const cbTransactionPageLast = $('.cb-transactions-pagination-last');
	const cbTransactionPagePrev = $('.cb-transactions-pagination-previous');
	const cbTransactionPageFirst = $('.cb-transactions-pagination-first');

	let transferToID = document.querySelector("#transfer_user_id");
	let sendToID = document.querySelector("#recipient_id");
	let transferToName = document.querySelector("#transfer_member_display_name");
	let sendToName = document.querySelector("#recipient_name");
	let sendToAmount = document.querySelector("#cb_request_amount");
	let userID = parseInt(cb_core.user_id);
	let activeTab = window.location.hash;
	let cbTotalTransactions = null;

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

	if ( activeTab ) {

		let $tab	= $($hubNav).find('div.cb-hub-nav-item.active');
		let $link	= $tab.find('a');
		let $module	= $($link.attr('href'));
		let $activeModule	= $('.cb-container.active');

		$tab.removeClass('active');
		$activeModule.removeClass('active');

		$module = $(activeTab).addClass('active');
		$tab	= $("a[href='" + activeTab + "']").parent().addClass('active');

	}

	$($hubNav).each( function() {

		let $this	= $(this);									
		let $tab	= $this.find('div.cb-hub-nav-item.active');	
		let $link	= $tab.find('a');
		let $module	= $($link.attr('href'));

		$this.on('click', '.cb-hub-nav-link', function ( e ) {
			e.preventDefault();
			let $link	= $(this);
			let id		= this.hash;

			if ( id && ! $link.is('.active') ) {

				$module.removeClass('active');	
				$tab.removeClass('active');

				$module = $(id).addClass('active');
				$tab	= $link.parent().addClass('active');

			}

		});

	});


	closeNoticeContainer.click( function() {
		closeNoticeContainer.parents('.cb-notice').slideUp( function(){
			closeNoticeContainer.parents('.cb-notice').remove();
			return false;
		});
	});
/*
	requestSubmitConfirm.submit( function() {

		if ( confirm("Are you sure you want to spend " + sendToAmount.value + " Confetti Bits? They will be deducted from your total balance and will no longer count toward future purchases.") ) {

			return true;

		} else {

			return false;

		}

	});	
*/
	submitMessage.ready().fadeIn();

	function unReadNotifications () {
		var notification_queue = [];
		$( document ).on(
			"click",
			".cb-read-all-notifications",
			function ( e ) {
				var data = {
					'action': 'buddyboss_theme_unread_notification',
					'notification_id': $( this ).data( 'notification-id' )
				};
				if ( notification_queue.indexOf( $( this ).data( 'notification-id' ) ) !== -1 ) {
					return false;
				}
				notification_queue.push( $( this ).data( 'notification-id' ) );
				var notifs = $( '.bb-icon-bell' );
				var notif_icons = $( notifs ).parent().children( '.count' );
				if ( notif_icons.length > 0 ) {
					if ( $( this ).data( 'notification-id' ) !== 'all' ) {
						notif_icons.html( parseInt( notif_icons.html() ) - 1 );
					} else {
						if ( parseInt( $( '#header-notifications-dropdown-elem ul.notification-list li' ).length ) < 25 ) {
							notif_icons.fadeOut();
						} else {
							notif_icons.html( parseInt( notif_icons.html() ) - parseInt( $( '#header-notifications-dropdown-elem ul.notification-list li' ).length ) );
						}
					}
				}
				if ( $( '.notification-wrap.menu-item-has-children.selected ul.notification-list li' ).length !== 'undefined' && $( '.notification-wrap.menu-item-has-children.selected ul.notification-list li' ).length == 1 || $( this ).data( 'notification-id' ) === 'all' ) {
					$( '#header-notifications-dropdown-elem ul.notification-list' ).html( '<p class="bb-header-loader"><i class="bb-icon-loader animate-spin"></i></p>' );
				}
				if ( $( this ).data( 'notification-id' ) !== 'all' ) {
					$( this ).parent().parent().fadeOut();
					$( this ).parent().parent().remove();
				}
				$.post(
					ajaxurl,
					data,
					function ( response ) {
						var notifs = $( '.bb-icon-bell' );
						var notif_icons = $( notifs ).parent().children( '.count' );
						if ( notification_queue.length === 1 && response.success && typeof response.data !== 'undefined' && typeof response.data.contents !== 'undefined' && $( '#header-notifications-dropdown-elem ul.notification-list' ).length ) {
							$( '#header-notifications-dropdown-elem ul.notification-list' ).html( response.data.contents );
						}
						if ( typeof response.data.total_notifications !== 'undefined' && response.data.total_notifications > 0 && notif_icons.length > 0 ) {
							$( notif_icons ).text( response.data.total_notifications );
							$( '.notification-header .cb-read-all-notifications' ).show();
						} else {
							$( notif_icons ).remove();
							$( '.notification-header .cb-read-all-notifications' ).fadeOut();
						}
						var index = notification_queue.indexOf( $( this ).data( 'notification-id' ) );
						notification_queue.splice( index, 1 );
					}
				);
			}
		);
	}

	unReadNotifications();

	requestAmount.change(function() {

		var requestData = jQuery(this).find(':selected').data('request-value');

		sendToAmount.value = requestData;

	});

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
	 * CB Get Total Transactions
	 *
	 * @param {string} status
	 * @param {string} eventType
	 * @returns {int}
	 *
	 */
	const cbGetTotalTransactions = async (userID) => {

		if ( cbTotalTransactions !== null ) {
			return;
		}

		let retval = await $.get({
			url: cb_core.get_transactions,
			data: {
				user_id: userID,
				count: true
			},
			success: (x) => {
				cbTotalTransactions = parseInt(x.text[0].total_count);
				return cbTotalTransactions;
			},
			error: (e) => {
				console.log(e)
			}
		});

		return retval;

	};

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
				class: `cb-transactions-pagination-button cb-transactions-pagination-numbered ${(k === page ? ' active' : '')}`,
				'data-cb-transactions-page': k,
				text: k
			});
			$('.cb-transactions-pagination-next').before(paginationButton);
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

		let paginationBars = $('.cb-transactions-pagination');
		let k = (page >= last - 2) && ((last - 2) > 0) ? last - 2 : page;

		if (k <= (page + 2) && k <= last) {
			paginationBars.each( (i, el) => {
				$(el).children('.cb-transactions-pagination-numbered').each((j, em) => {
					$(em).attr('data-cb-transactions-page', k);
					$(em).text(k);
					if (k === page) {
						$(em).addClass('active');
					}
					k++;
				});
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
	function cbSetupTransactionPaginationDigits(page = 1, last = 0) {

		let currentButtons = $('.cb-transactions-pagination-numbered');

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
	function cbSetupTransactionPaginationEnds(page = 1, prev = 0, next = 2, last = 0) {

		cbTransactionPagePrev.attr('data-cb-transactions-page', prev);
		cbTransactionPageNext.attr('data-cb-transactions-page', next);

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
	let cbTransactionsPagination = async (page, userID) => {

		await cbGetTotalTransactions(userID);
		if (cbTotalTransactions > 0) {

			let last = Math.ceil(cbTotalTransactions / 15);
			let prev = page - 1;
			let next = page + 1;

			cbTransactionPageLast.attr('data-cb-transactions-page', last);

			cbSetupTransactionPaginationEnds(page, prev, next, last);
			cbSetupTransactionPaginationDigits(page, last);

		} else {
			cbTransactionPageLast.toggleClass('disabled', true);
			cbTransactionPageNext.toggleClass('disabled', true);
			cbTransactionPagePrev.toggleClass('disabled', true);
			cbTransactionPageFirst.toggleClass('disabled', true);
			$('.cb-transactions-pagination-numbered').remove();
		}
	}

	/**
	 * CB Create Empty Transaction Notice
	 *
	 * @param {string} response
	 * @param {object} activePanel
	 * @returns {void}
	 */
	function cbCreateEmptyTransactionNotice(response) {

		transactionsTable.children().remove();

		let $emptyNotice = $(`<div class='cb-participation-admin-empty-notice'><p>No results found.</p></div>`);
		transactionsTable.append($emptyNotice);

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
	async function cbCreateTransactionEntry(transaction) {

		let sender = await cbGetUserData(transaction.sender_id);

		let recipient = await cbGetUserData(transaction.recipient_id);
		let senderName = sender.userDisplayName;
		let recipientName = recipient.userDisplayName;
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
			text: senderName
		});
		let transactionRecipient = $('<p>', {
			class:`cb-transactions-entry-data cb-transactions-recipient`,
			text: recipientName
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
	async function refreshTransactions(page, userID) {
		
		let getData = {
				recipient_id: userID,
				sender_id: userID,
				or: true,
				page: page,
				per_page: 15,
		};

		await $.get({
			url: cb_core.get_transactions,
			data: getData,
			success: async (data) => {
				cbTransactionsPagination(page, userID);
				if ( data.text !== false ) {
					transactionsTable.children().remove();
					formatTransactionsHeaderRow();
					for (let r of data.text) {
						await cbCreateTransactionEntry(r);
					}
				} else {
					cbCreateEmptyTransactionNotice(data);
				}
			},
			error: (e) => {
				console.log(e)
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

	$('.cb-transactions-pagination-container').on('click', '.cb-transactions-pagination-button', function(e) {
		e.preventDefault();
		let page = parseInt($(this).attr('data-cb-transactions-page'));
		refreshTransactions(page, userID);

	});

	refreshTransactions(1, userID );
	formatTransactionsHeaderRow();
	
	$(document).on('change', '.cb-file-input', function (e) {
	});

});