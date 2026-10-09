   <table>
        <thead>
            <tr>
                <th>{{ __('exports.no') }}</th>
                <th>{{ __('exports.id') }}</th>
                <th>{{ __('exports.name') }}</th>
                <th>{{ __('exports.email') }}</th>
                <th>{{ __('exports.mobile') }}</th>
                <th>{{ __('exports.status') }}</th>
                <th>{{ __('exports.signup_date') }}</th>

            </tr>
        </thead>
        <tbody>
            @php $i= 1; @endphp

            @forelse($results as $key => $user)
            @php
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
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $email }}</td>
                    <td>{{ $mobile }}</td>
                    @if ($user->active)
                        <td><span class="label label-success">{{ __('exports.active') }}</span></td>
                    @else
                        <td><span class="label label-danger">{{ __('exports.inactive') }}</span></td>
                    @endif
                    <td>{{ $user->getConvertedCreatedAtAttribute() }}</td>

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
