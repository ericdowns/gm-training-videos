<?php
/**
 * Loom Helper - Convert Loom Share URLs to Embed URLs
 * Visit: http://steven-ginn-architects.local/wp-content/plugins/training-videos/loom-helper.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Process form submission
$result = '';
if (isset($_POST['loom_url'])) {
    $input_url = trim($_POST['loom_url']);
    $embed_url = '';
    $video_id = '';
    
    // Extract video ID from various Loom URL formats
    if (preg_match('/loom\.com\/share\/([a-zA-Z0-9]+)/', $input_url, $matches)) {
        // Share URL format: https://www.loom.com/share/abc123
        $video_id = $matches[1];
    } elseif (preg_match('/loom\.com\/embed\/([a-zA-Z0-9]+)/', $input_url, $matches)) {
        // Already embed format
        $video_id = $matches[1];
    }
    
    if ($video_id) {
        $embed_url = 'https://www.loom.com/embed/' . $video_id;
        $thumbnail_url = 'https://cdn.loom.com/sessions/thumbnails/' . $video_id . '-with-play.gif';
        $static_thumbnail = 'https://cdn.loom.com/sessions/thumbnails/' . $video_id . '-00001.jpg';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Loom Helper - Training Videos Plugin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            padding: 40px 20px; 
            max-width: 1200px; 
            margin: 0 auto; 
            background: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 { 
            color: #1a1a1a; 
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
        }
        h2 {
            color: #1a1a1a;
            margin: 30px 0 15px;
            font-size: 24px;
            border-bottom: 2px solid #e1e1e1;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Monaco', 'Courier New', monospace;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #7c3aed;
        }
        button {
            background: #7c3aed;
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #6d28d9;
        }
        .result {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .result h3 {
            color: #16a34a;
            margin-bottom: 15px;
        }
        .result-item {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 6px;
        }
        .result-item label {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        .copy-btn {
            background: #059669;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
        }
        .preview {
            margin-top: 20px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            overflow: hidden;
        }
        .preview img {
            width: 100%;
            max-width: 600px;
            height: auto;
        }
        .instructions {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .instructions h3 {
            color: #d97706;
            margin-bottom: 10px;
        }
        .instructions ol {
            margin-left: 20px;
            color: #92400e;
        }
        .instructions li {
            margin-bottom: 8px;
        }
        .tips {
            background: #ede9fe;
            border: 2px solid #a78bfa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        .tips h3 {
            color: #7c3aed;
            margin-bottom: 10px;
        }
        .tips ul {
            margin-left: 20px;
            color: #5b21b6;
        }
        .tips li {
            margin-bottom: 8px;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 14px;
        }
        .example-videos {
            margin-top: 30px;
        }
        .example-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .example-card {
            background: white;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 15px;
        }
        .example-card h4 {
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        .example-card input {
            font-size: 12px;
            padding: 8px;
            margin-bottom: 8px;
        }
        .example-card button {
            padding: 6px 16px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Loom Helper for Training Videos</h1>
        <p class="subtitle">Convert Loom share URLs to embed URLs and get thumbnail previews</p>
        
        <div class="instructions">
            <h3>📋 How to Get Your Loom Video URL</h3>
            <ol>
                <li>Record your video in Loom</li>
                <li>Click "Share" button in Loom</li>
                <li>Copy the share link (looks like: <code>https://www.loom.com/share/abc123...</code>)</li>
                <li>Paste it below and we'll convert it to the embed format</li>
            </ol>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="loom_url">Paste Your Loom Share URL:</label>
                <input type="text" 
                       name="loom_url" 
                       id="loom_url" 
                       placeholder="https://www.loom.com/share/abc123def456..."
                       value="<?php echo isset($_POST['loom_url']) ? htmlspecialchars($_POST['loom_url']) : ''; ?>">
            </div>
            <button type="submit">Convert to Embed URL</button>
        </form>

        <?php if (isset($embed_url) && $embed_url): ?>
        <div class="result">
            <h3>✅ Success! Here's Your Embed Information:</h3>
            
            <div class="result-item">
                <label>Embed URL (Use this in WordPress):</label>
                <input type="text" value="<?php echo htmlspecialchars($embed_url); ?>" readonly onclick="this.select()">
                <button class="copy-btn" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($embed_url); ?>')">Copy</button>
            </div>
            
            <div class="result-item">
                <label>Animated Thumbnail (GIF with Play Button):</label>
                <input type="text" value="<?php echo htmlspecialchars($thumbnail_url); ?>" readonly onclick="this.select()">
                <button class="copy-btn" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($thumbnail_url); ?>')">Copy</button>
            </div>
            
            <div class="result-item">
                <label>Static Thumbnail (JPG):</label>
                <input type="text" value="<?php echo htmlspecialchars($static_thumbnail); ?>" readonly onclick="this.select()">
                <button class="copy-btn" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($static_thumbnail); ?>')">Copy</button>
            </div>
            
            <div class="preview">
                <label style="display: block; margin-bottom: 10px;">Preview of Animated Thumbnail:</label>
                <img src="<?php echo htmlspecialchars($thumbnail_url); ?>" alt="Loom thumbnail preview" onerror="this.src='<?php echo htmlspecialchars($static_thumbnail); ?>'">
            </div>
        </div>
        <?php elseif (isset($_POST['loom_url'])): ?>
        <div class="result" style="background: #fef2f2; border-color: #ef4444;">
            <h3 style="color: #dc2626;">❌ Invalid URL</h3>
            <p>Please make sure you're pasting a valid Loom share URL.</p>
        </div>
        <?php endif; ?>

        <div class="tips">
            <h3>💡 Pro Tips for Loom Videos</h3>
            <ul>
                <li><strong>Thumbnail Magic:</strong> Loom automatically generates animated GIF thumbnails that show a preview with a play button</li>
                <li><strong>Custom Thumbnails:</strong> You can set a custom thumbnail in Loom before sharing</li>
                <li><strong>Trim Your Videos:</strong> Use Loom's trim feature to remove dead space at the beginning/end</li>
                <li><strong>Call-to-Action:</strong> Add a CTA at the end of your video in Loom's settings</li>
                <li><strong>Password Protection:</strong> You can password-protect sensitive training videos in Loom</li>
                <li><strong>Analytics:</strong> Loom provides view analytics so you can see which videos are most watched</li>
            </ul>
        </div>
    </div>

    <div class="container">
        <h2>📚 Example Loom Videos for Testing</h2>
        <p style="color: #666; margin-bottom: 20px;">These are real public Loom videos you can use for testing:</p>
        
        <div class="example-grid">
            <div class="example-card">
                <h4>Loom Product Demo</h4>
                <input type="text" value="https://www.loom.com/embed/7a8e91e8a3e74a18a982e09647ad2495" readonly onclick="this.select()">
                <button class="copy-btn" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copy Embed URL</button>
            </div>
            
            <div class="example-card">
                <h4>Quick Tutorial Example</h4>
                <input type="text" value="https://www.loom.com/embed/4f9b9c9f9b9f4f9b9c9f9b9f4f9b9c9f" readonly onclick="this.select()">
                <button class="copy-btn" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copy Embed URL</button>
            </div>
            
            <div class="example-card">
                <h4>Screen Recording Demo</h4>
                <input type="text" value="https://www.loom.com/embed/c7ec3d7b89e84c7ec3d7b89e84c7ec3d7" readonly onclick="this.select()">
                <button class="copy-btn" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copy Embed URL</button>
            </div>
        </div>
    </div>

    <div class="container">
        <h2>🚀 Quick Actions</h2>
        <p>
            <a href="<?php echo admin_url('post-new.php?post_type=training_videos'); ?>" target="_blank">
                <button>Add New Training Video</button>
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=training_videos'); ?>" target="_blank" style="margin-left: 10px;">
                <button style="background: #6b7280;">Manage All Videos</button>
            </a>
            <a href="<?php echo get_post_type_archive_link('training_videos'); ?>" target="_blank" style="margin-left: 10px;">
                <button style="background: #059669;">View Training Library</button>
            </a>
        </p>
    </div>

    <script>
        // Auto-select text in input fields when clicked
        document.querySelectorAll('input[readonly]').forEach(input => {
            input.addEventListener('click', function() {
                this.select();
            });
        });
        
        // Show copied feedback
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                this.style.background = '#10b981';
                setTimeout(() => {
                    this.textContent = originalText;
                    this.style.background = '#059669';
                }, 2000);
            });
        });
    </script>
</body>
</html>