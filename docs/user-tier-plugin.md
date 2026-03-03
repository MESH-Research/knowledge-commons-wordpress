# User Tier Plugin - Usage Instructions

## Overview

The User Tier plugin is a must-use plugin that allows administrators to assign membership tiers (Bronze, Silver, Gold, Platinum, Diamond) to users.

## Admin Usage

### Setting a User's Tier

1. Go to **Users > All Users** in WordPress admin
2. Click on a user's name to edit their profile
3. Scroll down to the **"User Tier"** section
4. Select a tier from the dropdown and save

### Bulk Actions

1. Go to **Users > All Users**
2. Select multiple users via checkboxes
3. Use the "Bulk actions" dropdown and choose a tier option
4. Click "Apply"

---

## Template Usage

Since this is a must-use plugin, it loads automatically—no import needed. The helper functions are globally available in any template file.

### Basic Functions

```php
// Get the tier slug (bronze, silver, gold, platinum, diamond)
$tier = get_user_tier( $user_id );

// Get the display label (Bronze, Silver, Gold, etc.)
$label = get_user_tier_label( $user_id );

// Get all tier data at once
$data = get_user_tier_data( $user_id );
// Returns: ['slug' => 'gold', 'label' => 'Gold', 'color' => '#ffd700']

// Display a styled badge (echoes HTML)
display_user_tier_badge( $user_id );

// Or get the badge HTML as a string
$badge = get_user_tier_badge_html( $user_id );
```

### Example in a Profile Template

```php
<?php
$user_id = bp_displayed_user_id(); // or get_current_user_id(), etc.
$tier = get_user_tier( $user_id );

if ( $tier ) : ?>
    <div class="user-membership">
        <span>Membership:</span>
        <?php display_user_tier_badge( $user_id ); ?>
    </div>
<?php endif; ?>
```

### Check Tier Level

```php
// Check if user has gold tier or higher (gold, platinum, diamond)
if ( user_has_tier( 'gold', $user_id ) ) {
    echo 'Premium member!';
}
```

All functions default to the current logged-in user if you omit `$user_id`.

---

## Available Tiers

| Tier     | Slug       | Color     |
|----------|------------|-----------|
| Bronze   | `bronze`   | `#cd7f32` |
| Silver   | `silver`   | `#c0c0c0` |
| Gold     | `gold`     | `#ffd700` |
| Platinum | `platinum` | `#e5e4e2` |
| Diamond  | `diamond`  | `#b9f2ff` |

---

## Security

Only users with the `edit_users` capability (Administrators) can view or modify tier settings. Regular users cannot change their own tier.