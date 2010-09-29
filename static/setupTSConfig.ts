lib.parseFunc_RTE {
	
	# COPY ALLOW TAGS FROM PAGETS (WITHOUT b, i, link) sometimes there is a probblem with multiline so just use one line
	allowTags =	a, abbr, acronym, address, blockquote, br, caption, cite, code, dd, div, dl, dt, em, embed, h1, h2, h3, h4, h5, h6, hr, img, li, object, ol, p, param, pre, q, span, strong, sub, sup, table, tbody, td, th, tr, tt, ul
	
	# DO NOT ADD class="bodytext" TO EACH LINE
	nonTypoTagStdWrap.encapsLines.addAttributes.P.class >

	# DO NOT WRAP THOSE TAGS WITH <p>
	nonTypoTagStdWrap.encapsLines.encapsTagList = address, cite, div, dl, hr, h1, h2, h3, h4, h5, h6, p, pre, table
	
	# table handling
	table.stripNL = 1
	table.stdWrap.HTMLparser = 1
	table.callRecursive = 1
	table.callRecursive.tagStdWrap.HTMLparser = 1
	table.stdWrap.HTMLparser.keepNonMatchedTags = 1
	# Do NOT add any unwanted p elements in table cells, and do NOT remove any p-elements that have been added by authors!
	table.HTMLtableCells = 1
	table.HTMLtableCells.default.stdWrap.parseFunc =< lib.parseFunc 	

	# avoid unwanted p-elements in th/td on the way to FE
	externalBlocks.table.HTMLtableCells.default >
	externalBlocks.table.HTMLtableCells.default.stdWrap.parseFunc =< lib.parseFunc
	
	# DO NOT ADD class="contenttable" TO EVERY TABLE => ALLOW COSTUM class
	externalBlocks.table.stdWrap.HTMLparser.tags.table.fixAttrib.class  >
	
	# DO NOT AUTOLINK EVERY STRING THAT STARTS WITH HTTP OR MAILTO
	# such strings in the content are assumed to be intentional
	# makelinks >
	# externalBlocks.ul.stdWrap.parseFunc.makelinks = 0
	# externalBlocks.ol.stdWrap.parseFunc.makelinks = 0
	
}

# function to allow custom link attributes
lib.parseFunc.tags.a = COA
lib.parseFunc.tags.a {
	# Parsing of A tag if not an anchor
	10 = TEXT
	10.current = 1
	# Remove empty links
	10.required = 1	
	10.typolink.parameter.data = parameters : allParams
	10.typolink.parameter.postUserFunc = user_tinymce_rte->getHref
	10.typolink.ATagParams.data = parameters : allParams
	10.typolink.ATagParams.postUserFunc = user_tinymce_rte->getATagParams
	10.if.isTrue.data = parameters : allParams
	10.if.isTrue.postUserFunc = user_tinymce_rte->isNotAnchor
	
	# Parsing of A tag if an anchor
	20 = TEXT
	20.current = 1
	20.dataWrap = <a {parameters : allParams}>|</a>
	20.if.isTrue.data = parameters : allParams
	20.if.isTrue.postUserFunc = user_tinymce_rte->isNotAnchor
	20.if.negate = 1
}
lib.parseFunc_RTE.tags.a < lib.parseFunc.tags.a

# Remove empty links
lib.parseFunc.tags.link.required = 1
lib.parseFunc_RTE.tags.link.required = 1

# allow all values in the FE
lib.parseFunc.denyTags =
lib.parseFunc_RTE.denyTags =