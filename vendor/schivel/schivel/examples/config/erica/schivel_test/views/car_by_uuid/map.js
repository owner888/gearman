function (doc) {
	if (doc.class == 'car') {
		emit(doc._id, {_id: doc._id});
	}
}
