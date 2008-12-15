<INCLUDE_TYPOSCRIPT: source="FILE:EXT:tinymce_rte/static/common.ts">

RTE.default.init {
	plugins = inlinepopups,advlink,typo3filemanager
	theme_advanced_buttons1 = bold,italic,underline,|,bullist,numlist,|,justifyleft,justifycenter,justifyright,|,link,typo3link,unlink
	theme_advanced_buttons2 = 
	theme_advanced_buttons3 = 
	theme_advanced_statusbar_location = bottom
	# you could override the following option if you don't want to insert links.
	file_browser_callback = typo3filemanager
}

RTE.default {
	typo3filemanager {
		# width/height of the typo3filemanger popup
		window.width = 600
		window.height = 600
		# possible values for defaultTab = page,file,url,mail
		defaultTab = page
		# possible values for defaultImageTab = magic,plain,upload
		defaultImageTab = magic
		# in the imagebrowser how big should the thumbnails be
		thumbs.width = 200
		thumbs.height = 150
		# defines the maximum allowed image size if you create a plain image
		maxPlainImages.width = 1000
		maxPlainImages.height = 1000
	}
}