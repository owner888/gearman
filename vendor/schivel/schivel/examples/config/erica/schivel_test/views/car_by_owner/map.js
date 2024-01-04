function (doc) {
	if (doc.class == 'car') {
		emit(doc.state.owner, {_id: doc._id});
	}
}
