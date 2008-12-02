/**
 * allow to open the typo3 page/file and imagebrowser with one click 
 *
 * @author Thomas Allmer
 */

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('typo3filemanager');

	tinymce.create('tinymce.plugins.typo3filemanagerPlugin', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// TYPO3LINK
			ed.addCommand('mcetypo3link', function() {
				var url = '';
				var node = tinyMCE.activeEditor.selection.getNode();
				do {
					if (node.nodeName.toLowerCase() == "a" && node.getAttribute("href") != "") {
						url = node.getAttribute("href");
					}
				} while ((node = node.parentNode));
							
			  typo3filemanager('', url, 'link', '');
			});

			ed.addButton('typo3link', {
				title : 'typo3filemanager.link_desc',
				cmd : 'mcetypo3link',
				image : url + '/img/typo3link.gif'
			});

			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('typo3link', co && n.nodeName != 'A');
				cm.setActive('typo3link', n.nodeName == 'A' && !n.name);
			});
			
			// TYPO3IMAGE
			ed.addCommand('mcetypo3image', function() {
				var url = '';
				var node = tinyMCE.activeEditor.selection.getNode();
				do {
					if (node.nodeName.toLowerCase() == "img" && node.getAttribute("src") != "") {
						url = node.getAttribute("src");
					}
				} while ((node = node.parentNode));
							
			  typo3filemanager('', url, 'image', '');
			});

			ed.addButton('typo3image', {
				title : 'typo3filemanager.image_desc',
				cmd : 'mcetypo3image',
				image : url + '/img/typo3image.gif'
			});

			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setActive('typo3image', n.nodeName == 'IMG');
			});
			
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'opens the TYPO3 page/file/image browser',
				author : 'Thomas Allmer',
				authorurl : 'http://www.webteam.at',
				infourl : 'http://forge.typo3.org/wiki/extension-tinymce_rte/TinyMCE_typo3fileadmin_Plugin',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('typo3filemanager', tinymce.plugins.typo3filemanagerPlugin);
})();