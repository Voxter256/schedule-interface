@extends ('app')

@section('title')
Check Vacation Results
@endsection

@section('content')
<div class="container text-center">
    <h1 class="display-4">
        Vacation Planner
    </h1>
    <div class="container bg-light">
        <h2>
            {{ $start_date->toDateString() }} to {{ $end_date->toDateString() }}
        </h2>
        @if ($success !== False)
            <h2 class="text-success">Vacation is possible</h2>
            <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#messages" aria-expanded="false" aria-controls="messages">
                View Logic Messages
            </button>
            <div class="container-fluid col row">
                <div id="messages" class="col collapse">
        @endif
                    <?php $in_list_group = False; ?>
                    @foreach ($messages as $message)
                        @if ($message['type'] == 'header')
                            @if ($in_list_group)
                                <?php $in_list_group = False; ?>
                                </div>
                            @endif
                            @if ($message['size'] == 'large')

                                <h4>{{ $message['message'] }}</h4>
                            @else

                                <h6>{{ $message['message'] }}</h6>
                                <div class="list-group">
                                <?php $in_list_group = True ?>

                            @endif
                        @else
                            @if ($in_list_group)
                                <div class="list-group-item text-{{ $message['type'] }}">{{ $message['message'] }}</div>
                            @else
                                <div class="text-{{ $message['type'] }}">{{ $message['message'] }}</div>
                            @endif

                        @endif
                    @endforeach
            @if ($success !== False)
                </div>
            </div>
            @endif
            @if ($success !== False)
                <div class="col">
                @foreach ($call_array as $call)
                    <h3>{{ $call['original']->physician->name }}'s call on {{ $call['original']->shift_date->format('D, M j, Y') }}</h3>
                    @foreach ($call['potentials'] as $potential)
                        <div>
                            <!-- <a class="" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample"> -->
                                {{ $potential->physician->name }} on {{ $potential->shift_date->format('D, M j, Y') }}
                            <!-- </a> -->
                            <!-- <div class="collapse"> -->
                                <!-- Test -->
                            <!-- </div> -->
                        </div>
                    @endforeach
                @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
