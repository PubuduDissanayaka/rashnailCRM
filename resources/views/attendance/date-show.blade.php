@extends('layouts.vertical')

@section('title', 'Attendance by Date')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Attendance for {{ $date->format('d M Y') }}</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p>This page is under construction.</p>
                    <p>Will display attendance records for the selected date.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection