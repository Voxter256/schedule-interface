@extends ('app')

@section('title')
Physicians
@endsection

@section('content')
    @php
        $previous_position = "";
        $first = True;
    @endphp
    <div class="text-center mt-3" data-toggle="buttons">
        @foreach ($physicians as $physician)
            @php
                $position_name = $physician->position->name;
                $position_name_fixed = str_replace("+", "", $position_name)
            @endphp
            @if ($previous_position != $position_name)
                <?php $previous_position = $position_name; ?>
                @if ($first)
                    @php
                        $first = False;
                        $aria_expanded_value = "true";
                        $active_class = "active";
                    @endphp
                @else
                    @php
                        $aria_expanded_value = "false";
                        $active_class = "";
                    @endphp
                @endif
                <button class="btn btn-primary {{ $active_class }}" type="button" data-toggle="collapse" data-target="#collapse-{{ $position_name_fixed }}" aria-expanded="{{ $aria_expanded_value }}" aria-controls="collapse-{{ $position_name_fixed }}">{{ $position_name }}</button>
            @endif
        @endforeach
    </div>
    <div class="container text-center mt-3">
        @php
            $previous_position = "";
            $first = True;
        @endphp
        @foreach ($physicians as $physician)
            @php
                $position_name = $physician->position->name;
                $position_name_fixed = str_replace("+", "", $position_name)
            @endphp
            @if ($previous_position != $position_name)
                <?php $previous_position = $position_name; ?>
                @if ($first)
                    @php
                        $first = False;
                        $show_class = "show";
                    @endphp
                @else
                    <?php $show_class = "" ?>
                    {{-- End Previous Section --}}
                    </div>
                @endif
                <div class="collapse multi-collapse bg-light mb-2 {{ $show_class }}" id="collapse-{{ $position_name_fixed }}">
            @endif
            <div><a href="{{ action('PhysicianController@show', ['id' => $physician->id]) }}">{{ $physician->name }} | {{ $position_name }}</a></div>
        @endforeach
    </div>
@endsection
