const replaceTokens = (template, replacements = {}) => {
	return Object.entries(replacements).reduce(
		(output, [token, value]) => output.replace(new RegExp(`\\{${token}\\}`, 'g'), String(value)),
		template
	);
};

export const translate = (key, replacements = {}) => {
	const strings = window.lermData?.i18n ?? {};
	const template = strings[key] ?? key;

	return replaceTokens(template, replacements);
};
