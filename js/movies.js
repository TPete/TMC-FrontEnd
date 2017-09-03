var Movies = (function() {
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
            options = ['sort', 'filter', 'genres', 'collection', 'list'];

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
                var diff = container.height() - $(window).height() - e.originalEvent.pageY;

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
                                    $('#previewTitle').html(response.data.title);
                                    $('#previewOverview').html(response.data.overview);

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

    function addInstantSearchHandler()
    {
        $('#instant-search')
            .on('input', function () {
                var url,
                    filter = encodeURIComponent($(this).val());

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
                                    console.log('empty');
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

    function setup()
    {
        host = $('#host').val();
        container = $('#nm-movie-details-wrapper');
        fetchSize = container.data('fetch-size');
        addInfiniteScrollingHandler();
        addEditMovieHandler();
        addInstantSearchHandler();
    }

    return {
        'setup': setup
    };
}());

$(document).ready(function ()
{
    "use strict";
    Movies.setup();
});