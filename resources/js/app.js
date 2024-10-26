import './bootstrap';


// Image uploader
const uploadImage = (event, imageId, previewID) => {
    var formData = new FormData();
    formData.append('image', event.target.files[0]);

    fetch('/api/upload-image', {
        method: 'POST', body: formData, headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById(imageId).value = data.reference_id;
            var output = document.getElementById(previewID);
            output.src = data.image_url;
            output.style.display = 'block';
        })
        .catch(error => console.error('Error:', error));
}
window.uploadImage = uploadImage;
