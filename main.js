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
				$(this).prepend("<a id='episode-play-link' href='" + link + "' target='_blank' title='Play'>&#9654;</a>");
			}
			$.ajax({
				url: 'http://' + host + '/shows/' + category + '/episodes/' + id + '/',
				success: function(data){
					$('#episode-details').html(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		$('.episodes-wrapper')
		.off('scroll')
		.on('scroll', function(e){
			var top = $('.season-list > li:first').offset().top,
				titleHeight = $('.navbar').outerHeight(true),
				offset = $('.season-list > li:first > span').outerHeight(true),
				mh = $('.episodes-wrapper').innerHeight() - offset;

			if (top > titleHeight){
				$('#episode-details').css('top', (top + offset) + 'px');
				$('#episode-details').css('max-height', (mh - top) + 'px');
			}
			else{
				$('#episode-details').css('top', (titleHeight + offset) + 'px');
				$('#episode-details').css('max-height', (mh - titleHeight) + 'px');
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

sections['install'] = (function(){
	"use strict";
	var init;
	
	function markValid(selector){
		var formGroup = $(selector).closest('.form-group'),
            feedback = formGroup.find('.form-control-feedback');
		formGroup
			.removeClass('has-success')
            .removeClass('has-danger')
            .addClass('has-success');
        if (feedback) {
            feedback.remove();
        }
		$(selector)
            .removeClass('form-control-success')
            .removeClass('form-control-danger')
            .addClass('form-control-success');
	}
	
	function markInvalid(selector){
        var formGroup = $(selector).closest('.form-group'),
            feedback = formGroup.find('.form-control-feedback');
        formGroup
            .removeClass('has-success')
            .removeClass('has-danger')
            .addClass('has-danger');
        if (feedback) {
            feedback.remove();
        }
        $(selector)
            .removeClass('form-control-success')
            .removeClass('form-control-danger')
            .addClass('form-control-danger');
	}
	
	function showMessageBox(selector, msg){
	    var input = $(selector),
            formGroup = $(selector).closest('.form-group'),
            feedback = formGroup.find('.form-control-feedback');

        if (feedback) {
            feedback.remove();
        }
	    input
            .after('<div class="form-control-feedback">' + msg + '</div>');
	}
	
	init = function(){
		$('#restUrl')
		.on('change', function(){
			var obj = this,
				id = $(this).attr('id'),
				data = {};
			data[id] = $(obj).val();
			$.ajax({
				url: 'http://' + host + '/install/check/restUrl/',
				data: data,
				success: function(data){
				    data = JSON.parse(data);
					if (data['result'] === 'Ok'){
						markValid(obj);
					}
					else{
						markInvalid(obj);
                        showMessageBox('#restUrl', 'Die Url scheint nicht korrekt zu sein.');
					}
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		$('#dbHost, #dbName, #dbUser, #dbPassword')
		.on('change', function(){
			var restUrl = $('#restUrl').val(),
				dbHost = $('#dbHost').val(),
				dbName = $('#dbName').val(),
				dbUser = $('#dbUser').val(),
				dbPassword = $('#dbPassword').val();
			$('#db-box').remove();
			if (restUrl.length > 0 && dbHost.length > 0 && dbName.length > 0 && dbUser.length > 0 && dbPassword.length > 0){
				$.ajax({
					url: 'http://' + host + '/install/check/db/',
					data: {
						dbHost: dbHost,
						dbName: dbName,
						dbUser: dbUser,
						dbPassword: dbPassword,
						restUrl: restUrl
					},
					success: function(data){
						var msg;
						data = JSON.parse(data);
						if (data['dbAccess'] === 'Ok'){
							markValid('#dbHost');
							markValid('#dbName');
							markValid('#dbUser');
							markValid('#dbPassword');
							if (data['dbSetup'] === 'Ok'){
								msg = 'Alle erforderlichen Datenbanktabellen sind vorhanden.';
							}
							else{
							    //TODO show dialog
								msg = 'Die Datenbankeinrichtung ist unvollständig. Soll die Datenbank jetzt eingerichtet werden?';
								msg += '<br><form method="POST" action="install/db" id="install-db-form"><button type="submit">Setup DB</button></form>'
							}
							showMessageBox('#dbHost', msg);
						}
						else{
							markInvalid('#dbHost');
							markInvalid('#dbName');
							markInvalid('#dbUser');
							markInvalid('#dbPassword');
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						alert(errorThrown);
					}
				});
			}
		});
		$('#pathMovies, #aliasMovies')
		.on('change', function(){
			var restUrl = $('#restUrl').val(),
				pathMovies = $('#pathMovies').val(),
				aliasMovies = $('#aliasMovies').val();
			if (restUrl.length > 0 && pathMovies.length > 0 && aliasMovies.length > 0){
				$.ajax({
					url: 'http://' + host + '/install/check/movies/',
					data: {
						pathMovies: pathMovies,
						aliasMovies: aliasMovies,
						restUrl: restUrl
					},
					success: function(data){
                        var msg = 'Die folgenden Unterordner wurden gefunden und werden als Kategorien verwendet: ';
                            data = JSON.parse(data);
						if (data['result'] === 'Ok'){
                            data['folders'].forEach(function(element){
                                msg += element + ', ';
                            });
                            msg = msg.substr(0, msg.length - 2);
							markValid('#pathMovies');
							markValid('#aliasMovies');
                            showMessageBox('#aliasMovies', msg);
						}
						else{
							markInvalid('#pathMovies');
							markInvalid('#aliasMovies');
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						alert(errorThrown);
					}
				});
			}
		});
		$('#pathShows, #aliasShows')
		.on('change', function(){
			var restUrl = $('#restUrl').val(),
				pathShows = $('#pathShows').val(),
				aliasShows = $('#aliasShows').val();
			$('#shows-box').remove();
			if (restUrl.length > 0 && pathShows.length > 0 && aliasShows.length > 0){
				$.ajax({
					url: 'http://' + host + '/install/check/shows/',
					data: {
						pathShows: pathShows,
						aliasShows: aliasShows,
						restUrl: restUrl
					},
					success: function(data){
						var msg = 'Die folgenden Unterordner wurden gefunden und werden als Kategorien verwendet: ';
						    data = JSON.parse(data);
						if (data['result'] === 'Ok'){
							data['folders'].forEach(function(element){
								msg += element + ', ';
							});
							msg = msg.substr(0, msg.length - 2);
							markValid('#pathShows');
							markValid('#aliasShows');
							showMessageBox('#aliasShows', msg);
						}
						else{
							markInvalid('#pathShows');
							markInvalid('#aliasShows');
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						alert(errorThrown);
					}
				});
			}
		});
		
		$('#restUrl, #dbHost, #pathMovies, #pathShows').trigger('change');
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