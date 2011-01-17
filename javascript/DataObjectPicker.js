(function($) {

	$(document).ready(function() {
		
		var locked = false;
		
		function pick(li) {
			if(locked) return;
			var idbase = li.parent().attr("id").substr(0,li.parent().attr("id").length - 12);

			$('li', li.parent()).removeClass('picked');
			li.addClass('picked');
			var elementidsegments = li.attr('id').split('_');
			var dataobjectid = elementidsegments[elementidsegments.length - 1];
			$('#' + idbase).val(dataobjectid);
			$('#' + idbase + '_helper').val(li.attr('title'));
			var pos = li.position();
			
			if(pos.top < 0) {
				$('#' + idbase + '_suggestions').scrollTop($('#' + idbase + '_suggestions').scrollTop() + pos.top);
			} else if(pos.top + li.outerHeight() > $('#' + idbase + '_suggestions').height()) {
				$('#' + idbase + '_suggestions').scrollTop(
					$('#' + idbase + '_suggestions').scrollTop() + pos.top + li.outerHeight() - $('#' + idbase + '_suggestions').height()
				);
			}
		}
		
		function hide_suggestions(ul) {
			locked = true;
			ul.slideUp('fast', function(){ul.html(''); locked = false; });
		}
		
		$('form .DataObjectPickerHelper').live('keyup', function(event) {

			var idbase = $(this).attr("id").substr(0,$(this).attr("id").length - 7);
			
			switch(event.keyCode) {
				case $.ui.keyCode.ESCAPE:
					hide_suggestions($("#" + idbase + "_suggestions"));
					return false;
				case $.ui.keyCode.UP:
					if(!$('li', $('#' + idbase + '_suggestions')).size()) break;
					if(!$('li.picked', $('#' + idbase + '_suggestions')).size()) { pick($('li:last', $('#' + idbase + '_suggestions'))); return false; }
					sibs = $('li', $('#' + idbase + '_suggestions'));
					i = $('li', $('#' + idbase + '_suggestions')).size() - 1;
					while(i && !$(sibs[i]).hasClass('picked')) i--;
					if(i-- > 0) pick($(sibs[i]));
					return false;
					break;
				case $.ui.keyCode.DOWN:
					if(!$('li', $('#' + idbase + '_suggestions')).size()) break;
					if(!$('li.picked', $('#' + idbase + '_suggestions')).size()) { pick($('li', $('#' + idbase + '_suggestions')).first()); return false; }
					if(!$('li.picked ~ li', $('#' + idbase + '_suggestions')).size()) return false;
					pick($('li.picked ~ li', $('#' + idbase + '_suggestions')).first());
					return false;
				case $.ui.keyCode.END:
					pick($('li:last', $('#' + idbase + '_suggestions')));
					return false;
				case $.ui.keyCode.ENTER:
					if($('li', $('#' + idbase + '_suggestions')).size()) {
						hide_suggestions($("#" + idbase + "_suggestions"));
					}
					return false;
				case $.ui.keyCode.HOME:
					pick($('li:first', $('#' + idbase + '_suggestions')));
					return false;
			}
			
			$.getJSON($(this).attr('rel'), 'request=' + $(this).val(), function(data){
				var i=-1;
				var lis = "";
				while(data[++i]) {
					var full;
					lis += "<li";
					for(var j in data[i]) {
						if(j == 'full') {
							full = data[i][j];
							continue;
						} else if(j == 'id') {
							data[i][j] = idbase + '_suggestion_' + data[i][j];
						}
						lis += " " + j + '="' + data[i][j] + '"';
					}
					lis += ">" + full + "</li>";
				}
				$("#" + idbase + "_suggestions").html(lis).slideDown('fast');
			});
			
			return false;
		});
		
		$('form .DataObjectPickerHelper').live('blur', function(event) {
			var idbase = $(this).attr("id").substr(0,$(this).attr("id").length - 7);
			$(this).duetoclose = setTimeout(function(){hide_suggestions($("#" + idbase + "_suggestions"));},100);
		});

		$('form .DataObjectPickerSuggestions li').live('mouseover click', function(event) {
			pick($(this));
		});
	});

})(jQuery);