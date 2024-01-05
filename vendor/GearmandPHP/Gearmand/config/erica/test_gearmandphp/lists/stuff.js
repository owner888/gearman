function(head,req){

	// Example list function:
	//   shows how to merge documents to show "most recent state" of the app
	//   **Don't forget to SORT to make sure most recent changes are reflected in final 'target' value**
	//   http://localhost:5984/test_windowseat/_design/test_windowseat/_list/stuff/stuff?include_docs=true

	var extend = function(target, source) {
		target = target || {};
		for (var prop in source) {
			if (typeof source[prop] === 'object') {
				target[prop] = extend(target[prop], source[prop]);
			} else {
				target[prop] = source[prop];
			}
		}
		return target;
	}

	var row;
	var target = {};
	while(row=getRow()){
		if(row.doc.id){
			delete row.doc.id;
		}
		if(row.doc._rev){
			delete row.doc._rev;
		}
		if(row.doc._id){
			delete row.doc._id;
		}
		target = extend(target,row.doc);
	}
	return JSON.stringify(target);
}
