# needs any non TS line on top
<INCLUDE_TYPOSCRIPT: source="FILE:EXT:tinymce_rte/static/common.ts">

RTE.default {
	loadConfig = EXT:tinymce_rte/static/standard.ts
}

RTE.default.FE >
RTE.default.FE < RTE.default
RTE.default.FE {
	loadConfig = EXT:tinymce_rte/static/minimal.ts
	init {
		theme_advanced_resize_horizontal = false
	}
}