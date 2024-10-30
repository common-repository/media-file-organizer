'use strict';

let app = function ( $ ) {

    this.progress = new progressClass();

    this.requester = new requesterClass();

    this.requester.default_loader = this.progress ;
    this.requester.default_success_handler  = this.default_success_handler.bind( this );
    this.requester.default_error_handler  = this.default_error_handler.bind( this );

    this.left_panel = new panelClass('left_panel', this.requester);
    this.right_panel = new panelClass('right_panel', this.requester);

    let me = this ;

    $('.move_left').on('click', function () {
        me.handle_move( me.right_panel, me.left_panel ) ;
    });

    $('.move_right').on( 'click', function () {
        me.handle_move( me.left_panel, me.right_panel );
    });

    this.progress.setMessage( 'Root uploads directory listed ');

};


app.prototype.handle_move = function ( origin_panel, destination_panel ) {
    let items = origin_panel.getSelectedItems() ;
    let to = destination_panel.getCurrentDir() ;

    origin_panel.showLoading();
    destination_panel.showLoading();
    this.progress.setMessage( 'Moving '+items.length.toString()+ ' Items');
    this.progress.batch_mode = true ;
    this.progress.total_items = items.length;

    let chunk = parseInt( options.batch_size ) ;
    //let batch = items ;
    if( items.length > chunk ){
        let i = 0;
        for ( i; i < items.length; i+=chunk ) {
            let batch = items.slice( i, i + chunk );
            this.move_request( batch, to, this.handleBatchMoveResponse.bind( this ) );
        };
    }
    else
        this.move_request( items, to, this.handleBatchMoveResponse.bind( this ) );
};

app.prototype.move_request = function( batch, to, success_handler, error_handler, loader ){

    let data = {
        items: batch,
        to: to,
        operation: 'move'
    };

    this.requester.push( data, success_handler, error_handler, loader ) ;
};

app.prototype.handleMoveResponse = function ( response ) {
    this.refreshPanels();


    if( response.data.failed.length >= 1 )
        alert( response.data.failed[0].reason );
};

app.prototype.handleBatchMoveResponse = function( response ) {


    if( this.progress.processed_items >= this.progress.total_items ){
        this.progress.setMessage( this.progress.processed_items + ' Items Processed')
        if( this.progress.failed.length > 0 ){
            alert( 'Some items failed to move');
            this.progress.setMessage('Failed Items :');
            this.progress.failed.forEach( function (item){
                this.progress.setError( item.path + ' : ' + item.reason )
            }.bind(this));
        }
        this.progress.reset();
        this.refreshPanels();
    }
};

app.prototype.default_success_handler = function( response ){
    this.refreshPanels()
};

app.prototype.default_error_handler = function( response ){
    if( 'data' in response ){
        if( response.data && toString.call( response.data ) === '[object Object]')
        alert( 'an error occurred while executing last request' ); /* FIXME Should be different message at least */
        else
            alert( response.data );
    }
    else {
        alert( 'an error occurred while executing last request' );
    }

    this.left_panel.resetLoading();
    this.right_panel.resetLoading();
};


app.prototype.refreshPanels = function () {
    this.progress.pause( 4 );
    this.left_panel.refresh();
    this.right_panel.refresh();
};