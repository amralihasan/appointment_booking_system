<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    /**
     * Send WhatsApp notification after appointment booking
     */
    public function sendBookingConfirmation(Appointment $appointment, ?string $whatsappNumber = null): bool
    {
        // Get WhatsApp number from appointment or contact
        $whatsappNumber = $whatsappNumber ?? $this->getWhatsAppNumber($appointment);
        
        // If no WhatsApp number, don't send
        if (empty($whatsappNumber)) {
            return false;
        }
        
        // Format the message
        $message = $this->formatBookingMessage($appointment);
        
        // Send the message
        return $this->sendMessage($whatsappNumber, $message);
    }
    
    /**
     * Get WhatsApp number from appointment or contact
     */
    protected function getWhatsAppNumber(Appointment $appointment): ?string
    {
        // Use client_phone as WhatsApp number (customer's phone number)
        if (!empty($appointment->client_phone)) {
            return $appointment->client_phone;
        }
        
        // Fallback to contact's mobile if available
        if ($appointment->contact && !empty($appointment->contact->mobile)) {
            return $appointment->contact->mobile;
        }
        
        return null;
    }
    
    /**
     * Format the booking confirmation message
     */
    protected function formatBookingMessage(Appointment $appointment): string
    {
        $service = $appointment->service;
        $tenant = $appointment->tenant;
        
        // Get timezone for display
        $coachTimezone = 'Africa/Cairo';
        if ($appointment->employee) {
            $coachTimezone = $appointment->employee->timezone ?? 'Africa/Cairo';
        } else {
            $coachTimezone = $appointment->user->timezone ?? 'Africa/Cairo';
        }
        
        // Format date/time in coach's timezone
        $dateTime = $appointment->date_time->setTimezone($coachTimezone);
        $formattedDate = $dateTime->format('Y-m-d');
        $formattedTime = $dateTime->format('H:i');
        
        $message = "✅ *Appointment Confirmed*\n\n";
        $message .= "Hello {$appointment->client_name},\n\n";
        $message .= "Your appointment has been confirmed:\n\n";
        $message .= "📅 *Date:* {$formattedDate}\n";
        $message .= "⏰ *Time:* {$formattedTime}\n";
        $message .= "🛎️ *Service:* {$service->name}\n";
        
        if ($appointment->employee) {
            $message .= "👤 *Employee:* {$appointment->employee->full_name}\n";
        }
        
        if ($service->price > 0) {
            $message .= "💰 *Price:* EGP " . number_format($service->price, 2) . "\n";
        }
        
        $message .= "⏱️ *Duration:* {$service->duration} minutes\n\n";
        
        if ($appointment->notes) {
            $message .= "📝 *Notes:* {$appointment->notes}\n\n";
        }
        
        $message .= "Thank you for your booking!";
        
        return $message;
    }
    
    /**
     * Send WhatsApp message using Twilio API
     */
    protected function sendMessage(string $whatsappNumber, string $message): bool
    {
        try {
            // Format phone number for Twilio (E.164 format: +[country code][number])
            $whatsappNumber = $this->formatPhoneNumberForTwilio($whatsappNumber);
            
            // Validate phone number
            if (empty($whatsappNumber)) {
                Log::warning('Invalid WhatsApp number format', [
                    'number' => $whatsappNumber,
                ]);
                return false;
            }
            
            // Get Twilio configuration from environment
            $accountSid = config('services.twilio.account_sid');
            $authToken = config('services.twilio.auth_token');
            $fromNumber = config('services.twilio.whatsapp_from');
            
            // If no Twilio configuration, log and return false
            if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
                Log::warning('Twilio WhatsApp API not configured. Message not sent.', [
                    'number' => $whatsappNumber,
                ]);
                return false;
            }
            
            // Format from number (should be in format: whatsapp:+1234567890)
            if (!str_starts_with($fromNumber, 'whatsapp:')) {
                $fromNumber = 'whatsapp:' . $fromNumber;
            }
            
            // Format to number (should be in format: whatsapp:+1234567890)
            if (!str_starts_with($whatsappNumber, 'whatsapp:')) {
                $whatsappNumber = 'whatsapp:' . $whatsappNumber;
            }
            
            // Twilio API endpoint
            $apiUrl = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
            
            // Prepare request payload for Twilio
            $payload = [
                'From' => $fromNumber,
                'To' => $whatsappNumber,
                'Body' => $message,
            ];
            
            // Send via HTTP with Basic Auth (Twilio uses Account SID and Auth Token)
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->timeout(30)
                ->post($apiUrl, $payload);
            
            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully via Twilio', [
                    'to' => $whatsappNumber,
                    'sid' => $response->json('sid'),
                ]);
                return true;
            } else {
                Log::error('Failed to send WhatsApp message via Twilio', [
                    'to' => $whatsappNumber,
                    'response' => $response->body(),
                    'status' => $response->status(),
                    'error' => $response->json('message'),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending WhatsApp message via Twilio', [
                'number' => $whatsappNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
    
    /**
     * Format phone number for Twilio (E.164 format)
     * Converts various formats to +[country code][number]
     */
    protected function formatPhoneNumberForTwilio(string $phoneNumber): string
    {
        // Remove all non-digit characters except +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // If number doesn't start with +, assume it's a local number
        // For Egypt, add +20 prefix if missing
        if (!str_starts_with($phoneNumber, '+')) {
            // If starts with 0, remove it and add country code
            if (str_starts_with($phoneNumber, '0')) {
                $phoneNumber = substr($phoneNumber, 1);
            }
            // Add Egypt country code (+20) if not already present
            if (!str_starts_with($phoneNumber, '20')) {
                $phoneNumber = '20' . $phoneNumber;
            }
            $phoneNumber = '+' . $phoneNumber;
        }
        
        // Validate E.164 format (should start with + and have 10-15 digits after)
        $digits = substr($phoneNumber, 1);
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            Log::warning('Phone number does not match E.164 format', [
                'number' => $phoneNumber,
            ]);
            return '';
        }
        
        return $phoneNumber;
    }
}

