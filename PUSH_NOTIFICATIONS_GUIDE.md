# Push Notifications Implementation Guide

## Overview
This system allows admins to send push notifications to drivers when shipments are assigned. Drivers receive notifications on their mobile app, which can launch the app and navigate to the new shipment.

---

## Architecture

### Backend Flow
```
Admin assigns shipment
    ↓
POST /api/admin/shipments/{id}/assign-driver
    ↓
ShipmentAssignmentController::assignDriver()
    ↓
ExpoNotificationService sends notification via Expo API
    ↓
Driver receives push notification on device
```

### Mobile Flow
```
App starts
    ↓
_layout.tsx calls initializeNotifications()
    ↓
Get Expo push token
    ↓
POST /api/driver/register-push-token (stores token in DB)
    ↓
setupNotificationListeners() watches for incoming notifications
    ↓
User taps notification → navigate to shipment detail
```

---

## Implementation Steps

### 1. **Install Dependencies**

#### Backend (Already Done)
- Created: `app/Services/Notifications/ExpoNotificationService.php`
- Created: `app/Http/Controllers/API/ShipmentAssignmentController.php`
- Created: Migration `database/migrations/2026_04_08_000001_add_push_notification_token.php`

#### Mobile (Already Done)
- Added: `expo-notifications` to `package.json`
- Created: `src/services/NotificationService.ts`
- Updated: `app/_layout.tsx` to initialize notifications

**Next step:** Run migration and npm install:
```bash
# Backend: Apply database changes
php artisan migrate

# Mobile: Install new dependency
npm install
```

---

### 2. **Mobile Setup (Expo Configuration)**

Your `app.json` needs this configuration for Expo notifications to work:

```json
{
  "expo": {
    "projectId": "your-project-id-here",
    "plugins": [
      [
        "expo-notifications",
        {
          "icon": "./assets/notification-icon.png",
          "color": "#FFD700"
        }
      ]
    ]
  }
}
```

**Get your project ID:**
1. Create account at https://expo.dev if you don't have one
2. Log in: `npx eas login`
3. Create project: `eas project:create`
4. Copy the project ID to `app.json` under `expo.projectId`

---

### 3. **Testing the System**

#### Mobile App Testing

**Step 1:** Start the mobile app
```bash
npm start
```

**Step 2:** Log in as a driver
- The app will automatically request notification permissions
- Once granted, it registers the Expo push token with the backend
- Check backend logs: `POST /api/driver/register-push-token`

**Step 3:** Verify token was saved
```bash
# In database
SELECT id, firstname, expo_push_token FROM drivers WHERE id = 1;
```

---

#### Backend API Testing

**Step 1:** Create/get a shipment
```bash
# Get a shipment ID from database or create one
SELECT id FROM shipments LIMIT 1;
```

**Step 2:** Assign the shipment to a driver (with curl)
```bash
curl -X POST \
  'http://localhost/truckDispatch/public/api/admin/shipments/1/assign-driver' \
  -H 'Authorization: Bearer YOUR_ADMIN_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"driver_id": 1}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Shipment assigned successfully",
  "notification_sent": true,
  "shipment": { ... }
}
```

**Step 3:** Check mobile app
- Watch for push notification on device
- If in foreground: alert appears
- If in background: system notification appears
- Tap notification: app should open/navigate

---

### 4. **Integration with Admin Dashboard**

To integrate with your admin panel, add this JavaScript to dispatch forms:

```javascript
// When form submits to assign driver:
async function assignShipment(shipmentId, driverId) {
  try {
    const response = await fetch(
      `/api/admin/shipments/${shipmentId}/assign-driver`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${adminToken}`,
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ driver_id: driverId })
      }
    );
    
    const data = await response.json();
    
    if (data.success) {
      alert(`Shipment assigned! Notification ${data.notification_sent ? 'sent' : 'queued'}`);
      // Refresh shipment list
    } else {
      alert(`Error: ${data.message}`);
    }
  } catch (error) {
    alert(`Failed to assign shipment: ${error.message}`);
  }
}
```

---

### 5. **Customizing Notifications**

Edit `app/Services/Notifications/ExpoNotificationService.php`:

```php
// Customize shipment assigned notification:
public static function notifyShipmentAssigned(Driver $driver, $shipment): bool
{
    return self::sendToDriver(
        $driver,
        '📦 New Shipment: ' . $shipment->pickup_address,  // Custom title
        "Deliver to: {$shipment->drop_address}",          // Custom body
        [
            'type' => 'shipment_assigned',
            'shipment_id' => $shipment->id,
            'weight' => $shipment->weight,
            'distance' => $shipment->distance_miles,
            'custom_field' => 'custom_value'  // Add any custom data
        ]
    );
}
```

On mobile, handle custom data in `NotificationService.ts`:

```typescript
if (notification.request.content.data.type === 'shipment_assigned') {
  const shipmentId = notification.request.content.data.shipment_id;
  const weight = notification.request.content.data.weight;
  // Use this data to navigate or trigger actions
}
```

---

### 6. **Debugging**

#### Backend Logs
```bash
# Watch real-time logs
tail -f storage/logs/laravel.log

# Look for these log entries:
# - "Driver push token registered"
# - "Push notification sent successfully"
# - "Failed to send push notification"
```

#### Mobile Logs
On Expo, run:
```bash
npx expo start --clear
```

Check console output for:
- `"Expo Push Token: ..."` — token registered
- `"Notification received (foreground)"` — app got notification
- `"Notification tapped"` — user interacted with notification

#### Expo Dashboard
1. Go to https://expo.dev/projects
2. Select your project
3. View notification logs and device status

---

### 7. **Common Issues**

**Issue:** "Project ID not configured"
- **Fix:** Add `projectId` to your `app.json`

**Issue:** "Cannot connect to server"
- **Fix:** Ensure `API_BASE` in mobile config points to correct backend URL

**Issue:** "Push token is null"
- **Fix:** 
  - Ensure notifications permission is granted
  - Try on physical device (simulator may have issues)
  - Check buildAsync: `eas build --platform ios --profile preview`

**Issue:** Driver doesn't receive notification
- **Fix:**
  - Verify `expo_push_token` is saved in database
  - Check backend logs for "Failed to send"
  - Ensure Expo API is reachable from your server
  - Verify driver token hasn't expired (register new token)

---

### 8. **Production Deployment**

1. **Build the app:**
   ```bash
   eas build --platform ios
   eas build --platform android
   ```

2. **Test with production build:**
   ```bash
   eas submit --platform ios   # Submit to Apple App Store
   eas submit --platform android  # Submit to Google Play
   ```

3. **Monitor notifications in Expo Dashboard**

4. **Set up error alerting** (optional):
   ```php
   // In ExpoNotificationService.php, add Sentry or similar:
   \Sentry\captureException($e);
   ```

---

## API Reference

### Register Push Token
```
POST /api/driver/register-push-token
Authorization: Bearer {driver_token}
Content-Type: application/json

{
  "expo_push_token": "ExponentPushToken[...]"
}

Response:
{
  "success": true,
  "message": "Push token registered successfully"
}
```

### Assign Shipment (Send Notification)
```
POST /api/admin/shipments/{id}/assign-driver
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "driver_id": 1
}

Response:
{
  "success": true,
  "message": "Shipment assigned successfully",
  "notification_sent": true,
  "shipment": { ... }
}
```

---

## File Structure

```
Backend:
  app/
    Services/Notifications/
      ExpoNotificationService.php       ← Send notifications
    Http/Controllers/API/
      ShipmentAssignmentController.php  ← Handle assignment endpoint
      DriverAuthController.php          ← registerPushToken() method
  database/migrations/
    2026_04_08_000001_add_push_notification_token.php

Mobile:
  src/
    services/
      NotificationService.ts            ← Initialize & listen
  app/
    _layout.tsx                         ← Setup on app start
```

---

## Next Steps

1. Run the migration: `php artisan migrate`
2. Install mobile dependency: `npm install`
3. Test with a physical device or simulator
4. Integrate with your admin dashboard UI
5. Deploy to production

Good luck! 🚀
