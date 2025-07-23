<!DOCTYPE html>
<html>
    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
        <meta content="width=device-width" name="viewport"/> 
        <meta content="IE=edge" http-equiv="X-UA-Compatible"/>
        <title></title>
        <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css"/>
    </head>
    <body style="background-color:rgb(233 233 233); padding:20px; font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;">
        <div class="container" style="max-width: 600px; min-width:300px; padding:30px 0; margin: 20px auto; ">
            <div style="background-color: #ffffff; margin: 20px 0; border-radius: 10px;">            
                <div style="padding: 20px; text-align: center; ">
                    <h2>@lang('user.forgot_password')</h2>
                    <p>@lang('user.forgot_password_message')<br>@lang('user.use_the_link_below_to_get_started')</p>
                    <button style="background: rgb(8, 113, 184); padding:10px 25px; border:0; margin:20px 0;">
                        <a href="{{$url}}" style="text-decoration: none; color:#ffffff; " >@lang('user.reset_password')</a>
                    </button>
                    <br>
                    OR
                    <br><br>
                    <a href="{{$url}}" style="text-decoration: none;">Click Here</a>
                    <p style="max-width: 400px; text-align: center; margin: 20px auto;">@lang('user.if_you_did_not_request_password_reset')</p>
                </div>
            </div>
        </div>
    </body>
</html>