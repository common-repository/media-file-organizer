(function( $ ) {
    'use strict';

    $(document).ready( function () {
        $('.clear_log').on('click', function ( e ) {
            e.preventDefault();
            e.stopImmediatePropagation();
            let conf = confirm( ' Are you sure you want to clear the log file ?');
            if( ! conf )
                return ;

            $.ajax({
                url : ajax_obj.url,
                method: 'POST',
                data : {
                    'nonce' : ajax_obj.nonce,
                    'action' : 'options_ajax_request',
                    'operation' : 'clear_log',
                },
                success: function ( response ) {
                    alert('Log file was cleared successfully');
                },
                error: function ( response ) {
                  alert( 'An error occurred while clearing the log file')
                }
            })
        })
    });

})( jQuery );
