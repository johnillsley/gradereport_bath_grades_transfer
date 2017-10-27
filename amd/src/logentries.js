define(['jquery', 'core/templates', 'core/ajax', 'core/config', 'core/yui', 'core/notification'], function ($, templates, ajax, config, Y, notify) {
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
                headerContent: 'Transfer Logs',
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
                $.each(log_data, function (i, object) {
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

                //console.log(log_entries);
                console.log({'log': log_entries});
                templates.render('gradereport_transfer/transfer_log',
                    {'log': log_entries})
                    .then(function (html, js) {
                        yuiDialogue.set('bodyContent', html);
                    }).fail(function (ex) {
                    yuiDialogue.set('bodyContent', '');
                })
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
                var userid = 196;
                var mappingid = 11;
                //hide any previous YUI Dialogue
                console.log("clicked now");
                getLogs(userid, mappingid);
            });
        }
    };

});