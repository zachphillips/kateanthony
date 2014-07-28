/**
 * Low Variables JavaScript file
 *
 * @package         low-variables-ee_addon
 * @author          Lodewijk Schutte <hi@gotolow.com>
 * @link            http://gotolow.com/addons/low-variables
 * @copyright       Copyright (c) 2009-2011, Low
 * @since		1.1.5
 */

(function($){

	$(function(){

		// Group links
		$('a.low-grouplink').click(function(e){

			// Get the hash from the link
			var target = this.href.split('#')[1] || false;

			// Show all groups or just one
			if (target == 'all') {
				$('table.low-vargroup').show();
			} else {
				$('table.low-vargroup').hide();
				$('table#'+target).show();
			}

			// Activate clicked link
			$('a.low-grouplink').parent().removeClass('active');
			$(this).parent().addClass('active');

			// Remember active link
			$.cookie('exp_low_grouplink', target);

			// Don't go anywhere
			e.preventDefault();

		});

		// Remeber an active link? Activate it. Or else the first one
		var activate = $.cookie('exp_low_grouplink');
		var selector = 'a.low-grouplink';
		var active_link = $(selector + ':first');

		if (activate && $(selector + '[href$=#'+activate+']').length) {
			active_link = $(selector + '[href$=#'+activate+']');
		}

		// Engage!
		$(active_link).trigger('click');

		// Drag and Drop lists
		// Get each container
		$('.low-drag-lists').each(function(){

			// Get var_id from id attribute
			var var_id = $(this).attr('id').replace('low-drag-lists-', '');

			// Define callback function to alter dropped list item
			// We'll set the name-attribute of the hidden input field to either
			// the correct name or empty, depending on the list it was dropped into
			var updated = function(event, ui) {
				var newname = ui.item.parent().hasClass('low-on') ? 'var['+var_id+'][]' : '';
				$('input', ui.item).attr('name', newname);
			};

			// Initiate the sortable lists, confined within its container
			$('ul.low-off', this).sortable({connectWith: $('ul.low-on', this), opacity: 0.75, receive: updated});
			$('ul.low-on', this).sortable({connectWith: $('ul.low-off', this), opacity: 0.75, receive: updated});

		});

		// Toggle checkbox for EE2
		$("#low-toggle-all").click(function(){
			$('table tbody input[type=checkbox]').attr('checked', this.checked);
		});

		// Sorting of all variables (admin)
		$('#low-variables-list').sortable({
			axis: 'y',
			opacity: 0.75
		});

		$('.low-manager #low-sortable-groups').sortable({
			axis: 'y',
			opacity: 0.75,
			handle: 'span.low-handle',
			update: function(e, ui) {
				var new_order = [];

				$('.low-grouplink').each(function(){
					if ($(this).attr('id')) new_order.push($(this).attr('id').replace('group_id_', ''));
				});

				var key = $(this).hasClass('ee1') ? 'P' : 'method';

				$.ajax({
					url: location.href + '&'+key+'=save_group_order',
					data: 'groups=' + new_order.join('|'),
					type: 'GET' // POST fucks it right up somehow...
				});
			}
		});

		// Toggle variable type tables
		$('#low-select-type').change(function(){
			$('table.low-var-type').hide();
			$('#' + $('#low-select-type').val()).show();
		});

		// Toggle allow-multiple settings
		$('table.low-var-type').each(function(){
			var toggle = function() {
				var set = $(this).parents('tr').nextAll();
				this.checked ? set.show() : set.hide();
			};
			$('input[class=low-allow-multiple]', this).each(toggle).click(toggle);
		});

		// Code prompt
		$('a.low-var-name').click(function(e){
			if (e.altKey) {
				prompt('Code:', '{'+ $(this).text() +'}');
				e.preventDefault();
			}
		});

	});

})(jQuery);