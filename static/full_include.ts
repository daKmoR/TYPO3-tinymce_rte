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