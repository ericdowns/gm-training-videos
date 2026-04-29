# Training Videos Plugin

A WordPress plugin that creates a professional training video library portal using Loom videos. Built for Grain & Mortar client sites.

## Features

- **Custom Post Type** - Dedicated "Training Videos" section in WordPress admin
- **Loom Integration** - Automatic URL conversion and thumbnail generation
- **Documentation Resource** - Link to external docs (Google Doc, etc.) displayed prominently
- **Admin Bar Quick Access** - "Need Help?" dropdown for easy access to training materials
- **Self-Contained Templates** - Doesn't rely on theme templates
- **Login Required** - Videos only visible to logged-in users

## Installation

1. Copy the `gm-training-videos` folder to your site's `wp-content/plugins/` directory
2. Rename to `training-videos` (or keep as-is)
3. Activate the plugin in WordPress admin → Plugins
4. Go to **Training Videos → Settings** to configure the documentation resource

## Usage

### Adding Videos

1. Go to **Training Videos → Add New**
2. Enter the video title
3. Paste the Loom video URL (share or embed URL - plugin converts automatically)
4. Add a brief description (140 characters)
5. Set the menu order to control video sequence
6. Publish

### Configuring Documentation Resource

1. Go to **Training Videos → Settings**
2. Enter:
   - **Resource Title** - e.g., "Website Documentation"
   - **Resource URL** - Link to Google Doc or other documentation
   - **Description** - Brief description of what's in the doc
3. Save changes

The resource card will appear at the top of the video archive page.

### Accessing Videos

- **Frontend:** Visit `/training-videos/` on your site
- **Admin Bar:** Click "Need Help?" dropdown in the WordPress admin bar
- **Direct Link:** Each video has its own permalink

## Templates

The plugin includes self-contained templates that match California Forever's design:

- `templates/archive-training_videos.php` - Video grid with resource card
- `templates/single-training_videos.php` - Video player with sidebar navigation
- `templates/training-header.php` - Navy header
- `templates/training-footer.php` - Navy footer with credits

## Customization

### Colors

Templates use these color classes (defined in theme CSS):

| Color | Usage |
|-------|-------|
| Navy (#112D40) | Headers, primary backgrounds |
| Stone Blue (#3A5161) | Secondary text |
| Beige (#FDF9E3) | Light backgrounds |
| Linen (#EAE7D7) | Borders, card backgrounds |
| Orange (#FFBC21) | CTAs, active states |

### Thumbnail Generation

Thumbnails are automatically generated from Loom URLs:
```
https://cdn.loom.com/sessions/thumbnails/[VIDEO_ID]-with-play.gif
```

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Tailwind CSS (or theme with similar utility classes)
- Font Awesome (for icons)

## Version History

See [CHANGELOG.md](CHANGELOG.md) for the full history.

- **1.2.0** — Self-contained CSS, lazy-load Loom poster, mobile drawer, adaptive grid, deployment registry
- **1.1.1** — Loom thumbnail 403 fix for workspace-private videos
- **1.1.0** — Documentation resource, admin bar, 4-col grid (later count-adaptive)
- **1.0.0** — Initial release

## Author

Eric Downs - Technical Director at Grain & Mortar

## License

Proprietary - Grain & Mortar internal use only
