// Основной JavaScript файл для модуля Shop
class ShopModule {
    constructor() {
        this.init();
    }

    init() {
        // Инициализация всех компонентов
        this.initImageCarousels();
        this.initProductFilters();
        this.initDurationSelectors();
        this.initServerSelectors();
        this.initPurchaseConfirmation();
        this.initExportCSV();
        this.initSortableTables();
        this.initProductForms();
    }

    // Карусель изображений товара
    initImageCarousels() {
        document.querySelectorAll('.image-carousel').forEach(carousel => {
            const track = carousel.querySelector('.carousel-track');
            const items = carousel.querySelectorAll('.carousel-item');
            const dots = carousel.querySelectorAll('.carousel-dot');
            const prevBtn = carousel.querySelector('.carousel-btn.prev');
            const nextBtn = carousel.querySelector('.carousel-btn.next');
            
            let currentIndex = 0;
            
            const updateCarousel = () => {
                track.style.transform = `translateX(-${currentIndex * 100}%)`;
                
                // Обновляем точки
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentIndex);
                });
                
                // Обновляем миниатюры
                const thumbnails = document.querySelectorAll('.thumbnail');
                thumbnails.forEach((thumb, index) => {
                    thumb.classList.toggle('active', index === currentIndex);
                });
            };
            
            // Следующее изображение
            const nextSlide = () => {
                currentIndex = (currentIndex + 1) % items.length;
                updateCarousel();
            };
            
            // Предыдущее изображение
            const prevSlide = () => {
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                updateCarousel();
            };
            
            // Автопрокрутка каждые 5 секунд
            let autoplay = setInterval(nextSlide, 5000);
            
            // Остановка автопрокрутки при наведении
            carousel.addEventListener('mouseenter', () => clearInterval(autoplay));
            carousel.addEventListener('mouseleave', () => {
                autoplay = setInterval(nextSlide, 5000);
            });
            
            // Кнопки управления
            if (prevBtn) prevBtn.addEventListener('click', prevSlide);
            if (nextBtn) nextBtn.addEventListener('click', nextSlide);
            
            // Клик по точкам
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    currentIndex = index;
                    updateCarousel();
                });
            });
            
            // Клик по миниатюрам
            document.querySelectorAll('.thumbnail').forEach((thumb, index) => {
                thumb.addEventListener('click', () => {
                    currentIndex = index;
                    updateCarousel();
                });
            });
        });
    }

    // Фильтрация товаров по категориям
    initProductFilters() {
        const categoryItems = document.querySelectorAll('.category-item');
        const productCards = document.querySelectorAll('.product-card');
        
        categoryItems.forEach(item => {
            item.addEventListener('click', () => {
                const categoryId = item.dataset.category;
                
                // Активный класс для категории
                categoryItems.forEach(cat => cat.classList.remove('active'));
                item.classList.add('active');
                
                // Показываем/скрываем товары
                productCards.forEach(card => {
                    if (categoryId === 'all' || card.dataset.category === categoryId) {
                        card.style.display = 'block';
                        setTimeout(() => card.style.opacity = '1', 10);
                    } else {
                        card.style.opacity = '0';
                        setTimeout(() => card.style.display = 'none', 300);
                    }
                });
                
                // Плавная прокрутка к категории
                if (categoryId !== 'all') {
                    const section = document.getElementById(`category-${categoryId}`);
                    if (section) {
                        section.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
    }

    // Выбор длительности товара
    initDurationSelectors() {
        document.querySelectorAll('.duration-option').forEach(option => {
            option.addEventListener('click', function() {
                // Убираем активный класс у всех
                document.querySelectorAll('.duration-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                
                // Добавляем активный класс текущему
                this.classList.add('active');
                
                // Обновляем цену в форме
                const price = this.dataset.price;
                const durationId = this.dataset.id;
                
                document.querySelector('input[name="duration_id"]').value = durationId;
                document.querySelector('.selected-price').textContent = price;
                
                // Обновляем кнопку покупки
                this.updateBuyButton(price);
            });
        });
    }

    // Выбор сервера
    initServerSelectors() {
        document.querySelectorAll('.server-option').forEach(option => {
            option.addEventListener('click', function() {
                // Убираем активный класс у всех
                document.querySelectorAll('.server-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                
                // Добавляем активный класс текущему
                this.classList.add('active');
                
                // Обновляем сервер в форме
                const serverId = this.dataset.id;
                document.querySelector('input[name="server_id"]').value = serverId;
            });
        });
    }

    // Обновление кнопки покупки
    updateBuyButton(price) {
        const buyButton = document.querySelector('.btn-buy');
        if (buyButton) {
            const currency = window.ShopConfig?.currency || 'FC';
            buyButton.textContent = `Купить за ${price} ${currency}`;
            buyButton.dataset.price = price;
        }
    }

    // Подтверждение покупки
    initPurchaseConfirmation() {
        const confirmButtons = document.querySelectorAll('.btn-buy');
        
        confirmButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const price = this.dataset.price;
                const serverId = document.querySelector('input[name="server_id"]')?.value;
                const durationId = document.querySelector('input[name="duration_id"]')?.value;
                
                if (!serverId || !durationId) {
                    e.preventDefault();
                    this.showNotification('Пожалуйста, выберите сервер и длительность', 'error');
                    return;
                }
                
                // Показать модальное окно подтверждения
                this.showConfirmModal(productName, price);
            });
        });
    }

    // Модальное окно подтверждения
    showConfirmModal(productName, price) {
        const modal = `
            <div class="confirm-modal">
                <div class="modal-content">
                    <h3>Подтверждение покупки</h3>
                    <p>Вы уверены, что хотите купить "${productName}" за ${price}?</p>
                    <div class="modal-actions">
                        <button class="btn-cancel">Отмена</button>
                        <button class="btn-confirm">Подтвердить</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modal);
        
        // Обработчики кнопок
        document.querySelector('.confirm-modal .btn-cancel').addEventListener('click', () => {
            document.querySelector('.confirm-modal').remove();
        });
        
        document.querySelector('.confirm-modal .btn-confirm').addEventListener('click', () => {
            // Отправляем форму покупки
            document.querySelector('#purchase-form').submit();
        });
    }

    // Экспорт в CSV
    initExportCSV() {
        const exportBtn = document.querySelector('.btn-export');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportToCSV();
            });
        }
    }

    // Функция экспорта в CSV
    exportToCSV() {
        const table = document.querySelector('.purchases-table');
        const rows = table.querySelectorAll('tr');
        
        let csv = [];
        
        rows.forEach(row => {
            const rowData = [];
            const cells = row.querySelectorAll('th, td');
            
            cells.forEach(cell => {
                let text = cell.textContent.trim();
                // Экранирование кавычек для CSV
                text = text.replace(/"/g, '""');
                rowData.push(`"${text}"`);
            });
            
            csv.push(rowData.join(','));
        });
        
        const csvString = csv.join('\n');
        const blob = new Blob([csvString], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        // Создание ссылки для скачивания
        const a = document.createElement('a');
        a.href = url;
        a.download = `purchases_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    // Сортировка таблиц
    initSortableTables() {
        document.querySelectorAll('.sortable-header').forEach(header => {
            header.addEventListener('click', () => {
                const table = header.closest('table');
                const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                const isAscending = header.classList.contains('asc');
                
                // Сбрасываем сортировку у всех заголовков
                table.querySelectorAll('.sortable-header').forEach(h => {
                    h.classList.remove('asc', 'desc');
                });
                
                // Сортируем строки
                rows.sort((a, b) => {
                    const aValue = a.children[columnIndex].textContent.trim();
                    const bValue = b.children[columnIndex].textContent.trim();
                    
                    // Пытаемся преобразовать в число
                    const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                    const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return isAscending ? bNum - aNum : aNum - bNum;
                    }
                    
                    // Сравниваем как строки
                    return isAscending 
                        ? bValue.localeCompare(aValue)
                        : aValue.localeCompare(bValue);
                });
                
                // Обновляем таблицу
                const tbody = table.querySelector('tbody');
                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));
                
                // Обновляем класс заголовка
                header.classList.toggle('asc', !isAscending);
                header.classList.toggle('desc', isAscending);
            });
        });
    }

    // Формы товаров с загрузкой изображений
    initProductForms() {
        // Drag & Drop для загрузки изображений
        const dropZone = document.querySelector('.drop-zone');
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
            
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                this.handleFileUpload(files);
            });
            
            // Клик по кнопке выбора файлов
            const fileInput = dropZone.querySelector('input[type="file"]');
            const browseBtn = dropZone.querySelector('.browse-btn');
            
            browseBtn.addEventListener('click', () => {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', (e) => {
                this.handleFileUpload(e.target.files);
            });
        }
        
        // Динамические поля для особенностей
        const addFeatureBtn = document.querySelector('.add-feature');
        if (addFeatureBtn) {
            addFeatureBtn.addEventListener('click', () => {
                const featuresContainer = document.querySelector('.features-container');
                const featureCount = featuresContainer.querySelectorAll('.feature-item').length;
                
                const featureHtml = `
                    <div class="feature-item">
                        <input type="text" name="features[${featureCount}][text]" 
                               placeholder="Текст особенности" class="feature-text">
                        <input type="checkbox" name="features[${featureCount}][checked]" 
                               class="feature-checkbox">
                        <button type="button" class="remove-feature">×</button>
                    </div>
                `;
                
                featuresContainer.insertAdjacentHTML('beforeend', featureHtml);
            });
            
            // Удаление особенности
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-feature')) {
                    e.target.closest('.feature-item').remove();
                }
            });
        }
        
        // Динамические поля для длительностей
        const addDurationBtn = document.querySelector('.add-duration');
        if (addDurationBtn) {
            addDurationBtn.addEventListener('click', () => {
                const durationsContainer = document.querySelector('.durations-container');
                const durationCount = durationsContainer.querySelectorAll('.duration-item').length;
                
                const durationHtml = `
                    <div class="duration-item">
                        <input type="text" name="durations[${durationCount}][name]" 
                               placeholder="Название (напр. 1 месяц)" required>
                        <input type="number" name="durations[${durationCount}][months]" 
                               placeholder="Месяцы" min="0">
                        <input type="number" name="durations[${durationCount}][days]" 
                               placeholder="Дни" min="0">
                        <input type="number" name="durations[${durationCount}][hours]" 
                               placeholder="Часы" min="0">
                        <input type="number" name="durations[${durationCount}][minutes]" 
                               placeholder="Минуты" min="0">
                        <input type="number" name="durations[${durationCount}][price]" 
                               placeholder="Цена" step="0.01" required>
                        <button type="button" class="remove-duration">×</button>
                    </div>
                `;
                
                durationsContainer.insertAdjacentHTML('beforeend', durationHtml);
            });
            
            // Удаление длительности
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-duration')) {
                    e.target.closest('.duration-item').remove();
                }
            });
        }
    }

    // Обработка загрузки файлов
    handleFileUpload(files) {
        const formData = new FormData();
        const previewContainer = document.querySelector('.upload-preview');
        
        Array.from(files).forEach((file, index) => {
            // Проверка типа файла
            if (!file.type.match('image.*')) {
                this.showNotification('Можно загружать только изображения', 'error');
                return;
            }
            
            // Проверка размера
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                this.showNotification('Файл слишком большой (макс. 2MB)', 'error');
                return;
            }
            
            formData.append('images[]', file);
            
            // Предпросмотр
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = document.createElement('div');
                preview.className = 'image-preview';
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <span class="preview-name">${file.name}</span>
                    <button type="button" class="remove-preview" data-index="${index}">×</button>
                `;
                previewContainer.appendChild(preview);
            };
            reader.readAsDataURL(file);
        });
        
        // Отправка на сервер
        this.uploadImages(formData);
    }

    // Загрузка изображений на сервер
    async uploadImages(formData) {
        try {
            const response = await fetch('/admin/shop/products/upload-images', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Изображения успешно загружены', 'success');
                
                // Обновляем скрытые поля с ID изображений
                result.images.forEach(image => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'image_ids[]';
                    input.value = image.id;
                    document.querySelector('#product-form').appendChild(input);
                });
            } else {
                this.showNotification(result.message || 'Ошибка загрузки', 'error');
            }
        } catch (error) {
            this.showNotification('Ошибка сети', 'error');
        }
    }

    // Показать уведомление
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-notification">×</button>
        `;
        
        document.body.appendChild(notification);
        
        // Автоматическое скрытие
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
        
        // Закрытие по клику
        notification.querySelector('.close-notification').addEventListener('click', () => {
            notification.remove();
        });
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    window.Shop = new ShopModule();
    
    // Глобальные конфигурации
    window.ShopConfig = {
        currency: 'FC',
        currencyPosition: 'after',
        decimalSeparator: '.',
        thousandSeparator: ' '
    };
});