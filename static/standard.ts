RTE.default.init {
	# define a stylesheet
	# content_css = fileadmin/templates/main/css/screen.css
	# define styles and how they are displayed in the rte
	# theme_advanced_styles = Display Name=cssClassName;seperated=with;another Class=otherClass;
	
	plugins = safari,style,layer,table,advimage,advlink,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,fullscreen,noneditable,nonbreaking,xhtmlxtras,typo3filemanager,visualchars
	theme_advanced_buttons1 = newdocument,|,undo,redo,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,fullscreen,|,cleanup,nonbreaking
	theme_advanced_buttons2 = link,typo3link,unlink,|,image,typo3image,|,tablecontrols,|,visualchars
	theme_advanced_buttons3 = code,|,anchor,charmap,media,attribs,styleprops,|,forecolor,backcolor,strikethrough,sub,sup,|,bullist,numlist,|,outdent,indent,|,blockquote
	theme_advanced_buttons4 = styleselect,|,formatselect,|,fontselect,|,fontsizeselect,|,bold,italic,underline
	theme_advanced_statusbar_location = bottom
	width = 600
	height = 550
	fix_table_elements = true
	# you could override the following option if you don't want to insert links.
	file_browser_callback = typo3filemanager
}