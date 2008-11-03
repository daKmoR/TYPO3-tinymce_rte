# clear htmlarea skin?
RTE.default.skin > 

RTE.default {
	gzip = 1
	gzipFileCache = 1
	typo3filemanager {
		window.width = 600
		window.height = 600
		# possible values for defaultTab = page,file,url,mail
		defaultTab = page
		# possible values for defaultImageTab = magic,plain,upload
		defaultImageTab = magic
		thumbs.width = 200
		thumbs.height = 150
		maxPlainImages.width = 1000
		maxPlainImages.height = 1000
	}
	# the following should be set by the language extension itself, but can also be set directly
	# languagesExtension = tinymce_rte
	tiny_mcePath = EXT:tinymce_rte/res/tiny_mce/tiny_mce.js
	tiny_mceGzipPath = EXT:tinymce_rte/res/tiny_mce/tiny_mce_gzip.js
}

RTE.default.init {
	content_css = fileadmin/templates/main/css/screen.css
	plugins = safari,style,layer,table,advimage,advlink,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,fullscreen,noneditable,nonbreaking,xhtmlxtras,template,spellchecker
	theme_advanced_buttons1 = newdocument,|,undo,redo,|,bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,fullscreen
	theme_advanced_buttons2 = link,unlink,image,|,attribs,styleprops,anchor,charmap,tablecontrols
	theme_advanced_buttons3 = code,forecolor,backcolor,strikethrough,sub,sup,|,bullist,numlist,|,outdent,indent,|,cleanup,nonbreaking,blockquote,|,insertlayer,moveforward,movebackward,absolute
	theme_advanced_buttons4 = styleselect,|,formatselect,|,fontselect,|,fontsizeselect
	theme_advanced_resizing = true
	theme_advanced_toolbar_location = top
	theme_advanced_toolbar_align = left
	theme_advanced_statusbar_location = bottom
	spellchecker_languages = +English=en,German=de
	spellchecker_rpc_url = EXT:tinymce_rte/mod3/rpc.php
	width = 600
	height = 550
	button_tile_map = true
}
 
# MANDATORY RTE CONFIG IF YOU CHANGE ANY OF THESE THE RTE MIGHT FAIL TO WORK
RTE.default.init {
	mode = exact
	# the element will be set by the main class
	# elements = RTEarea1
	theme = advanced
	entity_encoding = raw
	# you could override the following option if you don't want to insert links.
	file_browser_callback = typo3filemanager
}

# Config used for the spellchecker
RTE.default.spellcheck {
	general.engine = GoogleSpell
	PSpell.mode = PSPELL_FAST
	PSpell.spelling =
	PSpell.jargon =
	PSpell.encoding =
	PSpellShell.mode = PSPELL_FAST
	PSpellShell.aspell =
	PSpellShell.tmp = ./tmp
}

RTE.default.FE >
RTE.default.FE < .RTE.default

# Default config for FE use; stripping off many buttons
RTE.default.FE.init {
	plugins = safari,inlinepopups,paste,link,image
	theme_advanced_buttons1 = cut,copy,pastetext,|,bold,italic,underline,forecolor,backcolor,|,indent,outdent
	theme_advanced_buttons2 = bullist,numlist,|,justifyleft,justifycenter,justifyright,|,link,unlink,image
	theme_advanced_buttons3 =
	theme_advanced_buttons4 =
	theme_advanced_resize_horizontal = false
	width = 
	height = 
	file_browser_callback =
}

# Default RTE processing rules
RTE.default.proc {
	# TRANSFORMATION METHOD
	# We assume that CSS Styled Content is used.
	overruleMode = ts_css
	
	# DO NOT CONVERT BR TAGS INTO LINEBREAKS
	# br tags in the content are assumed to be intentional.
	dontConvBRtoParagraph = 1
	
	# DO NOT USE HTML SPECIAL CHARS FROM DB TO RTE
	# needed if you want to save spezial chars like &#9829; &hearts; both displays a heart (first NUM-Code second HTML-Code)
	dontHSC_rte = 1

	# PRESERVE DIV
	# we don't want div to be remove or remaped to p
	preserveDIVSections = 1

	# TAGS ALLOWED OUTSIDE P & DIV
	allowTagsOutside = hr, address, ul, ol, li, img, table

	# TAGS ALLOWED
	# Added to the default internal list: b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote,strike,span
	# But, for the sake of clarity, we use a complete list in alphabetic order.
	# center, font, strike, sdfield and  u will be removed on entry (see below).
	# b and i will be remapped on exit (see below).
	allowTags (
		a, abbr, acronym, address, blockquote, b, br, caption, cite, code, div, em,
		h1, h2, h3, h4, h5, h6, hr, i, img, li, link, ol, p, pre, q, sdfield,
		span, strong, sub, sup, table, tbody, td, th, tr, tt, ul
	)

	# TAGS DENIED
	# Make sure we can set rules on any tag listed in allowTags.
	denyTags >

	# ALLOWED P & DIV ATTRIBUTES
	# Attributes class and align are always preserved
	# Align attribute will be unset on entry (see below)
	# This is a list of additional attributes to keep
	keepPDIVattribs = xml:lang, style
	
	# CONTENT TO DATABASE
	entryHTMLparser_db = 1
	entryHTMLparser_db {
		# TAGS ALLOWED
		# Always use the same list of allowed tags.
		allowTags < RTE.default.proc.allowTags

		# TAGS DENIED
		# Make sure we can set rules on any tag listed in allowTags.
		denyTags >

		# AVOID CONTENT BEING HSC'ed TWICE
		htmlSpecialChars = 0
		tags {
			# CLEAN ATTRIBUTES ON THE FOLLOWING TAGS
			p.fixAttrib.align.unset = 1
			div.fixAttrib.align.unset = 1
			hr.allowedAttribs = class, style
			br.allowedAttribs = class, style
			table.allowedAttribs = class, style, id, border, cellpadding, cellspacing
			b.allowedAttribs = xml:lang
			blockquote.allowedAttribs = xml:lang
			cite.allowedAttribs = xml:lang
			em.allowedAttribs = xml:lang
			i.allowedAttribs = xml:lang
			q.allowedAttribs = xml:lang
			strong.allowedAttribs = xml:lang
			sub.allowedAttribs = xml:lang
			sup.allowedAttribs = xml:lang
			tt.allowedAttribs = xml:lang
		}

		# REMOVE OPEN OFFICE META DATA TAGS AND DEPRECATED HTML TAGS
		# We use this rule instead of the denyTags rule so that we can protect custom tags without protecting these unwanted tags.
		removeTags = center, font, o:p, sdfield, strike, u

		# PROTECT CUSTOM TAGS
		keepNonMatchedTags = protect
	}
	
	HTMLparser_db {
		# STRIP ALL ATTRIBUTES FROM THESE TAGS
		# If this list of tags is not set, it will default to: b,i,u,br,center,hr,sub,sup,strong,em,li,ul,ol,blockquote,strike.
		# However, we want to keep xml:lang attribute on most tags and tags from the default list where cleaned on entry.
		noAttrib = 

		# XHTML COMPLIANCE
		# Note that applying xhtml_cleaning on exit would break non-standard attributes of typolink tags
		xhtml_cleaning = 1
	}

	exitHTMLparser_db = 1
	exitHTMLparser_db {
		# REMAP B AND I TAGS
		# This must be done on exit because the default HTMLparser_db parsing executes the reverse mapping.
		tags.b.remap = strong
		tags.i.remap = em

		# KEEP ALL TAGS
		# Unwanted tags were removed on entry.
		# Without this rule, the parser will remove all tags! Presumably, this rule will be more efficient than repeating the allowTags rule
		keepNonMatchedTags = 1

		# AVOID CONTENT BEING HSC'ed TWICE
		htmlSpecialChars = 0
	}

}

# RTE processing rules for bodytext column of tt_content table
# Erase settings from other extensions
RTE.config.tt_content.bodytext >

# Make sure we use ts_css transformation
RTE.config.tt_content.bodytext.proc.overruleMode = ts_css
RTE.config.tt_content.bodytext.types.text.proc.overruleMode = ts_css
RTE.config.tt_content.bodytext.types.textpic.proc.overruleMode = ts_css