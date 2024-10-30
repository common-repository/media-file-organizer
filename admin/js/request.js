let requestClass = function ( operation, data, success_handler, error_handler, loader ) {
    this.operation = label ;
    this.data = data;
    this.success_handler = success_handler;
    this.error_handler = error_handler;
    this.loader = loader;

};

requestClass.prototype.load = function(){
  this.loader.start( this.operation, this.data)
};

requestClass.prototype.finish = function ( response ) {
    this.loader.finish()
}



