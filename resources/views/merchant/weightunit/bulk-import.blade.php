@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="row">
                        <div class="col-md-3">
                            <h3 class="panel-title">@lang("$string_file.import_bulk_weightunit")</h3>
                        </div>

                    </div>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          name="bulk-import-weightunit"
                          id="bulk-import-weightunit"
                          action="{{route('weightunit.bulk-import.preview')}}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label>@lang("$string_file.import_file")  <span class="text-danger">*</span></label>
                                <div class="form-group">
                                    <input type="file" id="import_file" name="import_file" class="form-control" required>
                                    @if ($errors->has('import_file'))
                                        <label class="text-danger">{{ $errors->first('import_file') }}</label>
                                    @endif
                                </div>
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="form-actions d-flex flex-row-reverse p-2">
                                <button type="submit" class="btn btn-primary" id="previewButton">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.preview")
                                </button>
                            </div>
                            <div class="col-md">
                                <a href="{{asset('basic-images/weightunit_import.xlsx')}}">
                                    <button type="button" title="@lang(" $string_file.download_weightunit_excel_example")"
                                            class="btn btn-icon btn-info" style="margin:10px">
                                        @lang("$string_file.download_weightunit_excel_example") <i
                                                class="wb-download"></i>
                                    </button>
                                </a>
                            </div>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title">
                        <i class=" icon wb-shopping-cart" aria-hidden="true"></i>@lang("$string_file.weightunit")
                    </h3>
                </header>
                <div class="panel-body" style="overflow: scroll">
                    <table class="table table-bordered" id="" style="width:170%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.description")</th>
                            <th>@lang("$string_file.segment")</th>
                        </tr>
                        </thead>
                        @if(!empty($excelData))
                            @php $i = 1; @endphp
                            @foreach($excelData as $data)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$data['name']}}</td>
                                    <td>{{$data['description']}}</td>
                                    <td>{{$data['segment']}}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <th colspan="13" style="text-align: center">No Weightunit Found For Preview</th>
                            </tr>
                        @endif
                        <tbody>
                        @php $sr = 1; @endphp
                        </tbody>
                    </table>
                </div>
                @if(!empty($excelData))
                    <div class="panel-footer">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              name="bulk-import-weightunit-submit"
                              id="bulk-import-weightunit-submit"
                              action="{{route('weightunit.bulk-import.submit')}}">
                            @csrf
                            {{--<input type="hidden" name="excel_data" value="{{$excelData}}">--}}
                            <div class="form-actions d-flex flex-row-reverse p-2">
                                <button type="submit" class="btn btn-primary" id="submitButton">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.submit")
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
