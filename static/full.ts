RTE.default.init {
	plugins = safari,style,layer,table,advimage,advlink,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,fullscreen,noneditable,nonbreaking,xhtmlxtras,typo3filemanager,template,spellchecker
	theme_advanced_buttons1 = newdocument,|,undo,redo,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,fullscreen,|,cleanup,nonbreaking,spellchecker
	theme_advanced_buttons2 = link,typo3link,unlink,|,image,typo3image,|,tablecontrols
	theme_advanced_buttons3 = code,|,anchor,charmap,media,attribs,styleprops,|,forecolor,backcolor,strikethrough,sub,sup,|,bullist,numlist,|,outdent,indent,|,blockquote,template
	theme_advanced_buttons4 = styleselect,|,formatselect,|,fontselect,|,fontsizeselect,|,bold,italic,underline
	theme_advanced_statusbar_location = bottom
	width = 600
	height = 550
	fix_table_elements = true
	# you could override the following option if you don't want to insert links.
	file_browser_callback = typo3filemanager
	spellchecker_languages = +English=en,German=de
	spellchecker_rpc_url = EXT:tinymce_rte/mod3/rpc.php	

	template_templates {
		10 {
			title = static HTML
			src = EXT:tinymce_rte/res/tinymce_templates/static.html
			description = Adds some static HTML an expert could prepare
		}
		20 {
			title = TYPO3 mod
			description = Use an TYPO3 mod to get data easily into the the TinyMCE Template System
			include = EXT:tinymce_rte/res/tinymce_templates/advanced.php
		}
		12 {
			title = simple replace
			src = EXT:tinymce_rte/res/tinymce_templates/simpleReplace.html
			description = Adds some HTML where certain variables will be replaced
		}
		30 {
			title = TYPO3 mod not available
			description = The include file is not available and this should create an error
			# file is not available should create error
			include = fileadmin/tinymce_templates/notAvailable.php
		}
		25 {
			title = TYPO3 mod see get params
			src = EXT:tinymce_rte/mod4/TinyMCETemplate.php?wow=ja&woow=nein
			description = Use an TYPO3 mod to get data easily into the the TinyMCE Template System
			# inside this php file is only print_r(t3lib_div::_GET());
			include = EXT:tinymce_rte/res/tinymce_templates/getParams.php
		}
	}

	template_replace_values {
		username = Jack Black
	}
	
}

RTE.default {
	
	callbackJavascriptFile =
	
	linkhandler {
		tt_news {
			default {
				# instead of default you could write the id of the storage folder
				# id of the Single News Page
				parameter = 23
				additionalParams = &tx_ttnews[tt_news]={field:uid}
				additionalParams.insertData = 1
				# you need: uid, hidden, header [this is the displayed title] (use xx as header to select other properties)
				# you can provide: bodytext [alternative title], starttime, endtime [to display the current status]
				select = uid,title as header,hidden,starttime,endtime,bodytext
				sorting = crdate desc
			}
		}
	}
	
	# Config used for the spellchecker
	spellcheck {
		general.engine = GoogleSpell
		PSpell.mode = PSPELL_FAST
		PSpell.spelling =
		PSpell.jargon =
		PSpell.encoding =
		PSpellShell.mode = PSPELL_FAST
		PSpellShell.aspell =
		PSpellShell.tmp = ./tmp
	}
	
}