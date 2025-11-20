<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Program PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #000; }
        .center { text-align: center; }
        .title { font-size: 18px; font-weight: bold; }
        .week-title { font-size: 13px; font-weight: bold; margin: 8px 0 4px; }
        .day-title { font-size: 12px; font-weight: bold; margin: 6px 0 4px; }
        .circuit-title { font-size: 11px; font-weight: bold; margin: 4px 0 2px; }
        .exercise-title { font-size: 10px; font-weight: bold; }
        .meta-line { font-size: 9px; color: #444; margin: 2px 0; }
        .notes { font-size: 9px; font-style: italic; margin: 2px 0 6px; }
        .cooldown { font-style: italic; color: #666; margin: 2px 0 6px; }
        .custom-header { font-weight: bold; }
        .row { display: table; width: 100%; }
        .col { display: table-cell; }
        .mt-2 { margin-top: 2px; }
        .mb-10 { margin-bottom: 10px; }
        .logo { width: 80px; margin: 0 auto 8px; display: block; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px; border-bottom: 1px solid #ddd; text-align: left; }
        thead th { border-bottom: 1px solid #bbb; }
    </style>
</head>
<body>
    <div class="center">
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
        @endif
        <div class="title">{{ $program->name ?? 'Program' }}</div>
    </div>

    <div class="row">
        <div class="col" style="width:auto;">Trainer: {{ optional($program->trainer)->name ?? 'N/A' }}</div>
        <div class="col" style="width:60%;"></div>
        <div class="col" style="width:auto;">Client: {{ optional($program->client)->name ?? 'Unassigned' }}</div>
    </div>

    <div class="row mt-2 mb-10">
        <div class="col" style="width:auto;">Duration: {{ ($program->duration ?? 0) }} weeks</div>
        <div class="col" style="width:60%;"></div>
        <div class="col" style="width:auto;">Status: {{ $program->is_active ? 'Active' : 'Inactive' }}</div>
    </div>

    @if(!empty($program->description))
        <div class="mb-10">{{ $program->description }}</div>
    @endif

    @if($program->weeks && $program->weeks->count())
        @foreach($program->weeks as $week)
            <div class="week-title">Week {{ $week->week_number }}@if(!empty($week->title)) – {{ $week->title }}@endif</div>
            @if(!empty($week->description))
                <div class="mb-10">{{ $week->description }}</div>
            @endif

            @if($week->days && $week->days->count())
                @foreach($week->days as $day)
                    <div class="day-title">Day {{ $day->day_number }}@if(!empty($day->title)) – {{ $day->title }}@endif</div>
                    @if(!empty($day->description))
                        <div>{{ $day->description }}</div>
                    @endif

                    @if($day->circuits && $day->circuits->count())
                        @foreach($day->circuits as $circuit)
                            <div class="circuit-title">Circuit {{ $circuit->circuit_number }}@if(!empty($circuit->title)) – {{ $circuit->title }}@endif</div>
                            @if(!empty($circuit->description))
                                <div>{{ $circuit->description }}</div>
                            @endif

                            @if($circuit->programExercises && $circuit->programExercises->count())
                                @foreach($circuit->programExercises as $ex)
                                    @php
                                        $exTitle = $ex->name ?? optional($ex->workout)->name ?? optional($ex->workout)->title ?? 'Exercise';
                                        $metaLine = [];
                                        if (!empty($ex->tempo)) { $metaLine[] = 'Tempo: '.$ex->tempo; }
                                        if (!empty($ex->rest_interval)) { $metaLine[] = 'Rest: '.$ex->rest_interval; }
                                    @endphp
                                    <div class="exercise-title">{{ $exTitle }}</div>
                                    @if(count($metaLine))
                                        <div class="meta-line">{{ implode('  |  ', $metaLine) }}</div>
                                    @endif

                                    @if($ex->exerciseSets && $ex->exerciseSets->count())
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Set</th>
                                                    <th>Reps</th>
                                                    <th>Weight</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($ex->exerciseSets as $s)
                                                    <tr>
                                                        <td>Set {{ $s->set_number ?? '' }}</td>
                                                        <td>{{ $s->reps ?? '' }}</td>
                                                        <td>{{ $s->weight ?? '' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif

                                    @if(!empty($ex->notes))
                                        <div class="notes">Notes: {{ $ex->notes }}</div>
                                    @else
                                        <div class="notes" style="visibility:hidden;">.</div>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    @endif

                    @php $customRows = is_array($day->custom_rows ?? null) ? $day->custom_rows : []; @endphp
                    @if(count($customRows))
                        <div class="custom-header">Custom Rows</div>
                        <ul>
                            @foreach($customRows as $row)
                                <li>{{ $row }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if(!empty($day->cool_down))
                        <div class="cooldown">Cool Down: {{ $day->cool_down }}</div>
                    @endif
                @endforeach
            @endif
        @endforeach
    @endif
</body>
</html>