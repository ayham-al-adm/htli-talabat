<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Finance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .no-data {
            text-align: center;
            color: #333;
            font-size: 20px;
            padding: 20px;
        }
        .label {
            padding: 3px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 11px;
        }
        .label-success {
            background-color: #28a745;
        }
        .label-danger {
            background-color: #dc3545;
        }
        .label-primary {
            background-color: #007bff;
        }
        .label-warning {
            background-color: #ffc107;
            color: #000;
        }
        .label-info {
            background-color: #17a2b8;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .total-cell {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">Finance Report</h1>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Date</th>
                <th>Request Number</th>
                <th>Trip Start Time</th>
                <th>Trip End Time</th>
                <th>User</th>
                <th>Driver</th>
                <th>Owner</th>
                <th>Trip Status</th>
                <th>Payment Status</th>
                <th>Payment Option</th>
                <th>Vehicle Type</th>
                <th>Ride Type</th>
                <th>Trip Time</th>
                <th>Trip Distance</th>
                <th>Driver Commission</th>
                <th>Admin Commission</th>
                <th>Total Amount</th>
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
                    <td>{{"Individual"}}</td>
                    @endif

                    @if($requests->is_cancelled == 1)
                        <td><span class="label label-danger">cancelled</span></td>
                    @elseif($requests->is_completed == 1)
                        <td><span class="label label-success">completed</span></td>
                    @elseif($requests->is_trip_start == 0 && $requests->is_cancelled == 0)
                        <td><span class="label label-warning">not_started</span></td>
                    @else
                        <td>-</td>
                    @endif

                    @if ($requests->is_paid)
                        <td><span class="label label-success">paid</span></td>
                    @else
                        <td><span class="label label-danger">not_paid</span></td>
                    @endif

                    @if ($requests->payment_opt == 0)
                        <td><span class="label label-danger">card</span></td>
                    @elseif($requests->payment_opt == 1)
                        <td><span class="label label-primary">cash</span></td>
                    @elseif($requests->payment_opt == 2)
                        <td><span class="label label-warning">wallet</span></td>
                    @else
                        <td><span class="label label-info">cash_wallet</span></td>
                    @endif

                    <td>{{ $requests->vehicle_type_name }}</td>


                @php
                   $later = $requests->is_later;
                   $rental = $requests->is_rental;
                 @endphp
                 @if($later == 0)

                    @if(($later == 0) &&  ($rental == 0))
                    <td><span class="label label-success">regular_instant</span> </td>
                    @else(($later == 0) &&  ($rental == 1))
                    <td><span class="label label-success"> rental_instant</span> </td>
                    @endif

                @else($later == 1)

                    @if(($later == 1) &&  ($rental == 0))
                    <td><span class="label label-success">  regular_scheduled</span></td>
                    @else(($later == 1) &&  ($rental == 1 ))
                    <td><span class="label label-success"> rental_scheduled</span></td>
                    @endif

                @endif


                    <td>{{ $requests->total_time .' Mins' }}</td>
                    <td>{{ $requests->total_distance .'  '. $requests->request_unit}}</td>
                    <td>{{ $requests->requestBill ? $requests->currency .' '. number_format($requests->requestBill->driver_commision, 2) : '-' }}</td>
                    <td>{{ $requests->requestBill ? $requests->currency .' '. number_format($requests->requestBill->admin_commision_with_tax, 2) : '-' }}</td>
                    <td>{{ $requests->requestBill ? $requests->currency .' '. number_format($requests->requestBill->total_amount, 2) : '-' }}</td>                </tr>
                @empty
                <tr>
                    <td colspan="18">
                        <h4 class="text-center" style="color:#333;font-size:25px;">No Data Found</h4>
                    </td>
                </tr>
            @endforelse

            @if(isset($totals) && $results->count() > 0)
                <tr class="total-row">
                    <td colspan="15" class="total-cell">TOTAL:</td>
                    <td>{{ $results->first()->currency ?? '' }} {{ number_format($totals['driver_commission'], 2) }}</td>
                    <td>{{ $results->first()->currency ?? '' }} {{ number_format($totals['admin_commission'], 2) }}</td>
                    <td>{{ $results->first()->currency ?? '' }} {{ number_format($totals['total_amount'], 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
