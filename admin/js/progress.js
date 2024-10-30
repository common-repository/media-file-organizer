let progressClass = function ( ) {

    this.container = jQuery( '#progress' );
    this.messageContainer = this.container.find( '#activity' );
    this.statusContainer = this.container.find( '#status' );
    this.pause_counter  = 0 ;
    this.batch_mode = false ;
    this.total_items = 1;
    this.processed_items = 0;
    this.failed = [] ;

    this.operation_messages = {
        'list': {
            start: 'listing directory %dir',
            progress : 'sending request, please wait ..',
            successful_end: 'directory %dir listed',
            error_end: 'unable to list directory %dir'
        },
        'new_dir' :{
            start: 'creating directory %new_dir',
            progress : 'Creating directory, please wait ..',
            successful_end: 'directory %new_dir created',
            error_end: 'unable to create directory %new_dir'
        },
        'rename' :{
            start: 'Renaming %old to %new',
            progress: 'Renaming, please wait ...',
            successful_end: ' %old renamed to %new successfully',
            error_end: 'Unable to rename %old to %new'
        },
        'move' :{
            start: 'moving %items:count items to %to',
            start_batch: 'moving %items:count items out of %remaining remaining to %to ',
            progress : 'Moving items please wait... ',
            successful_end: ' %successful_items:count items successfully moved to %to, %failed_items:count failed.',
            /*successful_end_batch: ' %successful_items:count successfully moved to %to, %failed_items:count failed. <br> %failed:list',*/
            successful_end_batch : '%successful_items:count items successfully moved to %to, %failed_items:count failed.',
            error_end: 'an error occurred while moving items, please check the log '
        },
    };
};



progressClass.prototype.setMessage = function ( message ) {
    this.log('<strong>' + message + '</strong>');
};

progressClass.prototype.setError = function( message ){
    this.log( '<span class="error"><strong>' + message + '</strong></span>');
}

progressClass.prototype.start = function ( data ) {
    let operation = data[ 'operation' ];
    let message = this.batch_mode ? this.operation_messages[ operation ].start_batch : this.operation_messages[ operation ].start ;
    let status = this.operation_messages[ operation ].progress ;
    data = typeof data === 'undefined' ? {} : data ;
    message = this.prepareMessage( message, data );
    status = this.prepareMessage( status, data );

    this.log( message );
    this.update( status );
};

progressClass.prototype.log = function( message ){

    if( this.pause_counter > 0 ){
        this.pause_counter -- ;
        return ;
    }

    today = new Date();
    time = today.toLocaleTimeString();
    this.messageContainer.append( '<span class="message"><span class="time">'+time+' : </span>' + message + "</span><br><br>");
    this.messageContainer.scrollTop( this.messageContainer.prop( 'scrollHeight' ) );
};
progressClass.prototype.update = function ( data ) {
    this.statusContainer.html( 'Current Status : '+data );
};

progressClass.prototype.finish = function ( response, data ) {
    let operation = data[ 'operation' ];
    let message = '' ;
    if( 'status' in response && response.status === true )
        message =  this.batch_mode ? this.operation_messages[ operation ].successful_end_batch : this.operation_messages[ operation ].successful_end ;
    else
        message = this.operation_messages[ operation ].error_end ;

    data = typeof data === 'undefined' ? {} : data ;

    if ( response.data && toString.call( response.data ) === '[object Object]'  ){
        if( 'successful' in response.data ) {
            data['successful_items'] = response.data.successful;
            if( this.batch_mode )
                this.processed_items += response.data.successful.length;
        }

        if( 'failed' in response.data ) {
            data['failed_items'] = response.data.failed;
            if( this.batch_mode ){
                this.processed_items += response.data.failed.length ;
                response.data.failed.forEach( function ( item ) {
                    this.failed.push( item );
                }.bind(this));
            }
        }
    }

    message = this.prepareMessage( message, data );

    this.log( message );
    this.update( 'Idle' );


};

progressClass.prototype.prepareMessage = function (message, data) {
    jQuery.each( data, function (key, value) {
        message = message.replace( '%'+key+':count', value.length );
        message = message.replace( '%'+key, value );
    });

    if( this.batch_mode ){
        message = message.replace( '%remaining', this.total_items - this.processed_items );
    }

    return message ;
};

progressClass.prototype.reset = function(){
    this.batch_mode = false ;
    this.processed_items = 0 ;
    this.failed = [] ;
    this.total_items = 1 ;
};

progressClass.prototype.pause = function ( count ) {
  this.pause_counter = count ;
};
