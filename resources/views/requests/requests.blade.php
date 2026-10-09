   <table>
        <thead>
            <tr>
                <th>{{ __('exports.no') }}</th>
                <th>{{ __('exports.date') }}</th>
                <th>{{ __('exports.request_number') }}</th>
                <th>{{ __('exports.trip_start_time') }}</th>
                <th>{{ __('exports.trip_end_time') }}</th>
                <th>{{ __('exports.user') }}</th>
                <th>{{ __('exports.driver') }}</th>
                <th>{{ __('exports.owner') }}</th>
                <th>{{ __('exports.trip_status') }}</th>
                <th>{{ __('exports.payment_status') }}</th>
                <th>{{ __('exports.payment_option') }}</th>
                <th>{{ __('exports.vehicle_type') }}</th>
                <th>{{ __('exports.ride_type') }}</th>
                <th>{{ __('exports.trip_time') }}</th>
                <th>{{ __('exports.trip_distance') }}</th>
                <th>{{ __('exports.driver_commission') }}</th>
                <th>{{ __('exports.admin_commission') }}</th>
                <th>{{ __('exports.total_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $i= 1; @endphp

            @forelse($results as $key => $requests)
                <tr>
                    <td>{{ $i++ }} </td>
                    <td>{{ $requests->created_at->format("m/d/Y") }} </td>
                    <td>{{$requests->request_number}}</td>
                    <td>{{ $requests->converted_trip_start_time ?? '-' }}</td>
                    <td>{{ $requests->converted_completed_at ?? '-' }}</td>
                    <td>{{$requests->userDetail ? $requests->userDetail->name : '-'}}</td>
                    <td>{{$requests->driverDetail ? $requests->driverDetail->name : '-'}}</td>

                    @if($requests->owner_id)
                    <td>{{$requests->ownerDetail ? $requests->ownerDetail->owner_name : '-'}}</td>
                    @else
                    <td>{{ __('exports.individual') }}</td>
                    @endif

                    @if($requests->is_cancelled == 1)
                        <td><span class="label label-danger">{{ __('exports.cancelled') }}</span></td>
                    @elseif($requests->is_completed == 1)
                        <td><span class="label label-success">{{ __('exports.completed') }}</span></td>
                    @elseif($requests->is_trip_start == 0 && $requests->is_cancelled == 0)
                        <td><span class="label label-warning">{{ __('exports.not_started') }}</span></td>
                    @else
                        <td>-</td>
                    @endif

                    @if ($requests->is_paid)
                        <td><span class="label label-success">{{ __('exports.paid') }}</span></td>
                    @else
                        <td><span class="label label-danger">{{ __('exports.not_paid') }}</span></td>
                    @endif

                    @if ($requests->payment_opt == 0)
                        <td><span class="label label-danger">{{ __('exports.card') }}</span></td>
                    @elseif($requests->payment_opt == 1)
                        <td><span class="label label-primary">{{ __('exports.cash') }}</span></td>
                    @elseif($requests->payment_opt == 2)
                        <td><span class="label label-warning">{{ __('exports.wallet') }}</span></td>
                    @else
                        <td><span class="label label-info">{{ __('exports.cash_wallet') }}</span></td>
                    @endif

                    <td>{{ $requests->vehicle_type_name }}</td>


                @php
                   $later = $requests->is_later;
                   $rental = $requests->is_rental;
                 @endphp
                 @if($later == 0)

                    @if(($later == 0) &&  ($rental == 0))
                    <td><span class="label label-success">{{ __('exports.regular_instant') }}</span> </td>
                    @else(($later == 0) &&  ($rental == 1))
                    <td><span class="label label-success">{{ __('exports.rental_instant') }}</span> </td>
                    @endif

                @else($later == 1)

                    @if(($later == 1) &&  ($rental == 0))
                    <td><span class="label label-success">{{ __('exports.regular_scheduled') }}</span></td>
                    @else(($later == 1) &&  ($rental == 1 ))
                    <td><span class="label label-success">{{ __('exports.rental_scheduled') }}</span></td>
                    @endif

                @endif


                    <td>{{ $requests->total_time .' '. __('exports.mins') }}</td>
                    <td>{{ $requests->total_distance .'  '. $requests->request_unit}}</td>
                    <td>{{ $requests->requestBill ? $requests->currency .' '. $requests->requestBill->driver_commision : '-' }}</td>
                    <td>{{ $requests->requestBill ? $requests->currency .' '. $requests->requestBill->admin_commision_with_tax : '-' }}</td>
                    <td>{{ $requests->requestBill ? $requests->currency .' '. $requests->requestBill->total_amount : '-' }}</td>                </tr>
                @empty
                <tr>
                    <td colspan="11">
                        <h4 class="text-center" style="color:#333;font-size:25px;">{{ __('exports.no_data_found') }}</h4>
                    </td>
                </tr>
            @endforelse

            @if(isset($totals) && $results->count() > 0)
                <tr style="font-weight: bold; background-color: #f8f9fa;">
                    <td colspan="15" style="text-align: right;">{{ __('exports.total') }}</td>
                    <td>{{ $results->first()->currency ?? '' }} {{ number_format($totals['driver_commission'], 2) }}</td>
                    <td>{{ $results->first()->currency ?? '' }} {{ number_format($totals['admin_commission'], 2) }}</td>
                    <td>{{ $results->first()->currency ?? '' }} {{ number_format($totals['total_amount'], 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
