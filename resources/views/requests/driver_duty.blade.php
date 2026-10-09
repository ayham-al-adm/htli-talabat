<table>
    <thead>
        <tr>
            <th>{{ __('exports.no') }}</th>
            <th>{{ __('exports.date') }}</th>
            <th>{{ __('exports.driver_name') }}</th>
            <th>{{ __('exports.total_logged_in_hours') }}</th>
        </tr>
    </thead>
    <tbody>
        @php $i = 1; @endphp

        @forelse($results as $requests)
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $requests->date }}</td>
                <td>{{  $requests->driver_name }}</td>
                <td>
                    @if(isset($requests->total_duration_hours))
                        @php
                            // Get the total hours and minutes
                            $totalHours = floor($requests->total_duration_hours); // Whole hours
                            $totalMinutes = ($requests->total_duration_hours - $totalHours) * 60; // Convert decimal part to minutes
                        @endphp
                        {{ __('exports.hours_minutes', ['hours' => $totalHours, 'minutes' => floor($totalMinutes)]) }}
                    @else
                        -
                    @endif
                </td>
           </tr>
        @empty
            <tr>
                <td colspan="4">
                    <h4 class="text-center" style="color:#333; font-size:25px;">{{ __('exports.no_data_found') }}</h4>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>


