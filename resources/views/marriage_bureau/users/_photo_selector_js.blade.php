<script>
    function initMbPhotoSelector() {
        var input = document.getElementById('mb-profile-photos');
        var previews = document.getElementById('mb-new-photo-previews');

        if (!input || !previews) {
            return;
        }

        input.addEventListener('change', function () {
            previews.innerHTML = '';
            var hasSelectedPhoto = !!document.querySelector('input[name="selected_photo"]:checked');

            Array.from(input.files).forEach(function (file, index) {
                var reader = new FileReader();

                reader.addEventListener('load', function (event) {
                    var label = document.createElement('label');
                    label.className = 'border rounded p-2 text-center mb-0';
                    label.style.width = '120px';
                    label.style.cursor = 'pointer';
                    label.innerHTML =
                        '<img src="' + event.target.result + '" class="rounded mb-2" style="width:96px;height:96px;object-fit:cover;" alt="New profile photo">' +
                        '<span class="d-block"><input type="radio" name="selected_photo" value="new:' + index + '"> Set as profile</span>' +
                        '<span class="badge badge-info mt-1">New Photo</span>';
                    previews.appendChild(label);

                    if (!hasSelectedPhoto && index === 0) {
                        label.querySelector('input[type="radio"]').checked = true;
                    }
                });

                reader.readAsDataURL(file);
            });
        });
    }
</script>
