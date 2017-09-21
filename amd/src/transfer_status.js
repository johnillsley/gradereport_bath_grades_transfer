define(['jquery', 'core/templates', 'core/ajax', 'core/config', 'core/yui'], function ($, templates, ajax, config, Y) {
    var URL = config.wwwroot + '/grade/report/transfer/ajax.php';
    //Show an end summary of grades transferred and failed.
    var endSummary = function (data, total_time) {
        var success_transfers_count = data.success;
        var failed_transfers_count = data.failed;
        var succcess_transfer_text = "Grades successfully queued :" +
            " <span class='label-success label'>"
            + success_transfers_count + "</span>";
        var failed_transfer_text = "Failed transfers : <span class='label-danger label'>" + failed_transfers_count + "</span>";
        var total_text = "Total : <span class='label-info label'>" + (success_transfers_count + failed_transfers_count) + "</span>";
        var yuiDialogue = new M.core.dialogue({
            headerContent: 'Summary',
            bodyContent: "<div id='transfer_summary'>" +
            "<p>" + succcess_transfer_text + "</p>"
            + "<p>" + failed_transfer_text + "</p>"
            + "<p>" + total_text + "</p>"
            + "<p>Total time taken: " + total_time + " seconds</p>"
            + "</div>",
            draggable: false,
            visible: false,
            center: true,
            modal: true,
            width: 400,
            closeButton: true,
            closeButtonTitle: 'Close'
        });
        yuiDialogue.addButton({
            label: 'OK',
            action: function (e) {
                e.preventDefault();
                // Redirect to previous page.
                var id = findGetParameter('id');
                var mappingid = findGetParameter('mappingid');
                window.location.href = 'http://essd.bath.ac.uk/moodle31/grade/report/transfer/index.php?id=' + id + '&mappingid=' + mappingid;
                yuiDialogue.hide();
            },
            section: Y.WidgetStdMod.FOOTER
        });
        yuiDialogue.show();
    };
    var findGetParameter = function (parameterName) {
        var result = null,
            tmp = [];
        location.search
            .substr(1)
            .split("&")
            .forEach(function (item) {
                tmp = item.split("=");
                if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
            });
        return result;
    };
    var sendSingleGrade = function (users, data_json, succ_count, failed_count, startTime) {
        var single_user = users[0];
        var tr_node = $('#confirm_transfer_table tbody').find("tr[data-moodle-user-id='" + single_user + "']");
        /*tr_node
         .removeClass()
         .addClass('label label-warning transfer_status')
         .html('');*/
        var loading_div = tr_node.find('td').find('.loadingDiv');
        loading_div.show();
        $.each(data_json, function (i, obj) {
            //Remove the users keys and value and start again
            if (obj.name === 'users[]') {
                data_json.splice($.inArray(data_json[i], users), 1);
            }
        });
        var usr_obj = {'name': 'users[]', 'value': single_user};
        data_json.push(usr_obj);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: data_json,
            url: URL
        }).done(function (transfer_status) {
            console.log(transfer_status);
            if (tr_node.attr('data-moodle-user-id') == transfer_status.userid) {
                if (transfer_status.status == 'queued') {
                    //total transferred
                    succ_count++;
                    $('#confirm_transfer_table tbody').find("tr[data-moodle-user-id='" + transfer_status.userid + "']")
                        .find('.transfer_status')
                        //.removeClass('label-warning')
                        .addClass('label-success')
                        .html(transfer_status.reason);
                }
                else if (transfer_status.status == 'failure') {
                    failed_count++;
                    //console.log("Status is fail");
                    $('#confirm_transfer_table tbody').find("tr[data-moodle-user-id='" + transfer_status.userid + "']")
                        .find('.transfer_status')
                        .html(transfer_status.reason);
                }
            }
            // });
            //});
            loading_div.hide();
            //throw new Error("my error message");
            //Continue with the next one

            users.splice($.inArray(single_user, users), 1);
            //console.log("Spliced...");
            //Re-run the function
            if (users.length > 0) {
                sendSingleGrade(users, data_json, succ_count, failed_count, startTime);
            }
            else {
                var endTime = new Date().getTime();
                $("body").css("cursor", "default");
                var total_count = {'success': succ_count, 'failed': failed_count};
                var total_time = (endTime - startTime);
                total_time /= 1000;
                //var total_time_seconds = Math.round(total_time % 60);
                //console.log(total_time_seconds);
                endSummary(total_count, total_time);
            }
        });

    };
    var getUsers = function (nodes) {
        var users = [];
        $.each(nodes, function (i, tr_node) {
            var node_user_value = $(tr_node).attr('data-moodle-user-id');
            var already_in_queue_value = $(tr_node).attr('data-already-in-queue');
            users.push(node_user_value);
            /* if(already_in_queue_value == 1) {
             return true;
             }
             else{
             users.push(node_user_value);
             }*/
        });
        return users;
    };
    var sendGrades = function (node, e) {
        e.preventDefault();
        //disable the button
        $(node).attr('disabled', true);
        var form = $('#transferconfirmed');
        var data = form.serializeArray();
        $(node).next().html('Go Back').attr('href', config.wwwroot + '/grade/report/transfer/index.php?id='
            + data[3].value +
            '&mappingid=' +
            data[4].value);
        $("body").css("cursor", "progress");

        //Get submitted data
        var parent_tr_nodes = $('#confirm_transfer_table tbody tr');
        //console.log(data);
        var users = getUsers(parent_tr_nodes);
        //Now that I have the users, get the first one in the index.
        var success_transferred_count = 0;
        var failed_transferred_count = 0;
        var timestart = new Date().getTime();
        sendSingleGrade(users, data, success_transferred_count, failed_transferred_count, timestart);
    };
    return {
        init: function () {
            $('#proceed_grade_transfer').click(function (e) {
                sendGrades(this, e);
            });


        }
    };
});