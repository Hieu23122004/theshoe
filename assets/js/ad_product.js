function editProduct(product) {
    // Scroll to top of the form for better user experience
    document.querySelector('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    document.getElementById('edit_id').value = product.product_id;
    document.getElementById('name').value = product.name;
    document.getElementById('description').value = product.description;
    document.getElementById('original_price').value = product.original_price;
    document.getElementById('discount_percent').value = product.discount_percent;
    calculatePrice();
    document.getElementById('stock_quantity').value = product.stock_quantity;
    document.getElementById('material').value = product.material;
    document.getElementById('image_url').value = product.image_url;
    document.getElementById('is_featured').checked = product.is_featured == 1;

    // Always set color options to default ["Black", "Brown"] - no modifications allowed
    document.getElementById('color_options').value = '["Black", "Brown"]';
    console.log('Set color_options to default: ["Black", "Brown"]');

    // Handle category selection - find parent and set child
    if (product.category_id) {
        // Find which parent category this child belongs to
        let parentId = null;
        for (const [pId, children] of Object.entries(childCategories)) {
            if (children.some(child => child.category_id == product.category_id)) {
                parentId = pId;
                break;
            }
        }
        
        if (parentId) {
            // Set parent category
            document.getElementById('parent_category').value = parentId;
            // Load child categories
            loadChildCategories();
            // Set child category
            setTimeout(() => {
                document.getElementById('category_id').value = product.category_id;
            }, 100);
        }
    }

    // Initialize color sections based on category
    initializeColorSections();
    
    // Always use default colors ["Black", "Brown"] - no custom colors allowed
    let colors = ['Black', 'Brown'];
    
    // Parse existing size stock data
    const sizeStock = product.size_stock ? JSON.parse(product.size_stock) : {};
    
    // For each color, set quantities
    colors.forEach(color => {
        const section = document.querySelector(`.color-section:has(input[value="${color}"])`);
        if (section) {
            // Set quantities for all sizes, defaulting to 0 if not present
            section.querySelectorAll('.size-input').forEach(input => {
                const size = input.dataset.size;
                input.value = sizeStock[color] && sizeStock[color][size] ? sizeStock[color][size] : 0;
            });
        }
    });
    
    // Update all calculations
    updateSizeStock();

    // Load additional images for editing
    if (product.image_urls) {
        try {
            const urls = JSON.parse(product.image_urls);
            if (Array.isArray(urls)) {
                additionalImages = [...urls]; // Copy the array
                updateAdditionalImagesPreview();
                updateHiddenImageUrls();
            }
        } catch (e) {
            console.error('Error parsing image_urls:', e);
            additionalImages = [];
        }
    } else {
        additionalImages = [];
        updateAdditionalImagesPreview();
        updateHiddenImageUrls();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Xử lý và validate URL ảnh chính
function validateAndPreviewMainImage(input) {
    const previewContainer = document.getElementById('mainImagePreview');
    previewContainer.innerHTML = '';

    if (input.value) {
        const img = new Image();
        img.onload = function () {
            previewContainer.innerHTML = `
                        <div class="url-preview">
                            <img src="${input.value}" alt="Main image preview">
                            <span class="remove-url" onclick="clearMainImage()">×</span>
                        </div>`;
        };
        img.onerror = function () {
            alert('Invalid image URL');
            input.value = '';
        };
        img.src = input.value;
    }
}

function clearMainImage() {
    document.getElementById('image_url').value = '';
    document.getElementById('mainImagePreview').innerHTML = '';
}

// Xử lý additional images
let additionalImages = [];

function handleAdditionalImage(input) {
    if (additionalImages.length >= 3) {
        alert('Chỉ được phép thêm tối đa 3 ảnh phụ');
        input.value = '';
        return;
    }

    if (input.value) {
        const img = new Image();
        img.onload = function () {
            const imageUrl = input.value;
            additionalImages.push(imageUrl);
            updateAdditionalImagesPreview();
            input.value = '';
            updateHiddenImageUrls();

            // Chỉ hiện thông báo khi thêm ảnh đầu tiên
            if (additionalImages.length === 1) {
                alert('Vui lòng thêm đủ 3 ảnh phụ');
            }
        };
        img.onerror = function () {
            alert('URL ảnh không hợp lệ');
            input.value = '';
        };
        img.src = input.value;
    }
}

function removeAdditionalImage(index) {
    additionalImages.splice(index, 1);
    updateAdditionalImagesPreview();
    updateHiddenImageUrls();
}

function updateAdditionalImagesPreview() {
    const container = document.getElementById('additionalImagesPreview');
    container.innerHTML = additionalImages.map((url, index) => `
                <div class="url-preview">
                    <img src="${url}" alt="URL${index + 1}">
                    <span class="remove-url" onclick="removeAdditionalImage(${index})">×</span>
                </div>
            `).join('');
}

function updateHiddenImageUrls() {
    const hiddenInput = document.getElementById('image_urls');
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(additionalImages || []);
        console.log('Updated image_urls hidden input:', hiddenInput.value);
    } else {
        console.error('Hidden input image_urls not found');
    }
}

function resetForm() {
    document.getElementById('mainImagePreview').innerHTML = '';
    document.getElementById('additionalImagesPreview').innerHTML = '';
    additionalImages = [];
    updateHiddenImageUrls();
    document.getElementById('productForm').reset();
    document.getElementById('edit_id').value = '';
    
    // Reset color options to default
    document.getElementById('color_options').value = '["Black", "Brown"]';
    
    // Reset category dropdowns
    document.getElementById('parent_category').value = '';
    document.getElementById('category_id').innerHTML = '<option value="">Select sub category</option>';
    document.getElementById('category_id').disabled = true;
    
    // Reset color sections
    initializeColorSections();
    
    // Scroll to top of form
    document.querySelector('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function calculatePrice() {
    const originalPrice = parseFloat(document.getElementById('original_price').value) || 0;
    const discountPercent = parseFloat(document.getElementById('discount_percent').value) || 0;
    const finalPrice = originalPrice * (1 - discountPercent / 100);
    document.getElementById('price').value = Math.round(finalPrice);
}

function getSizeTemplate() {
    const categoryId = document.getElementById('category_id').value;
    // Footwear categories (ID 1 and its children)
    if (categoryId === '1' || ['4', '5', '6', '7', '8'].includes(categoryId)) {
        return document.getElementById('shoeSizesTemplate').content.cloneNode(true);
    }
    // Handbag categories (ID 2 and its children)
    if (categoryId === '2' || ['9', '10', '11'].includes(categoryId)) {
        return document.getElementById('bagSizesTemplate').content.cloneNode(true);
    }
    // Belt categories (ID 3 and its children)
    if (categoryId === '3' || ['12', '13', '14'].includes(categoryId)) {
        return document.getElementById('beltSizesTemplate').content.cloneNode(true);
    }
    return null;
}

function initializeColorSections() {
    const colorSizeContainer = document.getElementById('colorSizeContainer');
    colorSizeContainer.innerHTML = ''; // Clear existing sections
    
    // Get colors from color_options input
    const colorOptionsInput = document.getElementById('color_options');
    let colors = ['Black', 'Brown']; // Default colors
    
    if (colorOptionsInput && colorOptionsInput.value) {
        try {
            const parsedColors = JSON.parse(colorOptionsInput.value);
            if (Array.isArray(parsedColors) && parsedColors.length > 0) {
                colors = parsedColors;
            }
        } catch (e) {
            console.log('Using default colors due to JSON parse error:', e);
        }
    }
    
    colors.forEach(color => {
        const sizeTemplate = getSizeTemplate();
        if (sizeTemplate) {
            const section = document.createElement('div');
            section.className = 'color-section mb-3';
            section.innerHTML = `
                <div class="row align-items-end mb-2">
                    <div class="col-md-3">
                        <label>${color}</label>
                        <input type="hidden" class="color-select" value="${color}">
                    </div>
                    <div class="col-md-9 sizes-container">
                    </div>
                </div>
            `;
            section.querySelector('.sizes-container').appendChild(sizeTemplate);
            colorSizeContainer.appendChild(section);
        }
    });
    updateSizeStock();
}

function calculateTotalStock() {
    const colorSections = document.querySelectorAll('.color-section');
    let totalQuantity = 0;

    colorSections.forEach(section => {
        section.querySelectorAll('.size-input').forEach(input => {
            const quantity = parseInt(input.value) || 0;
            totalQuantity += quantity;
        });
    });

    return totalQuantity;
}

function updateSizeStock(input) {
    const colorSections = document.querySelectorAll('.color-section');
    const sizeStock = {};

    // Nếu có input được truyền vào (từ sự kiện input), kiểm tra giới hạn
    if (input) {
        const newTotal = calculateTotalStock();
        if (newTotal > 1000) {
            const currentValue = parseInt(input.value) || 0;
            const excess = newTotal - 1000;
            input.value = Math.max(0, currentValue - excess);
            alert('Tổng số lượng không được vượt quá 1000. Đã tự động điều chỉnh.');
        }
    }

    // Cập nhật lại tổng số lượng
    document.getElementById('stock_quantity').value = calculateTotalStock();

    // Cập nhật size_stock object - dynamic colors
    colorSections.forEach(section => {
        const colorSelect = section.querySelector('.color-select');
        const color = colorSelect.value;

        if (color) {
            sizeStock[color] = {};

            section.querySelectorAll('.size-input').forEach(input => {
                const size = input.dataset.size;
                const quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    sizeStock[color][size] = quantity;
                }
            });
        }
    });

    // Get current color options from form
    const colorOptionsInput = document.getElementById('color_options');
    let colorOptions = [];
    
    if (colorOptionsInput && colorOptionsInput.value) {
        try {
            colorOptions = JSON.parse(colorOptionsInput.value);
        } catch (e) {
            console.log('Error parsing color options:', e);
            colorOptions = [];
        }
    }

    // Chỉ lưu những color có size và quantity
    const validColorOptions = [];
    const validSizeStock = {};

    colorOptions.forEach(color => {
        if (color && typeof color === 'string' && color.trim() !== '' &&
            sizeStock[color] && Object.keys(sizeStock[color]).length > 0) {
            // Ensure color is trimmed and valid
            const trimmedColor = color.trim();
            validColorOptions.push(trimmedColor);
            validSizeStock[trimmedColor] = sizeStock[color];
        }
    });

    // Update hidden fields for form submission
    const form = document.getElementById('productForm');

    // Get color options from the input field
    let colorOptionsFromInput = ['Black', 'Brown']; // default
    try {
        const colorOptionsInput = document.getElementById('color_options');
        if (colorOptionsInput && colorOptionsInput.value.trim()) {
            const parsed = JSON.parse(colorOptionsInput.value);
            if (Array.isArray(parsed) && parsed.length > 0) {
                colorOptionsFromInput = parsed;
            }
        }
    } catch (e) {
        console.warn('Invalid color_options format, using default');
    }

    // Update size_stock
    let sizeStockInput = document.getElementById('size_stock_data');
    if (!sizeStockInput) {
        sizeStockInput = document.createElement('input');
        sizeStockInput.type = 'hidden';
        sizeStockInput.name = 'size_stock';
        sizeStockInput.id = 'size_stock_data';
        form.appendChild(sizeStockInput);
    }
    try {
        const validSizeStockJson = JSON.stringify(validSizeStock) || '{}';
        sizeStockInput.value = validSizeStockJson;
        console.log('Size stock:', validSizeStockJson);
    } catch (e) {
        sizeStockInput.value = '{}';
        console.error('Error processing size stock:', e);
    }

    // Update total stock quantity
    let totalStock = 0;
    Object.values(sizeStock).forEach(colorSizes => {
        Object.values(colorSizes).forEach(quantity => {
            totalStock += quantity;
        });
    });
    document.getElementById('stock_quantity').value = totalStock;
}

// Add event listeners
document.getElementById('original_price').addEventListener('input', calculatePrice);
document.getElementById('discount_percent').addEventListener('input', calculatePrice);
document.getElementById('category_id').addEventListener('change', function () {
    // Clear existing color sections when category changes
    document.getElementById('colorSizeContainer').innerHTML = '';
});

document.addEventListener('DOMContentLoaded', function () {
    const searchParams = new URLSearchParams(window.location.search);
    const msg = searchParams.get('msg');

    if (msg) {
        setTimeout(() => {
            const alertEl = document.querySelector('.alert');
            if (alertEl) {
                const alert = new bootstrap.Alert(alertEl);
                alert.close();
            }
        }, 2000);

        setTimeout(() => {
            window.location.href = window.location.pathname;
        }, 2500);
    }

    // Add event listener for color_options input to update color sections dynamically
    const colorOptionsInput = document.getElementById('color_options');
    if (colorOptionsInput) {
        colorOptionsInput.addEventListener('input', function() {
            // Debounce to avoid too many updates
            clearTimeout(this.updateTimer);
            this.updateTimer = setTimeout(function() {
                initializeColorSections();
            }, 500);
        });
        
        colorOptionsInput.addEventListener('blur', function() {
            // Update immediately when user leaves the field
            initializeColorSections();
        });
    }
});

// Validate form before submission
document.getElementById('productForm').onsubmit = function (e) {
    // First validate color options
    const colorOptionsField = document.getElementById('color_options');
    const colorOptionsValue = colorOptionsField.value;
    
    console.log('DEBUG: Submitting with color_options:', colorOptionsValue);
    
    try {
        const colors = JSON.parse(colorOptionsValue);
        if (!Array.isArray(colors) || 
            colors.length !== 2 || 
            !colors.includes('Black') || 
            !colors.includes('Brown')) {
            e.preventDefault();
            alert('Color Options must be exactly ["Black", "Brown"]. Please do not modify this field.');
            // Reset to default
            colorOptionsField.value = '["Black", "Brown"]';
            return false;
        }
    } catch (error) {
        e.preventDefault();
        alert('Color Options must be valid JSON format: ["Black", "Brown"]');
        // Reset to default
        colorOptionsField.value = '["Black", "Brown"]';
        return false;
    }

    // Validate additional images
    const additionalImagesCount = additionalImages.length;
    if (additionalImagesCount !== 3) {
        const remainingImages = 3 - additionalImagesCount;
        alert(`Vui lòng thêm đủ 3 ảnh phụ. Còn thiếu ${remainingImages} ảnh.`);
        document.getElementById('additional_url').focus();
        e.preventDefault();
        return false;
    }

    // Create hidden inputs for color_options and size_stock
    const colorSections = document.querySelectorAll('.color-section');
    const sizeStock = {};

    // Always use Black and Brown colors
    let colorOptions = ['Black', 'Brown'];

    // Initialize sizeStock with default colors
    colorOptions.forEach(color => {
        sizeStock[color] = {};
    });

    // Collect all size data, including zeros
    colorSections.forEach(section => {
        const color = section.querySelector('.color-select').value;
        if (color) {
            section.querySelectorAll('.size-input').forEach(input => {
                const size = input.dataset.size;
                const quantity = parseInt(input.value) || 0;
                sizeStock[color][size] = quantity;
            });
        }
    });

    // Check if at least one size has a quantity
    let hasQuantity = false;
    Object.values(sizeStock).forEach(sizes => {
        Object.values(sizes).forEach(quantity => {
            if (quantity > 0) hasQuantity = true;
        });
    });

    if (!hasQuantity) {
        alert('Vui lòng nhập ít nhất một kích thước với số lượng lớn hơn 0.');
        e.preventDefault();
        return false;
    }

    // Create and append hidden inputs
    const form = document.getElementById('productForm');

    // Update or create color_options input - always ensure it's exactly ["Black", "Brown"]
    let colorOptionsInput = form.querySelector('input[name="color_options"]');
    if (!colorOptionsInput) {
        colorOptionsInput = document.createElement('input');
        colorOptionsInput.type = 'hidden';
        colorOptionsInput.name = 'color_options';
        form.appendChild(colorOptionsInput);
    }
    colorOptionsInput.value = '["Black", "Brown"]'; // Force to exact value
    console.log('DEBUG: Setting hidden color_options to:', colorOptionsInput.value);

    // Update or create size_stock input
    let sizeStockInput = form.querySelector('input[name="size_stock"]');
    if (!sizeStockInput) {
        sizeStockInput = document.createElement('input');
        sizeStockInput.type = 'hidden';
        sizeStockInput.name = 'size_stock';
        form.appendChild(sizeStockInput);
    }
    sizeStockInput.value = JSON.stringify(sizeStock);
    console.log('DEBUG: Setting size_stock to:', sizeStockInput.value);

    return true;
}

// Form validation is handled in the onsubmit event above