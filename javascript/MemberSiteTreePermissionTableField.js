(function($) {

	$(document).ready(function() {

		var livingobjects = {};
		livelike = function (selector, callback) {
			if(!selector) {
				for(var selector in livingobjects) {
					$(selector).each(function(){
						if(!this.living) {
							this.living = true;
							livingobjects[selector]($(this));
						}
					});
				}
			} else {
				livingobjects[selector] = callback;
			}
		}
		setInterval(livelike, 20);

		livelike('#Form_EditForm_CustomiseUserPermissions', function(element){
			// $("#AccessOwnerID, .MemberSiteTreePermissionTableField").appendTo(element).css('background','white').css('border','1px solid #aaa').css('margin','3px').css('padding','3px');
			if($('#Form_EditForm_CustomiseUserPermissions_0')[0].checked) {
				$("#AccessOwnerID, .MemberSiteTreePermissionTableField").hide();
			}
		});
		
		$('#Form_EditForm_CustomiseUserPermissions').live('click',function(){
			if($('#Form_EditForm_CustomiseUserPermissions_0')[0].checked) {
				$("#AccessOwnerID, .MemberSiteTreePermissionTableField").slideUp('fast');
			} else {
				$("#AccessOwnerID, .MemberSiteTreePermissionTableField").slideDown('fast');
			}
		});

		$('.MemberSiteTreePermissionField .action').live('click', function(){

			$("input[id$='_page']", $(this).parentsUntil('MemberSiteTreePermissionTableField')).val();
			
			var container = $(this);
			while(!container.hasClass('MemberSiteTreePermissionField')) container = container.parent();

			if($(this).attr('name').substr(0,19) == 'action_remove_user_') {
				var namesegments = $("input[name^='View_']", container).attr('name').split('_');
				var data = {};
				data.remove = namesegments[1];
				$.getJSON('peruser/remove', data, function(data){
					if(!data) {
						statusMessage('An Error occurred', 'bad');
					} else if(data.status == 'good') {
						container.slideUp('fast', function(){
							container.detach();
						});
						statusMessage(data.message, 'good');
					} else if(data.status == 'bad') {
						statusMessage(data.message, 'bad');
					}
				});
			} else {
				var namesegments = container.attr('id').split('_');
				var data = {};
				data.user = $('.DataObjectPicker', container).val();
				data.pview = $("input[name^='View_']", container).attr('checked');
				data.pedit = $("input[name^='Edit_']", container).attr('checked');
				data.pcreate = $("input[name^='Create_']", container).attr('checked');
				data.pdelete = $("input[name^='Delete_']", container).attr('checked');
				data.ppublish = $("input[name^='Publish_']", container).attr('checked');
				data.page = $("input[id$='_page']", $(this).parentsUntil('MemberSiteTreePermissionTableField')).val();
				data.relation = namesegments[namesegments.length - 1];
				$.ajax({
					url: 'peruser/save', 
					data: data,
					dataType: 'json',
					success: function(data){
						if(!data) {
							statusMessage('An Error occurred', 'bad');
						} else if(data.status == 'bad') {
							statusMessage(data.message, 'bad');
						} else if(data.status == 'good') {
							statusMessage(data.message, 'good');
							if(data.html) {
								if(data.relation) {
									$('#relation_' + data.relation).replaceWith(data.html);
								} else {
									parent = container.parent();
									container.detach();
									parent.append(data.html);
									statusMessage(data.message, 'good');
								}
							}
						}
					}
				});
			}
			
			return false;
		});

	});

})(jQuery);