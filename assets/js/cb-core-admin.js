jQuery(document).ready(($) => {

	/**
	 * CB Get User Data
	 *
	 * Get user data from the BuddyBoss API
	 *
	 * @param {int} applicantId
	 * @returns {object}
	 * @async
	 * @since 2.0.0
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
	 * CB Format Date
	 *
	 * @param {string} date
	 * @returns {string}
	 * 
	 * @since 2.0.0
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

});