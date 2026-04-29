export const handleUpdateProfileSuccess = (response) => {
	const target = response?.redirect || lermData.redirect || window.location.href;
	window.location.href = target;
};
