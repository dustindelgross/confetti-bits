jQuery( document ).ready( ( $ ) => {

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
		} else if ( cbDropzone.files.length < 1 ) {
			formMessage.setMessage( 'No files selected.', 'error' );
		} else {
			await cbDropzone.processQueue();
		}
	});
	$(document).on( 'click', '.cb-participation-member-search-result', function () {
		substituteInput.val( $(this).text() );
		applicantId.val( $(this).data('cbParticipantId') );
		$('.cb-participation-member-search-results').remove();
	});


});