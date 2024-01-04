function (doc) {
	var blah = function(){
		return 5;
	}
	emit(doc._id, {_id:doc._id,stuff:blah()});
}
