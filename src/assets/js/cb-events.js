jQuery(document).ready(function($) {
	const eventStart = $('#cb_event_start_date');
	
	var dateFormat = "mm/dd/yy";
	
	eventStart.datepicker({
    // Set the date format
    dateFormat: dateFormat,

    // Show the current month and day by default
    defaultDate: new Date(),

    // When the user selects a date, update the input field
    onSelect: function(dateText, inst) {
      $(this).val(dateText);
    }
  });
});