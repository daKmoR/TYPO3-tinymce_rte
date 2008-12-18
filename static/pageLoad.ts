RTE.default {
	gzip = 1,
	gzipFileCache = 0
	tiny_mcePath = EXT:tinymce_rte/res/tiny_mce/tiny_mce.js
	tiny_mceGzipPath = EXT:tinymce_rte/res/tiny_mce/tiny_mce_gzip.js
	languagesExtension = tinymce_rte_languages
}

RTE.default.init {
	plugins = safari,style,layer,table,advimage,advlink,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,fullscreen,noneditable,nonbreaking,xhtmlxtras,typo3filemanager,template,spellchecker
	theme = advanced
	mode = none
}