<INCLUDE_TYPOSCRIPT: source="FILE:EXT:tinymce_rte/static/common.ts">

RTE.default.init {
	plugins = safari,style,layer,table,advimage,advlink,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,fullscreen,noneditable,nonbreaking,xhtmlxtras,typo3filemanager
	theme_advanced_buttons1 = newdocument,|,undo,redo,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,fullscreen,|,cleanup,nonbreaking
	theme_advanced_buttons2 = link,typo3link,unlink,|,image,typo3image,|,tablecontrols
	theme_advanced_buttons3 = code,|,anchor,charmap,media,attribs,styleprops,|,forecolor,backcolor,strikethrough,sub,sup,|,bullist,numlist,|,outdent,indent,|,blockquote
	theme_advanced_buttons4 = styleselect,|,formatselect,|,fontselect,|,fontsizeselect,|,bold,italic,underline
	theme_advanced_statusbar_location = bottom
	width = 600
	height = 550
	fix_table_elements = true
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
		defaultImagePath = ./fileadmin/
		# in the imagebrowser how big should the thumbnails be
		thumbs.width = 200
		thumbs.height = 150
		# defines the maximum allowed image size if you create a plain image
		maxPlainImages.width = 1000
		maxPlainImages.height = 1000
	}
}