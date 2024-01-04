function (doc) {
	if (doc.class == 'ViewTestClass') {
		emit(doc.state.name, {_id: doc._id});
	}
}
