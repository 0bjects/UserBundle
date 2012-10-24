$(document).ready(function(){
    //validate loginName
    $('#user-signup').click(function(){
        if ($('#form_loginName').attr('errorFlag') == "false") {
            return true;
        }else{
            var new_position = $('#form_loginName').offset();
            window.scrollTo(new_position.left,new_position.top);
            return false;
        }
    });
    
    $('#form_loginName').keyup(function(){
        //user loginName
        loginName = $.trim($('#form_loginName').val());
        var reg = XRegExp("^[a-zA-Z_0-9\\p{L}]+$");
        if(loginName.length > 3){
            if (reg.test(loginName)) {
                $('#loginName-error').text('');
                //check if the login name exist
                $.ajax({
                    url: loginNameCheckUrl+"/"+loginName,
                    success: function(msg) {
                        $('#loginName-error').show();
                        if(msg == 'exist'){
                            //add repeat flag to loginName field
                            $('#form_loginName').attr('errorFlag',true);
                            $('#loginName-error').text(loginNameExist);
                        }else if(msg == 'not-exist'){
                            //add repeat flag to loginName field
                            $('#form_loginName').attr('errorFlag',false);
                            $('#loginName-error').text(loginNameNotExist);
                        }
                    },
                    complete: function(msg) {
                    }
                });
            
            }else{
                $('#loginName-error').show();
                $('#form_loginName').attr('errorFlag',true);
                $('#loginName-error').text(loginNameError);
            }
        }
    });
    
    
    $('#form_email').blur(function() {
        $(this).mailcheck({
            //            domains: domains,                       // optional
            //            topLevelDomains: topLevelDomains,       // optional
            //            distanceFunction: superStringDistance,  // optional
            suggested: function(element, suggestion) {
                $('#mailcheck-message').show();
                // callback code
                $('#mailcheck-message').text(mailcheckMessage+" "+suggestion.full);
            },
            empty: function(element) {
                // callback code
                $('#mailcheck-message').text('');
            }
        });
    });
});