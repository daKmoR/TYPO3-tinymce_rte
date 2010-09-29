# needs any non TS line on top

# clear htmlarea skin?
RTE.default.skin > 

RTE.default {
	# do you want to create a single gzip file for the rte (much faster)
	gzip = 1
	# Should the gzip file saved in typo3temp/tinymce_rte/ for further use (faster)
	gzipFileCache = 0
	# the following should be set by the language extension itself, but can also be set directly
	# languagesExtension = tinymce_languages
	tiny_mcePath = EXT:tinymce_rte/res/tiny_mce/tiny_mce.js
	tiny_mceGzipPath = EXT:tinymce_rte/res/tiny_mce/tiny_mce_gzip.js
	# set the language to the same value as the default FE language
	defaultLanguageFE = en
	# just a dummy until I find out howto automatically detect FE editing mode
	useFEediting = 0
	# forces UTF8 on all key values of the configuration (neede if you save non standard chars in your config files as ANSI)
	forceUTF8 = 1
	
	typo3filemanager {
		# width/height of the typo3filemanger popup
		window.width = 600
		window.height = 600
		# possible values for defaultTab = page,file,url,mail
		defaultTab = page
		# possible values for defaultImageTab = magic,plain,upload
		defaultImageTab = magic
		defaultImagePath = fileadmin/
		# in the imagebrowser how big should the thumbnails be
		thumbs.width = 200
		thumbs.height = 150
		# defines the maximum allowed image size if you create a plain image
		maxPlainImages.width = 1000
		maxPlainImages.height = 1000
	}
}

RTE.default.init {
	content_css = fileadmin/templates/main/css/screen.css
	theme_advanced_resizing = true
	theme_advanced_toolbar_location = top
	theme_advanced_toolbar_align = left
	mode = exact
	# the element will be set by the main class
	# elements = RTEarea1
	theme = advanced
	entity_encoding = raw
	gecko_spellcheck = true
}

## Default RTE processing rules ####################################################################
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
	allowTagsOutside = hr, address, ul, ol, li, img, table, object, embed
	
	# DON'T FETCH EXTERNAL IMAGES
	dontFetchExtPictures = 1

	# TAGS ALLOWED
	# Added to the default internal list: b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote,strike,span
	# But, for the sake of clarity, we use a complete list in alphabetic order.
	# center, font, strike, sdfield and  u will be removed on entry (see below).
	# b and i will be remapped on exit (see below).
	allowTags (
		a, abbr, acronym, address, blockquote, b, br, caption, cite, code, dd, div, dl, dt, em, embed,
		h1, h2, h3, h4, h5, h6, hr, i, img, li, link, object, ol, p, param, pre, q,
		span, strong, sub, sup, table, tbody, td, th, tr, tt, ul
	)

	# TAGS DENIED
	# Make sure we can set rules on any tag listed in allowTags.
	denyTags >

	# ALLOWED P & DIV ATTRIBUTES
	# Attributes class and align are always preserved
	# Align attribute will be unset on entry (see below)
	# This is a list of additional attributes to keep
	keepPDIVattribs = xml:lang, style, class, id
	
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
			p.allowedAttribs = id, class, style
			div.fixAttrib.align.unset = 1
			hr.allowedAttribs = id, class, style
			br.allowedAttribs = id, class, style
			table.allowedAttribs = id, class, style, border, cellpadding, cellspacing, summary 
			thead.allowedAttribs = id, class, style
			tfoot.allowedAttribs = id, class, style
			b.allowedAttribs = xml:lang
			blockquote.allowedAttribs = id, class, style, xml:lang
			cite.allowedAttribs = xml:lang
			em.allowedAttribs = id, class, style, xml:lang
			i.allowedAttribs = xml:lang
			q.allowedAttribs = xml:lang
			strong.allowedAttribs = id, class, style, xml:lang
			sub.allowedAttribs = xml:lang
			sup.allowedAttribs = xml:lang
			tt.allowedAttribs = xml:lang
		}

		# REMOVE OPEN OFFICE META DATA TAGS AND DEPRECATED HTML TAGS
		# We use this rule instead of the denyTags rule so that we can protect custom tags without protecting these unwanted tags.
		removeTags = center, font, o:p, sdfield, strike, u

		# PROTECT CUSTOM TAGS
		keepNonMatchedTags = protect

		# XHTML COMPLIANCE
		# Note that applying xhtml_cleaning on exit would break non-standard attributes of typolink tags
		xhtml_cleaning = 1	
	}
	
	HTMLparser_db {
		# STRIP ALL ATTRIBUTES FROM THESE TAGS
		# If this list of tags is not set, it will default to: b,i,u,br,center,hr,sub,sup,strong,em,li,ul,ol,blockquote,strike.
		# However, we want to keep xml:lang attribute on most tags and tags from the default list where cleaned on entry.
		# So we just set any tag we don't use
		noAttrib = nonusedtag
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

## FE Config #######################################################################################
RTE.default.FE >
RTE.default.FE < RTE.default
RTE.default.FE {
	init {
		theme_advanced_resize_horizontal = false
	}
}