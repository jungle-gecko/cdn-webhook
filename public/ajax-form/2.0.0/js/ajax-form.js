+function ($) {
	'use strict';
	
	var ajaxFormScriptURL = $("script[src]").last().attr("src").split('?')[0].split('/').slice(0, -2).join('/')+'/';
	
	var AjaxForm = function (element, options)
	{
		var myAjaxForm = this;
		
		myAjaxForm.options = $.extend(true, {}, $.fn.ajaxform.defaults, options);
		myAjaxForm.$form = $(element);
		
		myAjaxForm.$form.addClass('ajax-form');
		
		myAjaxForm.$form.find('.ajax-form_submit').click(function() {
			myAjaxForm.$form.submit();
		});
		
		if (myAjaxForm.options.placeholders.both != null) {
			myAjaxForm.options.placeholders.success = myAjaxForm.options.placeholders.both;
			myAjaxForm.options.placeholders.error = myAjaxForm.options.placeholders.both;
		}
		else {
			if (myAjaxForm.options.placeholders.success == null) {
				myAjaxForm.options.placeholders.success = '.ajax-form_success_placeholder';
				myAjaxForm.$form.find(myAjaxForm.options.placeholders.success).remove(); // TODO check if required to remove parent .container-fluid
				myAjaxForm.$form.prepend(
						'<div class="container-fluid">' +
						'	<div class="ajax-form_success_placeholder hidden">' +
						'		<div class="alert alert-success alert-dismissible">' +
						'			<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
						'			<span class="glyphicon glyphicon-ok"></span>' +
						'			<span class="alert-text ajax-form_message"></span>' +
						'		</div>' +
						'	</div>' +
						'</div>'
				);
				myAjaxForm.$form.find(myAjaxForm.options.placeholders.success).hide().removeClass('hidden');
			}

			if (myAjaxForm.options.placeholders.error == null) {
				myAjaxForm.options.placeholders.error = '.ajax-form_error_placeholder';
				myAjaxForm.$form.find(myAjaxForm.options.placeholders.error).remove(); // TODO check if required to remove parent .container-fluid
				myAjaxForm.$form.prepend(
						'<div class="container-fluid">' +
						'	<div class="ajax-form_error_placeholder hidden">' +
						'		<div class="alert alert-danger alert-dismissible">' +
						'			<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
						'			<span class="glyphicon glyphicon-exclamation-sign"></span>' +
						'			<span class="alert-text ajax-form_message"></span>' +
						'		</div>' +
						'	</div>' +
						'</div>'
				);
				myAjaxForm.$form.find(myAjaxForm.options.placeholders.error).hide().removeClass('hidden');
			}
		}
		
		myAjaxForm.options.placeholders.any = myAjaxForm.options.placeholders.success + ', ' + myAjaxForm.options.placeholders.error;
		
		if (myAjaxForm.options.transitions.show.both != null) {
			myAjaxForm.options.transitions.show.success = myAjaxForm.options.transitions.show.both;
			myAjaxForm.options.transitions.show.error = myAjaxForm.options.transitions.show.both;
		}
		
		if (myAjaxForm.options.transitions.hide.both != null) {
			myAjaxForm.options.transitions.hide.success = myAjaxForm.options.transitions.hide.both;
			myAjaxForm.options.transitions.hide.error = myAjaxForm.options.transitions.hide.both;
		}
		
		if (myAjaxForm.options.callbacks.both != null) {
			myAjaxForm.options.callbacks.success = myAjaxForm.options.callbacks.both;
			myAjaxForm.options.callbacks.error = myAjaxForm.options.callbacks.both;
		}
	
		myAjaxForm.$form.find('button.close').click(function() {
			myAjaxForm.clearAlert($(this).closest(myAjaxForm.options.placeholders.any));
		});
		
		myAjaxForm.$form.submit(function(event) {

			event.stopPropagation();
			event.preventDefault();

			myAjaxForm.clearAlert(
				myAjaxForm.$form.find(myAjaxForm.options.placeholders.any),
				function()
				{
					var data = myAjaxForm.$form.serialize();
	
					var submission = $.ajax({
						type: myAjaxForm.$form.attr('method') || 'post',
						url: myAjaxForm.$form.attr('action'),
						data: data,
						dataType: "json",
						timeout: myAjaxForm.options.timeout
					})
					.done(function(result, status, jqXHR)
					{
						if (typeof result.code !== 'undefined' && result.code === 0)
						{
							myAjaxForm.showAlert('success', jqXHR);
							
							if (myAjaxForm.options.resetOnSuccess == true) 
							{
								myAjaxForm.$form[0].reset();
							}
		
							if ($.isFunction(myAjaxForm.options.callbacks.success))
							{
								if (typeof result.callback_vars !== 'undefined')
								{
									myAjaxForm.options.callbacks.success(myAjaxForm, result.callback_vars);
								}
								else
								{
									myAjaxForm.options.callbacks.success(myAjaxForm);
								}	
							}
						}
						else
						{
							myAjaxForm.showAlert('error', jqXHR);
							
							if ($.isFunction(myAjaxForm.options.callbacks.error))
							{
								if (typeof result.callback_vars !== 'undefined')
								{
									myAjaxForm.options.callbacks.error(myAjaxForm, result.callback_vars);
								}
								else
								{
									myAjaxForm.options.callbacks.error(myAjaxForm);
								}	
							}
						}
					})
					.fail(function(jqXHR, status, errorThrown)
					{
						myAjaxForm.showAlert('error', jqXHR);
						
						if ($.isFunction(myAjaxForm.options.callbacks.error))
						{
							if (typeof jqXHR.callback_vars !== 'undefined')
							{
								myAjaxForm.options.callbacks.error(myAjaxForm, jqXHR.callback_vars);
							}
							else
							{
								myAjaxForm.options.callbacks.error(myAjaxForm);
							}	
						}
					});
				}
			);
		});
		
		if ($.isFunction(myAjaxForm.options.init)) {
			myAjaxForm.options.init(myAjaxForm);
		}
	};
	
	AjaxForm.prototype.loadJSONResource = function(resource)
	{
		var resourceContent = null;
		$.ajax({
			async: false,
			url: resource,
			dataType: "json",
			success: function(result) {
				resourceContent = result;
			},
			error: function(request, status, err) {
				console.error("Unable to load '" + resource + "'");
			}
		});
		return resourceContent;
	};
	
	AjaxForm.prototype.getLocalizedText = function(key, params)
	{
		var myAjaxForm = this;
		
		if (myAjaxForm.localizedTexts == null) {
			myAjaxForm.localizedTexts = myAjaxForm.loadJSONResource(ajaxFormScriptURL + 'locales/' + myAjaxForm.options.locale + '/ajax-form-texts.json');
		}
		var text = myAjaxForm.localizedTexts[key];
		if (text !== 'undefined' && text != null && params != null && params instanceof Array) {
			for (var i = 0; i < params.length; i++) {
				text = text.replace('{' + i + '}', params[i]);
			}
		}
		return text;
	}
	
	AjaxForm.prototype.showAlert = function(type, jqXHR)
	{
		var myAjaxForm = this;
		var message = '';
		
		var content = jqXHR.responseJSON;
		
		// In case of content directly at the root
		if (typeof content === 'string')
		{
			message = content;
		}
		else if (content instanceof Array)
		{
			message = content.join('<br />');
		}
		else if (content instanceof Object)
		{
			for (var property in content) 
			{
				if (content.hasOwnProperty(property))
				{
					for (var i = 0; i < content[property].length; i++)
					{
						message += '<br/> &bull; ' + content[property][i];
						myAjaxForm.$form.find('[name=' + property + ']').closest('.form-group').addClass('has-error');
					}
				}
			}
		}

		// In case of code + message objects
		if (typeof content !== 'undefined' && typeof content.code !== 'undefined' && typeof content.message !== 'undefined')
		{
			if (typeof content.message === 'string')
			{
				message = content.message;
			}
			else if (content.message instanceof Array)
			{
				message = content.message.join('<br />');
			}
		}
		
		// In case of errors array
		if (typeof content !== 'undefined' && typeof content.errors !== 'undefined')
		{
			for (var property in content.errors) 
			{
				if (content.errors.hasOwnProperty(property))
				{
					for (var i = 0; i < content.errors[property].length; i++)
					{
						message += '<br/> &bull; ' + content.errors[property][i];
						myAjaxForm.$form.find('[name=' + property + ']').closest('.form-group').addClass('has-error');
					}
				}
			}
		}
		
		// In case of message still empty
		if (message == '' || message == 'undefined' )
		{
			message = myAjaxForm.getLocalizedText(type, [jqXHR.statusText]);
		}
		
		// Setting message
		var placeholderSelector = eval('myAjaxForm.options.placeholders.' + type);
		var $placeholder = myAjaxForm.$form.find(placeholderSelector);
		myAjaxForm.$form.find($placeholder).find('.ajax-form_message').html(message);

		// Starting transition
		myAjaxForm.options.transitions.show($placeholder);
	};
	
	AjaxForm.prototype.clearAllAlerts = function(callback)
	{
		var myAjaxForm = this;
		
		myAjaxForm.clearAlert(myAjaxForm.$form.find(myAjaxForm.options.placeholders.any), callback);
	}
	
	AjaxForm.prototype.clearAlert = function($placeholder, callback)
	{
		var myAjaxForm = this;
		
		$.when(
			myAjaxForm.options.transitions.hide($placeholder)
		).then(function()
		{
			$placeholder.find('.ajax-form_message, .ajax-form_message').empty();
			myAjaxForm.$form.find('.has-error').removeClass('has-error');

			if ($.isFunction(callback))
			{
				callback();
			}
		});
	};
	
	AjaxForm.prototype.serialize = function()
	{
	    var o = {};
	    var a = this.serializeArray();
	    $.each(a, function()
	    {
	        if (o[this.name] !== undefined)
	        {
	            if (!o[this.name].push)
	            {
	                o[this.name] = [o[this.name]];
	            }
	            o[this.name].push(this.value || '');
	        } else {
	            o[this.name] = this.value || '';
	        }
	    });
	    return o;
	};

	$.fn.ajaxform = function (options)
	{
		return new AjaxForm(this, options);
	};
	
	$.fn.ajaxform.defaults = {
		placeholders: {
			both: null,
			error: null,
			success: null
		},
		transitions: {
			show: function($placeholder) {
				$placeholder.css('opacity', 0).slideDown().animate(
					{ opacity: 1 },
					{ queue: false }
				);
			},
			hide: function($placeholder) {
				$placeholder.slideUp().animate(
					{ opacity: 0 },
					{ queue: false }
				);
			}
		},
		callbacks: {
			both: null,
			error: null,
			success: null
		},
		init: null,
		locale: 'en',
		resetOnSuccess: true,
		timeout: 60000
	};

}(jQuery);