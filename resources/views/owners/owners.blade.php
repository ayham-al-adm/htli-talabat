   <table>
        <thead>
            <tr>
                <th>{{ __('exports.no') }}</th>
                <th>{{ __('exports.service_location') }}</th>
                <th>{{ __('exports.name') }}</th>
                <th>{{ __('exports.email') }}</th>
                <th>{{ __('exports.mobile') }}</th>
                <th>{{ __('exports.transport_type') }}</th>
                <th>{{ __('exports.status') }}</th>
                {{-- <th>{{ __('exports.signup_date') }}</th> --}}

            </tr>
        </thead>
        <tbody>
            @php $i= 1; @endphp

            @forelse($results as $key => $owner)
            @php
            $user = $owner->user;
            $email = $user->email;
            $dial = $user->countryDetail ? $user->countryDetail->dial_code : '0';
            $mobile = $dial. " ".$user->mobile;
            if(env('APP_FOR') == 'demo'){
                $mobile = "**********";
                $email = "**********";
            }
            @endphp
                <tr>
                    <td>{{ $i++ }} </td>
                    <td>{{ $owner->area->name }}</td>
                    <td>{{ $owner->name }}</td>
                    <td>{{ $email }}</td>
                    <td>{{ $mobile }}</td>
                    <td>{{ trans()->has('exports.'.$owner->transport_type) ? __('exports.'.$owner->transport_type) : $owner->transport_type }}</td>
    
                    @if ($owner->approve)
                        <td><span class="label label-success">{{ __('exports.approved') }}</span></td>
                    @else
                        <td><span class="label label-danger">{{ __('exports.disapproved') }}</span></td>
                    @endif
                    {{-- <td>{{ $owner->getConvertedCreatedAtAttribute() }}</td> --}}

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
