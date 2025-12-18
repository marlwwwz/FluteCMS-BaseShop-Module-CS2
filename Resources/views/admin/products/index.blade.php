@extends('flute::layouts.admin')

@section('title', 'Товары - Магазин')

@section('content')
<div class="admin-header">
    <div class="admin-header-content">
        <h1 class="admin-title">
            <i class="ph-package"></i>
            Товары магазина
        </h1>
        <div class="admin-actions">
            <a href="{{ route('shop.admin.products.create') }}" class="btn btn-primary">
                <i class="ph-plus"></i>
                Добавить товар
            </a>
            <a href="{{ route('shop.admin.categories.index') }}" class="btn btn-secondary">
                <i class="ph-list"></i>
                Категории
            </a>
        </div>
    </div>
</div>

<div class="admin-content">
    <!-- Фильтры и поиск -->
    <div class="admin-filters">
        <div class="search-box">
            <i class="ph-magnifying-glass"></i>
            <input type="text" 
                   placeholder="Поиск товаров..." 
                   class="search-input"
                   value="{{ request('search') }}">
        </div>
        <div class="filter-options">
            <select class="filter-select" id="category-filter">
                <option value="">Все категории</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}" 
                        {{ request('category_id') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
                @endforeach
            </select>
            <select class="filter-select" id="status-filter">
                <option value="">Все статусы</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                    Активные
                </option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                    Неактивные
                </option>
            </select>
            <button class="btn btn-secondary" id="apply-filters">
                <i class="ph-funnel"></i>
                Применить
            </button>
            <button class="btn btn-outline" id="reset-filters">
                <i class="ph-arrow-counter-clockwise"></i>
                Сбросить
            </button>
        </div>
    </div>

    <!-- Таблица товаров -->
    <div class="admin-table">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Тип</th>
                        <th>Цена</th>
                        <th>Статус</th>
                        <th>Приоритет</th>
                        <th>Дата создания</th>
                        <th width="150">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>
                            <div class="product-info">
                                @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" 
                                     alt="{{ $product->name }}"
                                     class="product-thumb">
                                @endif
                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    @if($product->is_on_discount)
                                    <span class="badge badge-discount">
                                        <i class="ph-tag"></i>
                                        -{{ $product->discount_percentage }}%
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-category">
                                {{ $product->category->name }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $product->driver_type }}">
                                {{ $product->driver_type }}
                            </span>
                            @if($product->vip_group)
                            <small class="text-muted">({{ $product->vip_group }})</small>
                            @endif
                        </td>
                        <td>
                            <div class="price-display">
                                <span class="current-price">{{ $product->current_price }}</span>
                                @if($product->original_price && $product->original_price > $product->current_price)
                                <span class="original-price">{{ $product->original_price }}</span>
                                @endif
                                <span class="currency">{{ config('shop.currency', 'FC') }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $product->active ? 'active' : 'inactive' }}">
                                {{ $product->active ? 'Активен' : 'Неактивен' }}
                            </span>
                        </td>
                        <td>
                            <input type="number" 
                                   value="{{ $product->priority }}"
                                   class="priority-input"
                                   data-id="{{ $product->id }}"
                                   min="0">
                        </td>
                        <td>{{ $product->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <div class="action-buttons">
                                <a href="{{ route('shop.product', $product->id) }}" 
                                   class="btn-icon btn-view" 
                                   target="_blank"
                                   title="Просмотр">
                                    <i class="ph-eye"></i>
                                </a>
                                <a href="{{ route('shop.admin.products.edit', $product->id) }}" 
                                   class="btn-icon btn-edit"
                                   title="Редактировать">
                                    <i class="ph-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn-icon btn-delete" 
                                        data-id="{{ $product->id }}"
                                        data-name="{{ $product->name }}"
                                        title="Удалить">
                                    <i class="ph-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="ph-package ph-2x text-muted"></i>
                            <p class="mt-2">Товары не найдены</p>
                            <a href="{{ route('shop.admin.products.create') }}" class="btn btn-primary mt-3">
                                Добавить первый товар
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Пагинация -->
        @if($products->hasPages())
        <div class="admin-pagination">
            {{ $products->appends(request()->query())->links('flute::components.pagination') }}
        </div>
        @endif
    </div>
</div>

<!-- Модальное окно удаления -->
<div class="modal" id="delete-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Удаление товара</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Вы уверены, что хотите удалить товар "<strong id="delete-product-name"></strong>"?</p>
            <p class="text-danger">Это действие нельзя отменить!</p>
        </div>
        <div class="modal-footer">
            <form id="delete-form" method="POST">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-secondary modal-cancel">Отмена</button>
                <button type="submit" class="btn btn-danger">Удалить</button>
            </form>
        </div>
    </div>
</div>

<style>
.admin-header {
    background: linear-gradient(135deg, #2d3748, #1a202c);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    border: 1px solid rgba(255,255,255,0.1);
}

.admin-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.admin-title {
    font-size: 1.8rem;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-title i {
    color: #667eea;
}

.admin-actions {
    display: flex;
    gap: 10px;
}

.admin-filters {
    background: #1a1a2e;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
}

.filter-options {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-select {
    padding: 12px 20px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: white;
    font-size: 1rem;
    min-width: 180px;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
}

.btn {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #764ba2, #667eea);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: rgba(255,255,255,0.1);
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: #a0aec0;
    border: 1px solid rgba(255,255,255,0.2);
}

.btn-outline:hover {
    color: white;
    border-color: rgba(255,255,255,0.4);
}

.admin-table {
    background: #1a1a2e;
    border-radius: 15px;
    overflow: hidden;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: rgba(0,0,0,0.2);
    padding: 15px 20px;
    text-align: left;
    color: white;
    font-weight: 600;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.table td {
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    color: #a0aec0;
}

.table tbody tr:hover {
    background: rgba(255,255,255,0.05);
}

.product-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.product-thumb {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-discount {
    background: linear-gradient(45deg, #f56565, #ed8936);
    color: white;
    margin-left: 10px;
}

.badge-category {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
    border: 1px solid rgba(102, 126, 234, 0.3);
}

.badge-vip { background: #fbbf24; color: #000; }
.badge-rcon { background: #10b981; color: white; }
.badge-sourcebans { background: #8b5cf6; color: white; }
.badge-adminsystem { background: #3b82f6; color: white; }
.badge-other { background: #6b7280; color: white; }

.price-display {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.current-price {
    color: #68d391;
    font-weight: 700;
    font-size: 1.1rem;
}

.original-price {
    color: #a0aec0;
    text-decoration: line-through;
    font-size: 0.9rem;
}

.currency {
    color: #a0aec0;
    font-size: 0.9rem;
    margin-left: 5px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-active {
    background: rgba(72, 187, 120, 0.2);
    color: #48bb78;
    border: 1px solid rgba(72, 187, 120, 0.3);
}

.status-inactive {
    background: rgba(245, 101, 101, 0.2);
    color: #f56565;
    border: 1px solid rgba(245, 101, 101, 0.3);
}

.priority-input {
    width: 80px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    color: white;
    text-align: center;
}

.priority-input:focus {
    outline: none;
    border-color: #667eea;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-view {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.btn-view:hover {
    background: rgba(59, 130, 246, 0.3);
    transform: translateY(-2px);
}

.btn-edit {
    background: rgba(139, 92, 246, 0.2);
    color: #8b5cf6;
}

.btn-edit:hover {
    background: rgba(139, 92, 246, 0.3);
    transform: translateY(-2px);
}

.btn-delete {
    background: rgba(245, 101, 101, 0.2);
    color: #f56565;
}

.btn-delete:hover {
    background: rgba(245, 101, 101, 0.3);
    transform: translateY(-2px);
}

.admin-pagination {
    padding: 20px;
    text-align: center;
    border-top: 1px solid rgba(255,255,255,0.05);
}

/* Модальное окно */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: #1a1a2e;
    border-radius: 15px;
    width: 100%;
    max-width: 500px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: white;
    font-size: 1.3rem;
}

.modal-close {
    background: none;
    border: none;
    color: #a0aec0;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.modal-body {
    padding: 25px;
    color: #a0aec0;
    line-height: 1.6;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-danger {
    background: linear-gradient(45deg, #f56565, #e53e3e);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(45deg, #e53e3e, #f56565);
    transform: translateY(-2px);
}

.modal-cancel {
    background: transparent;
    color: #a0aec0;
    border: 1px solid rgba(255,255,255,0.2);
}

.modal-cancel:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фильтрация
    const searchInput = document.querySelector('.search-input');
    const categoryFilter = document.getElementById('category-filter');
    const statusFilter = document.getElementById('status-filter');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const resetFiltersBtn = document.getElementById('reset-filters');
    
    function applyFilters() {
        const params = new URLSearchParams();
        
        if (searchInput.value) {
            params.set('search', searchInput.value);
        }
        
        if (categoryFilter.value) {
            params.set('category_id', categoryFilter.value);
        }
        
        if (statusFilter.value) {
            params.set('status', statusFilter.value);
        }
        
        window.location.href = window.location.pathname + '?' + params.toString();
    }
    
    function resetFilters() {
        window.location.href = window.location.pathname;
    }
    
    applyFiltersBtn.addEventListener('click', applyFilters);
    resetFiltersBtn.addEventListener('click', resetFilters);
    
    // Поиск при нажатии Enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
    
    // Обновление приоритета
    document.querySelectorAll('.priority-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.id;
            const priority = this.value;
            
            fetch(`/admin/shop/products/${productId}/priority`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ priority: priority })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Приоритет обновлен', 'success');
                } else {
                    showNotification(data.message || 'Ошибка', 'error');
                    this.value = data.original_priority || 0;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ошибка сети', 'error');
            });
        });
    });
    
    // Удаление товара
    const deleteModal = document.getElementById('delete-modal');
    const deleteProductName = document.getElementById('delete-product-name');
    const deleteForm = document.getElementById('delete-form');
    
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.dataset.name;
            
            deleteProductName.textContent = productName;
            deleteForm.action = `/admin/shop/products/${productId}`;
            deleteModal.classList.add('show');
        });
    });
    
    // Закрытие модального окна
    document.querySelector('.modal-close').addEventListener('click', function() {
        deleteModal.classList.remove('show');
    });
    
    document.querySelector('.modal-cancel').addEventListener('click', function() {
        deleteModal.classList.remove('show');
    });
    
    // Клик вне модального окна
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.remove('show');
        }
    });
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-notification">×</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
        
        notification.querySelector('.close-notification').addEventListener('click', () => {
            notification.remove();
        });
    }
});
</script>
@endsection