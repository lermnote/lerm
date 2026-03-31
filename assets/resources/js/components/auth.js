const redirectTo = (target, fallback = window.location.href) => {
	window.location.href = target || fallback;
};

export const handleLoginSuccess = (response) => {
	redirectTo(response?.redirect, lermData.redirect || window.location.href);
};

export const handleRegisterSuccess = (response) => {
	redirectTo(response?.redirect, window.location.href);
};

export const handleResetSuccess = (response) => {
	const target = response?.redirect;
	if (!target) return;

	window.setTimeout(() => {
		redirectTo(target);
	}, 1200);
};
