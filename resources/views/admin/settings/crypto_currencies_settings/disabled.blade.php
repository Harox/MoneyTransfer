@extends('admin.layouts.master')
@section('title', 'Crypto Disabled')

@section('page_content')
    <!-- Main content -->
	<div class="row">
	    <div class="col-md-3 settings_bar_gap">
	        @include('admin.common.settings_bar')
	    </div>
	    <div class="col-md-9">
	    	@include('admin.common.crypto_preference_disabled_body')
	    </div>
	</div>
@endsection

@push('extra_body_scripts')
	@include('admin.common.crypto_preference_disabled_script')
@endpush

