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

    function setup()
    {
        host = $('#host').val();
        container = $('#nm-movie-details-wrapper');
        fetchSize = container.data('fetch-size');
        addInfiniteScrollingHandler();
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