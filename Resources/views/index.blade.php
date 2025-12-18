@extends('flute::layouts.main')

@section('title', __('shop.title'))

@section('content')
<div class="shop-container">
    <!-- Заголовок магазина -->
    <div class="shop-header">
        <h1 class="shop-title">@lang('shop.title')</h1>
        <div class="shop-nav">
            <a href="{{ route('shop.index') }}" class="nav-item {{ request()->is('shop') ? 'active' : '' }}">
                <i class="ph-shopping-cart"></i>
                @lang('shop.shop')
            </a>
            @can('shop.manage')
            <a href="{{ route('shop.admin.index') }}" class="nav-item {{ request()->is('admin/shop*') ? 'active' : '' }}">
                <i class="ph-gear"></i>
                @lang('shop.admin')
            </a>
            @endcan
            <a href="{{ route('shop.vip') }}" class="nav-item {{ request()->is('shop/vip') ? 'active' : '' }}">
                <i class="ph-crown"></i>
                @lang('shop.vip')
            </a>
            <a href="{{ route('shop.skins') }}" class="nav-item {{ request()->is('shop/skins') ? 'active' : '' }}">
                <i class="ph-palette"></i>
                @lang('shop.skins')
            </a>
            <a href="{{ route('shop.store') }}" class="nav-item {{ request()->is('shop/store') ? 'active' : '' }}">
                <i class="ph-storefront"></i>
                @lang('shop.store')
            </a>
        </div>
    </div>

    <!-- Контент магазина -->
    <div class="shop-content">
        <!-- Сайдбар с категориями -->
        <div class="categories-sidebar">
            <div class="category-item active" data-category="all">
                <i class="ph-grid-four"></i>
                Все товары
            </div>
            @foreach($categories as $category)
            <div class="category-item" data-category="{{ $category->id }}">
                @if($category->icon)
                <i class="{{ $category->icon }}"></i>
                @else
                <i class="ph-package"></i>
                @endif
                {{ $category->name }}
                <span class="category-count">{{ $category->products_count ?? 0 }}</span>
            </div>
            @endforeach
        </div>

        <!-- Основная сетка товаров -->
        <div class="products-main">
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="ph-shopping-cart stat-icon"></i>
                    <div class="stat-value">{{ $totalProducts ?? 0 }}</div>
                    <div class="stat-label">Всего товаров</div>
                </div>
                <div class="stat-card">
                    <i class="ph-tag stat-icon"></i>
                    <div class="stat-value">{{ $activeDiscounts ?? 0 }}</div>
                    <div class="stat-label">Активных скидок</div>
                </div>
                <div class="stat-card">
                    <i class="ph-trend-up stat-icon"></i>
                    <div class="stat-value">{{ $monthlyPurchases ?? 0 }}</div>
                    <div class="stat-label">Покупок за месяц</div>
                </div>
            </div>

            <!-- Поиск и фильтры -->
            <div class="shop-filters">
                <div class="search-box">
                    <i class="ph-magnifying-glass"></i>
                    <input type="text" placeholder="Поиск товаров..." class="search-input">
                </div>
                <div class="filter-options">
                    <select class="filter-select">
                        <option value="all">Все типы</option>
                        <option value="vip">VIP</option>
                        <option value="rcon">RCON</option>
                        <option value="sourcebans">SourceBans</option>
                        <option value="adminsystem">AdminSystem</option>
                    </select>
                    <select class="sort-select">
                        <option value="newest">Сначала новые</option>
                        <option value="price_asc">Цена по возрастанию</option>
                        <option value="price_desc">Цена по убыванию</option>
                        <option value="popular">Популярные</option>
                    </select>
                </div>
            </div>

            <!-- Сетка товаров -->
            <div class="products-grid" id="products-grid">
                @forelse($products as $product)
                    @include('shop::components.product-card', ['product' => $product])
                @empty
                <div class="empty-state">
                    <i class="ph-shopping-cart-empty"></i>
                    <h3>Товары не найдены</h3>
                    <p>В магазине пока нет доступных товаров.</p>
                </div>
                @endforelse
            </div>

            <!-- Пагинация -->
            @if($products->hasPages())
            <div class="shop-pagination">
                {{ $products->links('flute::components.pagination') }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Стили -->
<style>
.shop-filters {
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
    padding: 15px 20px 15px 50px;
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
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
}

.filter-options {
    display: flex;
    gap: 10px;
}

.filter-select,
.sort-select {
    padding: 15px 20px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: white;
    font-size: 1rem;
    min-width: 200px;
    cursor: pointer;
}

.filter-select:focus,
.sort-select:focus {
    outline: none;
    border-color: #667eea;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #a0aec0;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    color: white;
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.shop-pagination {
    margin-top: 40px;
    text-align: center;
}

/* Уведомления */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 10px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    max-width: 500px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

.notification-success {
    background: linear-gradient(135deg, #48bb78, #38a169);
}

.notification-error {
    background: linear-gradient(135deg, #f56565, #e53e3e);
}

.notification-info {
    background: linear-gradient(135deg, #4299e1, #3182ce);
}

.notification-warning {
    background: linear-gradient(135deg, #ed8936, #dd6b20);
}

.close-notification {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    margin-left: 15px;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.close-notification:hover {
    opacity: 1;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.fade-out {
    animation: fadeOut 0.3s ease forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* Загрузка */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(255,255,255,0.1);
    border-radius: 50%;
    border-top-color: #667eea;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<!-- Скрипты -->
<script>
// Глобальные переменные для магазина
window.shopFilters = {
    category: 'all',
    type: 'all',
    sort: 'newest',
    search: ''
};

// Поиск товаров
document.querySelector('.search-input').addEventListener('input', function(e) {
    window.shopFilters.search = e.target.value;
    debounce(filterProducts, 300)();
});

// Фильтрация по типу
document.querySelector('.filter-select').addEventListener('change', function(e) {
    window.shopFilters.type = e.target.value;
    filterProducts();
});

// Сортировка
document.querySelector('.sort-select').addEventListener('change', function(e) {
    window.shopFilters.sort = e.target.value;
    filterProducts();
});

// Дебаунс для оптимизации поиска
let debounceTimer;
function debounce(func, wait) {
    return function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(func, wait);
    };
}

// Функция фильтрации товаров
async function filterProducts() {
    const grid = document.getElementById('products-grid');
    grid.classList.add('loading');
    
    try {
        const response = await fetch('/shop/api/filter?' + new URLSearchParams(window.shopFilters));
        const html = await response.text();
        
        grid.innerHTML = html;
        grid.classList.remove('loading');
    } catch (error) {
        console.error('Filter error:', error);
        grid.classList.remove('loading');
    }
}

// Показать/скрыть меню категорий на мобильных
if (window.innerWidth < 768) {
    const categoriesToggle = document.createElement('button');
    categoriesToggle.className = 'categories-toggle';
    categoriesToggle.innerHTML = '<i class="ph-list"></i> Категории';
    document.querySelector('.shop-content').prepend(categoriesToggle);
    
    categoriesToggle.addEventListener('click', () => {
        document.querySelector('.categories-sidebar').classList.toggle('mobile-show');
    });
}
</script>
@endsection

@push('scripts')
<script src="{{ asset('modules/shop/js/shop.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/shop/css/shop.css') }}">
@endpush