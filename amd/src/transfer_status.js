define(['jquery', 'core/templates', 'core/ajax', 'core/config', 'core/yui'], function ($, templates, ajax, config, Y) {
    var URL = config.wwwroot + '/grade/report/transfer/ajax.php';
    //Show an end summary of grades transferred and failed.
    var endSummary = function (data) {
        var success_transfers_count = data.success;
        var failed_transfers_count = data.failed;
        var succcess_transfer_text = "Grades sent to SAMIS successfully : <span class='label-info label'>" + success_transfers_count + "</span>";
        var failed_transfer_text = "Failed transfers : <span class='label-danger label'>" + failed_transfers_count + "</span>";
        var yuiDialogue = new M.core.dialogue({
            headerContent: 'Summary',
            bodyContent: "<p>" + succcess_transfer_text + "</p>" + "<p>" + failed_transfer_text + "</p>",
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
                yuiDialogue.hide();
            },
            section: Y.WidgetStdMod.FOOTER
        });
        yuiDialogue.show();
    };
    var sendSingleGrade = function (users, data_json, succ_count, failed_count) {
        var single_user = users[0];
        var tr_node = $('#confirm_transfer_table tbody').find("tr[data-moodle-user-id='" + single_user + "']");
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
        //console.log(data_json);
        //console.log("sending grade for :" + single_user);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: data_json,
            url: URL
        }).done(function (transfer_status) {
            // $.each( transfer_status,function(index,value){
            //$.each( parent_tr_nodes,function(i,tr_node) {
            //var node_user_value = $(tr_node).attr('data-moodle-user-id');
            //console.log("Node user val:"+node_user_value);
            //console.log("Userid val:"+transfer_status.userid);
            if (tr_node.attr('data-moodle-user-id') == transfer_status.userid) {
                //console.log("YEs I found a target..Applying it");
                if (transfer_status.status == 'success') {
                    //total transferred
                    succ_count++;
                    //console.log("Status is success");
                    $('#confirm_transfer_table tbody').find("tr[data-moodle-user-id='" + transfer_status.userid + "']")
                        .find('.transfer_status')
                        .removeClass('label-warning')
                        .addClass('label-success')
                        .html("Grade transferred to SAMIS");
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
                sendSingleGrade(users, data_json, succ_count, failed_count);
            }
            else {
                $("body").css("cursor", "default");
                var total_count = {'success': succ_count, 'failed': failed_count};
                endSummary(total_count);
            }

        });

    };
    var getUsers = function (nodes) {
        var users = [];
        $.each(nodes, function (i, tr_node) {
            var node_user_value = $(tr_node).attr('data-moodle-user-id');
            users.push(node_user_value);
        });
        return users;
    };
    var sendGrades = function (node, e) {
        e.preventDefault();
        //disable the button
        $(node).attr('disabled', true);
        var data = form.serializeArray();
        $(node).next().html('Go Back').attr('href', config.wwwroot + '/grade/report/transfer/index.php?id=' + data[3].value);
        $("body").css("cursor", "progress");
        $('#confirm_transfer_table .transfer_status')
            .removeClass()
            .addClass('label label-warning transfer_status')
            .html('');
        var data_json = {};
        //Get submitted data
        //var promise = $.Deferred();
        var parent_tr_nodes = $('#confirm_transfer_table tbody tr');

        var form = $('#transferconfirmed');

        //console.log(data);
        var users = getUsers(parent_tr_nodes);
        //Now that I have the users, get the first one in the index.
        var success_transferred_count = 0;
        var failed_transferred_count = 0;
        sendSingleGrade(users, data, success_transferred_count, failed_transferred_count);
    };
    return {
        init: function () {
            $('#proceed_grade_transfer').click(function (e) {
                sendGrades(this, e);
            });


        }
    };
});