var Movies = (function(bootbox) {
    "use strict";
    var host,
        lock = false,
        container,
        page = 1,
        fetchSize,
        instantSearchXhr,
        timer,
        delay = 500;

    function get(option)
    {
        return container.data(option);
    }

    function set(option, value)
    {
        container.data(option, value);
    }

    function getSearchOptions()
    {
        var result = [],
            options = ['sort', 'filter', 'genre', 'collection', 'list'];

        options.forEach(function (option) {
            var val = get(option);

            if (val) {
                result.push(option + '=' + val);
            }
        });

        return result.join('&');
    }

    function getUrl(withOptions)
    {
        var baseUrl = '/movies/',
            url = 'http://' + host + baseUrl + get('category');

        if (withOptions) {
            url += '/?' + getSearchOptions();
        }

        return url;
    }

    function fetchMovies()
    {
        var url,
            offset;

        url = getUrl(true);
        offset = page * fetchSize;
        page++;
        console.log('Fetching');
        $.ajax(
            url,
            {
                type: 'get',
                data: {
                    offset: offset
                },
                success: function (data) {
                    if (data.length > 0) {
                        container.append(data);
                        lock = false;
                    } else {
                        console.log('thats all');
                    }
                },
                error: function (error) {
                    alert(error);
                    lock = false;
                }
            }
        )
    }

    function addInfiniteScrollingHandler()
    {
        $(window)
            .on('scroll', function (e) {
                var diff = container.height() - $(window).height() - window.scrollY;

                if (false === lock && diff < 0.3 * container.height()) {
                    lock = true;
                    fetchMovies();
                }
            });
    }

    function addEditMovieHandler()
    {
        var url = getUrl(false),
            id,
            filename;

        $('#movie-edit-box')
            .on('show.bs.modal', function (e) {
                var a = e.relatedTarget;

                id = $(a).data('id');
                filename = $(a).data('filename');
                filename = filename.substr(filename.lastIndexOf('/') + 1);

                $('#movie-edit-box').find('button[type=submit]').prop('disabled', true);
                $('#movieDbId').val(null);
            })
            .on('change', '#movieDbId', function () {
                if ($(this).val().length > 0) {
                    $.ajax(
                        url + '/lookup/?movieDbId=' + $(this).val(),
                        {
                            type: 'get',
                            success: function (response) {
                                if (response.status === 'Ok') {
                                    $('#previewTitle').html(response.data.attributes.title);
                                    $('#previewOverview').html(response.data.attributes.overview);

                                    $('#preview').show();

                                    $('#movie-edit-box').find('button[type=submit]').prop('disabled', false);
                                }
                            },
                            error: function (error) {
                                alert(error);
                                lock = false;
                            }
                        }
                    )
                }
            })
            .on('click', 'button[type=submit]', function () {
                $.ajax(
                    url + '/'+ id +'/',
                    {
                        type: 'post',
                        data: {
                            'movieDbId': $('#movieDbId').val(),
                            'filename': filename
                        },
                        success: function (response) {
                            if (response.status === 'Ok') {
                                window.location.reload();
                            } else {
                                alert(response.error);
                            }
                        },
                        error: function (error) {
                            alert(error);
                            lock = false;
                        }
                    }
                )
            });
    }

    function displayLoading()
    {
        if (container.find('.faded').length === 0) {
            container.prepend('<div class="faded"></div>');
        }
    }

    function displayEmpty()
    {
        container.html('<div class="empty-result text-center"><i class="fa fa-frown-o fa-4x"></i><h3>Leider nichts gefunden</h3></div>');
    }

    function addInstantSearchHandler()
    {
        $('#instant-search')
            .on('input', function () {
                var url,
                    filter = encodeURIComponent($(this).val());

                displayLoading();

                set('filter', filter);
                url = getUrl(true);
                window.history.pushState({}, '', url);

                if (instantSearchXhr) {
                    instantSearchXhr.abort();
                }

                clearTimeout(timer);

                timer = setTimeout(function() {
                    page = 1;
                    window.scrollTo(0, 0);
                    instantSearchXhr = $.ajax(
                        url,
                        {
                            type: 'get',
                            success: function (data) {
                                if (data.length > 0) {
                                    container.html(data);
                                } else {
                                    displayEmpty();
                                }
                            },
                            error: function (error) {
                                if (error.statusText !== 'abort') {
                                    alert(error.statusText);
                                }
                            }
                        }
                    );
                }, delay);
            });
    }

    function addMoviePosterHandler()
    {
        $('body')
            .on('click', '[data-toggle="movie-poster"]', function() {
                var url = getUrl();
                $.ajax(
                    url + '/' + $(this).data('id'),
                    {
                        type: 'get',
                        success: function (data) {
                            bootbox.dialog({
                                message: data.template,
                                buttons: {},
                                onEscape: true,
                                backdrop: true,
                                size: 'large'
                            });
                            $('[data-toggle="tooltip"]').tooltip();
                        },
                        error: function (error) {
                            alert(error.statusText);
                        }
                    }
                )
            })
    }

    function setup()
    {
        host = $('#host').val();
        container = $('#nm-movie-details-wrapper');
        fetchSize = container.data('fetch-size');
        addInfiniteScrollingHandler();
        addEditMovieHandler();
        addInstantSearchHandler();
        addMoviePosterHandler();

        $('[data-toggle="tooltip"]').tooltip();
    }

    return {
        'setup': setup
    };
}(bootbox));

$(document).ready(function ()
{
    "use strict";
    Movies.setup();
});