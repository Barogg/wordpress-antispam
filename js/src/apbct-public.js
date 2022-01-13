(function() {

	var ct_date = new Date(),
		ctTimeMs = new Date().getTime(),
		ctMouseEventTimerFlag = true, //Reading interval flag
		ctMouseData = [],
		ctMouseDataCounter = 0,
		ctCheckedEmails = {},
		ctScrollCollected = false,
		ctMouseMovedCollected = false;

	function apbct_attach_event_handler(elem, event, callback){
		if(typeof window.addEventListener === "function") elem.addEventListener(event, callback);
		else                                              elem.attachEvent(event, callback);
	}

	function apbct_remove_event_handler(elem, event, callback){
		if(typeof window.removeEventListener === "function") elem.removeEventListener(event, callback);
		else                                                 elem.detachEvent(event, callback);
	}

	//Writing first key press timestamp
	var ctFunctionFirstKey = function output(event){
		var KeyTimestamp = Math.floor(new Date().getTime()/1000);
		ctSetCookie("ct_fkp_timestamp", KeyTimestamp);
		ctKeyStopStopListening();
	};

	//Reading interval
	var ctMouseReadInterval = setInterval(function(){
		ctMouseEventTimerFlag = true;
	}, 150);

	//Writting interval
	var ctMouseWriteDataInterval = setInterval(function(){
		ctSetCookie("ct_pointer_data", JSON.stringify(ctMouseData));
	}, 1200);

	//Logging mouse position each 150 ms
	var ctFunctionMouseMove = function output(event){
		ctSetMouseMoved();
		if(ctMouseEventTimerFlag === true){

			ctMouseData.push([
				Math.round(event.clientY),
				Math.round(event.clientX),
				Math.round(new Date().getTime() - ctTimeMs)
			]);

			ctMouseDataCounter++;
			ctMouseEventTimerFlag = false;
			if(ctMouseDataCounter >= 50){
				ctMouseStopData();
			}
		}
	};

	//Stop mouse observing function
	function ctMouseStopData(){
		apbct_remove_event_handler(window, "mousemove", ctFunctionMouseMove);
		clearInterval(ctMouseReadInterval);
		clearInterval(ctMouseWriteDataInterval);
	}

	//Stop key listening function
	function ctKeyStopStopListening(){
		apbct_remove_event_handler(window, "mousedown", ctFunctionFirstKey);
		apbct_remove_event_handler(window, "keydown", ctFunctionFirstKey);
	}

	function checkEmail(e) {
		var current_email = e.target.value;
		if (current_email && !(current_email in ctCheckedEmails)) {
			// Using REST API handler
			if( ctPublicFunctions.data__ajax_type === 'rest' ){
				apbct_public_sendREST(
					'check_email_before_post',
					{
						method: 'POST',
						data: {'email' : current_email},
						callback: function (result) {
							if (result.result) {
								ctCheckedEmails[current_email] = {'result' : result.result, 'timestamp': Date.now() / 1000 |0};
								ctSetCookie('ct_checked_emails', JSON.stringify(ctCheckedEmails));
							}
						},
					}
				);
				// Using AJAX request and handler
			}else if( ctPublicFunctions.data__ajax_type === 'custom_ajax' ) {
				apbct_public_sendAJAX(
					{
						action: 'apbct_email_check_before_post',
						email : current_email,
					},
					{
						apbct_ajax: 1,
						callback: function (result) {
							if (result.result) {
								ctCheckedEmails[current_email] = {'result' : result.result, 'timestamp': Date.now() / 1000 |0};
								ctSetCookie('ct_checked_emails', JSON.stringify(ctCheckedEmails));
							}
						},
					}
				);
			} else if( ctPublicFunctions.data__ajax_type === 'admin_ajax' ) {
				apbct_public_sendAJAX(
					{
						action: 'apbct_email_check_before_post',
						email : current_email,
					},
					{
						callback: function (result) {
							if (result.result) {
								ctCheckedEmails[current_email] = {'result' : result.result, 'timestamp': Date.now() / 1000 |0};
								ctSetCookie('ct_checked_emails', JSON.stringify(ctCheckedEmails));
							}
						},
					}
				);
			}
		}
	}

	function ctSetPixelImg(pixelUrl) {
		ctSetCookie('apbct_pixel_url', pixelUrl);
		if( +ctPublic.pixel__enabled ){
			if( ! document.getElementById('apbct_pixel') ) {
				jQuery('body').append( '<img alt="Cleantalk Pixel" id="apbct_pixel" style="display: none; left: 99999px;" src="' + pixelUrl + '">' );
			}
		}
	}

	function ctGetPixelUrl() {
		// Using REST API handler
		if( ctPublicFunctions.data__ajax_type === 'rest' ){
			apbct_public_sendREST(
				'apbct_get_pixel_url',
				{
					method: 'POST',
					callback: function (result) {
						if (result) {
							ctSetPixelImg(result);
						}
					},
				}
			);
		// Using AJAX request and handler
		}else{
			var ajaxType = ctPublicFunctions.data__ajax_type === 'custom_ajax' ? 1 : 0;
			apbct_public_sendAJAX(
				{
					action: 'apbct_get_pixel_url',
				},
				{
					apbct_ajax: ajaxType,
					notJson: true,
					callback: function (result) {
						if (result) {
							ctSetPixelImg(result);
						}
					},
				}
			);
		}
	}

	function ctSetHasScrolled() {
		if( ! ctScrollCollected ) {
			ctSetCookie("ct_has_scrolled", 'true');
			ctScrollCollected = true;
		}
	}

	function ctSetMouseMoved() {
		if( ! ctMouseMovedCollected ) {
			ctSetCookie("ct_mouse_moved", 'true');
			ctMouseMovedCollected = true;
		}
	}

	apbct_attach_event_handler(window, "mousemove", ctFunctionMouseMove);
	apbct_attach_event_handler(window, "mousedown", ctFunctionFirstKey);
	apbct_attach_event_handler(window, "keydown", ctFunctionFirstKey);
	apbct_attach_event_handler(window, "scroll", ctSetHasScrolled);

	// Ready function
	function apbct_ready(){

		// Collect scrolling info
		var initCookies = [
			["ct_ps_timestamp", Math.floor(new Date().getTime() / 1000)],
			["ct_fkp_timestamp", "0"],
			["ct_pointer_data", "0"],
			["ct_timezone", ct_date.getTimezoneOffset()/60*(-1) ],
			["ct_screen_info", apbctGetScreenInfo()],
			["ct_has_scrolled", 'false'],
			["ct_mouse_moved", 'false'],
		];

		if( ctPublic.data__cookies_type !== 'native' ) {
			initCookies.push(['apbct_visible_fields', '0']);
		} else {
			// Delete all visible fields cookies on load the page
			var cookiesArray = document.cookie.split(";");
			if( cookiesArray.length !== 0 ) {
				for ( var i = 0; i < cookiesArray.length; i++ ) {
					var currentCookie = cookiesArray[i].trim();
					var cookieName = currentCookie.split("=")[0];
					if( cookieName.indexOf("apbct_visible_fields_") === 0 ) {
						ctDeleteCookie(cookieName);
					}
				}
			}
		}

		if( +ctPublic.pixel__setting ){
			if( +ctPublic.pixel__enabled ){
				ctGetPixelUrl();
			} else {
				initCookies.push(['apbct_pixel_url', ctPublic.pixel__url]);
			}
		}

		if ( +ctPublic.data__email_check_before_post) {
			initCookies.push(['ct_checked_emails', '0']);
			jQuery("input[type = 'email'], #email").blur(checkEmail);
		}

		ctSetCookie(initCookies);

		setTimeout(function(){

			var visible_fields_collection = {};
			var processedForms = [];

			for(var i = 0; i < document.forms.length; i++){
				var form = document.forms[i];

				//Exclusion for forms
				if (
					form.classList.contains('slp_search_form') || //StoreLocatorPlus form
					form.parentElement.classList.contains('mec-booking') ||
					form.action.toString().indexOf('activehosted.com') !== -1 || // Active Campaign
					(form.id && form.id === 'caspioform') || //Caspio Form
					(form.classList && form.classList.contains('tinkoffPayRow')) || // TinkoffPayForm
					(form.classList && form.classList.contains('give-form')) || // GiveWP
					(form.id && form.id === 'ult-forgot-password-form') || //ult forgot password
					(form.id && form.id.toString().indexOf('calculatedfields') !== -1) // CalculatedFieldsForm
				)
					continue;

				if( ! apbct_visible_fields_already_collected( processedForms, form ) ) {
					visible_fields_collection[i] = apbct_collect_visible_fields( form );
					processedForms.push( apbct_get_form_details( form ) );
				}

				form.onsubmit_prev = form.onsubmit;

				form.ctFormIndex = i;
				form.onsubmit = function (event) {

					if ( ctPublic.data__cookies_type !== 'native' && typeof event.target.ctFormIndex !== 'undefined' ) {

						var visible_fields = {};
						visible_fields[0] = apbct_collect_visible_fields(this);
						apbct_visible_fields_set_cookie( visible_fields, event.target.ctFormIndex );
					}

					// Call previous submit action
					if (event.target.onsubmit_prev instanceof Function) {
						setTimeout(function () {
							event.target.onsubmit_prev.call(event.target, event);
						}, 500);
					}
				};
			}

			apbct_visible_fields_set_cookie( visible_fields_collection );

		}, 1000);
	}
	apbct_attach_event_handler(window, "DOMContentLoaded", apbct_ready);

}());

function apbct_collect_visible_fields( form ) {

	// Get only fields
	var inputs = [],
		inputs_visible = '',
		inputs_visible_count = 0,
		inputs_invisible = '',
		inputs_invisible_count = 0,
		inputs_with_duplicate_names = [];

	for(var key in form.elements){
		if(!isNaN(+key))
			inputs[key] = form.elements[key];
	}

	// Filter fields
	inputs = inputs.filter(function(elem){

		// Filter already added fields
		if( inputs_with_duplicate_names.indexOf( elem.getAttribute('name') ) !== -1 ){
			return false;
		}
		// Filter inputs with same names for type == radio
		if( -1 !== ['radio', 'checkbox'].indexOf( elem.getAttribute("type") )){
			inputs_with_duplicate_names.push( elem.getAttribute('name') );
			return false;
		}
		return true;
	});

	// Visible fields
	inputs.forEach(function(elem, i, elements){
		// Unnecessary fields
		if(
			elem.getAttribute("type")         === "submit" || // type == submit
			elem.getAttribute('name')         === null     ||
			elem.getAttribute('name')         === 'ct_checkjs'
		) {
			return;
		}
		// Invisible fields
		if(
			getComputedStyle(elem).display    === "none" ||   // hidden
			getComputedStyle(elem).visibility === "hidden" || // hidden
			getComputedStyle(elem).opacity    === "0" ||      // hidden
			elem.getAttribute("type")         === "hidden" // type == hidden
		) {
			if( elem.classList.contains("wp-editor-area") ) {
				inputs_visible += " " + elem.getAttribute("name");
				inputs_visible_count++;
			} else {
				inputs_invisible += " " + elem.getAttribute("name");
				inputs_invisible_count++;
			}
		}
		// Visible fields
		else {
			inputs_visible += " " + elem.getAttribute("name");
			inputs_visible_count++;
		}

	});

	inputs_invisible = inputs_invisible.trim();
	inputs_visible = inputs_visible.trim();

	return {
		visible_fields : inputs_visible,
		visible_fields_count : inputs_visible_count,
		invisible_fields : inputs_invisible,
		invisible_fields_count : inputs_invisible_count,
	}

}

function apbct_visible_fields_set_cookie( visible_fields_collection, form_id ) {

	var collection = typeof visible_fields_collection === 'object' && visible_fields_collection !== null ?  visible_fields_collection : {};

	if( ctPublic.data__cookies_type === 'native' ) {
		for ( var i in collection ) {
			if ( i > 10 ) {
				// Do not generate more than 10 cookies
				return;
			}
			var collectionIndex = form_id !== undefined ? form_id : i;
			ctSetCookie("apbct_visible_fields_" + collectionIndex, JSON.stringify( collection[i] ) );
		}
	} else {
		ctSetCookie("apbct_visible_fields", JSON.stringify( collection ) );
	}
}

function apbct_visible_fields_already_collected( formsProcessed, form ) {

	if ( formsProcessed.length > 0 && form.elements.length > 0 ) {

		var formMethod      = form.method;
		var formAction      = form.action;
		var formFieldsCount = form.elements.length;
		var formInputs      = [];

		// Getting only input elements from HTMLFormControlsCollection and putting these into the simple array.
		for( var key in form.elements ){
			if( ! isNaN( +key ) ) {
				formInputs[key] = form.elements[key];
			}
		}

		for ( var i = 0; i < formsProcessed.length; i++ ) {
			// The form with the same METHOD has not processed.
			if ( formsProcessed[i].method !== formMethod ) {
				return false;
			}
			// The form with the same ACTION has not processed.
			if ( formsProcessed[i].action !== formAction ) {
				// @ToDo actions often are different in the similar forms
				//return false;
			}
			// The form with the same FIELDS COUNT has not processed.
			if ( formsProcessed[i].fields_count !== formFieldsCount ) {
				return false;
			}

			// Compare every form fields by their TYPE and NAME
			var fieldsNames = formsProcessed[i].fields_names;
			for ( var field in fieldsNames ) {
				var res = formInputs.filter(function(item, index, array){
					var fieldName = item.name;
					var fieldType = item.type;
					if( fieldsNames[field].fieldName === fieldName && fieldsNames[field].fieldType === fieldType ) {
						return true;
					}
				});
				if( res.length > 0  ) {
					return true;
				}
			}
		}

		return false;
	}

	return false;
}

function apbct_get_form_details( form ) {

	if( form.elements.length > 0 ) {

		var fieldsNames = {};

		// Collecting fields and their names
		var inputs = form.elements;
		for (i = 0; i < inputs.length; i++) {
			var fieldName = inputs[i].name;
			var fieldType = inputs[i].type;
			fieldsNames[i] = {
				fieldName : fieldName,
				fieldType : fieldType,
			}
		}

		return {
			'method' : form.method,
			'action' : form.action,
			'fields_count' : form.elements.length,
			'fields_names' : fieldsNames,
		};
	}

	return false;
}

function apbct_js_keys__set_input_value(result, data, params, obj){
	if( document.querySelectorAll('[name^=ct_checkjs]').length > 0 ) {
		var elements = document.querySelectorAll('[name^=ct_checkjs]');
		for ( var i = 0; i < elements.length; i++ ) {
			elements[i].value = result.js_key;
		}
	}
}

function apbctGetScreenInfo() {
	return JSON.stringify({
		fullWidth : document.documentElement.scrollWidth,
		fullHeight : Math.max(
			document.body.scrollHeight, document.documentElement.scrollHeight,
			document.body.offsetHeight, document.documentElement.offsetHeight,
			document.body.clientHeight, document.documentElement.clientHeight
		),
		visibleWidth : document.documentElement.clientWidth,
		visibleHeight : document.documentElement.clientHeight,
	});
}

if(typeof jQuery !== 'undefined') {

	// Capturing responses and output block message for unknown AJAX forms
	jQuery(document).ajaxComplete(function (event, xhr, settings) {
		if (xhr.responseText && xhr.responseText.indexOf('"apbct') !== -1) {
			var response = JSON.parse(xhr.responseText);
			if (typeof response.apbct !== 'undefined') {
				response = response.apbct;
				if (response.blocked) {
					document.dispatchEvent(
						new CustomEvent( "apbctAjaxBockAlert", {
							bubbles: true,
							detail: { message: response.comment }
						} )
					);

					// Show the result by modal
					cleantalkModal.loaded = response.comment;
					cleantalkModal.open();

					if(+response.stop_script == 1)
						window.stop();
				}
			}
		}
	});
}