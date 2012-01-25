/* see http://cubiq.org/add-to-home-screen "Advanced Features" section for details on configuration */
var addToHomeConfig = {
		animationIn: 'drop',		// drop || bubble || fade
		animationOut: 'fade',		// drop || bubble || fade
		startDelay: 1500,			// 2 seconds from page load before the balloon appears
		lifespan: 10000,			// 20 seconds before it is automatically destroyed
		bottomOffset: 14,			// Distance of the balloon from bottom
		expire: 2,					// Minutes to wait before showing the popup again (0 = always displayed)
		message: '',				// Customize your message or force a language ('' = automatic)
		touchIcon: true,			// Display the touch icon
		arrow: true,			    // Display the balloon arrow
		appname: 'Kurogo'
}
