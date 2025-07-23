@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
          @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        {{--<a href="{{route('merchant.send-test-invoice')}}" class="btn btn-icon btn-warrning">Send</a>--}}
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                    </div>
                    <h4 class="panel-title">
                        <i class=" icon fa-gears" aria-hidden="true"></i>
                        @lang("$string_file.email_configuration")
                    </h4>
                </header>
                <div class="panel-body container-fluid">
                    <h5>
                        <i class="icon fa-user" aria-hidden="true"></i>
                        @lang("$string_file.sender_details")
                    </h5>
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          action="{{ route('merchant.emailconfiguration.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="slug">
                                        Sending Partner
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" required name="slug" id='slug'>
                                        <option value="">@lang("$string_file.select")</option>
                                        <option value="PHP_MAILER"
                                                @if(isset($configuration['slug']) && $configuration['slug'] == 'PHP_MAILER')selected @endif> PHP MAILER
                                        </option>
                                        <option value="BREVO"
                                                @if(isset($configuration['slug']) && $configuration['slug'] == 'BREVO')selected @endif> BREVO
                                        </option>
                                        <!--@if(Auth::user('merchant')->demo == 1)-->
                                        <!--    <option value="MAILGUN"-->
                                        <!--        @if(isset($configuration['slug']) && $configuration['slug'] == 'MAILGUN')selected @endif> MAILGUN-->
                                        <!--    </option>-->
                                        <!--@endif-->
                                    </select>
                                    @if ($errors->has('slug'))
                                        <label class="danger">{{ $errors->first('slug') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.sender") @lang("$string_file.email")<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               name="sender" id="sender"
                                               placeholder="" required
                                               value="@if($configuration && isset($configuration['sender'])){{$configuration['sender']}}@endif">
                                        @if ($errors->has('sender'))
                                            <label class="danger">{{ $errors->first('sender') }}</label>
                                        @endif
                                    </div>
                                </div>
                        </div>
                        <div class="custom-hidden" id="api_key_div">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="api_key">Api Key
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               name="api_key" id="api_key"
                                               placeholder=""
                                               value="@if($configuration){{$configuration['api_key']}}@endif">
                                        @if ($errors->has('api_key'))
                                            <label class="danger">{{ $errors->first('api_key') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="custom-hidden" id="normal_div">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="host">
                                            @lang("$string_file.host_name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               name="host" id="host"
                                               placeholder=""
                                               value="@if($configuration){{$configuration['host']}}@endif">
                                        @if ($errors->has('host'))
                                            <label class="danger">{{ $errors->first('host') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.email") \ @lang("$string_file.username")<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               name="username" id="username"
                                               placeholder=""
                                               value="@if($configuration){{$configuration['username']}}@endif">
                                        @if ($errors->has('username'))
                                            <label class="danger">{{ $errors->first('username') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="password">
                                            @lang("$string_file.password")<span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control"
                                               name="password" id="password"
                                               placeholder=""
                                               value="@if($configuration){{$configuration['password']}}@endif">
                                        @if ($errors->has('password'))
                                            <label class="danger">{{ $errors->first('password') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="encryption">
                                            @lang("$string_file.encryption")<span class="text-danger">*</span><span
                                                    class="text-primary"> ( tls/ssl )</span>
                                        </label>
                                        <select class="form-control" name="encryption" id="encryption">
                                            <option value="">@lang("$string_file.select")</option>
                                            <option value="ssl"
                                                    @if(isset($configuration['encryption']) && $configuration['encryption'] == 'ssl')selected @endif>
                                                SSL
                                            </option>
                                            <option value="tls"
                                                    @if(isset($configuration['encryption']) && $configuration['encryption'] == 'tls')selected @endif>
                                                TLS
                                            </option>
                                        </select>
                                        @if ($errors->has('encryption'))
                                            <label class="danger">{{ $errors->first('encryption') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="port">
                                            @lang("$string_file.port_number") <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="port" id="port">
                                            <option value="">@lang("$string_file.select")</option>
                                            <option value="465"
                                                    @if(isset($configuration['port']) && $configuration['port'] == '465')selected @endif>
                                                465(SSL)
                                            </option>
                                            <option value="587"
                                                    @if(isset($configuration['port']) && $configuration['port'] == '587')selected @endif>
                                                587(TLS)
                                            </option>
                                        </select>
                                        @if ($errors->has('port'))
                                            <label class="danger">{{ $errors->first('port') }}</label>
                                        @endif
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        <!--<div class="row" id="mailgun_custom_div">-->
                        <!--    <div class="col-md-4">-->
                        <!--                <div class="form-group">-->
                        <!--                    <label for="mailgun_domain">-->
                        <!--                        @lang("$string_file.mailgun_domain") <span class="text-danger">*</span>-->
                        <!--                    </label>-->
                        <!--                    <input type="mailgun_domain" class="form-control"-->
                        <!--                           name="mailgun_domain" id="mailgun_domain"-->
                        <!--                           placeholder=""-->
                        <!--                           value="@if($configuration){{$configuration['mailgun_domain']}}@endif">-->
                        <!--                    @if ($errors->has('mailgun_domain'))-->
                        <!--                        <label class="danger">{{ $errors->first('mailgun_domain') }}</label>-->
                        <!--                    @endif-->
                        <!--                </div>-->
                        <!--    </div>-->
                        <!--    <div class="col-md-4">-->
                        <!--                <div class="form-group">-->
                        <!--                    <label for="mailgun_secret">-->
                        <!--                        @lang("$string_file.mailgun_secret") <span class="text-danger">*</span>-->
                        <!--                    </label>-->
                        <!--                    <input type="mailgun_secret" class="form-control"-->
                        <!--                           name="mailgun_secret" id="mailgun_secret"-->
                        <!--                           placeholder=""-->
                        <!--                           value="@if($configuration){{$configuration['mailgun_secret']}}@endif">-->
                        <!--                    @if ($errors->has('mailgun_secret'))-->
                        <!--                        <label class="danger">{{ $errors->first('mailgun_secret') }}</label>-->
                        <!--                    @endif-->
                        <!--                </div>-->
                        <!--    </div>-->
                        <!--</div>-->
                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if(Auth::user('merchant')->can('edit_email_configurations'))
                                @if($edit_permission)
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-square-o"></i> @lang("$string_file.save")
                                </button>
                            @else
                                <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                            @endif
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
<script>
    $(document).ready(function(){
        changeDiv();
    });

    $(document).on('change', '#slug', function(){
        changeDiv();
    });
    
    function changeDiv(){
        var slug = $('#slug').val();
        if(slug == "PHP_MAILER"){
            $('#normal_div').show();
            $('#api_key_div').hide();
            $('#api_key').prop('required', false);
            $('#mailgun_custom_div').hide();
            $('#mailgun_domain').prop('required', false);
            $('#mailgun_secret').prop('required', false);
            
            $('#host').prop('required', true);
            // $('#sender').prop('required', true);
            $('#username').prop('required', true);
            $('#password').prop('required', true);
            $('#encryption').prop('required', true);
            $('#port').prop('required', true);
        }else if(slug == "BREVO"){
            $('#normal_div').hide();
            $('#api_key_div').show();
            $('#api_key').prop('required', true);
            $('#mailgun_custom_div').hide();
            $('#mailgun_domain').prop('required', false);
            $('#mailgun_secret').prop('required', false);
            
            $('#host').prop('required', false);
            // $('#sender').prop('required', false);
            $('#username').prop('required', false);
            $('#password').prop('required', false);
            $('#encryption').prop('required', false);
            $('#port').prop('required', false);
        }
        // else if(slug == "MAILGUN"){
        //     $('#normal_div').show();
        //     $('#api_key_div').hide();
        //     $('#api_key').prop('required', false);
        //     $('#mailgun_custom_div').show();
        //     $('#mailgun_domain').prop('required', true);
        //     $('#mailgun_secret').prop('required', true);
            
        //     $('#host').prop('required', false);
        //     // $('#sender').prop('required', false);
        //     $('#username').prop('required', false);
        //     $('#password').prop('required', false);
        //     $('#encryption').prop('required', false);
        //     $('#port').prop('required', false);
        // }
    }
</script>
@endsection