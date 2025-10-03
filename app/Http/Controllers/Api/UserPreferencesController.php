<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * User Preferences Controller
 * 
 * Handles user preference management including SMS notification settings
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    API
 * @author      [Your Name]
 * @since       1.0.0
 */
class UserPreferencesController extends Controller
{
    /**
     * Get user's SMS notification preferences
     * 
     * @return JsonResponse
     */
    public function getSmsPreferences(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $preferences = [
                'sms_notifications_enabled' => $user->sms_notifications_enabled ?? true,
                'sms_marketing_enabled' => $user->sms_marketing_enabled ?? false,
                'sms_quiet_start' => $user->sms_quiet_start,
                'sms_quiet_end' => $user->sms_quiet_end,
                'sms_notification_types' => $user->sms_notification_types ?? [],
                'timezone' => $user->timezone ?? 'UTC',
                'available_notification_types' => User::getDefaultSmsNotificationTypes()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $preferences
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve SMS preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update user's SMS notification preferences
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSmsPreferences(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sms_notifications_enabled' => 'sometimes|boolean',
                'sms_marketing_enabled' => 'sometimes|boolean',
                'sms_quiet_start' => 'sometimes|nullable|date_format:H:i:s',
                'sms_quiet_end' => 'sometimes|nullable|date_format:H:i:s',
                'sms_notification_types' => 'sometimes|array',
                'sms_notification_types.*' => 'string|in:conversation,workout,appointment,progress,general',
                'timezone' => 'sometimes|string|timezone'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = Auth::user();
            $updateData = [];
            
            // Only update fields that are provided in the request
            if ($request->has('sms_notifications_enabled')) {
                $updateData['sms_notifications_enabled'] = $request->sms_notifications_enabled;
            }
            
            if ($request->has('sms_marketing_enabled')) {
                $updateData['sms_marketing_enabled'] = $request->sms_marketing_enabled;
            }
            
            if ($request->has('sms_quiet_start')) {
                $updateData['sms_quiet_start'] = $request->sms_quiet_start;
            }
            
            if ($request->has('sms_quiet_end')) {
                $updateData['sms_quiet_end'] = $request->sms_quiet_end;
            }
            
            if ($request->has('sms_notification_types')) {
                $updateData['sms_notification_types'] = $request->sms_notification_types;
            }
            
            if ($request->has('timezone')) {
                $updateData['timezone'] = $request->timezone;
            }
            
            // Validate quiet hours logic
            if (isset($updateData['sms_quiet_start']) && isset($updateData['sms_quiet_end'])) {
                if ($updateData['sms_quiet_start'] === $updateData['sms_quiet_end']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quiet start and end times cannot be the same'
                    ], 422);
                }
            }
            
            $user->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'SMS preferences updated successfully',
                'data' => [
                    'sms_notifications_enabled' => $user->sms_notifications_enabled,
                    'sms_marketing_enabled' => $user->sms_marketing_enabled,
                    'sms_quiet_start' => $user->sms_quiet_start,
                    'sms_quiet_end' => $user->sms_quiet_end,
                    'sms_notification_types' => $user->sms_notification_types,
                    'timezone' => $user->timezone
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SMS preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reset SMS preferences to default values
     * 
     * @return JsonResponse
     */
    public function resetSmsPreferences(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $defaultPreferences = [
                'sms_notifications_enabled' => true,
                'sms_marketing_enabled' => false,
                'sms_quiet_start' => null,
                'sms_quiet_end' => null,
                'sms_notification_types' => array_keys(User::getDefaultSmsNotificationTypes()),
                'timezone' => 'UTC'
            ];
            
            $user->update($defaultPreferences);
            
            return response()->json([
                'success' => true,
                'message' => 'SMS preferences reset to default values',
                'data' => $defaultPreferences
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset SMS preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available SMS notification types
     * 
     * @return JsonResponse
     */
    public function getSmsNotificationTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => User::getDefaultSmsNotificationTypes()
        ]);
    }
}