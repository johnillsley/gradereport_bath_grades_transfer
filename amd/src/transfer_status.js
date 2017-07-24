define(['jquery', 'core/templates', 'core/ajax', 'core/config'], function ($, templates, ajax, config) {
    var URL = config.wwwroot + '/grade/report/transfer/ajax.php';
    var sendGrade = function (userid,data_json){
        //Send a single grade to samis via ajax
        var users = [userid];
        //data_json.push(users);
        data_json.users = users;
        console.log(data_json);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: data_json,
            url: URL
        }).done(function (transfer_status) {
            //Set the transfer status
            //call func(n)
            $("body").css("cursor", "default");
            console.log(transfer_status);

            $.each( transfer_status,function(index,value){
                $.each( parent_tr_nodes,function(i,tr_node){
                    var node_user_value = $(tr_node).attr('data-moodle-user-id');
                    console.log(node_user_value);
                    if(node_user_value == value.userid ){
                        console.log("YEs I found a target..Applying it");
                        $('#confirm_transfer_table tbody').find("tr[data-moodle-user-id='"+value.userid+"']")
                            .find('.transfer_status')
                            .removeClass('label-warning')
                            .addClass('label-success')
                            .html('Grade sent successfully to SAMIS');
                    }
                });
            });

            //$('#confirm_transfer_table .transfer_status').html('Grade sent successfully to SAMIS')
        });

    };
    var sendGrades = function(node,e){
            e.preventDefault();
            //disable the button
            $(node).attr('disabled', true);
            $("body").css("cursor", "progress");
        $('#confirm_transfer_table .transfer_status')
            .removeClass()
            .addClass('label label-warning transfer_status')
            .html('');
            var parent_tr_nodes = $('#confirm_transfer_table tbody tr');
            var data_json = {};
            //Get submitted data
            //var promise = $.Deferred();
            var form = $('#transferconfirmed');
            var data = form.serializeArray();
            console.log(data);
            var users = [];
            $.each(data,function(i,field){
                if(field.name == 'user[]'){
                    users.push(field.value);
                    //data_json['users'] = users;
                }
                else{
                    data_json[field.name] = field.value || '';
                }

            });
            console.log(data_json);

            //Foreach user send a seperare AJAX request
            $.each(users,function(key,userid){
                sendGrade(userid,data_json);
            });




            /*$.ajax({
                type: 'POST',
                dataType: 'json',
                data: data,
                url: URL
            }).done(function (transfer_status) {
                //Set the transfer status
                //call func(n)
                $("body").css("cursor", "default");
                console.log(transfer_status);

                $.each( transfer_status,function(index,value){
                    $.each( parent_tr_nodes,function(i,tr_node){
                        var node_user_value = $(tr_node).attr('data-moodle-user-id');
                        console.log(node_user_value);
                        if(node_user_value == value.userid ){
                            console.log("YEs I found a target..Applying it");
                            $('#confirm_transfer_table tbody').find("tr[data-moodle-user-id='"+value.userid+"']")
                                .find('.transfer_status')
                                .removeClass('label-warning')
                                .addClass('label-success')
                                .html('Grade sent successfully to SAMIS');
                        }
                    });
                });

                //$('#confirm_transfer_table .transfer_status').html('Grade sent successfully to SAMIS')
            });*/

    };
    return {
        init: function () {
            //Catch the button click here
            $(document).bind("ajaxSend", function(){
                $(".loadingDiv").show();
            }).bind("ajaxComplete", function(){
                $(".loadingDiv").hide();
            });
            $('#proceed_grade_transfer').click(function (e) {
                sendGrades(this,e);
            });


        }
    };
});