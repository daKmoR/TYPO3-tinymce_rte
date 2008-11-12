lib.parseFunc_RTE {
	
	# COPY ALLOW TAGS FROM PAGETS (WITHOUT b, i, link) sometimes there is a probblem with multiline so just use one line
	allowTags =	a, abbr, acronym, address, blockquote, br, caption, cite, code, div, em, embed, h1, h2, h3, h4, h5, h6, hr, img, li, object, ol, p, param, pre, q, span, strong, sub, sup, table, tbody, td, th, tr, tt, ul
	
	
	# DO NOT ADD class="bodytext" TO EACH LINE
	nonTypoTagStdWrap.encapsLines.addAttributes.P.class >

	# DO NOT WRAP THOSE TAGS WITH <p>
	nonTypoTagStdWrap.encapsLines.encapsTagList = cite, div, p, pre, hr, h1, h2, h3, h4, h5, h6, table
	
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