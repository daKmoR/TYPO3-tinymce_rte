RTE.pageLoad {
	gzip = 1
	gzipFileCache = 1
	tiny_mcePath = EXT:tinymce_rte/res/tiny_mce/tiny_mce.js
	tiny_mceGzipPath = EXT:tinymce_rte/res/tiny_mce/tiny_mce_gzip.js
	# the following should be set by the language extension itself, but can also be set directly
	# languagesExtension = tinymce_languages	
}

RTE.pageLoad.init {
	plugins = safari,style,layer,table,advimage,advlink,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,fullscreen,noneditable,nonbreaking,xhtmlxtras,typo3filemanager,template,spellchecker,visualchars
	theme = advanced
	mode = none
}