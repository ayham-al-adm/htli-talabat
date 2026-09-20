<?php

namespace App\Helpers\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait MtnOtpHelper {
    /**
     * Send OTP via MTN Syria SMS service
     *
     * @param string $phoneNumber - The phone number to send OTP to
     * @param string $otp - The OTP code to send
     * @return bool - True if sent successfully, false otherwise
     */
    protected function sendMtnOtp($phoneNumber, $otp) {
        try {
            $url = 'https://services.mtnsyr.com:7443/general/MTNSERVICES/ConcatenatedSender.aspx';

            // Build the message
            $message = "Your verification code is: {$otp}";

            // Prepare query parameters
            $params = [
                'User' => config('services.mtn.user', env('MTN_SMS_USER')),
                'Pass' => config('services.mtn.pass', env('MTN_SMS_PASS')),
                'From' => config('services.mtn.from', env('MTN_SMS_FROM')),
                'Gsm' => '963' . $phoneNumber,
                'Msg' => $message,
                'Lang' => config('services.mtn.lang', env('MTN_SMS_LANG', '1')),
            ];

            // Send the HTTP request
            $response = Http::get($url, $params);

            // Log the response for debugging
            Log::info('MTN SMS Response', [
                'phone' => $phoneNumber,
                'otp' => $otp,
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            // Check if the request was successful
            return $response->successful();

        } catch (\Exception $e) {
            Log::error('MTN SMS Error', [
                'phone' => $phoneNumber,
                'otp' => $otp,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
