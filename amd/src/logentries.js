define(['jquery', 'core/templates', 'core/ajax', 'core/config', 'core/yui'],
    function ($, templates, ajax, config, Y) {
    var URL = config.wwwroot + '/grade/report/transfer/log_ajax.php';
    var alertclass = 'alert-info';
    var getLogs = function (userid, mappingid) {
        var log_entries = [];
        $.ajax({
            type: 'GET',
            dataType: 'json',
            data: {'userid': userid, 'mappingid': mappingid},
            url: URL
        }).done(function (log_data) {
            var yuiDialogue = new M.core.dialogue({
                headerContent: 'Transfer Logs [ Last 20 entries]',
                draggable: false,
                bodyContent: '',
                visible: false,
                modal: true,
                center: true,
                width: 400,
                closeButton: true,
                hideaftersubmit: false
            });
            if (log_data !== null) {
                //if logs are empty , show a popup nonetheless
                if (log_data['logs'] !== null) {
                    $.each(log_data['logs'], function (i, object) {
                        var outcomeid = object.id;

                        if (outcomeid == 1) {
                            //Its a success
                            alertclass = 'text-success';
                        }
                        else if (outcomeid == 3) {
                            alertclass = 'text-danger';
                        }
                        else if (outcomeid == 8) {
                            alertclass = 'text-info';
                        }
                        else {
                            alertclass = 'text-warning';

                        }
                        object.alertclass = alertclass;
                        log_entries.push(object);
                    });
                }
                templates.render('gradereport_transfer/transfer_log',
                    {'log': log_entries})
                    .then(function (html) {
                        yuiDialogue.set('bodyContent', html);
                    }).fail(function (ex) {
                    yuiDialogue.set('bodyContent', '');
                });
            }
            //Finally show the dialog box
            yuiDialogue.show();

        });
    };
    return {
        init: function () {
            //Show a popup
            $('.get_transfer_logs').click(function (e) {
                e.preventDefault();
                // First make sure the grade structure is not empty
                var userid = $(this).data('user-id');
                var mappingid = $(this).data('mapping-id');
                 getLogs(userid, mappingid);
            });
        }
    };

});