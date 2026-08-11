# Cache Clear Instructions

## Issue Fixed
The `PortfolioAssessment` class was being cached as a serialized object, which caused unserialization errors when the class definition wasn't loaded before `unserialize()` was called.

## Solution Applied
- Added `toArray()` and `fromArray()` methods to `PortfolioAssessment`
- Modified `DashboardController` and `AnalysisController` to cache array representation instead of objects
- This prevents PHP serialization issues with `readonly` properties

## Required Action: Clear Existing Cache

The old cached objects need to be cleared. Run ONE of these commands:

### Option 1: Clear All Cache (Recommended)
```bash
php artisan cache:clear
```

### Option 2: Clear Only Portfolio Caches
```bash
php artisan tinker
```

Then in tinker:
```php
// Get all user IDs
$userIds = \App\Models\User::pluck('id');

// Clear portfolio cache for each user
foreach ($userIds as $userId) {
    \Illuminate\Support\Facades\Cache::forget("portfolio_score_{$userId}");
    \Illuminate\Support\Facades\Cache::forget("portfolio_analysis_{$userId}");
}

exit
```

### Option 3: For Single User (Development)
```bash
php artisan tinker
```

Then:
```php
$userId = 1; // Your user ID
\Illuminate\Support\Facades\Cache::forget("portfolio_score_{$userId}");
exit
```

## Verification

After clearing cache:
1. Visit the dashboard at `/dashboard`
2. The portfolio score should load without errors
3. The cache will be regenerated using the new array-based format

## What Changed

### app/Support/PortfolioAssessment.php
- Added `toArray()` method to convert object to array
- Added `fromArray()` static method to reconstruct object from array

### app/Http/Controllers/DashboardController.php
- Changed `Cache::remember()` to cache `toArray()` result
- Reconstruct object using `PortfolioAssessment::fromArray()`

### app/Http/Controllers/AnalysisController.php
- Changed `Cache::remember()` to cache `toArray()` result in `index()` method
- Changed `Cache::put()` to cache `toArray()` result in `runPortfolioAnalysis()` method
- Reconstruct object using `PortfolioAssessment::fromArray()`

## Future Prevention

The array-based caching approach is now used, which:
- ✅ Avoids PHP serialization issues with `readonly` properties
- ✅ Works reliably across different PHP versions
- ✅ Doesn't require class definition to be loaded before unserialization
- ✅ Is more predictable and easier to debug
