# FC Advertisements

A simple plugin to create and manage advertisements for Fluent Community.

## Features

- **Ad Management**: Create, edit, delete, and toggle visibility of advertisements via a dedicated admin interface.
- **Space Targeting**: Target ads to specific Spaces or display them globally across all spaces.
- **Positioning**: Place ads in specific locations:
    - **Content**: Injected into the activity feed.
    - **Before Status Input**: Displayed right above the "What's on your mind?" input box.
- **User Tracking**: Tracks which user created the advertisement.
- **Click Tracking**: (Coming Soon)
- **Responsive Design**: Ads are styled to fit seamlessly into the Fluent Community design.

## Installation

1. Upload the `fc-advertisements` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

1. Navigate to **Advertisements** in the WordPress admin menu.
2. **Create New Advertisement**:
    - **Title**: Internal reference title for the ad.
    - **Space**: Select a specific space to show the ad only in that space, or select "All" for global visibility.
    - **Position**:
        - `Content`: Inserts the ad into the activity feed (every 2nd post).
        - `Before status creation field`: Displays the ad above the status creation box.
    - **URL**: The destination URL when the ad is clicked.
3. Click **Create Advertisement**.
4. **Manage Ads**:
    - Use the table at the bottom to view existing ads.
    - Use the **Enable/Disable** button to toggle ad visibility without deleting it.
    - **Delete** ads when they are no longer needed.

## Schema

The plugin creates a custom table `wp_fc_advertisements` with the following columns:
- `id`: Unique identifier.
- `title`: Ad title.
- `space`: Target space slug or 'all'.
- `position`: Placement identifier.
- `url`: Target URL.
- `user_id`: ID of the user who created the ad.
- `status`: 'enabled' or 'disabled' (Defaults to 'disabled').
- `created_at`: Creation timestamp.
