 // JavaScript Document

( function( $ ){
	
	/*$('#components').sortable({
	});*/
	
	menuAdmin = {}; menuAdmin.struct = menuAdmin.helpers = menuAdmin.prop = {}
	
	menuAdmin.prop.maxCustomCol	= 4;
	
	menuAdmin.init = function(){
	
		menuAdmin.struct = {
			edit			: false,
			activeTool		: '',
			components		: {},
			customEditing	: false,
		}
		$( "#md_settings_tabs" ).tabs({ active : 0 });
		
		$('#all_in_settings_submit').on('click', function(){
			var settings = {};
			$('#general_settings').find('*[name*=all_in_settings]').each(function(index, element) {
                settings[$(element).data('prop')] = menuAdmin.helpers.getValues( element );
            }); console.log(settings);
			$.ajax({
				url		: Ajax.url,
				type	: 'POST',
				data	: {
					action	: 'allin_save_settings',
					settings: settings,
				},
				beforeSend : function(){
					menuAdmin.helpers.showLoader( true );
				},
				success	: function( data ){
					menuAdmin.helpers.showLoader( false );
				},
				error	: function( error ){ console.log( error ); }
			});
		});
		
		$('#all_in_settings_flush').on('click', function(){
			$.ajax({
				url		: Ajax.url,
				type	: 'POST',
				data	: {
					action	: 'allin_flush_cache',
				},
				beforeSend : function(){
					menuAdmin.helpers.showLoader( true );
				},
				success	: function( data ){
					menuAdmin.helpers.showLoader( false );
				},
				error	: function( error ){ console.log( error ); }
			});
		});
	
	/*	----------------------------------------
		CUSTOM MENU TAB
		In custom menu tab the user can choose from
		various objects to add in the new menu tab.
		----------------------------------------	*/
		//	Clicking on md_tool the edit popup becomes visible with the 
		//	appropriate form and values in it.
		$('.md_placeholder_main').on( 'click', '.md_tool', function(){
			if ( $('#md_popup_editor').hasClass('closed') ){
				menuAdmin.struct.activeTool = this;
				var type 	= $(this).data('type'); 
				var tpl_id 	= $(this).data('tpl');
				var toolObj = menuAdmin.helpers.getToolValues(menuAdmin.struct.activeTool);
				var tpl		= document.getElementById( tpl_id ); 
				switch ( type ){
				  case 'image':
					if ( typeof wp !== 'undefined' && wp.media && wp.media.editor ){
					  wp.media.editor.open();
					  wp.media.editor.send.attachment = function(props, attachment) {
						console.log(attachment);
						var imageUrl	= ( attachment.sizes.large ) ? attachment.sizes.large.url : attachment.sizes.medium.url;
						var toolObj = {
							md_tool_image_url : imageUrl,
						}
						$(menuAdmin.struct.activeTool).find('.md_tool_content').empty();
						$(tpl).tmpl(toolObj)
						  .appendTo( $(menuAdmin.struct.activeTool).find('.md_tool_content') );
						//$('.md_tool_value', menuAdmin.struct.activeTool )
						  //.val( JSON.stringify([attachment.sizes.medium.url]));
					  }
					}
					break;
				  case 'header':
				  case 'link':
				  case 'paragraph':
				  case 'map':
				  case 'youtube':
				  case 'html':
					menuAdmin.helpers.openEditPopup( true ); console.log(toolObj);
					$('#md_popup_inputs').attr('data-tpl',tpl_id);
					$(tpl).tmpl(toolObj).appendTo( '#md_popup_inputs' );
					break;
				}
			}
		});
		$('#md_popup_editor').on( 'click', 'span.md_popup_close', function(){
			menuAdmin.helpers.openEditPopup( false );
		});
		


		//	Prevent forwarding to any link contained in any md_tool and lose
		//	any unsaved information
		$('.md_placeholder_main').on( 'click', 'a', function(e){
			e.preventDefault(); console.log('prevent');
		});
		

		$('#md_popup_submit').on( 'click', function(){
			var active	= menuAdmin.struct.activeTool;
			var toolObj = menuAdmin.helpers.getToolValues('#md_popup_inputs');
			var tpl		= document.getElementById( $(active).data('tpl') );
			var replacer= $(active).find('.md_tool_content').empty();

			$(tpl).tmpl(toolObj).appendTo(replacer);
			//$( '.md_tool_value', menuAdmin.struct.activeTool ).val( JSON.stringify(toolObj));
			menuAdmin.helpers.openEditPopup( false );
			$('#md_popup_inputs').empty();
		})
		
		$('#md_popup_editor').on( 'click', 'button[name="md_tool_find_icon"]', function(){
			var input 	= $(this).data('to-input'); console.log(input);
			var parent	= $(this).closest('.md_tool_icon_container');
			wp.media.editor.open();
			wp.media.editor.send.attachment = function(props, attachment) {
				//	Get the attachement url
				console.log(attachment);
				var img;
				if (attachment.sizes){
					if (attachment.sizes.thumbnail && attachment.sizes.thumbnail.url){
						img	= attachment.sizes.thumbnail.url;
					}
					else{
						img	= attachment.sizes.full.url;
					}
				}
				else{
					img = '';
				}
				console.log(img);
				$(parent).find('.md_tool_preview').attr('src', img).show();
				$(parent).find('button[name="md_tool_remove_icon"]').show();
				$(parent).find('input[name="'+input+'"]').val(img);
			}			
		});
		$('#md_popup_editor').on( 'click', 'button[name="md_tool_remove_icon"]', function(){
			var parent	= $(this).closest('.md_tool_icon_container');
			var input 	= $(this).data('to-input'); console.log(input);
			$(parent).find('.md_tool_preview').attr('src', null).hide();
			$(parent).find('button[name="md_tool_remove_icon"]').hide();
			$('input[name="'+input+'"]').val(null);
		});
		
		//	Opening the tab options in tabs lists
		$('#manage_components').on( 'click', 'i.md_open_controls', function(){
			var parentClass	= $(this).data('parent_class'); 
			var parent		= $(this).closest(parentClass).toggleClass('open_controls');
			//	Closing the tab options by mouseleaving the menu item
			$(parent).on( 'mouseleave', function(){
				var _this = this;
				setTimeout( function(){
					$(_this).removeClass('open_controls');
				}, 200);
			});
		});

		//	Edit the menu tab
		$('#manage_components').on( 'click', '.md_edit_icon', function(){
			var parent	= $(this).closest('.md_component_item');
			var menu_id = $(this).data('md_id');
			var type 	= $(this).data('type');
			var name	= $(parent).find('input[name="md_component_title"]').val();
			var compValues	= $(parent).find('input[name="md_component_values"]').val();
			//console.log( JSON.parse(compValues) );
			if ( type == 'static' || type == 'search' ){
				menuAdmin.helpers.message('md_error', 'No Edit options');
			}
			else{
				var compValues = ( compValues ) ? JSON.parse(compValues) : [];
				menuAdmin.helpers.loadCompForm(type, menu_id, name, compValues);
			}
		});
		//	Delete the menu tab
		$('#manage_components').on( 'click', '.md_delete_icon', function(){
			var type 		= $(this).data('type');
			var deleteItem	= $(this).data('delete');
			if ( type == 'static' || type == 'search' ){
				menuAdmin.helpers.message('md_error', 'Cannot delete this tab type');
			}
			else{
				switch (deleteItem){
					case 'component':
						var menu_id = $(this).data('md_id');
						menuAdmin.helpers.deleteComponent(menu_id);
						break;
					case 'tab_column':
						$(this).closest('.md_single_placeholder').remove();
				}
			}
		});

	/*	----------------------------------------
		MENU EDIT
		In custom menu tab the user can choose from
		various objects to add in the mew menu tab.
		----------------------------------------	*/
		// Create new menu
		$('#md_menu_add').on('click', function(){
			var name = $('input[name="md_menu_name"]').val();
			if ( name ){	
				menuAdmin.helpers.addMenu( name );	}
			else{
				menuAdmin.helpers.message( 'md_error', 'Please give a name') }
		});
		
		$('#md_menus_list').on( 'click', '.md_menu_edit', function(){
			var parent = $(this).closest('.md_menu_item');
			var rot = $(parent).hasClass('open');
			if ( rot === true ){
				$(parent).removeClass('open');
			}else{
				$(parent).addClass('open');
			}
		});
		//	Change the color theme of a menu
		$('#md_menus_list').on( 'click', '.md_menu_color', function(){
			//	Find the menu we are editing
			var parent 	= $(this).closest('.md_menu_item');
			//	and which color theme the user selected
			var theme	= $(this).data('color'); console.log(theme);
			//	remove the previous active state
			$(parent).find('.md_menu_color.active').removeClass('active');
			//	and add the new one
			$(this).addClass('active');
			//	Now the set the new state into the input hidden field to read it
			//	when the user press "Save Menu"
			$(parent).find('input[data-attr="color"]').val(theme);
		});		
		$('#md_menus_list').on( 'click', '.md_minicomp_delete', function(){
			var parent = $(this).closest('.md_minicomp_item');
			$(parent).remove();
		});
		
		//	The user presses the "Save Menu"
		//	Every field name="menu_tab[]" input's value must be parsed and update
		//	the menu values
		$('#md_menus_list').on( 'click', 'button.update_menu', function(){
			//	Find the menu we are editing
			var parent	= $(this).closest('.md_menu_hidden');
			//	and get the md_id value. We will use it to update the mysql record
			var menu_id = $(this).data('menu_id');
			//	read the name of the menu
			var name 	= $(parent).find('input[name="md_menu_settings_name"]').val();
			//	if name is empty we cannot continue
			if ( name ){
				//	Initialize the values object. This object will host all the new values
				var values = {};
				//	Start loop through the alignment placeholders and collect the menu tabs
				//	the user dropped in
				$(parent).find('.md_menu_comp_order').each(function(index, element) {
					var index = $(element).data('align');
					var valuesY = [];
					//	Second loop to get foreach align all the menu tabs
					$(element).find('input[name*="menu_tab"]').each(function(index, element) {
						//	and get the value
						valuesY.push( $(element).val() );
					});
					values[index] = valuesY;
				});
				//	Get the selected color theme
				values['color'] = $(parent).find('input[data-attr="color"]').val();
				//	Check if the user selected the menu to be sticky (on scroll down)
				values['sticky'] = $(parent).find('input[data-attr="sticky"]').attr('checked');
				//	Get the max-width of the inner element of the menu
				values['max_width'] = $(parent).find('input[data-attr="max_width"]').val();
				
				console.log( JSON.stringify(values));
				menuAdmin.helpers.updateMenu( menu_id, name, values );
			}
			else{
				menuAdmin.helpers.message('md_error', 'Menu name cannot be empty');
			}
		});
		
		//	The user presses the "Delete Menu"
		//	For better user experience first check if the user is sure she want to
		//	delete the menu so fadeIn the hidden_indicator span tag
		$('#md_menus_list').on( 'click', 'button.delete_menu', function(){
			//	Find the menu we are editing and the indicator
			var parent 		= $(this).closest('.md_menu_item');
			var indicator 	= $(parent).find('span.md_hidden_text_indicator');
			//	and show the indicator ask about the deletion of menu
			$(indicator).fadeIn(100);
			$(indicator).find('a.md_delete_menu').on( 'click', function(e){
				e.preventDefault();
				var md_id = $(this).data('md_id');
				menuAdmin.helpers.deleteComponent( md_id );
			})
			$(indicator).find('a.md_delete_menu_cancel').on( 'click', function(e){
				e.preventDefault();
				$(indicator).fadeOut(100);
			})
		});

		
	/*	----------------------------------------
		MENU ITEMS SETTINGS
		----------------------------------------	*/
		//	Fill the md_component_form with the appropriate form template
		//	to create a new menu item
		$( '#md_component_add' ).on( 'click', function(){
			var type 	= $('select[name="md_component_select"]').val();
			var values	= {};
			menuAdmin.helpers.loadCompForm( type, null, null, values );
		});
		//	Parse form values and create the new menu Item
		//	For any type of new menu tab EXCEPT "custom"
		$('#md_component_form').on( 'click', '.add-component', function(){
			var _this 	= this;
			var menu_id	= $(this).data('menu_id'); 
			//	Get the form values
			var storeObj = menuAdmin.helpers.readCompForm( _this );//console.log( storeObj );
			if ( storeObj ){
				menuAdmin.helpers.addComponent( storeObj, menu_id );
			} else{
				menuAdmin.helpers.message('md_error', 'Empty values');
			}
		});
		$( '#md_component_form' ).on( 'change','input,select,textarea',function(){
			if ( $(this).hasClass('empty_value')){
				$(this).removeClass('empty_value');
			}
		});
		//	Manage the depedencies for showing or hiding elements
		$( '#md_component_form' ).on( 'change', 'input[data-dependant*=from], select[data-dependant*=from]', function(){
			var con		= $(this).data('dependant'); //console.log(con);
			var value	= $(this).val(); //console.log(value);
			if ( value ){ // Show the dependents
				$('*[data-dependency='+con+']').show();			}
			else{	// Hide the dependents
				$('*[data-dependency='+con+']').hide();			}
		});
		//	Saving custom menu tabs
		$('#md_component_custom').on( 'click', '.add-component', function(){
			var _this = this; //console.log(_this,$(_this).data('menu_id'));
			menuAdmin.helpers.saveCustomTab(_this);
		})
		//	Add new placeholder (column) for custom menu tools
		$('#md_component_custom').on( 'click', '.add_placeholder', function(){
			menuAdmin.helpers.addToolPlaceholder();
		});
	
		/*$('#edit_popup').on( 'click', '.add-component', function(){
			
			var _this = this;
			var storeObj = menuAdmin.helpers.readCompForm( _this );
			$('section.menu-item.editing')
				.removeClass('editing')
				.find('input[name*=menudash_values]')
				.val(JSON.stringify(storeObj.values))
			$('#edit_popup').hide().empty();
			menuAdmin.struct.edit = false;
		});
	
		
		$('#menu_dash_grab_components').on( 'click', 'ins.close-button', function(){
			$(this).closest('section.menu-item').remove();
		});
	
		$('.grab_items').on( 'click', 'ins.edit-button', function(){
			
			if ( menuAdmin.struct.edit == false ){
				menuAdmin.struct.edit = true;
				
				var type	= $(this).data('type'); //console.log(type);
				var parent	= $(this).closest('section.menu-item'); //console.log(parent);
				var edit 	= $(parent).find('input[name*=menudash_values]').val(); //console.log(edit);
				var values 	= JSON.parse(edit); //console.log(values);
				var cloner	= $('#components .md_single_component[data-type='+type+']').clone(true).show();
				
				//	Prepare the popup and fill with the form;
				$('#edit_popup').append(cloner).show().css({
					top		: $(parent).position().top + 38,
					left	: $(parent).position().left,
					width	: 328,//$(parent).width(),
				}); //console.log(cloner);
				
				//	Add the class to the edited value to know 
				//	which menu item to alter
				$(parent).addClass('editing');
				
				//	Fill the form with the old values
				for ( var key in values ){ console.log( key, values[key] );
					
					var current = $(cloner).find('*[data-attr*='+ key +']');
					
					if ( $(current).prop('tagName') === 'INPUT' && $(current).attr('type') === 'checkbox' ){
						$(current).attr('checked', values[key]);
					}
					else{
						$(current).val(values[key]);
					}
					
					if ( $(current).data('dependant') && $(current).val() ){
						$(cloner).find('*[data-dependency="'+$(current).data('dependant')+'"]').show();
					}
				}
				
				$(cloner).find('.add-component').val('Change');
			}
			else{
			}
		});*/
	
		/*$('#menu_dash_grab_components').sortable({
			connectWith	: "#menu_dash_grab_components_right",
			dropOnEmpty	: true,
		});
		$('#menu_dash_grab_components_right').sortable({
			connectWith	: "#menu_dash_grab_components",
			dropOnEmpty	: true,
		});*/
		
		$('#md_component_form').on( 'keyup', 'input[data-field="search-post"]', function(){

				var value 	= $(this).val(); 
				var parent	= $(this).closest('.md_single_component');
				var results	= $(parent).find('.results');
				if ( value.length >= 3 ){
					$.ajax({
						url		: Ajax.url,
						data	: {
							action	: 'allin_search_posts',
							s		: value,
						},
						beforeSend : function(){
						},
						success	: function( data ){
							$(results).empty();
							if ( data.posts && data.posts.length > 0){
								$(results).show();
								$("#menu-autocomplete-post-tmpl")
									.tmpl(data.posts).appendTo(results);
							}
						},
						error	: function( error ){ console.log( error ); }
					});
				}
			//}, 1000);
		});
		
		$('#md_component_form').on( 'click', '.autocomplete-post-item', function(){
			var ID		= $(this).data('id'); console.log( ID );
			var parent	= $(this).closest('.md_single_component');
			$(parent).find('input[data-attr="postid"]').val(ID);
			$(parent).find('input[data-field="search-post"]').val( $(this).html() );
			$(parent).find('section.results').empty().hide();
		});
		
		//	Fill the forms with data 
		menuAdmin.helpers.loadItems();
	}
	
	
	menuAdmin.helpers.openEditPopup = function( show ){
		if ( show ){
			$('#md_popup_editor').removeClass('closed').addClass('open');
		}	else{
			$('#md_popup_inputs').empty();
			$('#md_popup_editor').removeClass('open').addClass('closed');
		}
	}
	menuAdmin.helpers.saveOptions = function(){
		
		// SAVE THE MENU ITEMS
		var values = {};
		$('input[name*="menudash_values"]').each(function(index, element){
			
			var holder	= $(element).closest('.grab_components').data('placeholder');
			if ( typeof values[holder] === 'undefined' ){
				values[holder] = [];
			}
						
			var type 	= $(element).data('type');
			var value	= menuAdmin.helpers.getValues( element );
			
			values[holder].push({
				type	: type,
				values	: value,
			});
        });		//console.log( values );
		$.ajax({
			url		: Ajax.url,
			type	: 'POST',
			dataType: 'json',
			data	: {
				action	: 'allin_save_options',
				values	: values,
			},
			success	: function( data ){
				console.log( data );
				$('#menu_dash_grab_components').sortable();
			},
			error	: function( error ){ console.log( error ); }
		});
	}

	menuAdmin.helpers.readCompForm = function ( _this ){
		
		var type 		= $(_this).data('type');
		var values		= {};
		var storeObj	= {};
		var container 	= $(_this).closest('.md_single_component'); 
		var title		= $(container).find('*[data-attr="title"]').val(); 
		var valid		= true;	// valid is true unless a "required" input is empty
		if ( typeof title === 'undefined' ){ title = type; }
		
		$(container).find( '*[name*="add-' + type + '"]' ).each(function(index, element) { 
			
			var fieldType	= $(element).data('attr');
			var need		= $(element).data('need');
			//	Reading the element's value and add it to the object
            values[fieldType] = menuAdmin.helpers.getValues( element );
			//	Check if a "required" form element is empty
			if ( values[fieldType] == false && need === 'required' ){
				$(element).addClass('empty_value');
				valid = false;
			}
			/*else{
				$(element).removeClass('empty_value');
			}*/
        });
		//	The final format of the returned object
		storeObj = {
			type	: type,
			title	: title,
			values	: values,
		}
		//	Return false if the form is not valid ( valid == false );
		if ( valid ){
			return storeObj;
		}else{
			return false;
		}
	}
	
/*	menuAdmin.helpers.getValues 
	The function "reads" the values of each form input
	*/
	menuAdmin.helpers.getValues = function( element ){

		//	GET VALUES PER INPUT TYPE
		if ( $(element).is('input') && $(element).attr('type') == 'checkbox' ){
			var value	= typeof $(element).attr('checked') === 'undefined' ? false : true;
		}
		else if (  $(element).is('input') && ( $(element).attr('type') == 'text' || $(element).attr('type') == 'hidden' ) ){
			var value	= $(element).val().replace(/(\r\n|\n|\r)/gm,"");;
		}
		else if ( $(element).is('select') ){
			var value	= $(element).val();
		}
		else if ( $(element).is('textarea') ){
			var value	= $(element).val().replace(/(\r\n|\n|\r)/gm,"");
			console.log( value );
		}
			
		return value;		
	}
	
	menuAdmin.helpers.addMenu = function( name ){
		$.ajax({
			url		: Ajax.url,
			type	: 'POST',
			dataType: 'json',
			data	: {
				action	: 'md_add_menu',
				name	: name,
			},
			beforeSend	: function(){
				menuAdmin.helpers.showLoader( true );
			},
			success	: function( data ){
				if ( data.error == 0 ){
					menuAdmin.helpers.message('md_success', 'Menu uccessfully added');
					menuAdmin.helpers.loadItems();					
				}
				else{
					menuAdmin.helpers.message('md_error', 'Try later or contact support team' );
				}
				menuAdmin.helpers.showLoader( false );
			},
			error	: function( error ){ console.log( error ); }
		});
	}
	menuAdmin.helpers.updateMenu = function( menu_id, name, values ){
		$.ajax({
			url		: Ajax.url,
			type	: 'POST',
			dataType: 'json',
			data	: {
				action	: 'md_update_menu',
				menu_id	: menu_id,
				name	: name,
				values	: JSON.stringify(values),
			},
			beforeSend	: function(){
				menuAdmin.helpers.showLoader( true );
			},
			success	: function( data ){
				if ( data.error == 0 ){
					menuAdmin.helpers.message('md_success', 'Menu uccessfully updated');
					menuAdmin.helpers.loadItems();					
				}
				else{
					menuAdmin.helpers.message('md_error', 'Try later or contact support team' );
				}
				menuAdmin.helpers.showLoader( false );
			},
			error	: function( error ){ console.log( error ); }
		});
	}
	/*menuAdmin.helpers.loadMenus = function(){
		$.ajax({
			url		: Ajax.url,
			type	: 'GET',
			dataType: 'json',
			data	: {
				action	: 'md_get_menus',
			},
			success	: function( data ){
				//console.log( data );
				if ( data.items ){
					$('#md_menus_list').empty(); 
					for ( var key in data.items ){
						if (data.items[key].md_values){
							data.items[key].md_values = JSON.parse(data.items[key].md_values);
						}
					}
					$('#md_menus_lists_tmpl').tmpl(data.items).appendTo('#md_menus_list');
				}
			},
			error	: function( error ){ console.log( error ); }
		});
	}*/
/***
 *	The function is responsible to add the new tab (component) in the database
 *	creating the $.ajax request
 */
	menuAdmin.helpers.addComponent = function( values, menu_id ){
		//console.log(values);
		$.ajax({
			url		: Ajax.url,
			type	: 'POST',
			dataType: 'json',
			data	: {
				action	: 'md_add_component',
				name	: values.title,
				values	: values,
				menu_id	: menu_id,
			},
			beforeSend : function(){
				menuAdmin.helpers.showLoader( true );
			},
			success	: function( data ){ //console.log(data);
				if ( data.error == 0 ){
					//menuAdmin.helpers.loadComponents();
					//	If no error occur then show the success message and
					//	initialize the interface
					menuAdmin.struct.customEditing = false;
					//	Empty the previous values
					$('#md_component_form, .md_placeholder_main').empty();	
					$('#md_placeholder_ctrls input[name="md_custom_tab_name"]').val('');
					$('#md_placeholder_ctrls button.add-component').data('menu_id', '');;
					
					menuAdmin.helpers.addToolPlaceholder();
					menuAdmin.helpers.loadItems();
					menuAdmin.helpers.message('md_success', 'Success');
				}
				else{
					menuAdmin.helpers.showLoader( false );
					menuAdmin.helpers.message('md_error', 'Something went wrong');
				}
			},
			error	: function( error ){ console.log( error ); }
		});
	}
	menuAdmin.helpers.updateComponent = function( menu_id, values ){
		$.ajax({
			url		: Ajax.url,
			type	: 'POST',
			dataType: 'json',
			data	: {
				action	: 'md_update_component',
				menu_id	: menu_id,
				values	: values,
			},
			success	: function( data ){ console.log(data);
				if ( data.error == 0 ){
					//menuAdmin.helpers.loadComponents();					
				}
			},
			error	: function( error ){ console.log( error ); }
		});
	}
	
	//	Removes a row from md_metas table by the md_id (menu_id)
	menuAdmin.helpers.deleteComponent = function( menu_id ){
		$.ajax({
			url		: Ajax.url,
			type	: 'POST',
			dataType: 'json',
			data	: {
				action	: 'md_delete_component',
				menu_id	: menu_id,
			},
			beforeSend : function(){
				menuAdmin.helpers.showLoader( true );
			},
			success	: function( data ){ //console.log(data);
				if ( data.error == 0 ){
					menuAdmin.helpers.loadItems();				
				}
			},
			error	: function( error ){ console.log( error ); }
		});
	}
	/*menuAdmin.helpers.loadComponents = function(){

		$.ajax({
			url		: Ajax.url,
			type	: 'GET',
			dataType: 'json',
			data	: {
				action	: 'md_get_components',
			},
			success	: function( data ){  console.log( data );
			if ( data.items ){
				$('#md_components_list, #md_menus_available_comp').empty(); 
				$('#md_components_list_tmpl').tmpl(data.items).appendTo('#md_components_list');
				$('#md_components_mini_list_tmpl').tmpl(data.items).appendTo('#md_menus_available_comp');
				menuAdmin.struct.components = {};
				for ( var key in data.items ){
					menuAdmin.struct.components[data.items[key].md_id]=data.items[key].md_name;
				}
			}
			},
			error	: function( error ){ console.log( error ); }
		});
	}*/
	
	//	This function updating the data in the settings forms
	menuAdmin.helpers.loadItems = function(){
		$.ajax({
			url		: Ajax.url,
			type	: 'GET',
			dataType: 'json',
			data	: {
				action	: 'md_get_items',
			},
			beforeSend : function(){
				menuAdmin.helpers.showLoader( true );
			},
			success	: function( data ){  //console.log( data );
			if ( data.error === 0 ){
				
				//	Fill the Tabs in the Tabs Section
				var menuTabs = '#md_components_list';
				$(menuTabs).empty(); 
				$('#md_components_list_tmpl').tmpl(data.tabs).appendTo(menuTabs);
				//	Fill the Tabs in the Menus Section. This is the mini list where
				//	the tabs can be added in a menu.
				var menuTabsMini = '#md_menus_available_comp';
				$(menuTabsMini).empty(); 
				//	Adding static menu tabs (home, search)
				$('#md_components_mini_list_tmpl').tmpl(data.tabs).appendTo(menuTabsMini);
				
				menuAdmin.struct.components = {};
				for ( var key in data.tabs ){
					menuAdmin.struct.components[data.tabs[key].md_id] = data.tabs[key].md_name;
				} 
				
				$('#md_menus_list').empty(); 
				$('#md_menus_lists_tmpl').tmpl(data.menus).appendTo('#md_menus_list');
				menuAdmin.helpers.showLoader( false );
				menuAdmin.helpers.setDraggables();
			}
			},
			error	: function( error ){ console.log( error ); }
		});
	}
   /*	
   	*	Contains all the appropriate functionality to set the elements
	*	sortable and draggable in admin page.
	*/
	menuAdmin.helpers.setDraggables = function(){	//console.log('setDraggables');
		//	MENUS SECTION
		//	Makes each menus components (left, center, right) sortable and
		//	to connect sortable each other
		$( ".md_menu_comp_order > .inner").sortable({
			revert: true,
			connectWith: ".md_menu_comp_order > .inner"
		});
		//	You can drag already created tabs to a menu alignment
		$('#md_menus_available_comp > .md_minicomp_item').draggable({
			connectToSortable: ".md_menu_comp_order > .inner",
			helper	: "clone",
			revert	: "invalid",
			stop	: function( event, ui ){
				console.log(event, ui);
			},
			start	: function( event, ui ){
				$('.md_menu_comp_order').addClass('dropping_accept');
			},
			stop	: function( event, ui ){
				$('.md_menu_comp_order').removeClass('dropping_accept');
			},
		})
		$('.md_menu_comp_order').droppable({
			over	: function( event, ui ){
				$('.md_menu_comp_order').removeClass('dropping_over');
				$(this).addClass('dropping_over');
			},
			out		: function( event, ui ){
				$('.md_menu_comp_order').removeClass('dropping_over');
			}
		});
		//	Open the menu edit options to drag the tab
		/*$('.md_menu_item').droppable({
			over: function(event, ui){
				console.log($(this).hasClass('open'));
				$('.md_menu_item').removeClass('open');
				if ( $(this).hasClass('open') === false ){
					$(this).addClass('open');
				}
			},
		});*/
		//	TABS SECTION
		//	
		$( ".md_single_placeholder > .md_single_inside" ).sortable({
			revert: true,
			handle	: 'span.header',
			connectWith: ".md_single_placeholder > .md_single_inside",
			start: function( event, ui ) {
				$('#md_custom_bin').css({paddingTop: '200px'}).animate({ bottom: 0}, 200);
			},
			stop: function( event, ui ) {
				$('#md_custom_bin').css({paddingTop: '0px'}).animate({ bottom: '-96px'}, 200);
			},
		});
		$('#md_custom_bin').droppable({
			over: function(event, ui){
				$(this).addClass('active');
			},
			out: function(event, ui){
				$(this).removeClass('active');
			},
			drop: function( event, ui ) {
				$(this).removeClass('active');
				var _this = ui.draggable[0];
				$(_this).remove();
			},
		})
		$( ".md_placeholder_main" ).sortable({
			revert: true,
			handle: "header.md_single_header",
			start: function( event, ui ) {
				$('#md_custom_bin').css({paddingTop: '200px'}).animate({ bottom: 0}, 200);
			},
			stop: function( event, ui ) {
				$('#md_custom_bin').css({paddingTop: '0px'}).animate({ bottom: '-96px'}, 200);
			},
		});
		$( "#md_placeholder_tools > .md_tool" ).draggable({
			connectToSortable: ".md_single_placeholder > .md_single_inside",
			helper	: "clone",
			revert	: "invalid",
			stop	: function( event, ui ){
				var _this = ui.helper[0]; console.log( $(_this).data('tpl') );
				var tpl	= document.getElementById( $(_this).data('tpl') );
				$(_this).css({ width : '260px', height : 'auto' });
				$(_this)[0].className = $(_this)[0].className
					.replace(/\ md_button_.*?\b/g, '');				
				$(tpl).tmpl().appendTo( $(_this).find('.md_tool_content') );
			}
		});
	}
	menuAdmin.helpers.getToolValues = function( parent ){
		var toolObj = {};
		$( '.md_tool_input', parent ).each(function(index, element) {
			var value	= $(element).val();
			//	Remove the new line characters from value
			toolObj[$(element).data('tpl_place')] = value.replace(/(\r\n|\n|\r)/gm,"");
        }); //console.log(toolObj);
		return toolObj;
	}
	menuAdmin.helpers.saveCustomTab = function( _this ){
		var menu_id	= $(_this).attr('data-menu_id'); console.log(menu_id);
		var title 	= $('input[name=md_custom_tab_name]').val();
		var storeObj = {
			type	: 'custom',
			title	: title,
			values	: [],
		}
		if ( title ){
		  $('.md_single_placeholder').each(function(x, elementX) {
			  //	Initialize the array for each single placeholder
			  storeObj.values[x] = [];
			  $(elementX).find('.md_tool_edit').each(function(y, elementY) {
				  // Initialize the object for each tool
				  var dataInput = $(elementY).data('input'); 
				  var tool = {};
				  $(elementY).find('.md_tool_input').each(function(z, elementZ) {
					  // Get the values and keys from the inputs for storing
					  var key		= $(elementZ).data('tpl_place');
					  var value 	= $(elementZ).val();
					  tool[key]	= value; //console.log( key, value );
				  });
				  storeObj.values[x].push(tool);
			  });
		  }); console.log(storeObj);
		  menuAdmin.helpers.addComponent( storeObj, menu_id );
		}else{
		  menuAdmin.helpers.message('md_error', 'Please submit a tab title');
		}
	}
	//	Load the form in the md_component_form div to submit the new menu tab
	//	Load the appropriate form template ccording the type parameter
	//	If the values parameter is not empty fill the form with the already submitted values
	menuAdmin.helpers.loadCompForm = function( type, md_id, name, values ){
		//	Check what type of menu tab the user selected
		//	Custom tab has SPECIAL MANIPULATION
		if ( type == 'custom' ){
			//	Set customEditing variable to true to avoid lose unsaved work
			menuAdmin.struct.customEditing = true;
			//	Change the interface. As we mention before custom tab is not like
			//	the other tabs
			$('#md_component_form').empty().hide();
			$('#md_component_custom').show();
			//	Empty the div if anything left from previous submissions
			$('.md_placeholder_main').empty();
			$('#md_placeholder_ctrls input[name="md_custom_tab_name"]').val('');
			//var singlePlaceholders = $('.md_single_placeholder').empty();
			if ( name ){
				$('input[name=md_custom_tab_name]').val(name);
			}
			//	if the object (values) is empty then the user creates a new custom tab else
			//	is editing an existing one
			if ( $.isEmptyObject(values) ){
				//	Insert a new empty placeholder column to start dragging content tools
				menuAdmin.helpers.addToolPlaceholder();
			}
			else{
				// Fill the values for editing
				for ( var x in values ){
					// each x key value is a distinct column
					var singlePlaceholder = menuAdmin.helpers.addToolPlaceholder();
					//	Second loop to extract the content tools
					//	each y key value is a distinct content tool
					for ( var y in values[x] ){
						//	Get the original empty content tool from the toolbar
						var source	= 'aside.md_tool[data-type="'+values[x][y].md_tool_type+'"]';
						var element = $('#md_placeholder_tools > '+ source);
						//	and append it into the current column
						var tool 	= element.clone().appendTo( $(singlePlaceholder).find('.md_single_inside'));
						//	finally we fill the values
						//	values[x][y] contains the values of the current content tool
						var tpl		= document.getElementById( 'md_tool_'+values[x][y].md_tool_type );
						$(tpl).tmpl(values[x][y]).appendTo( $(tool).find('.md_tool_content') );
					}
				}
			}
			//	Set the data-menu_id attribute of the trigger button so we can separate
			//	ADD from UPDATE mode
			$('#md_component_custom .add-component').attr('data-menu_id', md_id);
		}
		else{
			//	If it's not a custom tab then show the md_component_form div
			$('#md_component_custom').hide();
			$('#md_component_form').empty().show();
			//	Render the html form template
			$('#md_component_add_' + type).tmpl({md_id : md_id}).appendTo('#md_component_form');
			//	Fill the form with the submitted values (if it's for editing)
			for ( var key in values ){
				var formElement = $('#md_component_form *[data-attr='+key+']');
				var dependant	= $(formElement).data('dependant');
				var formTag		= formElement[0].nodeName;
				var formType	= $(formElement[0]).attr('type');
								
				if ( formTag == 'INPUT' && formType == 'checkbox' ){
					if (values[key] == 'true') $(formElement).attr('checked',true);
				}
				else{
					$( formElement ).val(values[key]);
				}
				//	Check if there is depedency
				if ( dependant && $(formElement).val() ){
					$('#md_component_form *[data-dependency="'+dependant+'"]').show();
				}
				
			}
		}
		//	Change the button html depending if it's for 'Add' or 'Update'
		var btnLabel = ( md_id ) ? 'Update Menu Tab' : 'Save Menu Tab';
		$('.add-component').html(btnLabel);
	}
	
	//	The function creates a new md_single_placeholder div (a new column) in a
	//	a custom menu tab. The script template of the div is "#md_tool_single_placeholder".
	//	the function returns the generated element so we can manipulate it outside this function
	menuAdmin.helpers.addToolPlaceholder = function(){	//console.log('addToolPlaceholder');
		// Get the number of columns (md_single_placeholder) exists
		var columns	= $('.md_placeholder_main > .md_single_placeholder').length;
		if ( columns < menuAdmin.prop.maxCustomCol ){
			var element = $('#md_tool_single_placeholder').tmpl().appendTo('#md_component_custom > .md_placeholder_main');
			//	It s important to re-enable the dragging-sorting events so they can take effect
			//	in the new element.
			menuAdmin.helpers.setDraggables();
			return element[0];
		}
		else{
			menuAdmin.helpers.message('md_error', 'A maximum number of '+ menuAdmin.prop.maxCustomCol +' columns allowed');
		}
	}
	//	The function shows the overlay loader between the ajax response delay.
	menuAdmin.helpers.showLoader = function( show ){
		if ( show ){
			$('#md_loader').show();
		}
		else{
			$('#md_loader').hide();
		}
	}
	//	The function shows the messages of the plugin in the admin area
	//	Can have the two following type of message: md_success, md_error
	menuAdmin.helpers.message = function( type, msg ){
		var indicator = document.getElementById('md_message_indicator');
		indicator.setAttribute('class',type);
		indicator.innerHTML = msg;
		$(indicator).fadeIn(300, function(){
			setTimeout( function(){
				$(indicator).fadeOut(300);
			}, 3000 );
		});
	}
		
	menuAdmin.init();

	
}) ( jQuery )
