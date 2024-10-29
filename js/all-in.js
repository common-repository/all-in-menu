// JavaScript Document
( function( $ ){ 
	
	var mainInj	= $('.main_categories_menu > li > a.menu-link');
	var mainLi	= $('.main_categories_menu > li');
	
	menuDash = {}; menuDash.helpers = menuDash.specs = menuDash.fetch = {};
	
	menuDash.specs = {
		fadeTimer	: 150,
		searchFocus	: false,
	}
	
	menuDash.init = function(){
		
		$('#all_in_wrapper').show();
		
		
		mainInj.on( 'click', function(){ 
			var _this = $(this).closest('.main_categories_menu > li');
			menuDash.helpers.openTab(_this);
		});
		
		if ( window.innerWidth > 768 ){
			mainInj.on( 'mouseenter', function(){ 
				var _this = $(this).closest('.main_categories_menu > li');
				menuDash.helpers.openTab(_this);
			});
		}
				

		//	Check if the "sticky" class was appended and make 
		//	the menu follow the user scrolling
		if ( $('#all_in_wrapper').hasClass('sticky') ){ 
			menuDash.helpers.checkSticky();
			
			$(window).on( 'scroll', function(){	
				menuDash.helpers.checkSticky();
			});
		}
	
		mainInj.click(function(e){
			if ( $(this).hasClass('menu-social') === false ){
				e.preventDefault();
				//	Prevent doubleclicking by checking the active class
				if ( ! $(this).hasClass('active') ){
					
					//	Configure the active class, remove from the previous and add to the current
					$('.main_categories_menu > li > a.menu-link.active').removeClass('active');
					$(this).addClass('active');
				}
			}
		})

		var searchRetard;
		$('#search_opener').on( 'click', function(){ console.log('search');
			clearTimeout( searchRetard );
			menuDash.helpers.openSearch();
			var container	= $(this).closest('li.menu-item');
			$(container).on( 'mouseleave', function(){
				searchRetard = setTimeout( function(){
					menuDash.helpers.closeSearch();
				}, 500 );
			});
		});
		
		$('#md_search_form_desktop input[name=s]').on( 'focus', function(){
			menuDash.specs.searchFocus = true;
		});
		$('#md_search_form_desktop input[name=s]').on( 'blur', function(){
			menuDash.specs.searchFocus = false;
			menuDash.helpers.closeSearch();
		});
		
		//	#all_in_mobile_extend is visible only on mobile devices and shows the menu on click
		$('#all_in_mobile_extend').on( 'click', function(){
			$('#md_nav').toggleClass('active');
		});
	}
	
	menuDash.helpers.openTab = function( tab ){
		var _retard;
		if ( $(tab).hasClass('active')){
			$(tab).removeClass('active').off('mouseleave');
		}else{
			$('.main_categories_menu > li.active').removeClass('active');
			$(tab).addClass('active');
			$('#all_in_wrapper').on('mouseleave', function(){
				_retard = setTimeout( function(){
					$(tab).removeClass('active').off('mouseleave');
				},800 );
			})
			$('#all_in_wrapper').on('mouseenter', function(){
				clearTimeout(_retard);
			})
		}
	}
	
	menuDash.helpers.loader = function( action ){
		if ( action == 'show' ){
			$('#menu-expanded-main-menu').css({ opacity: '0.7' });
			$('#menu-expanded-overlay').show();
		}
		else{
			$('#menu-expanded-main-menu').css({ opacity: '1' });
			$('#menu-expanded-overlay').hide();
		}
	}
	
	menuDash.helpers.checkSticky = function(){
		if (  $(window).scrollTop() > $('#all_in_wrapper').position().top 
				&& window.innerWidth > 782){
			$('#all_in_wrapper').addClass('sticky');
		}
		else{
			$('#all_in_wrapper').removeClass('sticky');
		}
	}
	
	menuDash.helpers.emptyHtml = function(){
		$("#menu-expanded-sidebar, #menu-expanded-lobby, #menu-expanded-small-lobby").empty();
	}
	
	menuDash.helpers.openSearch = function(){
		$('#md_search_form_desktop').stop().fadeIn( menuDash.specs.fadeTimer ).animate({ marginTop: 0, opacity : 1}, menuDash.specs.fadeTimer);
	};
	menuDash.helpers.closeSearch = function(){
		if ( menuDash.specs.searchFocus == false ){
			$('#md_search_form_desktop').stop().animate({ marginTop: 20, opacity : 0}, menuDash.specs.fadeTimer).fadeOut( menuDash.specs.fadeTimer );
		}
	}
	menuDash.helpers.smallLobby = function( show ){
		if ( show ){
			$('.menu-small-lobby').show();
		}
		else{
			$('.menu-small-lobby').hide();
		}
	}
/*	----------------------------------------
	FUNCTION TO SHOW OR HIDE THE SIDEBAR
	In each action the #menu-expanded-main-menu
	much contain the appropriate class name
	----------------------------------------	*/
	menuDash.helpers.sideBar = function( show ){
		$('#menu-expanded').removeClass('no-menu-sidebar menu-sidebar');
		if ( show == true ){
			//$("#menu-expanded-sidebar").show();
			$('#menu-expanded').addClass('menu-sidebar');
		}
		else{
			//$("#menu-expanded-sidebar").hide();
			$('#menu-expanded').addClass('no-menu-sidebar');
		}
	}
	
	menuDash.fetch.category = menuDash.fetch.post_tag = function(data){ console.log(data);
		if ( data.terms.length > 0 ){
			menuDash.helpers.sideBar( true );
			$("#menu-expanded-sidebar-tmpl")
				.tmpl(data.terms).appendTo("#menu-expanded-sidebar");
		}
		else{
			menuDash.helpers.sideBar( false );
		}
		console.log( data.smallposts );
		$("#menu-expanded-lobby-tmpl")
			.tmpl(data.posts)
			.appendTo("#menu-expanded-lobby");
		if ( data.smallposts ){
			$("#menu-expanded-small-lobby-tmpl")
				.tmpl(data.smallposts)
				.appendTo("#menu-expanded-small-lobby");
			menuDash.helpers.smallLobby( true );
		}
		else{
			menuDash.helpers.smallLobby( false );
		}
	}
	
	menuDash.fetch.post = function( data ){
		
		console.log(data.extra);
		if ( data.extra.show_excerpt == 1 ){
			menuDash.helpers.sideBar( true );
		}
		else{
			menuDash.helpers.sideBar( false );
		}
		$("#menu-post-sidebar-tmpl").tmpl(data.post).appendTo("#menu-expanded-sidebar");
		$("#menu-post-lobby-tmpl").tmpl(data.post).appendTo("#menu-expanded-lobby");
		if ( data.extra.show_title == 1 ){
			$('#menu-expanded.post .lobby-header').show();
		}
		else{
			$('#menu-expanded.post .lobby-header').hide();
		}
		//$('#menu-expanded-sidebar').html( data.post.post_excerpt );
		//$('#menu-expanded-lobby').html( data.html );
	}

	menuDash.fetch.youtube = function( data ){ console.log(data);
		menuDash.helpers.sideBar(true);
		$("#menu-expanded-sidebar").html(data.extra.description);
		$('#menu-expanded-lobby').html(data.iframe);
		menuDash.helpers.smallLobby( false );
	}
	
	menuDash.fetch.custom = function( data ){
		console.log(data);
		var values = data.extra.values;
		//	First of all check how much columns the menu tab contains
		var columns	= values.length; 
		//	Foreach column we render content
		for ( var x in values ){
			// Render content foreach md_tool
			$('#menu_custom_tool_holder').tmpl({
				column			: x,
				total_columns	: columns,
			}).appendTo('#menu-expanded-lobby');
			for ( var y in values[x] ){
				var tool = values[x][y];
				$('#menu_custom_tool_'+tool.md_tool_type)
					.tmpl(tool).appendTo('#menu_custom_holder_'+x+' > .menu_custom_inside');
			}
		}
	}
	
	menuDash.helpers.parent = function( parent ){
		if ( window.innerWidth < 782 ){
			return $(parent).find('.mobile-content');
		}
		else{
			return $('#menu-expanded');
		}
	}
	
	menuDash.init();
	/*$(window).load(function(){
		$("#menu-expanded-sidebar").mCustomScrollbar({
			theme		: 'minimal-dark',
			mouseWheel	: {
				enable	: true,
			}
		});
	});*/
	
	/*$('#menu-expanded-main-menu').mouseleave(function(){
		$("#menu-expanded-right").html('');
		$("#menu-expanded-left").html('');
		$(this).stop().delay(800).hide();
	})*/
	

}) ( jQuery )

