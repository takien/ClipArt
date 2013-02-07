(function() {
	tinymce.create('tinymce.plugins.InsertClipArt', {
		init : function(ed, url) {
			ed.addCommand('InsertClipArt', function() {
				ed.windowManager.open({
					url    : '?insert_clipart_dialog=1',
					width  : 820,
					height : 550,
					inline : 1
				});
			});
			ed.addButton('insertclipart', {
				title : 'Insert ClipArt',
				image : url+'/clipart-tinymce-button.png',
				cmd : 'InsertClipArt'
			});
		},
		getInfo : function() {
			return {
				longname : "Insert ClipArt",
				author   : 'takien',
				authorurl : 'http://takien.com/',
				infourl  : 'http://takien.com/',
				version  : "0.1"
			};
		}
	});
	tinymce.PluginManager.add('insertclipart', tinymce.plugins.InsertClipArt);
})();