RTE.default {
	callbackJavascriptFile =
	
	linkhandler {
		tt_news {
			# id of the Single News Page
			parameter = 27
			# id of the Storage folder containing the news (just used to mark already selected news) [set "storage >" if unsure or the user can select from more than one Storage Folder]
			storage = 25
			additionalParams = &tx_ttnews[tt_news]={field:uid}
			additionalParams.insertData = 1
			# you need: uid, hidden, header [this is the displayed title] (use xx as header to select other properties)
			# you can provide: bodytext [alternative title], starttime, endtime [to display the current status]
			select = uid,title as header,hidden,starttime,endtime,bodytext
			sorting = crdate desc
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