export const initializeCalendar = () => {
	const calendar = document.querySelector('#wp-calendar');
	if (!calendar) return;

	const calendarLinks = document.querySelectorAll('tbody td a');
	if (!calendarLinks.length) return;

	calendarLinks.forEach((link) => link.classList.add('has-posts'));
};
