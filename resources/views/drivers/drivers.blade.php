   <table>
        <thead>
            <tr>
                <th>{{ __('exports.no') }}</th>
                <th>{{ __('exports.id') }}</th>
                <th>{{ __('exports.name') }}</th>
                <th>{{ __('exports.email') }}</th>
                <th>{{ __('exports.mobile') }}</th>
                <th>{{ __('exports.transport_type') }}</th>
                <th>{{ __('exports.vehicle_type') }}</th>
                <th>{{ __('exports.status') }}</th>
                <th>{{ __('exports.signup_date') }}</th>

            </tr>
        </thead>
        <tbody>
            @php $i= 1; @endphp

            @forelse($results as $key => $driver)
            @php
            $email = $driver->email;
            $dial = $driver->countryDetail ? $driver->countryDetail->dial_code : '0';
            $mobile = $dial. " ".$driver->mobile;
            if(env('APP_FOR') == 'demo'){
                $mobile = "**********";
                $email = "**********";
            }
            @endphp
                <tr>
                    <td>{{ $i++ }} </td>
                    <td>{{ $driver->id }}</td>
                    <td>{{ $driver->name }}</td>
                    <td>{{ $email }}</td>
                    <td>{{ $mobile }}</td>
                    <td>{{ trans()->has('exports.'.$driver->transport_type) ? __('exports.'.$driver->transport_type) : $driver->transport_type }}</td>
                    <td>
                        @foreach($driver->driverVehicleTypeDetail as $vehicleType)
                        {{ $vehicleType->vehicleType->name.',' }}
                        @endforeach
                    </td>
    
                    @if ($driver->approve)
                        <td><span class="label label-success">{{ __('exports.approved') }}</span></td>
                    @else
                        <td><span class="label label-danger">{{ __('exports.disapproved') }}</span></td>
                    @endif
                    <td>{{ $driver->getConvertedCreatedAtAttribute() }}</td>

                </tr>
                @empty
                <tr>
                    <td colspan="11">
                        <h4 class="text-center" style="color:#333;font-size:25px;">{{ __('exports.no_data_found') }}</h4>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
