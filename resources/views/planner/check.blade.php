@extends ('app')

@section('title')
Check Vacation
@endsection

@section('content')
<div class="container text-center bg-light">
    <h1 class="display-4">
        Vacation Planner
    </h1>
    <form method="POST" action="vacation_planner">
        {{ csrf_field() }}
        <div class="form-row container-fluid justify-content-center">
            <div class="form-group col-12">
                <label for="physician">Physician:</label>
                <select class="form-control" id="physician" name="physician">
                    @foreach ($physicians as $physician)
                        @if ($physician->id == $user_id)
                            <?php $selected_string = "selected"; ?>
                        @else
                            <?php $selected_string = ""; ?>
                        @endif
                        <option value={{ $physician->id }}  {{ $selected_string }}>{{ $physician->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-12 col-sm-4">
                <label for="start_date">Start Date:</label>
                <input class="form-control" type="date" name="start_date">
            </div>
            <div class="form-group col-12 col-sm-4">
                <label for="end_date">End Date:</label>
                <input class="form-control" type="date" name="end_date">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
@endsection
