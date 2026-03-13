<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Upload Photo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 100%; height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0a0e17;
            color: #e5e7eb;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        .card {
            background: #0a0e17;
            width: 100%;
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            text-align: center;
        }
        @media (min-width: 600px) {
            body {
                align-items: center;
                justify-content: center;
                padding: 2rem;
                background: #f0f4f8;
            }
            .card {
                max-width: 500px;
                width: auto;
                border-radius: 1.5rem;
                box-shadow: 0 8px 40px rgba(0,0,0,0.12);
                flex: none;
                padding: 2rem 1.5rem;
            }
        }
        .icon { font-size: 4rem; margin-bottom: 1.25rem; }
        h1 { font-size: 1.6rem; margin-bottom: 0.75rem; }
        p { color: #4a5568; font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem; }
        .btn {
            display: inline-block;
            background: #06b6d4;
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            padding: 1.1rem 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            max-width: 400px;
            transition: background 0.2s;
        }
        .btn:hover { background: #0891b2; }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .spinner {
            display: none;
            margin: 1rem auto;
            width: 40px; height: 40px;
            border: 4px solid #e2e8f0;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .status { margin-top: 1rem; font-size: 0.9rem; }
        .status.success { color: #16a34a; }
        .status.error { color: #dc2626; }
        .expired { color: #dc2626; }
        .card-inner {
            width: 100%;
            max-width: 500px;
            padding: 0;
        }
        .file-input-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 1rem;
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            top: 0;
            left: 0;
        }
        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 3px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 2rem 1rem;
            background: #0a0e17;
            transition: border-color 0.2s, background 0.2s;
            cursor: pointer;
        }
        .file-input-label:hover,
        .file-input-label.dragover {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .file-input-label .file-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .file-input-label .file-text {
            font-size: 1.05rem;
            color: #4a5568;
            font-weight: 500;
        }
        .file-input-label .file-hint {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        .preview-container {
            display: none;
            margin: 1rem auto;
            max-width: 400px;
        }
        .preview-container img {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
        }
        .preview-info {
            font-size: 0.85rem;
            color: #4a5568;
            margin-top: 0.5rem;
        }
        .btn-change {
            display: inline-block;
            background: #e2e8f0;
            color: #e5e7eb;
            border: none;
            border-radius: 0.75rem;
            padding: 0.7rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 0.75rem;
        }
        .btn-change:hover { background: #cbd5e1; }
        .btn-upload {
            display: none;
            background: #06b6d4;
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            padding: 1.1rem 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            max-width: 400px;
            margin: 0.75rem auto 0;
            transition: background 0.2s;
        }
        .btn-upload:hover { background: #15803d; }
        .btn-upload:disabled { background: #94a3b8; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-inner">
        @if ($expired)
            <div class="icon">&#x23F0;</div>
            <h1>Link Expired</h1>
            <p>This photo upload link has expired. Please contact us if you still need to send a photo.</p>
        @else
            <div class="icon">&#x1F4F7;</div>
            <h1>Upload a Photo</h1>
            <p>
                Your {{ $companyName }} team has requested a photo.
                Take a picture or choose one from your gallery.
            </p>

            <div class="file-input-wrapper" id="dropZone">
                <label class="file-input-label" id="fileLabel">
                    <span class="file-icon">&#x1F4F1;</span>
                    <span class="file-text">Tap to take a photo or choose file</span>
                    <span class="file-hint">JPG, PNG, WEBP &mdash; up to 15 MB</span>
                </label>
                <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" capture="environment">
            </div>

            <div class="preview-container" id="previewContainer">
                <img id="previewImage" src="" alt="Preview">
                <p class="preview-info" id="previewInfo"></p>
                <button class="btn-change" onclick="changePhoto()">Choose a different photo</button>
            </div>

            <button class="btn-upload" id="uploadBtn" onclick="uploadPhoto()">Send Photo</button>
            <div id="spinner" class="spinner"></div>
            <div id="status" class="status"></div>

            <script>
                var selectedFile = null;

                var photoInput = document.getElementById('photoInput');
                var dropZone = document.getElementById('dropZone');
                var fileLabel = document.getElementById('fileLabel');
                var previewContainer = document.getElementById('previewContainer');
                var previewImage = document.getElementById('previewImage');
                var previewInfo = document.getElementById('previewInfo');
                var uploadBtn = document.getElementById('uploadBtn');

                photoInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        handleFile(this.files[0]);
                    }
                });

                // Drag-and-drop (mostly desktop, but nice to have)
                dropZone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    fileLabel.classList.add('dragover');
                });
                dropZone.addEventListener('dragleave', function () {
                    fileLabel.classList.remove('dragover');
                });
                dropZone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    fileLabel.classList.remove('dragover');
                    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                        handleFile(e.dataTransfer.files[0]);
                    }
                });

                function handleFile(file) {
                    var status = document.getElementById('status');
                    status.textContent = '';

                    // Validate type
                    var validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
                    if (validTypes.indexOf(file.type) === -1 && !file.name.match(/\.(heic|heif)$/i)) {
                        status.textContent = 'Please select a JPG, PNG, or WEBP image.';
                        status.className = 'status error';
                        return;
                    }

                    // Validate size (15 MB)
                    if (file.size > 15 * 1024 * 1024) {
                        status.textContent = 'File is too large. Maximum size is 15 MB.';
                        status.className = 'status error';
                        return;
                    }

                    selectedFile = file;

                    // Show preview
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        previewImage.src = e.target.result;
                        previewContainer.style.display = 'block';
                        dropZone.style.display = 'none';
                        uploadBtn.style.display = 'block';

                        var sizeMB = (file.size / (1024 * 1024)).toFixed(1);
                        previewInfo.textContent = file.name + ' (' + sizeMB + ' MB)';
                    };
                    reader.readAsDataURL(file);
                }

                function changePhoto() {
                    selectedFile = null;
                    previewContainer.style.display = 'none';
                    previewImage.src = '';
                    uploadBtn.style.display = 'none';
                    dropZone.style.display = 'block';
                    photoInput.value = '';
                    document.getElementById('status').textContent = '';
                }

                function uploadPhoto() {
                    if (!selectedFile) return;

                    var spinner = document.getElementById('spinner');
                    var status = document.getElementById('status');

                    uploadBtn.disabled = true;
                    uploadBtn.textContent = 'Uploading\u2026';
                    spinner.style.display = 'block';
                    status.textContent = '';

                    var formData = new FormData();
                    formData.append('photo', selectedFile);

                    fetch('/api/upload-photo/{{ $token }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                    .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data }; }); })
                    .then(function (result) {
                        spinner.style.display = 'none';

                        if (result.data.ok) {
                            status.innerHTML = '&#x2705; Photo sent successfully! Thank you.';
                            status.className = 'status success';
                            uploadBtn.style.display = 'none';
                            previewContainer.querySelector('.btn-change').style.display = 'none';
                        } else {
                            var errMsg = result.data.error || result.data.message || 'Something went wrong. Please try again.';
                            if (result.data.errors && result.data.errors.photo) {
                                errMsg = result.data.errors.photo[0];
                            }
                            status.textContent = errMsg;
                            status.className = 'status error';
                            uploadBtn.disabled = false;
                            uploadBtn.textContent = 'Send Photo';
                        }
                    })
                    .catch(function () {
                        spinner.style.display = 'none';
                        status.textContent = 'Network error. Please check your connection and try again.';
                        status.className = 'status error';
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = 'Send Photo';
                    });
                }
            </script>
        @endif
        </div>
    </div>
</body>
</html>
