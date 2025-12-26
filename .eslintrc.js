module.exports = {
	extends: [
		'@nextcloud',
	],
	rules: {
		// Allow console in development
		'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
		'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
	},
}
