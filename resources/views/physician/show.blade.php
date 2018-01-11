@extends ('app')

@section('title')
{{ $physician->name }}
@endsection

@section('content')
    <h1 class="display-4 text-center">{{ $physician->name }}</h1>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-sm bg-light mb-3">
                <h1 class="text-center">Call Days</h1>
                @php
                    $call_shifts = $physician->shifts->where('shift_date', '>=', $today)->sortBy('shift_date');
                @endphp
                @foreach($call_shifts as $shift)
                    @if ($shift->service->is_call == 1)
                        <div class="text-center">{{ $shift->shift_date->format('D, M j, Y') }}: {{ $shift->service->name }} </div>
                        @endif
                @endforeach
            </div>
            <div class="col-sm bg-light mb-3">
                <h1 class="text-center mb-0">Vacation Days</h1>
                <div class="text-center mb-3"><em>Days Remaining: {{ $vacation_days_available }}</em></div>
                @foreach($vacation_days as $vacation_day)
                    <div class="text-center">{{ $vacation_day->start_date->format('D, M j, Y') }} to {{ $vacation_day->end_date->format('D, M j, Y') }}</div>
                @endforeach
            </div>
            <div class="col-sm bg-light mb-3">
                <h1 class="text-center">Remaining Rotations</h1>
                @foreach($shifts as $key => $shift)
                    @if($key % 2 == 0)
                        <div class="text-center">{{ $shift->service->name }}: {{ $shift->shift_date->format('M j, Y') }} to
                    @else
                        {{ $shift->shift_date->format('M j, Y') }}</div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endsection
