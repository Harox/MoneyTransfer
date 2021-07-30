@extends('admin.layouts.master')
@section('title', 'Admin')

@section('head_style')
    <!-- dataTables -->
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/DataTables_latest/DataTables-1.10.18/css/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/DataTables_latest/Responsive-2.2.2/css/responsive.dataTables.min.css') }}">
@endsection

@section('page_content')

<!-- Main content -->
<div class="row">
  <div class="col-xs-12">

    <div class="box box_info">
      <div class="box-header">
        <h3 class="box-title">Admin User Management</h3>
        <div style="float:right;"><a class="btn btn-success" href="{{ url('admin/add_admin') }}">Add Admin User</a></div>
      </div>
      <!-- /.box-header -->
      <div class="box-body">
      {!! $dataTable->table() !!}
      </div>
    </div>
  </div>
</div>

@endsection

@push('extra_body_scripts')

<!-- jquery.dataTables js -->
<script src="{{ asset('public/backend/DataTables_latest/DataTables-1.10.18/js/jquery.dataTables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('public/backend/DataTables_latest/Responsive-2.2.2/js/dataTables.responsive.min.js') }}" type="text/javascript"></script>

{!! $dataTable->scripts() !!}
@endpush
