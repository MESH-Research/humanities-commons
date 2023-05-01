// jQuery Plugin TabSelect
// A plugin to select one or more array or form entries visually, 
// for example to filter a list or replace a multiselect form element.
// Version 0.2 - 6. 6. 2011
// by Fredi Bach
// fredibach.ch

/**
 * Note 09-17-21 - Mike Thicke
 *
 * This plugin is not longer maintained and the website does not exist. This
 * appears to be the latest version of the plugin.
 */

(function($) {

    $.tabSelect = function(element, options) {

        var defaults = {
            activeClass: 'active',
			inactiveClass: 'inactive',
			elementType: 'span',
			tabElements: [],
			selectedTabs: [],
			formElement: undefined,
			multipleSelections: true,
			onChange: function() {}
        }

        var plugin = this;

        plugin.settings = {}

        var $element = $(element),
             element = element;

        plugin.init = function() {
            plugin.settings = $.extend({}, defaults, options);
			
			if (plugin.settings.selectedTabs == 'all'){
				plugin.settings.selectedTabs = plugin.settings.tabElements;
			}
			
			if (plugin.settings.formElement != undefined){
				if ($(plugin.settings.formElement).is("select")){
					$(plugin.settings.formElement).find(':selected').each(function(i, selected){
						plugin.settings.selectedTabs[i] = $(selected).text();
					});
				} else {
					plugin.settings.selectedTabs = $(plugin.settings.formElement).val().split(',');
				}
			}
			
            $.each(plugin.settings.tabElements, function(index, value){
				var tab = document.createElement('span');
				if (plugin.settings.selectedTabs.indexOf(value) == -1){
					$(tab).html(value).addClass(plugin.settings.inactiveClass);
				} else {
					$(tab).html(value).addClass(plugin.settings.activeClass);
				}
				$(tab).click(function(){
					if ($(this).hasClass(plugin.settings.activeClass)){
						$(this).removeClass(plugin.settings.activeClass).addClass(plugin.settings.inactiveClass);
					} else {
						$(this).removeClass(plugin.settings.inactiveClass).addClass(plugin.settings.activeClass);
					}
					plugin.onChange();
				});
				$element.append(tab);
			});
        }

        plugin.onChange = function(){
			// The default onChange handler assumes that the value of select
			// option is the same as the content of the associate span element,
			// which is not true for how we're using in on Humanities Commons.
			// Further, it seems to be causing problems with the DOM, so the
			// code was removed. 09-17-21 Mike
			plugin.settings.onChange(plugin.getAllSelected());
        }

		plugin.getAllSelected = function() {
			var values = [];
			$element.find(plugin.settings.elementType).each(function(){
				if ($(this).hasClass(plugin.settings.activeClass)){
					values.push($(this).html());
				}
			});
			return values;
        }
		
		plugin.selectAll = function() {
			var cnt = 0;
			$element.find(plugin.settings.elementType).each(function(){
				if ($(this).hasClass(plugin.settings.inactiveClass)){
					$(this).removeClass(plugin.settings.inactiveClass).addClass(plugin.settings.activeClass);
					cnt++;
				}
			});
			if (cnt > 0){
				plugin.onChange();
			}
        }
		
		plugin.deselectAll = function() {
			var cnt = 0;
			$element.find(plugin.settings.elementType).each(function(){
				if ($(this).hasClass(plugin.settings.activeClass)){
					$(this).removeClass(plugin.settings.activeClass).addClass(plugin.settings.inactiveClass);
					cnt++;
				}
			});
			if (cnt > 0){
				plugin.onChange();
			}
        }

        plugin.init();

    }

    $.fn.tabSelect = function(options) {

        return this.each(function() {
            if (undefined == $(this).data('tabSelect')) {
                var plugin = new $.tabSelect(this, options);
                $(this).data('tabSelect', plugin);
            }
        });

    }

})(jQuery);

if (!Array.prototype.indexOf) {
	Array.prototype.indexOf = function(obj, start) {
	     for (var i = (start || 0), j = this.length; i < j; i++) {
	         if (this[i] === obj) { return i; }
	     }
	     return -1;
	}
}