var Movies = (function() {
    "use strict";
    var host,
        lock = false,
        container,
        page = 1,
        fetchSize;

    function get(option){
        return container.data(option);
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

    function fetchMovies()
    {
        var baseUrl = '/movies/',
            url,
            offset;

        url = 'http://' + host + baseUrl + get('category') + '?' + getSearchOptions();
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
        var baseUrl = '/movies/',
            url = 'http://' + host + baseUrl + get('category'),
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

    function setup()
    {
        host = $('#host').val();
        container = $('#nm-movie-details-wrapper');
        fetchSize = container.data('fetch-size');
        addInfiniteScrollingHandler();
        addEditMovieHandler();
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