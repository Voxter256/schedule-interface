@extends ('app')

@section('title')
Shifts
@endsection

@section('content')
<div class="container">
    <div class="container mb-3 mt-3">
        <form method="POST" action="shifts">
            {{ csrf_field() }}
            <div class="form-row align-items-center justify-content-center">
                <div class="col-auto">
                    <input class="form-control" type="date" name="date" value="{{ $date->toDateString() }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary ">View</button>
                </div>
            </div>

        </form>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-4 col-sm-6 mb-3 bg-light">
            <h3 class="text-center">Day Shift</h3>

            @foreach($day_shifts as $shift)
                <div class="row align-items-center">
                    <div class="col pl-0 text-right">{{ $shift->physician->name }}</div>
                    <div class="col pr-0 text-left">{{ $shift->service->name }}</div>
                </div>
            @endforeach
        </div>
        <div class="col-md-4 col-sm-6 bg-light mb-3">
            <h3 class="text-center mb-0">On Call</h3>
            @foreach($call_shifts as $shift)
            <div class="row align-items-center">
                <div class="col pl-0 text-right">{{ $shift->physician->name }}</div>
                <div class="col pr-0 text-left">{{ $shift->service->name }}</div>
            </div>
            @endforeach
        </div>
        <div class="col-md-2 col-sm-6 bg-light mb-3">
            <h3 class="text-center">Post Call</h3>
            @foreach($physicians_post_call as $physician)
                <div class="text-center">{{ $physician->name }}</div>
            @endforeach
        </div>
        <div class="col-md-2 col-sm-6 bg-light mb-3">
            <h3 class="text-center">On Vacation</h3>
            @foreach($physicians_on_vacation as $physician)
                <div class="text-center">{{ $physician->name }}</div>
            @endforeach
        </div>

    </div>
</div>
@endsection
