export const handleUpdateProfileSuccess = () => {
	const target = lermData.redirect || window.location.href;
	window.location.href = target;
};
