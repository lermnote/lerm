export const handleCommentSuccess = (data) => {
	const comment = data?.comment;
	const commentHtml = data?.comment_html?.trim();
	if (!comment || !commentHtml) return;

	const respond = document.getElementById('respond');
	const commentList = document.querySelector('.comment-list');
	const template = document.createElement('template');
	template.innerHTML = commentHtml;

	const commentNode = template.content.firstElementChild;
	if (!commentNode) return;

	const parentId = Number(comment.comment_parent || 0);

	if (parentId > 0) {
		const parentComment = document.getElementById(`comment-${parentId}`);
		if (parentComment) {
			let children = Array.from(parentComment.children).find(
				(child) => child.classList?.contains('children')
			);

			if (!children) {
				children = document.createElement('ul');
				children.className = 'children';
				parentComment.appendChild(children);
			}

			children.appendChild(commentNode);
		} else if (commentList) {
			commentList.insertAdjacentElement('afterbegin', commentNode);
		}
	} else if (commentList) {
		commentList.insertAdjacentElement('afterbegin', commentNode);
	} else if (respond?.parentNode) {
		const newCommentCard = document.createElement('div');
		newCommentCard.className = 'card mb-3';

		const list = document.createElement('ol');
		list.className = 'comment-list p-0 m-0 list-group list-group-flush';
		list.appendChild(commentNode);
		newCommentCard.appendChild(list);
		respond.parentNode.appendChild(newCommentCard);
	}

	if (window.addComment?.cancelForm) {
		window.addComment.cancelForm();
	}
};
