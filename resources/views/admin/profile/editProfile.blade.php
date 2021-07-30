@extends('admin.layouts.master')

@section('title', 'Admin Profile')

@section('page_content')

    <!-- Main content -->
    <div class="row">
        <!-- /.col -->
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <div class="tab-content">
                    <div class="box-body box-profile">
                        @if (!empty(Auth::guard('admin')->user()->picture))
                            <img alt="User profile picture" src='{{url("public/uploads/userPic/$admin_picture")}}' style="height: 100px !important;" class="profile-user-img img-responsive img-circle asa" id="admin-picture-preview">
                        @else
                            <img alt="User profile picture" src='{{url("public/admin_dashboard/img/avatar.jpg")}}' style="height: 100px !important;" class="profile-user-img img-responsive img-circle asa">
                        @endif

                        <h3 class="profile-username text-center">{{ ucwords(Auth::guard('admin')->user()->first_name.' '.Auth::guard('admin')->user()->last_name) }}</h3>

                        <a class="btn btn-primary btn-block" href='{{ url("admin/change-password")}}'><b>Change Password</b></a>
                    </div>

                    <div>
                        <form action='{{ url("admin/update-admin/$admin_id") }}' method="POST" class="form-horizontal" enctype="multipart/form-data" id="profile_form">
                            {!! csrf_field() !!}

                            <div class="form-group">
                                <label class="col-sm-2 control-label">First Name</label>
                                <div class="col-sm-10">
                                    <input type="text" value="{{Auth::guard('admin')->user()->first_name}}" class="form-control"
                                           id="first_name" name="first_name">
                                    <span id="val_fname" style="color: red"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Last name</label>
                                <div class="col-sm-10">
                                    <input type="text" value="{{Auth::guard('admin')->user()->last_name}}" class="form-control"
                                           id="last_name" name="last_name">
                                    <span id="val_lname" style="color: red"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="inputEmail">Email</label>
                                <div class="col-sm-10">
                                    <input type="email" value="{{Auth::guard('admin')->user()->email}}" class="form-control" id="em"
                                           name="email" readonly>
                                    <span id="val_em" style="color: red"></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="inputEmail3" class="col-sm-2 control-label">Picture</label>
                                <div class="col-sm-10">
                                    <input type="file" name="picture" class="form-control input-file-field" id="admin-picture">
                                    <input type="hidden" name="pic" value="{{ Auth::guard('admin')->user()->picture ? Auth::guard('admin')->user()->picture : '' }}">
                                    <div class="clearfix"></div>
                                    <small class="form-text text-muted"><strong>{{ allowedImageDimension(100,100) }}</strong></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    {{-- <button type="submit" class="btn btn-primary btn-flat" id="update">
                                        <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="update-text">Update</span>
                                    </button> --}}
                                    <button type="submit" class="btn btn-primary btn-flat" id="update">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- /.tab-pane -->
                </div>
                <!-- /.tab-content -->
            </div>
            <!-- /.nav-tabs-custom -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->

@endsection

@push('extra_body_scripts')

<!-- jquery.validate -->
<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

<!-- jquery.validate additional-methods -->
<script src="{{ asset('public/dist/js/jquery-validation-1.17.0/dist/additional-methods.min.js') }}" type="text/javascript"></script>

<!-- read-file-on-change -->
@include('common.read-file-on-change')

<script type="text/javascript">

    // preview admin picture and small admin picture on change
    $(document).on('change','#admin-picture', function()
    {
        let orginalSource = '{{ url('public/uploads/userPic/default-image.png') }}';
        readFileOnChange(this, $('.user-image'), orginalSource);
        readFileOnChange(this, $('.img-circle'), orginalSource);
        readFileOnChange(this, $('#admin-picture-preview'), orginalSource);
    });

    $('#profile_form').validate({
        rules: {
            first_name: {
                required: true
            },
            last_name: {
                required: true
            },
            picture: {
                extension: "png|jpg|jpeg|gif|bmp",
            },
        },
        messages: {
          picture: {
            extension: "Please select (png, jpg, jpeg, gif or bmp) file!"
          },
        },
        // submitHandler: function(form)
        // {
        //     $(".fa-spin").show();
        //     $("#update-text").text('Updating...');
        //     $("#update").attr("disabled", true).click(function(e) {
        //         e.preventDefault();
        //     });
        //     form.submit();
        // }
    });
</script>

@endpush

