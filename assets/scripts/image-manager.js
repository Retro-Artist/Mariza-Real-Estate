/**
 * Property Image Manager
 * Handles multiple image selection, preview, management, and deletion of existing images
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const additionalImagesInput = document.getElementById('images');
    const additionalImagesPreview = document.getElementById('imagePreview');
    const existingImagesGallery = document.querySelector('.image-gallery');
    const deleteImagesInput = document.getElementById('delete_images') || 
                             (document.querySelector('form.property-form') && 
                              document.createElement('input'));
    
    // Store selected additional images
    let selectedAdditionalImages = [];
    // Store IDs of images to be deleted
    let imagesToDelete = [];
    
    // Create hidden input to store images to delete if it doesn't exist
    if (existingImagesGallery && !document.getElementById('delete_images')) {
        deleteImagesInput.type = 'hidden';
        deleteImagesInput.name = 'delete_images';
        deleteImagesInput.id = 'delete_images';
        existingImagesGallery.parentNode.appendChild(deleteImagesInput);
    }
    
    // Add delete buttons to existing images
    if (existingImagesGallery) {
        const existingImages = existingImagesGallery.querySelectorAll('.image-preview');
        
        existingImages.forEach(image => {
            // Extract image number from the image-number element
            const imageNumberElement = image.querySelector('.image-number');
            if (!imageNumberElement) return;
            
            const imageNumber = imageNumberElement.textContent.trim();
            
            // Skip the main image (number 1)
            if (imageNumber === '1') return;
            
            // Add delete button
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'image-preview__remove';
            deleteButton.title = 'Remover imagem';
            deleteButton.innerHTML = '<i class="fas fa-times"></i>';
            deleteButton.dataset.imageNumber = imageNumber;
            
            deleteButton.addEventListener('click', function() {
                const number = this.dataset.imageNumber;
                // Add image number to delete list
                imagesToDelete.push(number);
                
                // Update hidden input
                deleteImagesInput.value = imagesToDelete.join(',');
                
                // Apply visual indication this image will be deleted
                image.classList.add('image-preview--to-delete');
                
                // Replace delete button with undo button
                const undoButton = document.createElement('button');
                undoButton.type = 'button';
                undoButton.className = 'image-preview__undo';
                undoButton.title = 'Desfazer remoção';
                undoButton.innerHTML = '<i class="fas fa-undo"></i>';
                undoButton.dataset.imageNumber = number;
                
                undoButton.addEventListener('click', function() {
                    const numberToKeep = this.dataset.imageNumber;
                    // Remove from deletion list
                    imagesToDelete = imagesToDelete.filter(num => num !== numberToKeep);
                    
                    // Update hidden input
                    deleteImagesInput.value = imagesToDelete.join(',');
                    
                    // Remove visual indication
                    image.classList.remove('image-preview--to-delete');
                    
                    // Replace undo button with delete button again
                    image.removeChild(this);
                    image.appendChild(deleteButton);
                });
                
                image.removeChild(this);
                image.appendChild(undoButton);
            });
            
            image.appendChild(deleteButton);
        });
    }

    // Additional images preview and management
    if (additionalImagesInput) {
        additionalImagesInput.addEventListener('change', function() {
            // Convert FileList to Array and append to our selectedAdditionalImages array
            const newFiles = Array.from(this.files);
            
            // Process each new file
            newFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    // Create a unique ID for this file to identify it later
                    file.uniqueId = Date.now() + '-' + Math.random().toString(36).substring(2, 9);
                    selectedAdditionalImages.push(file);
                }
            });
            
            // Reset the file input to allow selecting the same file again
            this.value = '';
            
            // Update the preview
            updateAdditionalImagesPreview();
        });
        
        // Function to update the additional images preview
        function updateAdditionalImagesPreview() {
            additionalImagesPreview.innerHTML = '';
            
            if (selectedAdditionalImages.length > 0) {
                // Create header
                const header = document.createElement('div');
                header.className = 'image-preview__header';
                header.innerHTML = `
                    <h4>Novas Imagens Selecionadas (${Math.min(selectedAdditionalImages.length, 11)} de 11 máximo)</h4>
                    <p class="form-help">Clique no X vermelho para remover uma imagem da seleção</p>
                `;
                additionalImagesPreview.appendChild(header);
                
                // Create container for images
                const container = document.createElement('div');
                container.className = 'image-preview__grid';
                additionalImagesPreview.appendChild(container);
                
                // Show up to 11 images
                for (let i = 0; i < Math.min(selectedAdditionalImages.length, 11); i++) {
                    const file = selectedAdditionalImages[i];
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'image-preview__item';
                    imgContainer.dataset.id = file.uniqueId;
                    
                    // If this file already has a preview URL
                    if (file.previewUrl) {
                        imgContainer.innerHTML = `
                            <div class="image-preview__number">Nova</div>
                            <img src="${file.previewUrl}" alt="Preview" class="image-preview__img">
                            <div class="image-preview__filename">${file.name}</div>
                            <button type="button" class="image-preview__remove" title="Remover imagem" data-id="${file.uniqueId}">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        container.appendChild(imgContainer);
                    } else {
                        // Create a preview for this file
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            // Store the preview URL with the file for future reference
                            file.previewUrl = e.target.result;
                            
                            imgContainer.innerHTML = `
                                <div class="image-preview__number">Nova</div>
                                <img src="${e.target.result}" alt="Preview" class="image-preview__img">
                                <div class="image-preview__filename">${file.name}</div>
                                <button type="button" class="image-preview__remove" title="Remover imagem" data-id="${file.uniqueId}">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                        };
                        
                        reader.readAsDataURL(file);
                        container.appendChild(imgContainer);
                    }
                }
                
                // Add event listeners to remove buttons after all images are processed
                setTimeout(() => {
                    document.querySelectorAll('.image-preview__remove').forEach(button => {
                        if (button.hasAttribute('data-id')) {
                            button.addEventListener('click', function() {
                                const imageId = this.getAttribute('data-id');
                                
                                // Remove from array
                                selectedAdditionalImages = selectedAdditionalImages.filter(img => img.uniqueId !== imageId);
                                
                                // Update preview
                                updateAdditionalImagesPreview();
                            });
                        }
                    });
                }, 100);
            }
        }
        
        // Function to prepare selected images for upload before form submission
        function prepareImagesForUpload() {
            // Limit to 11 images
            const imagesToUpload = selectedAdditionalImages.slice(0, 11);
            
            // Create a new DataTransfer object to populate the file input
            const dataTransfer = new DataTransfer();
            
            // Add each file to the DataTransfer object
            imagesToUpload.forEach(file => {
                dataTransfer.items.add(file);
            });
            
            // Set the files property of the file input
            additionalImagesInput.files = dataTransfer.files;
        }
        
        // Handle form submission
        const form = document.querySelector('.property-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Prepare images before form submission
                prepareImagesForUpload();
                
                // Continue with form submission (validation will still occur elsewhere)
            });
        }
    }
    
    // Add custom styles
    const style = document.createElement('style');
    style.textContent = `
        .image-preview__item {
            position: relative;
        }
        .image-preview {
            position: relative;
        }
        .image-preview__remove, .image-preview__undo {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: background-color 0.2s;
            border: none;
        }
        .image-preview__remove {
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
        }
        .image-preview__remove:hover {
            background-color: rgba(255, 0, 0, 0.9);
        }
        .image-preview__undo {
            background-color: rgba(0, 123, 255, 0.7);
            color: white;
        }
        .image-preview__undo:hover {
            background-color: rgba(0, 123, 255, 0.9);
        }
        .image-preview--to-delete {
            opacity: 0.5;
            position: relative;
        }
        .image-preview--to-delete::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                rgba(0, 0, 0, 0.1),
                rgba(0, 0, 0, 0.1) 10px,
                rgba(0, 0, 0, 0.2) 10px,
                rgba(0, 0, 0, 0.2) 20px
            );
            z-index: 1;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
});