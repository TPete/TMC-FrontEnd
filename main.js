var main, sections = {}, host = $('#host').val();

sections["shows"] = (function(){
	"use strict";
	var init;
	
	init = function(){
		$('.episode-link-present, .episode-link-missing')
		.off('click')
		.on('click', function(){
			var category = sections.getSubCategory(),
				id,
				link;
			$('#episode-details').show();
			$('.episode-link-present, .episode-link-missing').removeClass('selected');
			$(this).addClass('selected');
			$('#episode-play-link').remove();
			id = $(this).find('a').data('id');
			if ($(this).hasClass('episode-link-present')){
				link = $(this).find('a').data('href');
				$(this).prepend("<a id='episode-play-link' href='" + link + "' target='_blank' title='Play'><i class='fa fa-play'></i></a>");
			}
			$.ajax({
				url: 'http://' + host + '/shows/' + category + '/episodes/' + id + '/',
                data: {
				    link: link
                },
				success: function(data){
					$('#episode-details').html(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});

        $('.container-fluid')
            .on('click', '#episode-details-close-btn', function() {
                $('#episode-details').hide();
            });
		
		$('.episodes-wrapper')
		.off('scroll')
		.on('scroll', function(){
			var top = $('#season-list').offset().top,
				titleHeight = $('.navbar').outerHeight(true),
				offset = $('.season-list').outerHeight(true),
				mh = $('.episodes-wrapper').innerHeight() - offset,
				episodeDetails = $('#episode-details');

			if (top > titleHeight){
                episodeDetails
					.css('top', (top + offset) + 'px')
					.css('max-height', (mh - top) + 'px');
			}
			else{
                episodeDetails
					.css('top', (titleHeight + offset) + 'px')
					.css('max-height', (mh - titleHeight) + 'px');
			}
		}).
		trigger('scroll');
		
	};
	
	return {
		init: init
	};
}());


sections["movies"] = (function(){
	"use strict";
	var init;
		
	function showDetailsDialog(category, dbid, movieDBID, filename, type){
		var url = 'http://' + host + '/movies/' + category + '/';
		if (type === 'lookup'){
			url = 'http://' + host + '/movies/' + category + '/lookup/';
		}
		$.ajax({
			url: url + dbid + '/',
			data: {
				output: 'edit',
				movieDBID: movieDBID
			},
			success: function(data){
				$('#movie-details-dialog').remove();
				$('body').append(data);
				$('#movie-details-dialog')
				.dialog({
					width: 650,
					height: 450,
					modal: true,
					buttons: [
						{text: "Speichern",
						 click : function(){
							$.ajax({
								url: 'http://' + host + '/movies/' + category + '/' + dbid + '/',
								type: 'POST',
								context: this,
								data: {filename: filename,
									movieDBID: movieDBID},
								success: function(resp){
									if (resp.substr(0, 2) === "OK"){
										alert('Daten gespeichert');
									}
									else{
										alert('Fehler beim Speichern');
									}
									window.location.reload();
								},
								error: function(jqXHR, textStatus, errorThrown){
									alert(errorThrown);
									window.location.reload();
								}
							});
						}
						},
						{text: 'Abbrechen',
						 click: function(){
							$(this).dialog("close");
						}
						}
					]
					
				});
				$('#movie-id')
				.on('change', function(){
					showDetailsDialog(category, dbid, $(this).val(), filename, 'lookup');
				});
			},
			error: function(jqXHR, textStatus, errorThrown){
				alert(errorThrown);
			}
		});
	}
	
	function addPosterClickHandler(){
	    //TODO use bootstrap modal
		$('#nm-movie-poster > img')
		.eq(0)
		.off('click')
		.on('click', function(){
			var src = $(this).attr('src'),
				div = '<div id="movie-poster-dialog"></div>',
				img,
				h = $('.content-wrapper').height(), 
				w = h/3*2;
			src = src.substr(0, src.lastIndexOf('_')) + '_big.jpg';
			img = '<img src="' + src + '">';
			if ($('#movie-poster-dialog').length > 0){
				$('#movie-poster-dialog').remove();
			}
			$('body').append(div);
			$('#movie-poster-dialog')
			.append(img)
			.dialog({
				height: h,
				width: w,
				modal: true,
				dialogClass: 'ui-dialog-no-title',
				open: function(event, ui){
					$('.ui-widget-overlay').bind('click', function(){
						$("#movie-poster-dialog").dialog('close');
					}); 
				}
			});
		});
	}
	
	function addEditLinkHandler(){
		$('#movie-edit-link')
		.on('click', function(e){			
			e.preventDefault();
			var cat = sections.getSubCategory(),
				dbid = $(this).data('id'),
				movieDBID = $(this).data('moviedbid'),
				filename = $(this).data('filename');
			showDetailsDialog(cat, dbid, movieDBID, filename, 'store');
			
			return false;
		});
	}
	
	function updateMovieOverview(query, pushHistory){
		var cat = sections.getSubCategory(),
			loader = '<div id="loader-movie-overview">',
			ajaxUrl = 'http://' + host + '/movies/' + cat + '/',
			pushUrl = 'http://' + host + '/movies/' + cat + '/';
		if (query.length > 0){
			ajaxUrl += query + '&display=overview';
			pushUrl += query;
		}
		else{
			ajaxUrl += '?display=overview';
		}
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<br class="clear">';
		loader += '</div>';
		if (query.toLowerCase() !== 'javascript: void(0);'){
			$('#movie-overview').empty().append(loader);
			$.ajax({
				url: ajaxUrl,
				success: function(data){
					$('#movie-overview').html(data);
					if (pushHistory){
						history.pushState(null, '', pushUrl);
					}
					initHandlers();
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		}
	}
		
	function initHandlers(){
		window.onpopstate = function(){
			updateMovieOverview(window.location.search.substring(1), false);
		};
		
		
		$('.movie-overview-poster')
		.on('click', function(){
			$('#nm-movie-details-wrapper').html('<div id="loader-movie-details"></div>');
			var id = $(this).data('id'),
				cat = sections.getSubCategory();
			$.ajax({
				url: 'http://' + host + '/movies/' + cat + '/' + id + '/',
				success: function(data){
					$('#nm-movie-details-wrapper').html(data);
					addEditLinkHandler();
					addPosterClickHandler();
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		$('#movie-overview-prev, #movie-overview-next')
		.on('click', function(){
			var query = $(this).attr('href');
			updateMovieOverview(query, true);
			
			return false;
		});
	}
	
	init = function(){
		initHandlers();
		$('.movie-overview-poster')
		.eq(0)
		.trigger('click');
	};
	
	return {
		init: init
	};
}());

sections.getPath = function(){
	var path = window.location.href,
	    host = $('#host').val();

	path = path.substr(path.indexOf(host) + host.length);
	if (path.substr(0, 1) === '/'){
		path = path.substr(1);
	}
	
	return path.split('/');
};

sections.getCategory = function(){
	var path = this.getPath();
			
	return path[0];
};

sections.getSubCategory = function(){
	var path = this.getPath();
	
	return path[1];
};

sections.getId = function(){
	var path = this.getPath();
	
	return path[2];
};

sections.init = (function(){
	var init;
		
	init = function(){
		var cat = sections.getCategory();
		if (cat.length === 0){
			cat = 'main';
		}
		if (cat !== null){
			console.log("init " + cat);
			sections[cat].init();
		}
		else{
			console.log("no init");
		}
	};
	
	return init;
}());

main = (function(){
	"use strict";
	var init;	
	
	init = function(){
		sections.init();
	};
	
	return {
		init: init
	};
}());

main.init();