'use strict';

let panelClass = function ( id, requester ) {

    this.last_checked = null ;
    this.container_element = jQuery('#'+id) ;
    this.requester = requester ;

    this.listContainer = jQuery( this.container_element.find('.panel-list') ) ;

    this.current_dir = this.listContainer.find('#dir').attr('data-value');
    this.up_button = this.container_element.find('#panel_actions .up');
    this.new_button = this.container_element.find('#panel_actions .new');
    this.select_all_button = this.container_element.find( '#panel_actions .select-all' );
    this.deselect_all_button = this.container_element.find( '#panel_actions .deselect-all' );

    let me = this ;

    this.up_button.on( 'click', function ( e ) {
        e.preventDefault();
        let current_dir =  me.getCurrentDir() ;
        let levels = current_dir.split('/');
        levels.pop();
        let path = levels.join('/');
        me.list( path );
    });

    this.new_button.on( 'click', function ( e ) {
        e.preventDefault();
        let name = prompt("Please enter dir name");
        if( name )
            me.newDir( name ) ;
    });

    this.select_all_button.on( 'click', function ( e ) {

            me.listContainer.find(' .selector').each( function (i,element) {
                jQuery( element ).prop("checked", true );
            });

    });

    this.deselect_all_button.on( 'click', function ( e ) {
        me.listContainer.find(' .selector').each( function (i,element) {
            jQuery( element ).prop("checked", false );
        });
    });

    this.bindListItemEvents();

};

panelClass.prototype.getCurrentDir = function(){
    this.current_dir = this.listContainer.find('#dir').attr( 'data-value' ).toString();
    return this.current_dir ;
};

panelClass.prototype.refresh = function () {
    let dir = this.getCurrentDir();
   this.list( dir );
};

panelClass.prototype.list = function( dir ){
    this.showLoading();
    dir = typeof dir === 'undefined' ? this.getCurrentDir() : dir ;

    if( dir === '' )
        dir = '/' ;

    this.requester.push( { operation: 'list', dir: dir  }, this.loadList.bind( this ) ) ;
};

panelClass.prototype.newDir = function( dirName ){
    this.showLoading();
    let new_dir = this.getItemPath( dirName );
    this.requester.push({
            operation: 'new_dir',
            new_dir: new_dir
        },
        //this.loadList.bind( this )
    )
};

panelClass.prototype.rename = function( item ){

    let old_name = jQuery( item ).attr( 'data-name' );
    let new_name = prompt( 'please enter new name for ' + old_name, old_name );

    if( ! new_name  )
        return ;

    this.showLoading();
    this.requester.push({
        operation: 'rename',
        old: this.getItemPath( old_name ),
        new: this.getItemPath( new_name )
    },
        //this.loadList.bind( this )
    );
};

panelClass.prototype.loadList = function( response ){
    if( response.status ){
        this.listContainer.html( response.data ) ;
        let current_dir = this.getCurrentDir();
        current_dir = current_dir === '/' ? '/' : '/'+current_dir;
        this.container_element.find('.dir-text').val( current_dir );
        this.bindListItemEvents();
        this.resetLoading();
    }
};

panelClass.prototype.getSelectedItems = function () {
    let selectors = this.listContainer.find('.selector:checked');
    let selected = [] ;
    let current_dir = this.getCurrentDir() ;
    selectors.each(function (i,e) {
        selected.push( current_dir + '/' + jQuery( e ).attr( 'data-name') );
    });

    return selected ;
};

panelClass.prototype.bindListItemEvents = function () {

    let parent = this ;

    this.listContainer.find('.folder').each( function ( i, element ) {

        //if( jQuery( element ).hasClass( 'folder' ) ){

            jQuery( element ).on('click', function ( e ) {
                var $me = jQuery(this);
                //if the time now minus the time stored on the link, or 0 if there is not one, is less than 800, it's valid
                if ( Date.now() - ($me.data('touchtime') || 0) < 500 ) {
                    e.preventDefault();
                    let dir = parent.getItemPath( jQuery(this).attr( 'data-name') );
                    parent.list( dir ) ;
                } else {
                    //time did not exist, or it exceeded the 800 threshold, set a new one
                    $me.data('touchtime', Date.now());
                }
            });
        //}

    } );

    this.listContainer.contextMenu({
        selector : '.folder, .file',
        callback: function(key, options){
            parent.rename( this );
        },
        items: {
            "rename": {
                name: "Rename"
            },
        }
    }).bind( parent );

    /**
     * Checkbox click handler
     */
    this.listContainer.find( '.selector').each( function ( i, element ) {
       jQuery( element ).on('click', function ( e ) {
           // if shift key is pressed, check all previous checkboxes

           if( e.shiftKey && parent.last_checked !== null ){
                let last_checked_index = jQuery( parent.last_checked ).data('index');
                let this_element_index = jQuery( element ).data('index');

                let start = 0;
                let end = 0 ;
                if( last_checked_index > this_element_index ){
                    start = this_element_index ;
                    end = last_checked_index ;
                }
                else {
                    start = last_checked_index ;
                    end = this_element_index ;
                }

                for( let i = start ; i < end ; i ++ ){
                    parent.listContainer.find('.chk-'+i.toString()).prop('checked', true);
                }
            };

            parent.last_checked = element ;

       });
    }).bind( parent );

};

panelClass.prototype.getItemPath = function ( itemName ) {
    let current_dir = this.getCurrentDir() ;

    return current_dir + '/' + itemName;
};

panelClass.prototype.showLoading = function () {
    this.listContainer.css('opacity','0.3');
    this.container_element.addClass('wait');
};

panelClass.prototype.resetLoading = function () {
    this.listContainer.css('opacity','1');
    this.container_element.removeClass('wait');
};