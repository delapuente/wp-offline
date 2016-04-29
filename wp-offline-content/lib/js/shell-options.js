// Create event listeners for the file listing helpers
jQuery(document.body)
  .on('click', '.offline-shell-suggest-file', function() {
    var $this = jQuery(this);
    var suggestedCounter = 0;

    // Suggest main level CSS and JS files
    var $recommended = jQuery('.files-list input[type="checkbox"].recommended:not(:checked)')
      .prop('checked', 'checked')
      .closest('tr').addClass('offline-shell-suggested');

    // Update sugget button now that the process is done
    $this
      .text($this.data('suggested-text') + ' ' + $recommended.length)
      .prop('disabled', 'disabled');
  })
  .on('click', '.offline-shell-toggle-all', function() {
    jQuery('.files-list input[type="checkbox"]').prop('checked', 'checked');
  })
  .on('click', '.offline-shell-clear-all', function() {
    jQuery('.files-list input[type="checkbox"]').prop('checked', '');
  })
  .on('click', '.offline-shell-file-all', function(e) {
    e.preventDefault();
    jQuery(this.parentNode).next('.files-list').find('input[type=checkbox]').prop('checked', 'checked');
  });

// Load the file listing async so as to not brick the page on huge themes
// ajaxurl is a WordPress global JS var
jQuery.post(ajaxurl, {
  action: 'offline_shell_files',
  data: 'files'
}).done(function(response) {
  // Place the file listing
  jQuery('#offline-shell-file-list').html(response);
  // Notify admin that the files have been loaded and placed
  jQuery('#offline_shell_files_loaded').val(1);
  // Enable the file control buttons
  jQuery('.offline-shell-buttons button').removeProp('disabled');
});
