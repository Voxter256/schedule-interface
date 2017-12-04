@extends ('app')

@section('title')
Check Vacation Results
@endsection

@section('content')
<div class="title m-b-md">
    Vacation Planner
</div>
<div class="container">
    <h2>
        {{ $start_date->toDateString() }} to {{ $end_date->toDateString() }}
    </h2>
    <div style="display:flex">
        <div class="container">
        @foreach ($messages as $message)
            @if ($message['type'] == 'header')
                @if ($message['size'] == 'large')
                    <h3>{{ $message['message'] }}</h3>
                @else
                    <h4>{{ $message['message'] }}</h4>
                @endif
            @else
                <div>{{ $message['message'] }}</div>
            @endif
        @endforeach
        </div>
        @if ($success !== False)
            <div class="container">
            @foreach ($call_array as $call)
                <h3>{{ $call['original']->physician->name }}'s call on {{ $call['original']->shift_date->format('D, M j, Y') }}</h3>
                @foreach ($call['potentials'] as $potential)
                    {{ $potential->physician->name }} on {{ $potential->shift_date->format('D, M j, Y') }}</br>
                @endforeach
            @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
