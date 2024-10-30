let requesterClass = function () {
    this.requests = [] ;
    this.status = 0 ;

    this.default_loader = {
        start : function ( operation ) {

            jQuery( '#panels' ).css('cursor', 'wait');
            jQuery( '.folder' ).css('cursor', 'wait');
        },
        finish: function ( operation ) {
            jQuery( '#panels' ).css('cursor', 'default');
            jQuery( '.folder' ).css('cursor', 'pointer');
        }
    };

    this.default_success_handler = function ( response ) {
        console.log( response );
    };

    this.default_error_handler = function ( response ) {
        alert('An error occurred while executing the last request, please refresh the page'  );
    }
};

requesterClass.prototype.execute = function(){
    let request =  this.requests.shift();

    if( typeof request === 'undefined' ){
        this.status = 0  ;
        return ;
    }
    else
        this.status = 1 ;

    request.data['action'] = 'ajax_request' ;
    request.data['nonce'] = ajax_obj.nonce;
    let requester = this ;

    request.loader.start( request.data );

    jQuery.ajax({
        data: request.data,
        url: ajax_obj.url,
        method: 'POST',
        success: function ( response ) {

            request.loader.finish( response, request.data );

            if( response.status )
                request.success_handler( response );
            else
                request.error_handler( response ) ;

        }.bind( request ),
        error : function( response ){ /* FIXME: if anything other than json returned it is an error */
            request.loader.finish( response, request.data );
            request.error_handler( response ) ;
        }.bind( request ),
        complete: function ( response ) {
            requester.execute() ;
        }.bind( request )
    });


};
requesterClass.prototype.push = function( data, success_handler, error_handler, loader ){

    loader = typeof loader === 'undefined' ? this.default_loader : loader ;
    success_handler = typeof success_handler === 'undefined' ? this.default_success_handler : success_handler ;
    error_handler = typeof error_handler === 'undefined' ? this.default_error_handler : error_handler ;

    this.requests.push( {
        data: data,
        success_handler: success_handler,
        error_handler: error_handler,
        loader: loader
    } );

    if( this.status == 0 )
        this.execute();
};

requesterClass.prototype.end = function () {

};

