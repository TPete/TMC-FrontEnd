var Settings = (function() {
    "use strict";

    function showMessageBox(selector, valid, msg)
    {
        var input = $(selector),
            formGroup = $(selector).closest('.form-group'),
            feedback = formGroup.find('.valid-feedback, .invalid-feedback'),
            className = valid ? 'valid-feedback' : 'invalid-feedback';

        if (feedback) {
            feedback.remove();
        }


        input
            .after('<div class="' + className + '">' + msg + '</div>');
    }

    /**
     * Marks a form field as valid or invalid.
     *
     * @param {string}  selector
     * @param {boolean} valid
     * @param {string}  [msg]
     */
    function mark(selector, valid, msg)
    {
        var formGroup = $(selector).closest('.form-group'),
            feedback = formGroup.find('.valid-feedback, .invalid-feedback'),
            className = valid ? 'is-valid' : 'is-invalid';

        if (feedback) {
            feedback.remove();
        }

        $(selector)
            .removeClass(['is-valid', 'is-invalid'])
            .addClass(className);

        if (msg) {
            showMessageBox(selector, valid, msg);
        }
    }

    /**
     * Marks a form field as valid.
     *
     * @param {string} selector
     * @param {string} [msg]
     */
    function markValid(selector, msg)
    {
        mark(selector, true, msg);
    }

    /**
     * Marks a form field as invalid.
     *
     * @param {string} selector
     * @param {string} [msg]
     */
    function markInvalid(selector, msg)
    {
        mark(selector, false, msg);
    }

    /**
     * Adds the change handler for the REST API's url.
     *
     * The handler validates the url by trying to call the API check url.
     */
    function addRestUrlHandler()
    {
        $('#restUrl')
            .on('change', function(){
                var obj = this,
                    id = $(this).attr('id'),
                    data = {};
                data[id] = $(this).val();

                $.ajax(
                    'http://' + host + '/install/check/restUrl/',
                    {
                        data: data,
                        success: function(data){
                            if (data['result'] === 'Ok'){
                                markValid(obj, 'Die Url ist gültig.');
                            }
                            else{
                                markInvalid(obj, 'Die Url scheint nicht korrekt zu sein.');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown){
                            alert(errorThrown);
                        }
                    }
                );
            });
    }

    function capitalize(s)
    {
        return s[0].toUpperCase() + s.slice(1);
    }

    /**
     * Adds the change handler for the form fields of the movie or show.
     *
     * The handler validates the url by calling the API check url.
     */
    function addSectionInputHandler(section)
    {
        var pathId = 'path' + capitalize(section),
            aliasId = 'alias' + capitalize(section),
            pathSelector = '#' + pathId,
            aliasSelector = '#' + aliasId;

        $(pathSelector + ', ' + aliasSelector)
            .on('change', function(){
                var restUrl = $('#restUrl').val(),
                    path = $(pathSelector).val(),
                    alias = $(aliasSelector).val(),
                    data = {};
                data[pathId] = path;
                data[aliasId] = alias;
                data['restUrl'] = restUrl;

                if (restUrl.length > 0 && path.length > 0 && alias.length > 0){
                    $.ajax(
                        'http://' + host + '/install/check/' + section + '/',
                        {
                            data: data,
                            success: function(data){
                                var msg;

                                if (data['result'] === 'Ok'){
                                    msg = 'Die folgenden Unterordner wurden gefunden und werden als Kategorien verwendet: ';
                                    msg += data['folders'].join(', ');

                                    markValid(pathSelector);
                                    markValid(aliasSelector, msg);
                                }
                                else{
                                    markInvalid(pathSelector);
                                    markInvalid(aliasSelector);
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown){
                                alert(errorThrown);
                            }
                        }
                    );
                }
            });
    }

    /**
     * Adds the change handler for the movie form fields.
     *
     * The handler validates the url by calling the API check url.
     */
    function addMovieInputsHandler()
    {
        addSectionInputHandler('movies');
    }

    /**
     * Adds the change handler for the show form fields.
     *
     * The handler validates the url by calling the API check url.
     */
    function addShowInputsHandler()
    {
        addSectionInputHandler('shows');
    }

    /**
     * Adds the change handler for the database form fields.
     *
     * The handler validates the url by calling the API check url.
     */
    function addDatabaseHandler()
    {
        $('#dbHost, #dbName, #dbUser, #dbPassword')
            .on('change', function(){
                var restUrl = $('#restUrl').val(),
                    dbHost = $('#dbHost').val(),
                    dbName = $('#dbName').val(),
                    dbUser = $('#dbUser').val(),
                    dbPassword = $('#dbPassword').val();

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

                            if (data['dbAccess'] === 'Ok'){
                                markValid('#dbHost');
                                markValid('#dbName');
                                markValid('#dbUser');
                                markValid('#dbPassword');

                                if (data['dbSetup'] === 'Ok'){
                                    msg = 'Alle erforderlichen Datenbanktabellen sind vorhanden.';
                                }
                                else{
                                    msg = 'Die Datenbankeinrichtung ist unvollständig. Soll die Datenbank jetzt eingerichtet werden?';
                                    msg += '<br><form method="POST" action="install/db" id="install-db-form"><button type="submit">Setup DB</button></form>'
                                }

                                showMessageBox('#dbPassword', true, msg);
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
    }

    function setup()
    {
        addRestUrlHandler();
        addMovieInputsHandler();
        addShowInputsHandler();
        addDatabaseHandler();

        $('#restUrl, #dbHost, #pathMovies, #pathShows').trigger('change');
    }

    return {
        'setup': setup
    };
}(bootbox));

$(document).ready(function ()
{
    "use strict";
    Settings.setup();
});